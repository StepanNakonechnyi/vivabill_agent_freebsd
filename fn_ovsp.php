<?php

function cmanager_ovsp_action ($id, $ch, $connect_url, $connect_api, $action, $comment) {
 include ("config.php");
 $func_role = basename(__FILE__)." ".__FUNCTION__ ;
 $result="";

 curl_setopt($ch, CURLOPT_URL, $connect_url."set_cmanager_ovsp_action");
 curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"id\":\"".$id."\",\"action\":\"".$action."\",\"api\":\"".$connect_api."\"}");
 $return=curl_exec($ch);
 if(curl_errno($ch)) {
  echo  system_addlog($func_role,"1","Curl error: ". curl_error($ch) );
 } else {
  $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ($httpcode == "200") {
   $result_json = json_decode($return, true);
   $result = $result_json["result"];
  } else {
   $last_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
   echo system_addlog ($func_role,"1","Error [".$httpcode."] ".$last_url);
  }
 }

 return $result;
}


function cmanager_ovsp_task_result ($id_comx_manager, $ch, $connect_url, $connect_api) {
 include ("config.php");

 $task_result="0"; 
 foreach (glob($ovsp_dir."*.task") as $filename) {
  $task_result="1";
 }

 if ( $task_result == "1") {
  cmanager_ovsp_check($id_comx_manager, $ch, $connect_url, $connect_api);
 }

}

function cmanager_ovsp_check ($id_comx_manager, $ch, $connect_url, $connect_api) {
 include ("config.php");
 $func_role = basename(__FILE__)." ".__FUNCTION__ ;
 foreach (glob($ovsp_dir."*.task") as $filename) {
  $data = file_get_contents ($filename);
  $config_json = json_decode($data, true);

  $id=$config_json["process"]["id"];
  $status=$config_json["process"]["status"];
  $comment=$config_json["process"]["comment"];
  $file_task=$ovsp_dir.$id.".task";
  $file_data=$ovsp_dir.$id.".data";

  if ( $status == "success") {
   if ( cmanager_ovsp_action($id, $ch, $connect_url, $connect_api, "success", $comment) == "success") {
    if (file_exists($file_task)) { unlink($file_task); }
    if (file_exists($file_data)) { unlink($file_data); }
    echo system_addlog($func_role, "0", "Task ID-".$id.". ".$comment);
   }
  }

  if ( $status == "error") {
   if ( cmanager_ovsp_action($id, $ch, $connect_url, $connect_api, "error", $comment) == "success") {
    if (file_exists($file_task)) { unlink($file_task); }
    if (file_exists($file_data)) { unlink($file_data); }
    echo system_addlog($func_role, "1", "Task ID-".$id.". ".$comment);
   }
  }

 }
}


function cmanager_ovsp_task ($id_comx_manager, $ch, $connect_url, $connect_api) {
 include ("config.php");
 $func_role = basename(__FILE__)." ".__FUNCTION__ ;

 curl_setopt($ch, CURLOPT_URL, $connect_url."get_cmanager_ovsp_list");
 curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"id_comx_manager\":\"".$id_comx_manager."\",\"api\":\"".$connect_api."\"}");
 $return=curl_exec($ch);

 if(curl_errno($ch)) {
  echo  system_addlog($func_role,"1","Curl error: ". curl_error($ch) );
 } else {
  $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ($httpcode == "200") {


   $r_ovsp = json_decode($return,true);
   foreach ($r_ovsp as $ovsp) {
     $id=$ovsp["id"];

     $data_file=$ovsp_dir.$id.".data";
     $task_file=$ovsp_dir.$id.".task";

     if (!file_exists($task_file)) {

      $fp = fopen($data_file, "w");
      fwrite($fp, json_encode($ovsp));
      fclose($fp);

      $execute_name=$driver_dir.$ovsp["execute"]["name"];

      if ( cmanager_ovsp_action($id, $ch, $connect_url, $connect_api, "process","") == "success" ) {
       $execute_command=$execute_name." ".$id." < /dev/null >> ".$log_dir."vivabill.log &";
       $return=system_exec($execute_command);
       echo system_addlog($func_role, $return, $execute_command );
      }

     } else {
      echo message_addlog($func_role,"Task ".$id." is exists");
     }

   }

  } else {
   $last_url=curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
   echo system_addlog($func_role,"1","Error [".$httpcode."] ".$last_url);
  }


 }

}

function cmanager_comx ($id_comx_manager, $ch, $connect_url, $connect_api) {
include("config.php");


}

?>