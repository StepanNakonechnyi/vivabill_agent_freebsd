#!/usr/local/bin/php
<?php

 function task_executant($id, $idm, $act) {

 include ("/usr/local/etc/vivabill/config.php");
 include ("/usr/local/etc/vivabill/fn_core.php");
 $func_role = basename(__FILE__)." ".__FUNCTION__ ;
 error_reporting(0);

 function set_task($vfile_task, $vact, $vid, $vidm, $vstatus, $vcomment) {

   file_put_contents($vfile_task, "{\"process\":{".
                                              "\"act\":\"".$vact."\",".
                                              "\"id\":\"".$vid."\",".
                                              "\"idm\":\"".$vidm."\",".
                                              "\"status\":\"".$vstatus."\",".
                                              "\"date\":\"".date('Y-m-d H:i:s', time())."\",".
                                              "\"comment\":\"".$vcomment."\"}}"
                                              );

 }

 $file_task=$tftp_cm_process."m".$idm.".task";
 $file_json=$tftp_cm_process."m".$idm.".json";
 $file_key=$tftp_cm_process."m".$idm.".key";
 $file_src=$tftp_cm_source."m".$idm.".src";
 $file_bin=$tftp_cm_config."m".$idm.".b";

 set_task ($file_task, $act, $id, $idm, "run","");

  #get modem config
  $data = file_get_contents ($file_json);
  $config_json = json_decode($data, true);

  $tftp_key=$config_json["tftp"]["key"];
  $modem_ip=$config_json["modem"]["ip"];
  $snmp_community=$config_json["snmp"]["community"];
  $snmp_model=$config_json["snmp"]["model"];
  $snmp_reboot=$config_json["snmp"]["reboot"];

  # create key file --
  file_put_contents($file_key, $tftp_key);

  # delete old binary file
  if (file_exists($file_bin)) { unlink($file_bin); }

  # action=1 concat and execute command
  if ( $act =="1" ) {
   $execute_command=$driver_dir."gen_tftp_bin -e ".$file_src." ".$file_key." ".$file_bin. " > /dev/null";
   $return=system_exec($execute_command);
   if ( $return == "0" ) {
    echo system_addlog($func_role, $return, "File $file_bin configured"); 
   }
  }

  # action=2 delete .src file
  if ( $act =="2" ) {
   if (file_exists($file_src)) { unlink($file_src); }
   $return="0";
  }

  if ( $return == "0" ) {

   # start reboot modem --
   $session = new SNMP(SNMP::VERSION_2c, $modem_ip, $snmp_community, 100000, 2);
   $session->valueretrieval = SNMP_VALUE_PLAIN;
   $model = $session->getnext("$snmp_model");
   $err = $session->getError();

   if ( $err == "" ) {
     $vreboot_dmodem = $session->set("$snmp_reboot","i","1");
     $err = $session->getError();
     if ( $err == "") {
      echo system_addlog($func_role, "0", "Reboot Modem ID $idm");
     }
   } else {
    echo message_addlog($func_role, "Modem ID $idm offline");
   }

   $session->close();

   # stop reboot modem --
   set_task ($file_task, $act, $id, $idm, "success","");

  } else {
   set_task ($file_task, $act, $id, $idm, "error","");
   echo system_addlog($func_role, $return, $execute_command);
  }

 }

 task_executant($argv[1],$argv[2],$argv[3]);

?>