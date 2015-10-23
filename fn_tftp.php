<?php

function tftp_generate_config_data($id, $idm, $ch, $connect_url, $connect_api) {
 include("config.php");
 $func_role = basename(__FILE__)." ".__FUNCTION__ ;

 $file_data=$tftp_cm_process."m".$idm.".data";

 curl_setopt($ch, CURLOPT_URL, $connect_url."get_tftp_modem_config");
 curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"id_log_modem_tftp\":\"".$id."\",\"api\":\"".$connect_api."\"}");
 $return=curl_exec($ch);

 if(curl_errno($ch)) {
  echo  system_addlog($func_role,"1","Curl error: ". curl_error($ch) );
 } else {
  $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ($httpcode == "200") {

   if (file_exists($file_data)) {
    unlink($file_data);
    echo system_addlog($func_role,"0","File $file_data was deleted");
   }

   $config_data = json_decode($return,true);
   $fp = fopen($file_data, "w");
   fwrite($fp, json_encode($config_data));
   fclose($fp);

   if (file_exists($file_data)) {
   echo system_addlog($func_role,"0","File $file_data was created");
   }

  } else {
   $last_url=curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
   echo system_addlog ($func_role,"1","Error [".$httpcode."] ".$last_url);
  }
 }

 return $config_data;

}



function tftp_generate_local_src($idm, $ch, $connect_url, $connect_api) {
 include("config.php");
 $func_role = basename(__FILE__)." ".__FUNCTION__ ;

 $file_src=$tftp_cm_source."m".$idm.".src";

 // Create src TFTP file if need
 curl_setopt($ch, CURLOPT_URL, $connect_url."get_tftp_modem_source");
 curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"idm\":\"".$idm."\",\"type\":\"dynamic\",\"api\":\"".$connect_api."\"}");
 $return=curl_exec($ch);


 if(curl_errno($ch)) {
  echo  system_addlog($func_role,"1","Curl error: ". curl_error($ch) );
 } else {
  $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ($httpcode == "200") {

   if (file_exists($file_src)) { unlink($file_src); }

    $config_data = json_decode($return,true);

    foreach ($config_data as $line_num => $line) {
     file_put_contents($file_src, $line.chr(10), FILE_APPEND | LOCK_EX);
    }

   if (file_exists($file_src)) {
    echo system_addlog($func_role,"0","ID modem $idm. File $file_src was created");
   }

  } else {
   $last_url=curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
   echo system_addlog ($func_role,"1","Error [".$httpcode."] ".$last_url);
  }
 }

}

function tftp_server_task_result($tftpserver, $ch, $connect_url, $connect_api) {
 include("config.php");
 $task_result="0";

 foreach (glob($tftp_cm_process."*.task") as $filename) {
  $task_result="1";
 }

 if ( $task_result == "1" ) {
  tftp_server_check($tftpserver, $ch, $connect_url, $connect_api);
 }

}

function tftp_server_check($tftpserver, $ch, $connect_url, $connect_api) {
 include("config.php");
 $func_role = basename(__FILE__)." ".__FUNCTION__ ;

 foreach (glob($tftp_cm_process."*.task") as $filename) {

  $data = file_get_contents ($filename);
  $config_data = json_decode($data, true);

  $act=$config_data["process"]["act"];
  $id=$config_data["process"]["id"];
  $idm=$config_data["process"]["idm"];
  $status=$config_data["process"]["status"];
  $date=$config_data["process"]["date"];

  if ( $id == "" ) {
   $status="zombie";
   $file_data=$tftp_cm_process."m".$idm.".data";
   $file_task=$tftp_cm_process."m".$idm.".task";
   $file_key=$tftp_cm_process."m".$idm.".key";
   if (file_exists($file_data)) { unlink($file_data); }
   if (file_exists($file_task)) { unlink($file_task); }
   if (file_exists($file_key)) { unlink($file_key); }
  }

  if ( $status == "run") {
   // check time from run to current time, and clear process
  }

  if ( $status == "error") {

   // check time from run to current time, and clear process
   echo message_addlog($func_role, "Detect status [".$status."] for ID modem $idm");

   // increment error counter
   curl_setopt($ch, CURLOPT_URL, $connect_url."set_tftp_modem_error");
   curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"id\":\"".$id."\",\"api\":\"".$connect_api."\"}");
   $return=curl_exec($ch);
   if(curl_errno($ch)) {
    echo  system_addlog($func_role,"1","Curl error: ". curl_error($ch) );
   } else {
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpcode == "200") {
    } else {
     $last_url=curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
     echo system_addlog ($func_role,"1","Error [".$httpcode."] ".$last_url);
    }
   }

   // get paramerets and create data file
   $config_data=tftp_generate_config_data($id, $idm, $ch, $connect_url, $connect_api);

   // set main parameters
   $execute_mode=$config_data["execute"]["mode"];
   $execute_name=$driver_dir.$config_data["execute"]["name"];

   // generate .src local config if necessary
   if ( $execute_mode == "local" ) {
    if ( $act == "1" ) {
     tftp_generate_local_src($idm, $ch, $connect_url, $connect_api);
    }
   }

   // build command and execute in thread
   $execute_command=$execute_name." ".$id." ".$idm." ".$act." < /dev/null >> ".$log_dir."vivabill.log &";
   $return=system_exec($execute_command);
   echo system_addlog($func_role, $return, $execute_command );

  }


  if ( $status == "success") {
  // success - close process

   echo message_addlog($func_role, "Detect status [".$status."] for ID modem $idm");

   curl_setopt($ch, CURLOPT_URL, $connect_url."set_tftp_modem_delete");
   curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"id\":\"".$id."\",\"api\":\"".$connect_api."\"}");
   $return=curl_exec($ch);

   if(curl_errno($ch)) {
    echo  system_addlog($func_role,"1","Curl error: ". curl_error($ch) );
   } else {

    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpcode == "200") {
     $result_data = json_decode($return, true);
     $result=$result_data["result"];
     if ( $result == "success" ) {
      $file_data=$tftp_cm_process."m".$idm.".data";
      $file_task=$tftp_cm_process."m".$idm.".task";
      $file_key=$tftp_cm_process."m".$idm.".key";
      if (file_exists($file_data)) { unlink($file_data); }
      if (file_exists($file_task)) { unlink($file_task); }
      if (file_exists($file_key)) { unlink($file_key); }
     }
    }

   }

  }

 }

}

function tftp_server_config($tftpserver, $ch, $connect_url, $connect_api) {
 include("config.php");

}

function tftp_server_modem($tftpserver, $ch, $connect_url, $connect_api) {
 include("config.php");
 $func_role = basename(__FILE__)." ".__FUNCTION__ ;

 curl_setopt($ch, CURLOPT_URL, $connect_url."get_tftp_modem_change");
 curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"id_tftp_server\":\"".$tftpserver."\",\"api\":\"".$connect_api."\"}");
 $return=curl_exec($ch);

 if(curl_errno($ch)) {
  echo  system_addlog($func_role,"1","Curl error: ". curl_error($ch) );
 } else {
  $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ($httpcode == "200") {

   $tftp = json_decode($return, true);

   foreach ($tftp as $row) { // Start change TFTP modem

    $id=$row["id"]; $idm=$row["idm"]; $act=$row["act"];
    $file_task=$tftp_cm_process."m".$idm.".task";
    if (file_exists($file_task)) {
     echo message_addlog($func_role, "Task $file_task is runing by other process. Wait.");
    } else {

     // get paramerets and create yml file
     $config_data=tftp_generate_config_data($id, $idm, $ch, $connect_url, $connect_api);
     // set main parameters
     $execute_mode=$config_data["execute"]["mode"];
     $execute_name=$driver_dir.$config_data["execute"]["name"];
     // generate .src local config if necessary
     if ( $execute_mode == "local" ) {
      if ( $act == "1" ) {
       tftp_generate_local_src($idm, $ch, $connect_url, $connect_api);
      }
     }
     // build command and execute in thread
     curl_setopt($ch, CURLOPT_URL, $connect_url."set_tftp_modem_process");
     curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"id\":\"".$id."\",\"api\":\"".$connect_api."\"}");
     $return=curl_exec($ch);

     if(curl_errno($ch)) {
      echo  system_addlog($func_role,"1","Curl error: ". curl_error($ch) );
     } else {
      $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      if ($httpcode == "200") {
       $result_data = json_decode($return, true);
       $result=$result_data["result"];
       if ( $result == "success" ) {
        $execute_command=$execute_name." ".$id." ".$idm." ".$act." < /dev/null >> ".$log_dir."vivabill.log &";
        $return=system_exec($execute_command);
        echo system_addlog($func_role, $return, $execute_command );
       }
      } else {
       $last_url=curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
       echo system_addlog ($func_role,"1","Error [".$httpcode."] ".$last_url);
      }
     }
    }
   } // Finish change TFTP modems

  } else {
   $last_url=curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
   echo system_addlog ($func_role,"1","Error [".$httpcode."] ".$last_url);
  }

 }
}
?>