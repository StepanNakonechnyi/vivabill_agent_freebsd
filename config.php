<?php

 //###################//
 //  FreeBSD 9.x 10.x //
 //###################//

 // Get home dir
 $home_dir="/usr/local/etc/vivabill/";

 // Get JSON data
 $data = file_get_contents ($home_dir."config.json");
 $config = json_decode($data, true);

 // Get path settings
 $data_dir= $config["data_dir"];
 $driver_dir= $config["driver_dir"];
 $pid_dir=$config["pid_dir"];
 $log_dir=$config["log_dir"];
 $facter_dir=$config["facter_dir"];
 $sysconfig_dir=$config["sysconfig_dir"];
 $dhcp_dir=$config["dhcp_dir"];
 $tftp_cm_config=$config["tftp_cm_config"];
 $tftp_cm_process=$config["tftp_cm_process"];
 $tftp_cm_source=$config["tftp_cm_source"];
 $ovsp_dir=$config["ovsp_dir"];
 $comx_dir=$config["comx_dir"];

 // OS environment
 $hostname="/bin/hostname";
 $uname="/usr/bin/uname";
 $cat="/bin/cat";
 $expect="/usr/local/bin/expect";
 $ls="/bin/ls";
 $ps="/bin/ps";
 $env="/usr/bin/env";
 $chmod="/bin/chmod";
 $php="/usr/local/bin/php";
 $ipfw="/sbin/ipfw";
 $ifconfig="/sbin/ifconfig";
 $route="/sbin/route";
 $netstat="/usr/bin/netstat";
 $dhcpd="/usr/local/etc/rc.d/isc-dhcpd";
 $dhcrelay="/usr/local/etc/rc.d/isc-dhcrelay";
 $facter="/usr/local/bin/facter";
 $dhcprelay_config=$sysconfig_dir."dhcrelay";
 $dhcpserver_config=$sysconfig_dir."dhcpd";
 $dhcp_config=$dhcp_dir."dhcpd.conf";
 $log_file=$log_dir."vivabill.raw";
 date_default_timezone_set('Europe/Kiev');

 // Puppet roles
 $prole="viva_roles";
 $prole_router="router";
 $prole_vpnserver="vpnserver";
 $prole_dhcpserver="dhcpserver";
 $prole_dhcprelay="dhcprelay";
 $prole_radserver="radserver";
 $prole_tftpserver="tftpserver";
 $prole_bmrserver="bmrserver";
 $prole_iptvserver="iptvserver";
 $prole_gponserver="gponserver";
 $prole_eponserver="eponserver";
 $prole_statistic="statistic";
 $prole_brasserver="brasserver";
 $prole_cmanager="cmanager";

 // Local PostgreSQL settings
 $pg_settings="127.0.0.1 6543 viva viva viva 5";

?>
