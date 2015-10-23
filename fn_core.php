<?php

function get_network($ip, $cidr) {
 $bitmask = $cidr == 0 ? 0 : 0xffffffff << (32 - $cidr);
 return long2ip(ip2long($ip) & $bitmask);
}

function addlog ($role, $message) {
 include("config.php");
 $W="\033[0;37m";
 $N="\033[0m";
 $result="[MESSAGE] ";
 $resultc="${W}[MESSAGE]${N} ";
 $role="[".$role."] ";
 file_put_contents($log_file, "[".date('Y-m-d H:i:s', time())."] ".$result.$role.$message.chr(10), FILE_APPEND | LOCK_EX);
 return "[".date('Y-m-d H:i:s', time())."] ".$resultc.$role.$message.chr(10);
}

function system_addlog ($role, $result, $command) {
 include("config.php");
 $iresult=$result;
 $R="\033[0;31m";
 $G="\033[0;32m";
 $N="\033[0m";
 if ($result=="0") {
  $result ="[SUCCESS] ";
  $resultc="${G}[SUCCESS]${N} ";
 } else {
  $result ="[ERROR ".$iresult."] ";
  $resultc="${R}[ERROR ".$iresult."]${N} ";
 }
 $role="[".$role."] ";
 file_put_contents($log_file, "[".date('Y-m-d H:i:s', time())."] ".$result.$role.$command.chr(10), FILE_APPEND | LOCK_EX);
 return "[".date('Y-m-d H:i:s', time())."] ".$resultc.$role.$command.chr(10);
}

function message_addlog ($role, $command) {
 include("config.php");
 $W="\033[0;37m";
 $N="\033[0m";
 $result="[MESSAGE] ";
 $resultc="${W}[MESSAGE]${N} ";
 $role="[".$role."] ";
 file_put_contents($log_file, "[".date('Y-m-d H:i:s', time())."] ".$result.$role.$command.chr(10), FILE_APPEND | LOCK_EX);
 return "[".date('Y-m-d H:i:s', time())."] ".$resultc.$role.$command.chr(10);
}

function system_exec_addlog ($role, $command) {
 include("config.php");
 $output=system ($command,$result);
 $iresult=$result;
 $R="\033[0;31m";
 $G="\033[0;32m";
 $N="\033[0m";
 if ($result=="0") {
  $result="[SUCCESS] ";
  $resultc="${G}[SUCCESS]${N} ";
 } else {
  $result="[ERROR ".$iresult."] ";
  $resultc="${R}[ERROR ".$iresult."]${N} ";
 }
 $role="[".$role."] ";
 file_put_contents($log_file, "[".date('Y-m-d H:i:s', time())."] ".$result.$role.$command.chr(10), FILE_APPEND | LOCK_EX);
 return "[".date('Y-m-d H:i:s', time())."] ".$resultc.$role.$command.chr(10);
}

function system_exec_addlog_puppet ($role, $command) {
 include("config.php");
 $output=system ($command,$result);
 $iresult=$result;
 $R="\033[0;31m";
 $G="\033[0;32m";
 $N="\033[0m";
 if ( ($result=="0") || ($result=="2") ) {
  $result="[SUCCESS] ";
  $resultc="${G}[SUCCESS]${N} ";
 } else {
  $result="[ERROR ".$iresult."] ";
  $resultc="${R}[ERROR ".$iresult."]${N} ";
 }
 $role="[".$role."] ";

 file_put_contents($log_file, "[".date('Y-m-d H:i:s', time())."] ".$result.$role.$command.chr(10), FILE_APPEND | LOCK_EX);
 return "[".date('Y-m-d H:i:s', time())."] ".$resultc.$role.$command.chr(10);
}

function system_exec ($command) {
 $output=system ($command, $result);
 return $result;
}

function identical_values( $arrayA , $arrayB ) {
 sort( $arrayA );
 sort( $arrayB );
 return $arrayA == $arrayB;
}


?>