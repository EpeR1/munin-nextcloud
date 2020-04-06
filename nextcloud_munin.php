

#!/usr/bin/php
<?php


$set['host'] = getenv('host');
//$set['occ_user'] = getenv('occ_user');
//$set['occ_path'] = getenv('occ_path');
$set['db_user'] = getenv('db_user');
$set['db_pass'] = getenv('db_pass');
$set['db_db'] = getenv('db_db');
$set['db_host'] = getenv('db_host');
$set['db_prefix'] = getenv('db_prefix');
$set['diff_minutes'] = intval(getenv('diff_minutes'));
/*
$set['host'] = "felho.example.com";
$set['db_user'] = "root";
$set['db_pass'] = "";
$set['db_db']	= "Nextcloud_db";
$set['db_host'] = "localhost";
$set['db_prefix'] = "oc_";
$set['diff_minutes'] = 5;   // --> 5 minutes
*/
$replace_chars = array("\"","\$","@","^","`",",","|","%",";",".","~","(",")","/","\\","{","}",":","?","[","]","=","+","#","!","-",);    // Special chars replace
$time_req = time() - 60*$set['diff_minutes'];
$dta = array();
$dtv = array();


	function  get_auth_stat($l) {
		global $set, $time_req;
		$ret = array('all' => 0,'app' => 0, 'web' => 0, 'windows' => 0, 'linux' => 0, 'other_os' => 0, 'firefox' => 0, 'chrome' => 0, 'safari' => 0, 'other_bw' => 0 );
		mysqli_set_charset($l, "utf8");
		$r = mysqli_query($l," SELECT * FROM ".$set['db_db'].".".$set['db_prefix']."authtoken WHERE `last_activity` >= ".$time_req." ORDER BY `last_activity` DESC ");

		while ($row = mysqli_fetch_array($r, MYSQLI_ASSOC)) {
	        	if($row['type'] == 0) {		//web login
 				if(strpos($row['name'], "mirall" ) !== false){	//without token on desktop
					$ret['app'] ++;
				} else {
					$ret['web'] ++; 
				}
			} else if($row['type'] == 1) { 	//with application
				$ret['app'] ++; 
			}
			$ret['all'] ++; 		// everybody
			

			if(stripos($row['name'], 'windows') !== false){
				$ret['windows']++;
			} else if(stripos($row['name'], 'linux') !== false){
				$ret['linux']++;
			} else {
				$ret['other_os']++;
			}

			if(stripos($row['name'], 'firefox') !== false){
                                $ret['firefox']++;
                        } else if(stripos($row['name'], 'chrome') !== false){	//Chrome is using Safari motor also
                                $ret['chrome']++;
                        } else if(stripos($row['name'], 'safari') !== false){
                                $ret['safari']++;
                        } else {
                                $ret['other_bw']++;
                        }

		}
		@mysqli_free_result($r);
	return $ret;
	}



       function  get_activity_stat($l) {
                global $set, $time_req;
                $ret = array('all' => 0, 'file' =>0, 'share' => 0, 'calendar' => 0, 'comment' => 0, 'public' => 0, 'settings' => 0, 'virus' => 0, 'talk' => 0);
                mysqli_set_charset($l, "utf8");
                $q = " SELECT * FROM ".$set['db_db'].".".$set['db_prefix']."activity
                       WHERE `timestamp` >= ".$time_req." 
                        AND ( ((`type` LIKE '%file%' AND `subject` NOT LIKE '%by%') 
                            OR (`type` LIKE '%shared%' AND `subject` NOT LIKE '%self%') ) 
                           OR ( `type` NOT LIKE '%file%' AND `type`  NOT LIKE '%shared%') )
                      ORDER BY `timestamp` DESC ;
                     ";
		$r = mysqli_query($l,$q);
                while ($row = mysqli_fetch_array($r, MYSQLI_ASSOC)) {
			if(stripos($row['type'], 'file_') !== false ){
				$ret['file'] ++; 
			}
			if(stripos($row['type'], 'shared') !== false ){
                                $ret['share'] ++;
                        } 
			if(stripos($row['type'], 'calendar') !== false ){
                                $ret['calendar'] ++;
                        } 
			if(stripos($row['type'], 'virus_detected') !== false ){
				if(!isset($ret['virus'])){ $ret['virus'] = 0; }
                                $ret['virus'] ++;
                        } 
			if(stripos($row['type'], 'comments') !== false ){
                                $ret['comment'] ++;
                        }
			if(stripos($row['type'], 'public_link') !== false ){
                                $ret['public'] ++;
                        }
                        if(stripos($row['type'], 'settings') !== false ){
                                $ret['settings'] ++;
                        }

                        if(stripos($row['type'], 'spreed') !== false ){	//Nextcloud talk
				if(!isset($ret['talk'])){$ret['talk'] = 0; }
                                $ret['talk'] ++;
                        }
			$ret['all']++;
                }
                @mysqli_free_result($r);
        return $ret;
        }


	function print_headers($inp){
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
		echo iconv("UTF-8", "ISO-8859-2", $cf).PHP_EOL;
	}


	function print_values($inp){
		global $replace_chars;
		if(isset($inp['data'])){
	                $cf  = "multigraph ".$inp['g_name']."\n";
        		foreach($inp['data'] as $key => $val){
	        		$cf .= "nxt_".str_replace($replace_chars, "_", $key).".value ".$val['value']."\n";
		        }
			$cf .= "\n";
        		echo iconv("UTF-8", "ISO-8859-2", $cf).PHP_EOL;
		}
	}




if (function_exists('mysqli_connect') and PHP_MAJOR_VERSION >= 7 ) { //mysqli && php > 7.0   needed

	$l = mysqli_connect($set['db_host'], $set['db_user'], $set['db_pass'], $set['db_db']);
	if(!$l){
		//error
	} else {
		$auth = get_auth_stat($l);
		$actv = get_activity_stat($l);
		@mysqli_close($l);
	}
}

$dta['g_name'] = "nxt_active_users_".str_replace($replace_chars, "_", $set['host'] );
$dta['g_host'] = $set['host'];
$dta['g_title'] = "Nextcloud Users";
$dta['g_vlabel'] = "Users";
$dta['g_category'] = "Nxt_users";
$dta['g_info'] = "nextcloud_active_users";
$dta['g_order'] = "nxt_users_all nxt_users_web nxt_users_app";

$dta['data']['users_all']['value'] = $auth['all'];
$dta['data']['users_all']['label'] = "Total";
$dta['data']['users_all']['draw'] =  "LINE1.2";
$dta['data']['users_all']['info'] = "Total client";
$dta['data']['users_all']['type'] = "GAUGE";

$dta['data']['users_web']['value'] = $auth['web'];
$dta['data']['users_web']['label'] = "By Web";
$dta['data']['users_web']['draw'] =  "LINE1.2";
$dta['data']['users_web']['info'] = "Webpage clients";
$dta['data']['users_web']['type'] = "GAUGE";

$dta['data']['users_app']['value'] = $auth['app'];
$dta['data']['users_app']['label'] = "By APP";
$dta['data']['users_app']['draw'] =  "LINE1.2";
$dta['data']['users_app']['info'] = "Connected clients by app.";
$dta['data']['users_app']['type'] = "GAUGE";

$dta['data']['users_win']['value'] = $auth['windows'];
$dta['data']['users_win']['label'] = "Windows";
$dta['data']['users_win']['draw'] =  "LINE1.2";
$dta['data']['users_win']['info'] = "Total client";
$dta['data']['users_win']['type'] = "GAUGE";

$dta['data']['users_linux']['value'] = $auth['linux'];
$dta['data']['users_linux']['label'] = "Linux";
$dta['data']['users_linux']['draw'] =  "LINE1.2";
$dta['data']['users_linux']['info'] = "Webpage clients";
$dta['data']['users_linux']['type'] = "GAUGE";

$dta['data']['users_oos']['value'] = $auth['other_os'];
$dta['data']['users_oos']['label'] = "Other OS";
$dta['data']['users_oos']['draw'] =  "LINE1.2";
$dta['data']['users_oos']['info'] = "Connected clients by app.";
$dta['data']['users_oos']['type'] = "GAUGE";




$dtb['g_name'] = "nxt_events_".str_replace($replace_chars, "_", $set['host'] );
$dtb['g_host'] = $set['host'];
$dtb['g_title'] = "Nextcloud Users Activity";
$dtb['g_vlabel'] = "Events";
$dtb['g_category'] = "Nxt_events";
$dtb['g_info'] = "nextcloud_events";
$dtb['g_order'] = "nxt_act_all nxt_act_files nxt_act_share";

$dtb['data']['act_all']['value'] = $actv['all'];
$dtb['data']['act_all']['label'] = "Total events";
$dtb['data']['act_all']['draw'] =  "LINE1.2";
$dtb['data']['act_all']['info'] = "Total client";
$dtb['data']['act_all']['type'] = "GAUGE";
$dtb['data']['act_files']['value'] = $actv['file'];
$dtb['data']['act_files']['label'] = "File operations";
$dtb['data']['act_files']['draw'] =  "LINE1.2";
$dtb['data']['act_files']['info'] = "Webpage clients";
$dtb['data']['act_files']['type'] = "GAUGE";
$dtb['data']['act_share']['value'] = $actv['share'];
$dtb['data']['act_share']['label'] = "Saring events";
$dtb['data']['act_share']['draw'] =  "LINE1.2";
$dtb['data']['act_share']['info'] = "Sharing to groups or users";
$dtb['data']['act_share']['type'] = "GAUGE";
$dtb['data']['act_pub']['value'] = $actv['public'];
$dtb['data']['act_pub']['label'] = "Pub.link download";
$dtb['data']['act_pub']['draw'] =  "LINE1.2";
$dtb['data']['act_pub']['info'] = "Sharing by public links";
$dtb['data']['act_pub']['type'] = "GAUGE";
$dtb['data']['act_comm']['value'] = $actv['comment'];
$dtb['data']['act_comm']['label'] = "Comments";
$dtb['data']['act_comm']['draw'] =  "LINE1.2";
$dtb['data']['act_comm']['info'] = "Comments";
$dtb['data']['act_comm']['type'] = "GAUGE";
$dtb['data']['act_talk']['value'] = $actv['talk'];
$dtb['data']['act_talk']['label'] = "Talk events";
$dtb['data']['act_talk']['draw'] =  "LINE1.2";
$dtb['data']['act_talk']['info'] = "settings Change";
$dtb['data']['act_talk']['type'] = "GAUGE";
$dtb['data']['act_cal']['value'] = $actv['calendar'];
$dtb['data']['act_cal']['label'] = "Calendar operations";
$dtb['data']['act_cal']['draw'] =  "LINE1.2";
$dtb['data']['act_cal']['info'] = "Calendar.";
$dtb['data']['act_cal']['type'] = "GAUGE";
$dtb['data']['act_virus']['value'] = $actv['virus'];
$dtb['data']['act_virus']['label'] = "Virus detection";
$dtb['data']['act_virus']['draw'] =  "LINE1.2";
$dtb['data']['act_virus']['info'] = "Virus Detected";
$dtb['data']['act_virus']['type'] = "GAUGE";
$dtb['data']['act_setts']['value'] = $actv['settings'];
$dtb['data']['act_setts']['label'] = "Change Settings";
$dtb['data']['act_setts']['draw'] =  "LINE1.2";
$dtb['data']['act_setts']['info'] = "settings Change";
$dtb['data']['act_setts']['type'] = "GAUGE";





if (isset($argv[1]) and $argv[1] == "config"){
	print_headers($dta);
	print_headers($dtb);
} else {
        print_values($dta);
        print_values($dtb);
}


?>

