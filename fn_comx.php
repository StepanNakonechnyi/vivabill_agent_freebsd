<?php

function cmanager_comx_action ($id, $ch, $connect_url, $connect_api, $action, $comment) {
 include ("config.php");
 $func_role = basename(__FILE__)." ".__FUNCTION__ ;
 $result="";
 curl_setopt($ch, CURLOPT_URL, $connect_url."set_cmanager_comx_action");
 curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"id\":\"".$id."\",\"action\":\"".$action."\",\"comment\":\"".$comment."\",\"api\":\"".$connect_api."\"}");
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

function cmanager_comx_driver ($data_file, $id_driver_mode ,$id_comx, $id_log_generate, $ch, $connect_url, $connect_api) {
 include ("config.php");
 $func_role = basename(__FILE__)." ".__FUNCTION__ ;
 $result="";
 curl_setopt($ch, CURLOPT_URL, $connect_url."get_comx_driver");
 curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"id_driver_mode\":\"".$id_driver_mode."\",\"id_comx\":\"".$id_comx."\",\"id_log_generate\":\"".$id_log_generate."\",\"api\":\"".$connect_api."\"}");
 $return=curl_exec($ch);
 if(curl_errno($ch)) {
  echo  system_addlog($func_role,"1","Curl error: ". curl_error($ch) );
 } else {
  $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ($httpcode == "200") {
   $result = json_decode($return, true);
   if (file_exists($data_file)) { unlink($data_file); }
   foreach ( $result as $line_num => $line ) {
    file_put_contents ($data_file, $line.chr(10), FILE_APPEND | LOCK_EX );
   }
  } else {
   $last_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
   echo system_addlog ($func_role,"1","Error [".$httpcode."] ".$last_url);
  }
 }
}

function cmanager_comx_task_result ($id_comx_manager, $ch, $connect_url, $connect_api) {
 include ("config.php");
 $task_result="0"; 
 foreach (glob($comx_dir."*.task") as $filename) {
  $task_result="1";
 }
 if ( $task_result == "1") {
  cmanager_comx_check($id_comx_manager, $ch, $connect_url, $connect_api);
 }
}

function cmanager_comx_check ($id_comx_manager, $ch, $connect_url, $connect_api) {
 include ("config.php");
 $func_role = basename(__FILE__)." ".__FUNCTION__ ;
 foreach (glob($comx_dir."*.task") as $filename) {
  $data = file_get_contents ($filename);
  $config_json = json_decode($data, true);

  $id=$config_json["process"]["id"];
  $pid=$config_json["process"]["pid"];
  $status=$config_json["process"]["status"];
  $comment=$config_json["process"]["comment"];
  $file_task=$comx_dir.$id.".task";
  $file_data=$comx_dir.$id.".data";

  if ( $status == "run") {

  }

  if ( $status == "success") {
   if ( cmanager_comx_action($id, $ch, $connect_url, $connect_api, "success", $comment) == "success") {
    if (file_exists($file_task)) { unlink($file_task); }
    if (file_exists($file_data)) { unlink($file_data); }
    echo system_addlog($func_role, "0", "Task ID-".$id.". ".$comment);
   }
  }

  if ( $status == "error") {
   if ( cmanager_comx_action($id, $ch, $connect_url, $connect_api, "error", $comment) == "success") {
    if (file_exists($file_task)) { unlink($file_task); }
    if (file_exists($file_data)) { unlink($file_data); }
    echo system_addlog($func_role, "1", "Task ID-".$id.". ".$comment);
   }
  }

 }
}


function cmanager_comx_task ($id_comx_manager, $ch, $connect_url, $connect_api) {
 include ("config.php");
 $func_role = basename(__FILE__)." ".__FUNCTION__ ;

 curl_setopt($ch, CURLOPT_URL, $connect_url."get_cmanager_comx_list");
 curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"id_comx_manager\":\"".$id_comx_manager."\",\"api\":\"".$connect_api."\"}");
 $return=curl_exec($ch);

 if(curl_errno($ch)) {
  echo  system_addlog($func_role,"1","Curl error: ". curl_error($ch) );
 } else {
  $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ($httpcode == "200") {


   $r_comx = json_decode($return,true);
   foreach ($r_comx as $comx) {
     $id=$comx["id"];

     $data_file=$comx_dir.$id.".data";
     $task_file=$comx_dir.$id.".task";

     if (!file_exists($task_file)) {

      $fp = fopen($data_file, "w");
      fwrite($fp, json_encode($comx));
      fclose($fp);

      $execute_mode = $comx["execute"]["mode"];
      $id_driver_mode = $comx["model"]["id_driver_mode"];
      $vlan_check = $comx["model"]["vlan_check"];
      $vlan_mib = $comx["model"]["vlan_mib"];

      // begin create Local config
      if ( $execute_mode == "local" ) {

       $file_exp=$tftp_cm_config.$comx["id"].".exp";
       $file_tmp=$tftp_cm_config.$comx["id"].".tmp";
       $id=$comx["id"];
       $id_comx=$comx["id_comx"];

       // TFTP mode
       if ( $id_driver_mode == "1" ) {
        cmanager_comx_driver($file_exp, "0", $id_comx, $id, $ch, $connect_url, $connect_api);
        cmanager_comx_driver($file_tmp, $id_driver_mode, $id_comx, $id, $ch, $connect_url, $connect_api);
       }

       // EXPECT mode
       if ( $id_driver_mode == "2" ) {
        cmanager_comx_driver($file_exp, $id_driver_mode, $id_comx, $id, $ch, $connect_url, $connect_api);
       }

       // EXPECT Wi-Fi mode
       if ( $id_driver_mode == "3" ) {
        cmanager_comx_driver($file_tmp, $id_driver_mode, $id_comx, $id, $ch, $connect_url, $connect_api);
       }

      }
      // end create Local config

      $execute_name=$driver_dir.$comx["execute"]["name"];

      if ( cmanager_comx_action($id, $ch, $connect_url, $connect_api, "prepare", "Prepare to run by ".$comx["execute"]["name"] ) == "success" ) {
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

?>