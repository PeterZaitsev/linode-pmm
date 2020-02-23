<?php

/* This script deploys PMM2   test environment on Linode  */

require 'linode.php';
require 'set_variables.php';

/* Allow setting additional parameters for variables; Use Defaults if they are not set */


$pmm_image=getenv("PMM_IMAGE") or $pmm_image="perconalab/pmm-server:dev-latest";


/* Typical  PMM Images 

    perconalab/pmm-server:dev-latest   -   PMM Server - Latest Dev 
    percona/pmm-server:2               - PMM Server - Latest GA *
    aleksi/pmm-vm                      - Victoria Metrics Test  Image by Aleksi 

*/

$pmm_server_type=getenv("PMM_SERVER_TYPE") or $pmm_server_type="g6-nanode-1";

/* Typical PMM Server Types 

   g6-nanode-1            - 1 VCPU 1GB MEM  25GB Disk
   g6-standard-1          - 1 VCPU 2GB MEM  50GB Disk 

Run:  "linode-cli linodes types"  for full list

*/

echo("Starting PMM Server - Image $pmm_image   Server Type:  $pmm_server_type\n");


$pmm_ip=deploy_pmm($pmm_image,$pmm_password,$pmm_server_type);
if (!$pmm_ip)
  die("Failed to deploy PMM Server\n");
echo("run: \n  export PMM_SERVER=\"$pmm_ip\"\n");

?>
