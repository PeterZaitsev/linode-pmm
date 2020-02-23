<?php

/* We do not want to keep password information in repository so we initialize them from environment variables */

/* Use Prefix for Instance Naming to allow multiple deployments */
$prefix=getenv("PMM_PREFIX") or die("Set PMM_PREFIX Environment Variable\n");

echo("Using Prefix $prefix for deployment\n");

$root_password=getenv("LINODE_ROOT_PASSWORD") or die("Set LINODE_ROOT_PASSWORD Environment Variable\n");
$pmm_password=getenv("PMM_ADMIN_PASSWORD") or die("Set PMM_ADMIN_PASSWORD Environment Variable\n");
$pmm_server_addr=getenv("PMM_SERVER");   /* This variable is not always needed */
if ($pmm_server_addr)
  echo("Configured PMM Server:  $pmm_server_addr\n");


?>
