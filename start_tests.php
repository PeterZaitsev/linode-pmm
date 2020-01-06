<?php

/* Deploy  Tests.  This file requires PMM Server Running */

require 'linode.php';
require 'set_variables.php';

if(!$pmm_server_addr)
  die("PMM Server Address Required \n");


$tests=array(
    "ps8test" => array("instances"=>10,"type"=>"g6-nanode-1","stackscript_id"=>"565985","params"=>array())
);


/* In this script we just deploy all tests we do not wait for initialization to complete */



foreach($tests as $testname => $test)
{
 for($i=1; $i<=$test["instances"]; $i++)
 {
  $name=$testname.$i;
  $params=$test['params'];
  $params['pmmserver']=$pmm_server_addr;
  $params['pmmpassword']=$pmm_password;
  $r=provision_linode($name,$test['type'],$test['stackscript_id'],$params);
  $ip=$r[0]["ipv4"][0];
  echo("Starting $name  as $ip\n");
 } 
}


?>