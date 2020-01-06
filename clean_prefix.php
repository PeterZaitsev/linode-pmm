<?php

/* If we deploy many linodes with same prefix for a test it is a pain to clean it manually
   This script destroys all linodes for given prefix except PMMServer which can be removed manually*/


require 'linode.php';
require 'set_variables.php';

$remove_pmm_server=false;

clean_prefix($prefix,$remove_pmm_server);

?>
