<?php


function ipfw_start($router, $ch, $connect_url, $connect_api) {
 include("config.php");

 if ( ipfw_syncro($router, $ch, $connect_url, $connect_api) == "0" ) {
  ipfw_syncro_recovery();
 }

}


function ipfw_syncro_recovery() {
 include("config.php");

 $file_ipaddr=$data_dir."ipfw_ipaddr_bi.json";
 $file_tables=$data_dir."ipfw_tables_bi.json";

 if (file_exists($file_ipaddr)) {
  $data = file_get_contents ($file_ipaddr);
  $ipaddr_bi = json_decode($data, true);
 } else {
  $ipaddr_bi = array();
 }

 if (file_exists($file_tables)) {
  $data = file_get_contents ($file_tables);
  $tables_bi = json_decode($data, true);
 } else {
  $tables_bi = array();
 }

 $ipaddr_os = ipfw_ipaddr_os($tables_bi);

 ipfw_ipaddr_comparer($ipaddr_bi, $ipaddr_os);

}


function ipfw_syncro($router, $ch, $connect_url, $connect_api) {
 include("config.php");

 $sync_success="0";
 $tables_bi = ipfw_tables_bi($router, $ch, $connect_url, $connect_api);

 if ( $tables_bi > 0 ) {

  $ipaddr_os = ipfw_ipaddr_os($tables_bi);
  $ipaddr_bi = ipfw_ipaddr_bi($router, $ch, $connect_url, $connect_api);

  if ( $ipaddr_bi > 0 ) {

   ipfw_ipaddr_comparer($ipaddr_bi, $ipaddr_os);
   ipfw_ipaddr_savedata($ipaddr_bi);
   ipfw_tables_savedata($tables_bi);
   $sync_success="1";

  }

 }

 return $sync_success;

}


function ipfw_ipaddr_os($tables_bi) {
 include("config.php");
 $func_role = basename(__FILE__)." ".__FUNCTION__ ;

 $arr_os = array();
 $cou_os = 0;

 //Start table cross
 foreach ($tables_bi as $num_tables => $num_table) {
  unset ($data);
  exec($ipfw." table ".$num_table." list", $data);
  foreach ($data as $row) {
   $rows=explode("/",$row);
   $ip=$rows[0];
    array_push($arr_os , $num_table.":".$ip);
   }
   $cou_data=count($data);
   if ( $cou_data > 0 ) {
    echo message_addlog ($func_role, "Check table ".$num_table.". Avaliable ".$cou_data." IP");
    $cou_os = $cou_os+$cou_data;
  }
 }

 $cou_os = count($arr_os);

 if ( $cou_os > 0 ) {
  echo message_addlog ($func_role, "Get ".$cou_os." IP from OS");
 } else {
  echo message_addlog ($func_role, "IP list from OS is empty");
 }

 return $arr_os;

}


// Get list of tables which use in billing -----------------------------------
function ipfw_tables_bi($router, $ch, $connect_url, $connect_api) {
 include("config.php");
 $func_role = basename(__FILE__)." ".__FUNCTION__ ;

 $config_tables = array();
 curl_setopt($ch, CURLOPT_URL, $connect_url."get_ipfw_tables");
 curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"id_router\":\"".$router."\",\"".$connect_api."\":\"1\"}");
 $return=curl_exec($ch);

 if(curl_errno($ch)) {
  echo  system_addlog($func_role,"1","Curl error: ". curl_error($ch) );
 } else {

  $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ($httpcode == "200") {
   $config_tables = json_decode($return,true);
  } else {
   $last_url=curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
   echo system_addlog ($func_role,"1","Error [".$httpcode."] ".$last_url);
  }
 }

 $cou_tables = count($config_tables);

 if ( $cou_tables > 0 ) {
  echo message_addlog ($func_role, "Get ".$cou_tables." Tables from Billing");
 } else {
  echo message_addlog ($func_role, "Tables list from Billing is empty");
 }

 return $config_tables;
}


function ipfw_ipaddr_savedata($arr_bi) {
 include("config.php");
 $func_role = basename(__FILE__)." ".__FUNCTION__ ;

 // Save data to OS
 $fp = fopen($data_dir."ipfw_ipaddr_bi.json", "w");
 fwrite($fp, json_encode($arr_bi));
 fclose($fp);
 echo message_addlog ($func_role, "Data saved to ipfw_ipaddr_bi.json");
}

function ipfw_tables_savedata($arr_bi) {
 include("config.php");
 $func_role = basename(__FILE__)." ".__FUNCTION__ ;

 // Save data to OS
 $fp = fopen($data_dir."ipfw_tables_bi.json", "w");
 fwrite($fp, json_encode($arr_bi));
 fclose($fp);
 echo message_addlog ($func_role, "Data saved to ipfw_tables_bi.json");
}

function ipfw_ipaddr_comparer($arr_bi, $arr_os) {
 include("config.php");
 $func_role = basename(__FILE__)." ".__FUNCTION__ ;

 if ( count($arr_bi)>0 ) {

  // Find ip addresess for delete
  foreach ($arr_os as $row) {
   if (!in_array($row, $arr_bi)) {
    $vrow=explode(chr(10),$row);
    $srow=explode(":", $vrow[0]);
    if ( count($srow) == 2 ) {
     echo system_exec_addlog ($func_role, $ipfw." -q -f table ".$srow[0]." delete ".$srow[1]);
    }
   }
  }

  // Find ip addresess for add
  foreach ($arr_bi as $row) {
   if (!in_array($row, $arr_os)) {
    $vrow=explode(chr(10),$row);
    $srow=explode(":", $vrow[0]);
    if ( count($srow) == 2 ) {
     echo system_exec_addlog (basename(__FILE__)." ".__FUNCTION__, $ipfw." -q -f table ".$srow[0]." add ".$srow[1]);
    }
   }
  }

  echo system_addlog ($func_role, "0", "Compare successfully completed");

 } else {
  echo message_addlog ($func_role, "Data from Billing is empty");
 }

}

function ipfw_ipaddr_bi($router, $ch, $connect_url, $connect_api) {
 include("config.php");
 $func_role = basename(__FILE__)." ".__FUNCTION__ ;

 $config_ipaddr = array();

 curl_setopt($ch, CURLOPT_URL, $connect_url."get_ipfw_ipaddr");
 curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"id_router\":\"".$router."\",\"".$connect_api."\":\"1\"}");
 $return=curl_exec($ch);
 if(curl_errno($ch)) {
  echo  system_addlog($func_role,"1","Curl error: ". curl_error($ch) );
 } else {
  $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ($httpcode == "200") {
   $config_ipaddr = json_decode($return,true);
  } else {
   $last_url=curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
   echo system_addlog ($func_role,"1","Error [".$httpcode."] ".$last_url);
  }
 }

 $cou_ipaddr=count($config_ipaddr);

 if ( count($cou_ipaddr > 0 ))  {
  echo message_addlog ($func_role, "Get ".$cou_ipaddr." IP from Billing");
 } else {
  echo message_addlog ($func_role, "IP list from Billing is empty");
 }

 return $config_ipaddr;

}


function ipfw_update($router, $ch, $connect_url, $connect_api) {
 include("config.php");
 $func_role = basename(__FILE__)." ".__FUNCTION__ ;

 curl_setopt($ch, CURLOPT_URL, $connect_url."get_ipfw_update");
 curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"id_router\":\"".$router."\",\"".$connect_api."\":\"1\"}");
 $return=curl_exec($ch); 

 if(curl_errno($ch)) {
  echo  system_addlog($func_role,"1","Curl error: ". curl_error($ch) );
 } else {

  $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ($httpcode == "200") {

   $config_ipfw = json_decode($return,true);

   foreach ($config_ipfw as $line_num => $line) {
    echo system_exec_addlog ($func_role, $ipfw." -q -f table ".$line);
   }

  } else {
   $last_url=curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
   echo system_addlog ($func_role,"1","Error [".$httpcode."] ".$last_url);
  }
 }


}

?>