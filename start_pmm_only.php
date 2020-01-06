<?php

/* This script deploys PMM2   test environment on Linode  */

require 'linode.php';
require 'set_variables.php';

$pmm_image="percona/pmm-server:2";   /*PMM Server Image to Deploy */
$pmm_server_type="g6-standard-2";
#$pmm_server_type="g6-nanode-1";



$pmm_ip=deploy_pmm($pmm_image,$pmm_password,$pmm_server_type);
if (!$pmm_ip)
  die('Failed to deploy PMM Server');
echo("run: \n  export PMM_SERVER=\"$pmm_ip\"\n");

?>