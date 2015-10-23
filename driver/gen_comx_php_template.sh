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

  $id_comx=$data["id_comx"]; # ID node in billing

  # data from node
  $access_community=$data["access"]["community"];
  $access_login=$data["access"]["login"];
  $access_password=$data["access"]["password"];
  $access_ip_cur=$data["access"]["ip_cur"];
  $access_ip_new=$data["access"]["ip_new"];

  # data from zone
  $zone_community=$data["access"]["community"];
  $zone_login=$data["access"]["login"];
  $zone_password=$data["access"]["password"];

  # data from epon olt
  $epon_olt_community=$data["epon_olt"]["community"];
  $epon_olt_ip=$data["epon_olt"]["ip"];

  # END DATA SECTION

  # BEGIN CONFIG SECTION


  # begin example check status configuration by SNMP
  $check_ip = #IP;
  $check_community = #COMMUNITY;
  $check_mib = "SNMPv2-MIB::sysContact.0";

  set_task($file_task,$id,"run","Prepare to check updates by SNMP. ".$check_ip." ".$check_mib);

  $session = new SNMP(SNMP::VERSION_2c, $check_ip, $check_community);
  $session->valueretrieval = SNMP_VALUE_PLAIN;
  $check_id = $session->get($check_mib);
  $err = $session->getError();
  if ( $err == "" ) {

   if ( $check_id == $id ) {
    $result="success";
   } else {
    $err = "check_id=".$check_id.' id='.$id;
   }

   $session->close();

  } else {
   echo message_addlog($func_role, "Device COMX ID-$id_comx offline");
  }
  # end example check status configuration by SNMP


  # END CONFIG SECTION

  if  ( $result == "success") {
   set_task ($file_task, $id, "success",$comment);
  }  else {
   set_task ($file_task, $id, "error", $err);
  }

 }

 task_executant($argv[1]);

?>