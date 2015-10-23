<?php

function vrdat_update($router, $ch, $connect_url, $connect_api) {
 $func_role = basename(__FILE__)." ".__FUNCTION__ ;

 curl_setopt($ch, CURLOPT_URL, $connect_url."get_vrdat");
 curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"id_router\":\"".$router."\",\"action\":\"update\",\"api\":\"".$connect_api."\"}");
 $return=curl_exec($ch);

 if(curl_errno($ch)) {
  echo  system_addlog($func_role,"1","Curl error: ". curl_error($ch) );
 } else {

  $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ($httpcode == "200") {

   $result_json = json_decode($return, true);
   $vrdat=$result_json["vrdat"];
   set_vrdat_os($ch, $connect_url, $connect_api, $vrdat);

  } else {
   $last_url=curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
   echo system_addlog($func_role,"1","Error [".$httpcode."] ".$last_url);
  }

 }

}


function vrdat_syncro($router, $ch, $connect_url, $connect_api) {
 $func_role = basename(__FILE__)." ".__FUNCTION__ ;

 $sync_success="0";

 curl_setopt($ch, CURLOPT_URL, $connect_url."get_vrdat");
 curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"id_router\":\"".$router."\",\"action\":\"syncro\",\"api\":\"".$connect_api."\"}");
 $return=curl_exec($ch);

 if(curl_errno($ch)) {
  echo  system_addlog($func_role,"1","Curl error: ". curl_error($ch) );
 } else {
  $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ($httpcode == "200") {

   $result_json = json_decode($return, true);
   $iface=$result_json["iface"];
   $vrdat=$result_json["vrdat"];

   get_vrdat_os($iface);
   get_vrdat_bi($vrdat);
   sync_vrdat();
   $sync_success="1";

  } else {
   $last_url=curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
   echo system_addlog($func_role,"1","Error [".$httpcode."] ".$last_url);
  }
 }

 return $sync_success;

}


function get_vrdat_os($a_iface) {
 include("config.php");


 $iface_file=$data_dir."iface.os";
 if (file_exists($iface_file)) { unlink($iface_file); }
 file_put_contents($iface_file,"", FILE_APPEND | LOCK_EX);

 $vlans_file=$data_dir."vlans.os";
 if (file_exists($vlans_file)) { unlink($vlans_file); }
 file_put_contents($vlans_file,"", FILE_APPEND | LOCK_EX);

 $route_file=$data_dir."route.os";
 if (file_exists($route_file)) { unlink($route_file);}
 file_put_contents($route_file, "", FILE_APPEND | LOCK_EX);

 foreach ( $a_iface as $iface ) {

  unset ($data_route);
  exec($ifconfig." | grep 'flags\|inet ' | awk '$2~/^flags/{_1=$1;getline;if($1~/^inet/){print _1\"\"$2\"-\"$4}}' | sed s/:/-/  | grep ".$iface."." , $data_route);

  unset ($data_addr);
  exec($netstat." -rn -f inet -W | grep ".$iface.". | awk '{ print $1,$7 }'", $data_addr);

  foreach ($data_route as $a_data) {
   $s_data=explode("-",$a_data);
   $cou=count($s_data);
   if ( $cou == 3 ) {
    $arr_iface=explode(".",$s_data[0]);
    if ( count($arr_iface) == 2 ) {
     $siface=$arr_iface[0];
     $svlan=$arr_iface[1];
     $sip=$s_data[1];
     $smask=netmask2cidr(long2ip($s_data[2]));
     file_put_contents($iface_file, $siface.".".$svlan."-".$sip."/".$smask.chr(10), FILE_APPEND | LOCK_EX);
    }
   }
  }

  foreach ($data_addr as $row) {
   $srow=explode(" ",$row);
   $cou=count($srow);
   if ( $cou == 2 ) {
    $arr_iface=explode(".",$srow[1]);
    if ( count($arr_iface) == 2 ) {
     $siface=$arr_iface[0];
     $svlan=$arr_iface[1];
     $arr_network=explode("/",$srow[0]);
     $sip=$arr_network[0];
     $smask=$arr_network[1];
     file_put_contents($route_file, $siface.".".$svlan."-".$sip."/".$smask.chr(10), FILE_APPEND | LOCK_EX);
     file_put_contents($vlans_file, $siface.".".$svlan.chr(10), FILE_APPEND | LOCK_EX);
    }
   }
  }

 }

}

function get_vrdat_bi($command) {

 include("config.php");

 $file_route=$data_dir."route.bi";
 $file_iface=$data_dir."iface.bi";
 $file_vlans=$data_dir."vlans.bi";

 if (file_exists($file_route)) { unlink($file_route); }
 if (file_exists($file_iface)) { unlink($file_iface); }
 if (file_exists($file_vlans)) { unlink($file_vlans); }

 file_put_contents($file_route, "", FILE_APPEND | LOCK_EX);
 file_put_contents($file_iface, "", FILE_APPEND | LOCK_EX);
 file_put_contents($file_vlans, "", FILE_APPEND | LOCK_EX);

 foreach ($command as $row) {
  $srow=explode(" ",$row);
  $s_route_bi=$srow[0].".".$srow[1]."-".$srow[3]."/".$srow[4];
  $s_iface_bi=$srow[0].".".$srow[1]."-".$srow[2]."/".$srow[4];
  $s_vlans_bi=$srow[0].".".$srow[1];
  file_put_contents($file_route, $s_route_bi.chr(10), FILE_APPEND | LOCK_EX);
  file_put_contents($file_iface, $s_iface_bi.chr(10), FILE_APPEND | LOCK_EX);
  file_put_contents($file_vlans, $s_vlans_bi.chr(10), FILE_APPEND | LOCK_EX);
 }

}


function sync_vrdat() {

 include("config.php");
 $func_role = basename(__FILE__)." ".__FUNCTION__ ;

 $file_route_os=$data_dir."route.os";
 $file_route_bi=$data_dir."route.bi";
 $file_iface_os=$data_dir."iface.os";
 $file_iface_bi=$data_dir."iface.bi";
 $file_vlans_os=$data_dir."vlans.os";
 $file_vlans_bi=$data_dir."vlans.bi";

 if (file_exists($file_route_os))
 { $a_route_os=file($file_route_os); } else { $a_route_os=array(); }

 if (file_exists($file_route_bi))
 { $a_route_bi=file($file_route_bi); } else { $a_route_bi=array(); }

 if (file_exists($file_iface_os))
 { $a_iface_os=file($file_iface_os); } else { $a_iface_os=array(); }

 if (file_exists($file_iface_bi))
 { $a_iface_bi=file($file_iface_bi); } else { $a_iface_bi=array(); }

 if (file_exists($file_vlans_os))
 { $a_vlans_os=file($file_vlans_os); } else { $a_vlans_os=array(); }

 if (file_exists($file_vlans_bi))
 { $a_vlans_bi=file($file_vlans_bi); } else { $a_vlans_bi=array(); }

 echo system_addlog($func_role,"0","Get Iface from OS-".count($a_iface_os).", from Billing-".count($a_iface_bi));

 // Destroy Iface
 foreach ($a_iface_os as $row) {
  if (!in_array($row, $a_iface_bi)) {
   $vrow=explode(chr(10),$row);
   $srow=explode("-", $vrow[0]);
   if ( count($srow) == 2 ) {
    echo system_exec_addlog ($func_role, $ifconfig." ".$srow[0]." destroy");
#    echo system_addlog ($func_role, "0",$ifconfig." ".$srow[0]." destroy");
   }
  }
 }
 // Create Iface
 foreach ($a_iface_bi as $row) {
  if (!in_array($row, $a_iface_os)) {
   $vrow=explode(chr(10),$row);
   $srow=explode("-", $vrow[0]);
   if ( count($srow) == 2 ) {

    $arr_network=explode("/",$srow[1]);
    $arr_iface=explode(".",$srow[0]);
    $ip=$arr_network[0];
    $mask=$arr_network[1];
    $iface=$arr_iface[0];
    $vlan=$arr_iface[1];

    echo system_exec_addlog ($func_role, $ifconfig." ".$iface.".".$vlan." create inet ".$ip."/".$mask." up");
#     echo system_addlog ($func_role,"0", $ifconfig." ".$iface.".".$vlan." create inet ".$ip."/".$mask." up");

    if ( $mask == "32" ) {
     echo system_exec_addlog ($func_role, $route." delete ".$ip."/".$mask." -iface ".$iface.".".$vlan);
#     echo system_addlog ($func_role,"0", $route." delete ".$ip."/".$mask." -iface ".$iface.".".$vlan);
    }

   }
  }
 }


 // Create route
 foreach ($a_route_bi as $row) {
  if (!in_array($row, $a_route_os)) {
   $vrow=explode(chr(10),$row);
   $srow=explode("-", $vrow[0]);
   if ( count($srow) == 2 ) {

    $arr_network=explode("/", $srow[1]);
    $arr_iface=explode(".", $srow[0]);
    $ip=$arr_network[0];
    $mask=$arr_network[1];
    $iface=$arr_iface[0];
    $vlan=$arr_iface[1];

    if ( $mask == "32" ) {
#     echo system_addlog ($func_role, "0", $route." add ".$ip."/".$mask." dev ".$iface.".".$vlan);
     echo system_exec_addlog ($func_role, $route." add ".$ip."/".$mask." dev ".$iface.".".$vlan);
    }

   }
  }
 }


}

function set_vrdat_os($ch, $connect_url, $connect_api, $command) {
 include("config.php");
 $func_role = basename(__FILE__)." ".__FUNCTION__ ;

 foreach ($command as $row) {

  $srow=explode(" ",$row);

  if ( $srow[1] == "C" ) { //VRdat create

   $command_ifconfig = $ifconfig." ".$srow[2].".".$srow[3]." create inet ".$srow[4]."/".$srow[6]." up";
   echo system_exec_addlog ($func_role, $command_ifconfig);
   //echo system_addlog ($func_role, "0", $command_ifconfig);

   if ($srow[6] == "32") {

    $command_route = $route." delete ".$srow[4]."/".$srow[6]." -iface ".$srow[2].".".$srow[3];
    echo system_exec_addlog ($func_role, $command_route);
    //echo system_addlog ($func_role, "0", $command_route);

    $command_route = $route." add -net ".$srow[5]."/".$srow[6]." -iface ".$srow[2].".".$srow[3];
    echo system_exec_addlog ($func_role, $command_route);
    //echo system_addlog ($func_role, "0", $command_route);
   }

   set_vrdat_bi($ch, $connect_url, $connect_api, $srow[0],"success");

  }

  if ( $srow[1] == "D" ) { //VRdat delete

   $command_ip = $ifconfig." ".$srow[2].".".$srow[3]." destroy";
   echo system_exec_addlog ($func_role, $command_ip);
   //echo system_addlog ($func_role, "0",$command_ip);

   if ( $srow[6] == "32" ) {
    $command_ip = $route." delete ".$srow[5]."/".$srow[6];
    echo system_exec_addlog ($func_role, $command_ip);
    //echo system_addlog ($func_role, "0",$command_ip);
   }

   set_vrdat_bi($ch, $connect_url, $connect_api, $srow[0],"success");

  }

 }
}

function set_vrdat_bi($ch, $connect_url, $connect_api,  $id_vrdat, $status) {
 include("config.php");
 $func_role = basename(__FILE__)." ".__FUNCTION__ ;
 curl_setopt($ch, CURLOPT_URL, $connect_url."set_vrdat");
 curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"id_vrdat\":\"".$id_vrdat."\",\"status\":\"".$status."\",\"api\":\"".$connect_api."\"}");
 $return=curl_exec($ch);
 if(curl_errno($ch)) {
  echo  system_addlog($func_role,"1","Curl error: ". curl_error($ch) );
 } else {
  $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ($httpcode == "200") {
   echo system_addlog($func_role,"0", "Commit id_vrdat=".$id_vrdat);
  } else {
   $last_url=curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
   echo system_addlog($func_role,"1","Error [".$httpcode."] ".$last_url);
  }
 }
}


function netmask2cidr($netmask) {
 $cidr = 0;
 foreach (explode('.', $netmask) as $number) {
  for (;$number> 0; $number = ($number <<1) % 256) {
   $cidr++;
  }
 }
 return $cidr;
}

?>