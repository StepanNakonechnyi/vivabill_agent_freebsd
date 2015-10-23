<?php

function get_dhcpserver_include($id, $type, $ch, $connect_url, $connect_api) {
 include ("config.php");
 $func_role = basename(__FILE__)." ".__FUNCTION__ ;
 $dhcp_file=$dhcp_dir."dhcpd_".$type."_".$id.".conf";

 curl_setopt($ch, CURLOPT_URL, $connect_url."get_dhcpserver_include");
 curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"id\":\"".$id."\",\"type\":\"".$type."\",\"api\":\"".$connect_api."\"}");
 $return=curl_exec($ch);

 if(curl_errno($ch)) {
  echo  system_addlog($func_role,"1","Curl error: ". curl_error($ch) );
 } else {

  $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ($httpcode == "200") {

   $config_json = json_decode($return,true);

   if (file_exists($dhcp_file)) {
    unlink($dhcp_file);
   }

   foreach ($config_json as $line_num => $line) {
    file_put_contents($dhcp_file, $line.chr(10), FILE_APPEND | LOCK_EX);
   }

   if (file_exists($dhcp_file)) {
    echo system_addlog($func_role, "0", "File ".$dhcp_file." was created");
   } else {
    echo system_addlog($func_role, "1", "File ".$dhcp_file." was not created");
   }

  }

 }

 return $dhcp_file;
}

function dhcpserver_update($dhcpserver, $ch, $connect_url, $connect_api) {
 include ("config.php");
 $func_role = basename(__FILE__)." ".__FUNCTION__ ;
 $boo_restart="0";

 curl_setopt($ch, CURLOPT_URL, $connect_url."get_dhcpserver_update");
 curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"id_dhcp_server\":\"".$dhcpserver."\",\"api\":\"".$connect_api."\"}");
 $return=curl_exec($ch);

 if(curl_errno($ch)) {
  echo  system_addlog($func_role,"1","Curl error: ". curl_error($ch) );
 } else {

  $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ($httpcode == "200") {

   $result_json = json_decode($return,true);
   $includes_comx=$result_json["comx"];
   $includes_docx=$result_json["docx"];

   // create includes for comx
   if ( count($includes_comx) >0 ) {
    foreach ($includes_comx as $row) {
     if ( $row > 0 ) {
      get_dhcpserver_include($row, "comx", $ch, $connect_url, $connect_api);
      $boo_restart="1";
     }
    }
   }

   // create includes for docx
   if ( count($includes_docx) >0 ) {
    foreach ($includes_docx as $row) {
     if ( $row > 0 ) {
      get_dhcpserver_include($row, "docx", $ch, $connect_url, $connect_api);
#      echo system_addlog($func_role,"0","dhcpd_tbl ID-".$row);
      $boo_restart="1";
     }
    }
   }

  } else {
   $last_url=curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
   echo system_addlog($func_role,"1","Error [".$httpcode."] ".$last_url);
  }
 }

 if ($boo_restart == "1" ) {
  dhcpserver_restart();
 }

}

function dhcpserver_config($dhcpserver, $ch, $connect_url, $connect_api) {
 include ("config.php");
 $func_role = basename(__FILE__)." ".__FUNCTION__ ;

 curl_setopt($ch, CURLOPT_URL, $connect_url."get_dhcpserver_config");
 curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"id_dhcp_server\":\"".$dhcpserver."\",\"api\":\"".$connect_api."\"}");
 $return=curl_exec($ch);

 if(curl_errno($ch)) {
  echo  system_addlog($func_role,"1","Curl error: ". curl_error($ch) );
 } else {
  $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ($httpcode == "200") {

   $result_json = json_decode($return, true);

   $iface=$result_json["iface"];
   $includes_comx=$result_json["comx"];
   $includes_docx=$result_json["docx"];
   $includes_main=$result_json["main"];

   // Configure DHCP server
   if (file_exists($dhcpserver_config)) { unlink($dhcpserver_config); }
   if ($iface == "auto") { $iface="eth0"; }
   $a_iface=explode(",",$iface);
   file_put_contents($dhcpserver_config, "DHCPDARGS=\"".implode(" ",$a_iface)."\"".chr(10), FILE_APPEND | LOCK_EX);
   echo message_addlog($func_role, "File ".$dhcpserver_config." was created with interfaces '".$iface."'");

   // Configure DHCP file
   if (file_exists($dhcp_config)) { unlink($dhcp_config); }
   echo message_addlog($func_role, "Create file ".$dhcp_config);

   foreach ($includes_main as $row) {
    file_put_contents($dhcp_config, $row.chr(10), FILE_APPEND | LOCK_EX);
   }

 file_put_contents($dhcp_config, "shared-network viva {".chr(10), FILE_APPEND | LOCK_EX);

 foreach ($a_iface as $row) {
  unset ($data);
  $s_row=explode(chr(10),$row);
  $s_iface=$s_row[0];
  exec($ifconfig." ".$s_iface." | grep 'inet ' | awk '{print ($2,$4)}'", $data);
  foreach ($data as $row) {
   $srow=explode(" ",$row);
   $s_ipaddr=$srow[0];
   $s_mask=long2ip($srow[1]);
   $s_cidr=netmask2cidr($s_mask);
   $s_network=get_network($s_ipaddr,$s_cidr);
   file_put_contents($dhcp_config, "subnet ".$s_network." netmask ".$s_mask." { }".chr(10), FILE_APPEND | LOCK_EX);
   echo message_addlog($func_role, "Network ".$s_network."/".$s_cidr." was added to file ".$dhcp_config);
  }
 }

 foreach ($includes_comx as $row) {
  if ( $row > 0 ) {
   $comx_file="dhcpd_comx_".$row.".conf";
   file_put_contents($dhcp_config, "include \"".$dhcp_dir.$comx_file."\";".chr(10), FILE_APPEND | LOCK_EX);
   echo message_addlog($func_role, "Include file ".$comx_file." to file ".$dhcp_config);
  }
 }

 foreach ($includes_docx as $row) {
  if ( $row > 0 ) {
   $docx_file="dhcpd_docx_".$row.".conf";
   file_put_contents($dhcp_config, "include \"".$dhcp_dir.$docx_file."\";".chr(10), FILE_APPEND | LOCK_EX);
   echo message_addlog($func_role, "Include file ".$docx_file." to file ".$dhcp_config);
  }
 }

 file_put_contents($dhcp_config, "}".chr(10), FILE_APPEND | LOCK_EX);

 // select all old files which were included to DHCP config and delete them
 unset($data);
 exec($ls." -al ".$dhcp_dir." | grep dhcpd_ | awk '{print ($9)}'", $data);
 foreach ($data as $row) {
  unlink($dhcp_dir.$row);
  echo message_addlog($func_role, "File ".$dhcp_dir.$row." was deleted");
 }


 // create includes for comx
 if ( count($includes_comx) >0 ) {
  foreach ($includes_comx as $row) {
   if ( $row > 0 ) {
    get_dhcpserver_include($row, "comx", $ch, $connect_url, $connect_api);
   }
  }
 }

 // create includes for docx
 if ( count($includes_docx) >0 ) {
  foreach ($includes_docx as $row) {
   if ( $row > 0 ) {
    get_dhcpserver_include($row, "docx", $ch, $connect_url, $connect_api);
   }
  }
 }

 dhcpserver_restart();

  } else {
   $last_url=curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
   echo system_addlog($func_role,"1","Error [".$httpcode."] ".$last_url);
  }
 }
}


function dhcprelay_config($router, $ch, $connect_url, $connect_api) {
 // Still emply. Needs to be added
 dhcprelay_restart();
}


function dhcprelay_update($router, $ch, $connect_url, $connect_api) {
 include("config.php");
 $func_role = basename(__FILE__)." ".__FUNCTION__ ;

 curl_setopt($ch, CURLOPT_URL, $connect_url."get_dhcprelay_dhcpservers");
 curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"id_router\":\"".$router."\",\"api\":\"".$connect_api."\"}");
 $return=curl_exec($ch); 

 if(curl_errno($ch)) {
  echo  system_addlog($func_role,"1","Curl error: ". curl_error($ch) );
 } else {
  $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ($httpcode == "200") {
   $dh = json_decode($return,true);
   dhcprelay_makefile($dh);
   dhcprelay_restart();
  } else {
   $last_url=curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
   echo system_addlog($func_role,"1","Error [".$httpcode."] ".$last_url);
  }
 }
}


function dhcprelay_restart() {
 include ("config.php");
 $func_role = basename(__FILE__)." ".__FUNCTION__ ;
 echo system_exec_addlog ($func_role, $dhcrelay." restart >/dev/null");
}

function dhcpserver_restart() {
 include ("config.php");
 $func_role = basename(__FILE__)." ".__FUNCTION__ ;
 echo system_exec_addlog ($func_role, $dhcpd." restart >/dev/null");
}

function dhcprelay_makefile($dh) {
 include ("config.php");
 $func_role = basename(__FILE__)." ".__FUNCTION__ ;

 if (file_exists($dhcprelay_config)) { unlink($dhcprelay_config); }
 file_put_contents($dhcprelay_config, "DHCRELAYARGS=\"-a -q -A 1400\"".chr(10), FILE_APPEND | LOCK_EX);
 file_put_contents($dhcprelay_config, "INTERFACES=\"\"".chr(10), FILE_APPEND | LOCK_EX);
 file_put_contents($dhcprelay_config, "DHCPSERVERS=\"".implode(",", $dh)."\"".chr(10), FILE_APPEND | LOCK_EX);
 echo message_addlog($func_role, "File ".$dhcprelay_config." was created");
}

?>