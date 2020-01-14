<?php

/* This script deploys PMM2   test environment on Linode  */

require 'linode.php';
require 'set_variables.php';

$pmm_image="perconalab/pmm-server:dev-latest";   /*PMM Server - Latest Dev  */
#$pmm_image="percona/pmm-server:2";   /*PMM Server - Latest GA */
$pmm_server_type="g6-standard-4";
#$pmm_server_type="g6-nanode-1";



$pmm_ip=deploy_pmm($pmm_image,$pmm_password,$pmm_server_type);
if (!$pmm_ip)
  die('Failed to deploy PMM Server');
echo("run: \n  export PMM_SERVER=\"$pmm_ip\"\n");

?>
