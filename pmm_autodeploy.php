<?php

/* This script automatically deploys new PMM Server when new Tag appers at DockerHub 
   Currently it is WIP - it only detects latest version once and exits 
*/


require 'linode.php';
require 'set_variables.php';

$prefix="at";   /* We can override prefix from environment variable if wanted */

$pmm_server_name="PMM2Server";
$pmm_server_type="g6-standard-2";



while (true) 
{
  # First "clean the prefix" removing everything
  clean_prefix($prefix,true);
  # Get the latest PMM tag 
#  $t=get_pmm_latest_tag();
  $t="dev-latest";  /* Use this tag instead */
  # Deploy PMM Server with this Tag
  $tag="perconalab/pmm-server:$t";
  echo("Deploying PMM Server with Tag $t Instance Type: $pmm_server_type\n");
  $pmm_ip=deploy_pmm($tag,$pmm_password,$pmm_server_type);
  if(!$pmm_ip)
    die("Failed to deploy PMM Server\n"); 
  deploy_mysql_test_environment($pmm_ip);
  exit;

} 



?>
