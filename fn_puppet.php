<?php

function check_roles_facter ($hw_bi, $log_data_dir) {

 $boo_load_puppet="0";
 $file_facter_os=$log_data_dir."facter_os.json";

 // load roles .os
 if (file_exists($file_facter_os)) {

  // Get JSON data
  $data_os = file_get_contents ($file_facter_os);
  $hw_os = json_decode($data_os,true);

  foreach($hw_bi as $key => $val) {
   if (isset($hw_os[$key])) {
    if ( $hw_bi[$key] != $hw_os[$key] ) {
     $boo_load_puppet="1";
    }
   }
  }

 } else {
  $boo_load_puppet = "1";
  $fp = fopen($file_facter_os, "w");
  fwrite($fp, json_encode($hw_bi));
  fclose($fp);
 }

 if ( $boo_load_puppet =="1" ) {

  include ("config.php");
  $func_role = basename(__FILE__)." ".__FUNCTION__ ;

  $sm_router=$hw_bi["router"];
  $sm_vpnserver=$hw_bi["vpnserver"];
  $sm_dhcpserver=$hw_bi["dhcpserver"];
  $sm_dhcprelay=$hw_bi["dhcprelay"];
  $sm_radserver=$hw_bi["radserver"];
  $sm_tftpserver=$hw_bi["tftpserver"];
  $sm_bmrserver=$hw_bi["bmrserver"];
  $sm_iptvserver=$hw_bi["iptvserver"];
  $sm_gponserver=$hw_bi["gponserver"];
  $sm_eponserver=$hw_bi["eponserver"];
  $sm_statistic=$hw_bi["statistic"];
  $sm_brasserver=$hw_bi["bras"];
  $sm_cmanager=$hw_bi["cmanager"];

  // for router
  if ( $sm_router == "none" ) {
   $boo_prole_router = "false";
  } else {
   $boo_prole_router = "true";
  }

  // vpnserver
  if ( $sm_vpnserver == "none" ) {
   $boo_prole_vpnserver = "false";
  } else {
   $boo_prole_vpnserver = "true";
  }

  //dhcpserver
  if ( $sm_dhcpserver == "none" ) {
   $boo_prole_dhcpserver = "false";
  } else {
   $boo_prole_dhcpserver = "true";
  }

  //dhcprelay
  if ( $sm_dhcprelay == "none" ) {
   $boo_prole_dhcprelay = "false";
  } else {
   $boo_prole_dhcprelay = "true";
  }

  //radserver
  if ( $sm_radserver == "none" ) {
   $boo_prole_radserver = "false";
  } else {
   $boo_prole_radserver = "true";
  }

  //tftpserver
  if ( $sm_tftpserver == "none" ) {
   $boo_prole_tftpserver = "false";
  } else {
   $boo_prole_tftpserver = "true";
  }

  //bmrserver
  if ( $sm_bmrserver == "none" ) {
   $boo_prole_bmrserver = "false";
  } else {
   $boo_prole_bmrserver = "true";
  }

  //bmrserver
  if ( $sm_iptvserver == "none" ) {
   $boo_prole_iptvserver = "false";
  } else {
   $boo_prole_iptvserver = "true";
  }

  //gponserver
  if ( $sm_gponserver == "none" ) {
   $boo_prole_gponserver = "false";
  } else {
   $boo_prole_gponserver = "true";
  }

  //eponserver
  if ( $sm_eponserver == "none" ) {
   $boo_prole_eponserver = "false";
  } else {
   $boo_prole_eponserver = "true";
  }

  //statistic
  if ( $sm_statistic == "none" ) {
   $boo_prole_statistic = "false";
  } else {
   $boo_prole_statistic = "true";
  }

  //brasserver
  if ( $sm_brasserver == "none" ) {
   $boo_prole_brasserver = "false";
  } else {
   $boo_prole_brasserver = "true";
  }

  //comx manager
  if ( $sm_cmanager == "none" ) {
   $boo_prole_cmanager = "false";
  } else {
   $boo_prole_cmanager = "true";
  }


  //  Report, that role puppet will change 
  foreach($hw_bi as $key => $val) {
   if (isset($hw_os[$key])) {
    if ( $hw_bi[$key] != $hw_os[$key] ) {
     echo message_addlog($func_role, "New puppet declaration for ".$key." ".$hw_os[$key].">".$hw_bi[$key]);
    }
   }
  }


  //create file factor for puppet

  $file_ff=$facter_dir."viva-roles.sh";

  if (file_exists($file_ff)) {
   unlink($file_ff);
  }

  file_put_contents($file_ff, "#!".$env." bash".chr(10), FILE_APPEND | LOCK_EX);
  file_put_contents($file_ff, chr(10), FILE_APPEND | LOCK_EX);
  file_put_contents($file_ff, "echo '".$prole."=".
  $prole_router.",".
  $prole_vpnserver.",".
  $prole_dhcpserver.",".
  $prole_dhcprelay.",".
  $prole_radserver.",".
  $prole_tftpserver.",".
  $prole_bmrserver.",".
  $prole_iptvserver.",".
  $prole_gponserver.",".
  $prole_eponserver.",".
  $prole_statistic.",".
  $prole_brasserver.",".
  $prole_cmanager.
  "'"
  .chr(10), FILE_APPEND | LOCK_EX); //--<

  file_put_contents($file_ff, "echo '".$prole."_".$prole_router."=".$boo_prole_router."'".chr(10), FILE_APPEND | LOCK_EX); //--<
  file_put_contents($file_ff, "echo '".$prole."_".$prole_vpnserver."=".$boo_prole_vpnserver."'".chr(10), FILE_APPEND | LOCK_EX); //--<
  file_put_contents($file_ff, "echo '".$prole."_".$prole_dhcpserver."=".$boo_prole_dhcpserver."'".chr(10), FILE_APPEND | LOCK_EX); //--<
  file_put_contents($file_ff, "echo '".$prole."_".$prole_dhcprelay."=".$boo_prole_dhcprelay."'".chr(10), FILE_APPEND | LOCK_EX); //--<
  file_put_contents($file_ff, "echo '".$prole."_".$prole_radserver."=".$boo_prole_radserver."'".chr(10), FILE_APPEND | LOCK_EX); //--<
  file_put_contents($file_ff, "echo '".$prole."_".$prole_tftpserver."=".$boo_prole_tftpserver."'".chr(10), FILE_APPEND | LOCK_EX); //--<
  file_put_contents($file_ff, "echo '".$prole."_".$prole_bmrserver."=".$boo_prole_bmrserver."'".chr(10), FILE_APPEND | LOCK_EX); //--<
  file_put_contents($file_ff, "echo '".$prole."_".$prole_iptvserver."=".$boo_prole_iptvserver."'".chr(10), FILE_APPEND | LOCK_EX); //--<

  file_put_contents($file_ff, "echo '".$prole."_".$prole_gponserver."=".$boo_prole_gponserver."'".chr(10), FILE_APPEND | LOCK_EX); //--<
  file_put_contents($file_ff, "echo '".$prole."_".$prole_eponserver."=".$boo_prole_eponserver."'".chr(10), FILE_APPEND | LOCK_EX); //--<
  file_put_contents($file_ff, "echo '".$prole."_".$prole_statistic."=".$boo_prole_statistic."'".chr(10), FILE_APPEND | LOCK_EX); //--<
  file_put_contents($file_ff, "echo '".$prole."_".$prole_brasserver."=".$boo_prole_brasserver."'".chr(10), FILE_APPEND | LOCK_EX); //--<
  file_put_contents($file_ff, "echo '".$prole."_".$prole_cmanager."=".$boo_prole_cmanager."'".chr(10), FILE_APPEND | LOCK_EX); //--<

  echo system_exec_addlog($func_role, $chmod." 777 ".$file_ff);

  // load puppet
  // echo system_exec_addlog_puppet($func_role, $puppet." agent -t --environment=".$puppet_env." >/dev/null");

  // rewrite .os
  $fp = fopen($file_facter_os, "w");
  fwrite($fp, json_encode($hw_bi));
  fclose($fp);

 }

 return "0";
}

?>