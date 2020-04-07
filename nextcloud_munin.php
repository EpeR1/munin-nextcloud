#!/usr/bin/php
<?php

$set['host'] = getenv('host');
//$set['occ_user'] = getenv('occ_user');	//If "serverinfo" would be a part of occ commands...
//$set['occ_path'] = getenv('occ_path');
$set['db_user'] = getenv('db_user');
$set['db_pass'] = getenv('db_pass');
$set['db_db'] = getenv('db_db');
$set['db_host'] = getenv('db_host');
$set['db_prefix'] = getenv('db_prefix');
$set['diff_minutes'] = intval(getenv('diff_minutes'));
$set['nxt_plugins'] = explode(" ", getenv('nxt_plugins'));
/*	//For debugging
$set['host'] = "cloud.example.com";			
$set['db_user'] = "root";
$set['db_pass'] = "";
$set['db_db']	= "Nextcloud_db";
$set['db_host'] = "localhost";
$set['db_prefix'] = "oc_";
$set['diff_minutes'] = 5;   // --> 5 minutes
*/
$replace_chars = array("\"","\$","@","^","`",",","|","%",";",".","~","(",")","/","\\","{","}",":","?","[","]","=","+","#","!","-",);    // Special chars to replace
$time_req = time()-60*$set['diff_minutes'];

//--------------------------------------------------------------------------------------------------------------------------------------//
	function  prepare_auth_stat($l){
		global $set, $time_req, $replace_chars;
		$ret = array();
		$ret['g_name'] = "nxt_act_users_".str_replace($replace_chars, "_", $set['host'] );
		$ret['g_host'] = $set['host'];
		$ret['g_title'] = "Nextcloud Active Users";
		$ret['g_vlabel'] = "Users";
		$ret['g_category'] = "Nxt_actv_users";
		$ret['g_info'] = "nextcloud_active_users";
		$ret['g_order'] = "nxt_users_all nxt_users_web nxt_users_app";

		// skeleton for returning
		foreach(array('users_all', 'users_web', 'users_app','users_flash') as $key => $val){	
			$ret['data'][$val]['value'] = 0;
			$ret['data'][$val]['draw'] =  "LINE1.2";
			$ret['data'][$val]['type'] = "GAUGE";
		}
		$ret['data']['users_all']['label'] = "Total";
		$ret['data']['users_all']['info'] = "Total clients";
		$ret['data']['users_web']['label'] = "By Web";
		$ret['data']['users_web']['info'] = "Clients logged in by webpage.";
		$ret['data']['users_app']['label'] = "By App";
		$ret['data']['users_app']['info'] = "Connected clients with an app.";
		$ret['data']['users_flash']['label'] = "Web(<5min)";
		$ret['data']['users_flash']['info'] = "Rapid web users (succesfully logged out in less than 5 minutes)";


		mysqli_set_charset($l, "utf8");
		$r = mysqli_query($l, "SELECT * FROM ".mysqli_real_escape_string($l, $set['db_db']).".".mysqli_real_escape_string($l, $set['db_prefix'])."authtoken 
								WHERE `last_activity` >= ".mysqli_real_escape_string($l, $time_req)." 
								ORDER BY `last_activity` DESC ;"
							);
		while ($row = mysqli_fetch_array($r, MYSQLI_ASSOC)) {
			if($row['type'] == 0) {									//with own password (usually on web browser)
 				if(strpos($row['name'], "mirall" ) !== false){		//with own password on (mirall) desktop client
					$ret['data']['users_app']['value']++;
				} else {											//password over web
					$ret['data']['users_web']['value']++;		
				}
			} else if($row['type'] == 1) { 							//with token --> (usually on app)
				$ret['data']['users_app']['value']++;
			}
			$ret['data']['users_all']['value']++; 					//everybody
		}
		@mysqli_free_result($r);

		$r = mysqli_query($l, "SELECT COUNT(*) AS `cnt` FROM ".mysqli_real_escape_string($l, $set['db_db']).".".mysqli_real_escape_string($l, $set['db_prefix'])."preferences 
								WHERE `configkey`='lastLogin' AND `configvalue` >= ".mysqli_real_escape_string($l, $time_req)." AND `userid` NOT IN (
									SELECT `uid` FROM ".mysqli_real_escape_string($l, $set['db_db']).".".mysqli_real_escape_string($l, $set['db_prefix'])."authtoken  
										WHERE `last_activity` >= ".mysqli_real_escape_string($l, $time_req)." AND `type` = 0
								);"
							);
		$ret['data']['users_flash']['value'] = mysqli_fetch_array($r, MYSQLI_ASSOC)['cnt'];
		$ret['data']['users_all']['value'] += $ret['data']['users_flash']['value'];

		@mysqli_free_result($r);
		return $ret;
	}


//--------------------------------------------------------------------------------------------------------------------------------------//
	function  prepare_event_stat($l){
		global $set, $time_req, $replace_chars;
		$d_pfx = "evt_";
		$ret['g_name'] = "nxt_events_".str_replace($replace_chars, "_", $set['host'] );
		$ret['g_host'] = $set['host'];
		$ret['g_title'] = "Nextcloud Activity";
		$ret['g_vlabel'] = "Events";
		$ret['g_category'] = "Nxt_events";
		$ret['g_info'] = "nextcloud_events";
		$ret['g_order'] = "nxt_evt_all nxt_evt_files nxt_evt_share";	
			// skeleton for returning
		foreach(array_merge(array('all', 'files', 'share', 'pub', 'comm', 'cal'), $set['nxt_plugins'], array( 'other')) as $key => $val){ // Order ->  merge(arr(),arr(), arr( 'other'))
			$ret['data'][$d_pfx.$val]['value'] = 0;
			$ret['data'][$d_pfx.$val]['draw'] =  "LINE1.2";
			$ret['data'][$d_pfx.$val]['type'] = "GAUGE";
		}
		$ret['data'][$d_pfx.'all']['label'] = "Total events";
		$ret['data'][$d_pfx.'all']['info'] = "Total Actions generated";
		$ret['data'][$d_pfx.'files']['label'] = "File actions";
		$ret['data'][$d_pfx.'files']['info'] = "Files operations";				
		$ret['data'][$d_pfx.'share']['label'] = "Saring actions";
		$ret['data'][$d_pfx.'share']['info'] = "Sharing by groups or users inside";
		$ret['data'][$d_pfx.'pub']['label'] = "Pub.link download";
		$ret['data'][$d_pfx.'pub']['info'] = "Public Link downloaded";
		$ret['data'][$d_pfx.'comm']['label'] = "Comments";
		$ret['data'][$d_pfx.'comm']['info'] = "Comments";
		$ret['data'][$d_pfx.'cal']['label'] = "Calendar actions";
		$ret['data'][$d_pfx.'cal']['info'] = "Calendar actions/operations";
		$ret['data'][$d_pfx.'other']['label'] = "Other actions";
		$ret['data'][$d_pfx.'other']['info'] = "Other actions";
		//------------ Applications enabled ------------//
		$ret['data'][$d_pfx.'talk']['label'] = "Talk events";
		$ret['data'][$d_pfx.'talk']['info'] = "Nextcloud Talk events";
		$ret['data'][$d_pfx.'antivirus']['label'] = "Virus detect";
		$ret['data'][$d_pfx.'antivirus']['info'] = "Virus Detections by files_antivirus";

		mysqli_set_charset($l, "utf8");
		$r = mysqli_query($l, " SELECT * FROM ".mysqli_real_escape_string($l, $set['db_db']).".".mysqli_real_escape_string($l, $set['db_prefix'])."activity
								WHERE `timestamp` >= ".mysqli_real_escape_string($l, $time_req)." 
									AND ( ((`type` LIKE '%file%' AND `subject` NOT LIKE '%by%') 
										OR (`type` LIKE '%shared%' AND `subject` NOT LIKE '%self%') ) 
									OR ( `type` NOT LIKE '%file%' AND `type`  NOT LIKE '%shared%') )
								ORDER BY `timestamp` DESC ;"
							);
		while ($row = mysqli_fetch_array($r, MYSQLI_ASSOC)) {
			if(stripos($row['type'], 'file_') !== false ){
				$ret['data'][$d_pfx.'files']['value']++;

			} else if(stripos($row['type'], 'shared') !== false ){
				$ret['data'][$d_pfx.'share']['value']++;
				
			} else if(stripos($row['type'], 'public_link') !== false ){
				$ret['data'][$d_pfx.'pub']['value']++;

			} else if(stripos($row['type'], 'comments') !== false ){
				$ret['data'][$d_pfx.'comm']['value']++;

			} else if(stripos($row['type'], 'calendar') !== false ){
				$ret['data'][$d_pfx.'cal']['value']++;

			} else if(stripos($row['type'], 'spreed') !== false ){			//nextcloud_talk app
				$ret['data'][$d_pfx.'talk']['value']++;

			} else if(stripos($row['type'], 'virus_detected') !== false ){ // files_antivirus app
				$ret['data'][$d_pfx.'antivirus']['value']++;
			} else {
				$ret['data'][$d_pfx.'other']['value']++;
			} 
			$ret['data'][$d_pfx.'all']['value']++;
		}
		@mysqli_free_result($r);
		return $ret;
    }


//--------------------------------------------------------------------------------------------------------------------------------------//

	function print_config($inp){
		global $replace_chars;
		$cf  = "multigraph ".$inp['g_name']."\n";
		$cf .= "host_name ".$inp['g_host']."\n";
		$cf .= "graph_title ".$inp['g_title']."\n";
		$cf .= "graph_args --base 1000 \n";
		$cf .= "graph_vlabel ".$inp['g_vlabel']."\n";
		$cf .= "graph_category ".$inp['g_category']."\n";
		$cf .= "graph_info ".$inp['g_info']."\n";
		$cf .= "graph_order ".$inp['g_order']."\n";

		foreach($inp['data'] as $key => $val){
			$cf .= "nxt_".str_replace($replace_chars, "_", $key).".label ".$val['label']." \n";
			$cf .= "nxt_".str_replace($replace_chars, "_", $key).".draw ".$val['draw']." \n";
			$cf .= "nxt_".str_replace($replace_chars, "_", $key).".info ".$val['info']." \n";
			$cf .= "nxt_".str_replace($replace_chars, "_", $key).".type ".$val['type']." \n";
		}
		$cf .= "\n";
		//echo iconv("UTF-8", "ISO-8859-2", $cf).PHP_EOL;	//If you want Hungarian chars
		echo $cf.PHP_EOL;
	}


	function print_values($inp){
		global $replace_chars;
		if(isset($inp['data'])){
			$cf  = "multigraph ".$inp['g_name']."\n";
			foreach($inp['data'] as $key => $val){
				$cf .= "nxt_".str_replace($replace_chars, "_", $key).".value ".$val['value']."\n";
			}
			$cf .= "\n";
			//echo iconv("UTF-8", "ISO-8859-2", $cf).PHP_EOL; 	//If you want Hungarian chars
			echo $cf.PHP_EOL;
		}
	}



//--------------------------------------------------------------------------------------------------------------------------------------//
if (function_exists('mysqli_connect') and PHP_MAJOR_VERSION >= 7 ) { //mysqli && php > 7.0   needed

	$l = mysqli_connect($set['db_host'], $set['db_user'], $set['db_pass'], $set['db_db']);
	if(!$l){
		echo "\n MYSQL error. \n";
	} else {
		$auth_stat = prepare_auth_stat($l);
		$actv_stat = prepare_event_stat($l);
		if (isset($argv[1]) and $argv[1] == "config"){
			print_config($auth_stat);
			print_config($actv_stat);
		} else {
			print_values($auth_stat);
			print_values($actv_stat);
		}
		@mysqli_close($l);
	}
}



?>

