#!/usr/local/bin/php
<?php

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

 $result="not given";

  $file_task=$ovsp_dir.$id.".task";
  $file_data=$ovsp_dir.$id.".data";

  set_task ($file_task, $id, "run","");

  $data = file_get_contents ($file_data);
  $config = json_decode($data, true);

  $ip=$config["ip"];
  $model=$config["model"];
  $port=$config["port"];
  $speed_new=$config["speed"];
  $snmp_community=$config["snmp"]["community"];
  $snmp_mib=$config["snmp"]["mib"];

  $session = new SNMP(SNMP::VERSION_2c, $ip, $snmp_community, 100000, 2);
  $session->valueretrieval = SNMP_VALUE_PLAIN;
  $speed_get = $session->set($snmp_mib.$port,"i", $speed_new );
  $err = $session->getError();

  if ( $err == "" ) {

   if ( $speed_get == $speed_new ) {
    $comment =  $model." ".$ip." ".$snmp_mib.$port."=".$speed_new;
    $result="success";
   } else {
    $err =  $model." ".$ip." ".$snmp_mib.$port."=" .$speed_get.". Given ".$speed_new;
   }

  } else {
   echo system_addlog($func_role, "1", $err);
  }

  $session->close();

  if  ( $result == "success") {
   set_task ($file_task, $id, "success",$comment);
  }  else {
   set_task ($file_task, $id, "error", $err);
  }

 }

 task_executant($argv[1]);

?>