#!/usr/local/bin/php
<?php

# This script accepts only one parameter - ID task
# Before run this script, system will generate file with data,
# which contains all require parameters.
#
# Before start process of namagement device, this script MUST create
# file with task extension, and keep in it information about process
# of management in JSON data with next parameters:
#    id       ID task
#    pid      PID of this script process
#    status   Task status (accepts "run", "success", or "error")
#    date     Date of change/create task
#    comment  Text information which can be sent to billing 
#             to report about process, or text error
#
# After finish of management device, script MUST set result in status section
# Notice:
#  Task with "success" status will automaticly closed by vivabill process
#  Task with "error" status will recreate task again, and report billing
#  Task with "run" status will ignored
#
# DATA SECTION contains data which required to manage device.
# Please, use lines in DATA SECTION if they necessary in CONFIG SECTION
# CONFIG SECTION contains code hardware configuration

 function task_executant($id) {

 include ("/usr/local/etc/vivabill/config.php");
 include ("/usr/local/etc/vivabill/fn_core.php");
 $func_role = basename(__FILE__)." ".__FUNCTION__ ;
 error_reporting(0);

 function set_task($vfile_task, $vid, $vstatus, $vcomment) {

   file_put_contents($vfile_task, "{\"process\":{".
                                              "\"id\":\"".$vid."\",".
                                              "\"pid\":\"".getmypid ()."\",".
                                              "\"status\":\"".$vstatus."\",".
                                              "\"date\":\"".date('Y-m-d H:i:s', time())."\",".
                                              "\"comment\":\"".$vcomment."\"}}"
                                              );

 }

  $file_task=$comx_dir.$id.".task";
  $file_data=$comx_dir.$id.".data";

  set_task ($file_task, $id, "run","");

  $a_data = file_get_contents ($file_data);
  $data = json_decode($a_data, true);

  # BEGIN DATA SECTION

  $id_comx=$data["id_comx"];
  $id_driver_mode=$data["model"]["id_driver_mode"];
  $model_eth=$data["model"]["eth"];
  $model_epon_onu=$data["model"]["epon_onu"];

  $access_community=$data["access"]["community"];
  $access_login=$data["access"]["login"];
  $access_password=$data["access"]["password"];
  $access_ip_cur=$data["access"]["ip_cur"];
  $access_ip_new=$data["access"]["ip_new"];

  $zone_community=$data["access"]["community"];
  $zone_login=$data["access"]["login"];
  $zone_password=$data["access"]["password"];

  $epon_olt_community=$data["epon_olt"]["community"];
  $epon_olt_ip=$data["epon_olt"]["ip"];

  # END DATA SECTION

  # BEGIN CONFIG SECTION

  $exp_file=$tftp_cm_config.$id.".exp";
  $tmp_file=$tftp_cm_config.$id.".tmp";
  set_task($file_task,$id,"run","Prepare execute. ".$expect." -f ".$exp_file);
  echo system_exec_addlog($func_role, $expect." -f ".$exp_file);

  if ( $model_epon_onu == "1" ) {
   $check_ip = $epon_olt_ip;
   $check_community = $epon_olt_community;
   $check_mib = "SNMPv2-MIB::sysContact.0";
  }

  if ( $model_eth == "1" ) {

   if ( $access_ip_new !="" ) {
    $check_ip=$access_ip_new;
   } else {
    $check_ip=$access_ip_cur;
   }

   if ( $access_community != "" ) {
    $check_community = $access_community;
   } else {
    $check_community = $zone_community;
   }

   $check_mib = "SNMPv2-MIB::sysContact.0";

  }

  set_task($file_task,$id,"run","Prepare to check updates by SNMP. ".$check_ip." ".$check_mib);

  $session = new SNMP(SNMP::VERSION_2c, $check_ip, $check_community);
  $session->valueretrieval = SNMP_VALUE_PLAIN;
  $check_id = $session->get($check_mib);
  $err = $session->getError();
  if ( $err == "" ) {

   if ( $check_id == $id ) {
    $result="success";
    if (file_exists($exp_file)) { unlink($exp_file); }
    if (file_exists($tmp_file)) { unlink($tmp_file); }
   } else {
    $err = "check_id=".$check_id.' id='.$id;
   }

  $session->close();
  } else {
   echo message_addlog($func_role, "Device COMX ID-$id_comx offline");
  }

  # END CONFIG SECTION

  if  ( $result == "success") {
   set_task ($file_task, $id, "success",$comment);
  }  else {
   set_task ($file_task, $id, "error", $err);
  }

 }

 task_executant($argv[1]);

?>