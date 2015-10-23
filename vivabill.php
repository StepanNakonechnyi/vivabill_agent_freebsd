<?php

 function task_manager($vcpid) {

 $func_role = basename(__FILE__)." ".__FUNCTION__ ;

 include("config.php");
 include("fn_core.php");
 include("fn_puppet.php");
 include("fn_ipfw.php");
 include("fn_dhcp.php");
 include("fn_vrdat.php");
 include("fn_tftp.php");
 include("fn_ovsp.php");
 include("fn_comx.php");

 $pid_file=$pid_dir."vivabill.pid";

 if (file_exists($pid_file)) {

  // b_work -------------------------------------------------

  $a_vfpid=file($pid_file);
  $vfpid=$a_vfpid[0];

  if ( $vfpid == $vcpid ) {

   exec($uname." -o",$rname_os);
   exec($uname." -r",$rname_rel);
   exec($hostname, $rname_host);

   $name_os=$rname_os[0];
   $name_rel=$rname_rel[0];
   $name_host=$rname_host[0];

   $data = file_get_contents ($home_dir."vivabill.json");
   $config = json_decode($data, true);
   $uuid=$config["uuid"];
   $connect_url=$config["connect"]["url"]."/viva/";
   $connect_api=$config["connect"]["api"];
   $connect_username=$config["connect"]["username"];
   $connect_password=$config["connect"]["password"];

   echo message_addlog ($func_role, "Open process UUID=".$uuid);

   $rproc=true;
   $current_maintain = "0";
   $current_notify_interval = 10;
   $counter = 0;

   // Set SSL parameters
   $ch = curl_init();
   $headers = array("Content-Type: application/json");
   curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
   curl_setopt($ch, CURLOPT_CAINFO, $home_dir."viva-cert.pem");
   curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
   curl_setopt($ch, CURLOPT_HEADER, 0);
   curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
   curl_setopt($ch, CURLOPT_USERPWD, $connect_username . ":" . $connect_password);
   curl_setopt($ch, CURLOPT_POST, 1);
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

   //Set HW
   curl_setopt($ch, CURLOPT_URL, $connect_url."set_hw");
   curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"uuid\":\"".$uuid."\",\"name_os\":\"".$name_os."\",\"name_rel\":\"".$name_rel."\",\"hostname\":\"".$name_host."\",\"api\":\"".$connect_api."\"}");
   $return=curl_exec($ch);
   if(curl_errno($ch)) {
    echo  system_addlog($func_role,"1","Curl error: ". curl_error($ch) );
   } else {
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpcode == "200") {

     $status_load = json_decode($return,true);
     $status=$status_load["status"];
     $message=$status_load["message"];
     echo system_addlog($func_role,$status,$message);

    } else {
     $last_url=curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
     echo system_addlog($func_role,"1","Error [".$httpcode."] ".$last_url);
    }
   }

   echo message_addlog ($func_role, "Start Task manager with API v.".$connect_api." URL ".$connect_url);

   while ($rproc == true) { // Start cycle

    if (!file_exists($pid_file)) {
     echo message_addlog ($func_role, "Stop Task manager");
     $rproc=false;
     break;
    } else {
     $a_vfpid=file($pid_file);
     $vfpid=$a_vfpid[0];
     if ( $vfpid != $vcpid ) {
      echo message_addlog ($func_role, "Lost process UUID=".$uuid);
      $rproc=false;
      break;
     }
    }

   // COUTNER
   if ($counter>600) {
    $counter=0;
   }

   //#######################
   //# START HW SEMAPHORES #
   //#######################

   curl_setopt($ch, CURLOPT_URL, $connect_url."get_semaphores_hw");
   curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"uuid\":\"".$uuid."\",\"api\":\"".$connect_api."\"}");
   $return=curl_exec($ch);

   if(curl_errno($ch))
   {
    echo  system_addlog($func_role,"1","URL ".$current_url." error: ". curl_error($ch) );
   } else {

    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpcode == "200") {

     $hw = json_decode($return, true);

     // Get TASK options
     $sm_maintain=$hw["maintain"];
     $sm_notify_interval=$hw["notify_interval"];

     // get PROC options
     $router=$hw["router"];
     $sm_vpnserver=$hw["vpnserver"];
     $dhcpserver=$hw["dhcpserver"];
     $sm_dhcprelay=$hw["dhcprelay"];
     $sm_radserver=$hw["radserver"];
     $tftpserver=$hw["tftpserver"];
     $sm_bmrserver=$hw["bmrserver"];
     $sm_iptvserver=$hw["iptvserver"];
     $sm_gponserver=$hw["gponserver"];
     $sm_eponserver=$hw["eponserver"];
     $sm_statistic=$hw["statistic"];
     $sm_brasserver=$hw["bras"];
     $cmanager=$hw["cmanager"];

     // get maintain data
     if ( $current_maintain != $sm_maintain ) {
     switch ($sm_maintain) {
      case "0":
       echo message_addlog ($func_role, "End of maintain");
       break;
      case "1":
       echo message_addlog ($func_role, "Begin maintain");
       break;
      }
     }

     // get notify data
     if ( $current_notify_interval != $sm_notify_interval ) {
      echo message_addlog ($func_role, "Interval queries to API set to ".$sm_notify_interval." second");
     }

     $current_maintain=$sm_maintain;
     $current_notify_interval=$sm_notify_interval;

     if ( $sm_maintain == "0" ) { // begin task process

        ////////////////////////
       // PUPPET declaration //
      ////////////////////////
      check_roles_facter ($hw,$data_dir);

        ////////////////////////
       // TFTP Server        //
      ////////////////////////
      if ( $tftpserver != "none" ) {

       curl_setopt($ch, CURLOPT_URL, $connect_url."get_semaphores_tftpserver");
       curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"id_tftp_server\":\"".$tftpserver."\",\"api\":\"".$connect_api."\"}");
       $return=curl_exec($ch);

       if(curl_errno($ch)) {
        echo  system_addlog($func_role,"1","Curl error: ". curl_error($ch) );
       } else {
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpcode == "200") {

         $tftp = json_decode($return, true);
         $sm_tftp_server_config=$tftp["config"];
         $sm_tftp_server_modem=$tftp["modem"];
         $sm_tftp_server_check=$tftp["check"];

         if ( $sm_tftp_server_config=="1" ) {
          echo message_addlog($func_role, "[tftp_server_config]");
          tftp_server_config($tftpserver, $ch, $connect_url, $connect_api);
         }

         if ( $sm_tftp_server_modem=="1" ) {
          echo message_addlog($func_role, "[tftp_server_modem]");
          tftp_server_modem($tftpserver, $ch, $connect_url, $connect_api);
         }

         if ( $sm_tftp_server_check=="1" ) {
          echo message_addlog($func_role, "[tftp_server_check]");
          tftp_server_check($tftpserver, $ch, $connect_url, $connect_api);
         }

         tftp_server_task_result($tftpserver, $ch, $connect_url, $connect_api);

        } else {
         $last_url=curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
         echo system_addlog($func_role,"1","Error [".$httpcode."] ".$last_url);
        }
       }

      }

        ////////////////////////
       // DHCP Server        //
      ////////////////////////
      if ( $dhcpserver != "none" ) {

       curl_setopt($ch, CURLOPT_URL, $connect_url."get_semaphores_dhcpserver");
       curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"id_dhcp_server\":\"".$dhcpserver."\",\"api\":\"".$connect_api."\"}");
       $return=curl_exec($ch);

       if(curl_errno($ch)) {
        echo  system_addlog($func_role,"1","Curl error: ". curl_error($ch) );
       } else {
         $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
         if ($httpcode == "200") {

          $dhcp = json_decode($return, true);
          $dhcpserver_config=$dhcp["config"];
          $dhcpserver_update=$dhcp["update"];

          if ( $dhcpserver_config=="1" ) {
           echo message_addlog($func_role, "[dhcpserver_config]");
           dhcpserver_config($dhcpserver, $ch, $connect_url, $connect_api);
          }

          if ( $dhcpserver_update=="1" ) {
           echo message_addlog($func_role, "[dhcpserver_update]");
           dhcpserver_update($dhcpserver, $ch, $connect_url, $connect_api);
          }


         } else {
          $last_url=curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
          echo system_addlog($func_role,"1","Error [".$httpcode."] ".$last_url);
         }

       }

      }

        ////////////////////////
       // Router             //
      ////////////////////////

      if ( $router != "none" ) {

       curl_setopt($ch, CURLOPT_URL, $connect_url."get_semaphores_router");
       curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"id_router\":\"".$router."\",\"api\":\"".$connect_api."\"}");
       $return=curl_exec($ch);

       if(curl_errno($ch)) {
        echo  system_addlog($func_role,"1","Curl error: ". curl_error($ch) );
       } else {

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpcode == "200") {

         $rou = json_decode($return, true);

         $dhcprelay_config=$rou["dhcprelay"]["config"];
         $dhcprelay_update=$rou["dhcprelay"]["update"];

         $vrdat_update=$rou["vrdat"]["update"];
         $vrdat_syncro=$rou["vrdat"]["syncro"];

         $ipfw_update=$rou["ipfw"]["update"];
         $ipfw_syncro=$rou["ipfw"]["syncro"];

         if ($dhcprelay_config == "1" ) {
          echo message_addlog($func_role, "[dhcpdelay_config]");
          dhcprelay_config($router, $ch, $connect_url, $connect_api);
         }

         if ($dhcprelay_update == "1" ) {
          echo message_addlog($func_role, "[dhcpdelay_update]");
          dhcprelay_update($router, $ch, $connect_url, $connect_api);
         }


         if ($vrdat_update == "1" ) {
          echo message_addlog($func_role, "[vrdat_update]");
          vrdat_update($router, $ch, $connect_url, $connect_api);
         }

         if ($vrdat_syncro == "1" or $counter == 0) {
          echo message_addlog($func_role, "[vrdat_syncro]");
          vrdat_syncro($router, $ch, $connect_url, $connect_api);
         }


         if ($ipfw_update == "1" ) {
          echo message_addlog($func_role, "[ipfw_update]");
          ipfw_update($router, $ch, $connect_url, $connect_api);
         }

         if ($ipfw_syncro == "1" or $counter == 0 ) {
          echo message_addlog($func_role, "[ipfw_syncro]");
          ipfw_syncro($router, $ch, $connect_url, $connect_api);
         }


        } else {
         $last_url=curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
         echo system_addlog($func_role,"1","Error [".$httpcode."] ".$last_url);
        }

       }

      }

        ////////////////////////
       // CManager           //
      ////////////////////////

      if ( $cmanager != "none" ) {
       foreach (explode(".", $cmanager) as $id_comx_manager) {

        curl_setopt($ch, CURLOPT_URL, $connect_url."get_semaphores_cmanager");
        curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"id_comx_manager\":\"".$id_comx_manager."\",\"api\":\"".$connect_api."\"}");
        $return=curl_exec($ch);

        if(curl_errno($ch)) {
         echo  system_addlog($func_role,"1","Curl error: ". curl_error($ch) );
        } else {

         $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
         if ($httpcode == "200") {

          $cman = json_decode($return, true);
          $cmanager_ovsp_task=$cman["ovsp"]["task"];
          $cmanager_ovsp_check=$cman["ovsp"]["check"];
          $cmanager_comx_task=$cman["comx"]["task"];
          $cmanager_comx_check=$cman["comx"]["check"];

          if ( $cmanager_ovsp_task=="1" ) {
           echo message_addlog($func_role, "[cmanager_ovsp_task]");
           cmanager_ovsp_task($id_comx_manager, $ch, $connect_url, $connect_api);
          }

          if ( $cmanager_ovsp_check=="1" ) {
           echo message_addlog($func_role, "[cmanager_ovsp_check]");
           cmanager_ovsp_check($id_comx_manager, $ch, $connect_url, $connect_api);
          }

          cmanager_ovsp_task_result($id_comx_manager, $ch, $connect_url, $connect_api);

          if ( $cmanager_comx_task=="1" ) {
           echo message_addlog($func_role, "[cmanager_comx_task]");
           cmanager_comx_task($id_comx_manager, $ch, $connect_url, $connect_api);
          }

          if ( $cmanager_comx_check=="1" ) {
           echo message_addlog($func_role, "[cmanager_comx_check]");
           cmanager_comx_check($id_comx_manager, $ch, $connect_url, $connect_api);
          }

          cmanager_comx_check($id_comx_manager, $ch, $connect_url, $connect_api);

         }
        }
       }
      }

        ////////////////////////
       //                    //
      ////////////////////////


        ////////////////////////
       // End of tasks       //
      ////////////////////////




     } // end task process

    } else {
     $last_url=curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
     echo system_addlog($func_role,"1","Error [".$httpcode."] ".$last_url);
    }

   }

   sleep ($current_notify_interval);

   $counter=$counter+$current_notify_interval;


   } // End cycle

  curl_close ($ch);
  echo message_addlog ($func_role, "Close process UUID=".$uuid);

 }

  // e_work -----------------------------------------

 } else {
  echo message_addlog($func_role, "PID was not found.");
 }

}

task_manager($argv[1]);

?>