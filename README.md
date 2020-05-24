### This is a [Munin](http://munin-monitoring.org/) plugin to monitor your [Nextcloud](https://nextcloud.com) server.  
* It queries the nextcloud server's database directly, and converts data for munin.  
* MySQL database is supported (currently)  
* Requires:  
    * MySQL or MariaDB server  
    * PHP 7.0 or above  
    * Installed Nextloud instance.  
    * Working munin-node
* When statistics will be integrated into nextloud's "serverinfo" package, MySQL or any databases connection will be unnecessary.  


## Usage 

Number of Active Clients  
![users](http://git.bmrg.hu/images/munin-nextcloud.git/nxt_act_users.png)  

Number of Events/Actions  
![events](http://git.bmrg.hu/images/munin-nextcloud.git/nxt_events.png)  



## Installing on Debian

1. Copy the **nextcloud_munin.php** into the **/usr/share/munin/plugins/** folder.  
   
2. Set the rights:  
`chmod 755 /usr/share/munin/plugins/nextcloud_munin.php`  

3. Create a symlink to this file:  
`ln -s /usr/share/munin/plugins/nextcloud_munin.php /etc/munin/plugins/nextcloud_munin`  

4. Edit the **/etc/munin/munin.conf** and **/etc/munin/plugin-conf.d/munin-node** files, add the following configuration lines.  

5. Restart the munin, and munin-node with `/etc/init.d/munin restart` and `/etc/init.d/munin-node restart` commands.  

6. Test the plugin with the `munin-run nextcloud_munin` command.  

7. Check for munin configuration with: `munin-run nextcloud_munin config` command.  

  


## CONFIGURATION

Edit the **/etc/munin/munin.conf** with the following options:  


    [nextcloud.company.com]   #Nextcloud server hostname
      address 127.0.0.1       #This plugin uses a wirtual munin node on localhost,
      use_node_name no        #but don't need to use the node name.

  
Edit the **/etc/munin/plugin-conf.d/munin-node**, and use the following configurations:  

    [nextcloud_munin]
        user             -   User, who will run this plugin (can be root)     
        timeout          -   Munin-update timeout for this plugin. 
        env.host         -   Nextcloud server hostname
        env.db_user      -   MySQL database username
        env.db_pass      -   MySQL database password (void/comment-out, When runner is root) 
        env.db_db        -   MySQL database name 
        env.db_host      -   MySQL host
        env.db_prefix    -   MySQL table prefix
        env.diff_minutes -   Munin update interval in minutes (for example: 5)
        env.nxt_plugins  -   Installed plugins 
      
  
For example:

    [nextcloud_munin]
      user             root
      timeout          60
      env.host         nextcloud.company.com
      env.db_user      root
      #env.db_pass 
      env.db_db        Nextcloud_db
      env.db_host      localhost
      env.db_prefix    oc_
      env.diff_minutes 5
      env.nxt_plugins  talk antivirus

      

### AUTHOR

Copyright (C) 2018-2020 Gergő J. Miklós.



### LICENSE

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; version 2 dated June,
1991.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.



