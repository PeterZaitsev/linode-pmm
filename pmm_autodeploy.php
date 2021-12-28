<?php

/* This script automatically deploys new PMM Server when new Tag appers at DockerHub 
   Currently it is WIP - it only detects latest version once and exits 
*/


require 'linode.php';
require 'set_variables.php';

$pmm_server_name="PMM2Server";
$pmm_server_type="g6-standard-2";



while (true) 
{
  # First "clean the prefix" removing everything
  clean_prefix($prefix,true);
  # Get the latest PMM tag 
#  $t=get_pmm_latest_tag();
  # Deploy PMM Server with this Tag
#  $tag="perconalab/pmm-server:$t";
  $tag="percona/pmm-server:2";
#  $tag="perconalab/pmm-server-fb:PR-863-f8d2456";
  echo("Deploying PMM Server  $tag Instance Type: $pmm_server_type\n");
  $pmm_ip=deploy_pmm($tag,$pmm_password,$pmm_server_type);
  if(!$pmm_ip)
    die("Failed to deploy PMM Server\n"); 
  deploy_mysql_test_environment($pmm_ip);
  exit;

} 



?>
