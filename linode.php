<?php

/* Functions to work with Linode from PHP */



function provision_linode($hostname,$type,$stackscript_id,$params)
{
    global $root_password;
    global $prefix;
    $label=$prefix.$hostname; /*Label Must be unique so pre-append host name with Prefix */
    $params["hostname"]=$hostname; 
    $params_json=json_encode($params);
#    echo("PARAMS: $params_json\n");
    $command="linode-cli --json linodes create --type $type --label $label --root_pass $root_password --stackscript_id $stackscript_id  --stackscript_data '$params_json'";
    exec($command,$output,$return_val);
    if ($return_val!=0)
	return FALSE;
    $ret=json_decode($output[0],TRUE);
#    var_dump($ret);
    return $ret;

}

function list_linodes()
{
  $command="linode-cli --json linodes list";
  exec($command,$output,$return_val);
  if ($return_val!=0)
    return FALSE;
  $ret=json_decode($output[0],TRUE);
#    var_dump($ret);
  return $ret;

}

function  delete_linode($id)
{
  $command="linode-cli --json linodes delete $id";
  exec($command,$output,$return_val);
  if ($return_val!=0)
    return FALSE;
  return TRUE;

}


function wait_linode($linode,$port,$timeout)
{
  /* Take the object returned by provision_linode and wait for it to complete boot */
  $ip=$linode[0]["ipv4"][0];
#  echo("IP: $ip\n");

  $start_time=time();
  do {
    $fp = @fsockopen($ip, $port, $errno, $errstr, 5);
    if ($fp)
    {
      fclose($fp);
      return TRUE;
    } 
    /* Avoid too often attempts if connection is refused promptly */
    sleep(1);
  }
  while (time()-$start_time<$timeout);
  /* Did not become available until timeout */
  return FALSE;
}

function wait_pmm_linode($linode,$timeout)
{
/* We use special wait for PMM to become available through the HTTP API not just port rediness */
$ip=$linode[0]["ipv4"][0];
#echo("IP: $ip\n");

$start_time=time();
do {
  $ch=curl_init("https://$ip/v1/readyz");
  curl_setopt($ch, CURLOPT_FAILONERROR, true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
  curl_setopt($ch, CURLOPT_TIMEOUT, 10);
  $res=curl_exec($ch);
  curl_close($ch);
  $r=json_decode($res,TRUE);
  if (!is_null($r)) {
    return TRUE;
    }
  /* Avoid too often attempts if connection is refused promptly */
  sleep(1);
  }
  while (time()-$start_time<$timeout);
  /* Did not become available until timeout */
return FALSE;

}

/* Remove all nodes with given prefix; optionally remove PMM server too */
function clean_prefix($prefix,$remove_pmm_server)
{
  $pmm_server_name="PMM2Server";
  /* Safety - avoid removing all nodes if prefix not specified */
  if (strlen($prefix)<1)
   return false;
 
  $linodes=list_linodes();

  foreach($linodes as $l)
  {
    $label=$l['label'];
    $id=$l['id'];
    if( substr($label, 0, strlen($prefix)) === $prefix)
    {
    /* Match Prefix for Removal */     
      if(!$remove_pmm_server) /* Do not remove PMM */
        if ($label==$prefix.$pmm_server_name)
        {
          echo("Skipping PMM Server $label  with ID $id\n");
          continue;
        }      
    echo("Removing $label with ID $id \n");
   delete_linode($id);
    }
  } 
   return true;
}

/* Get tags of PMM Server from Docker Hub */
function get_pmm_tags()
{
  $ch=curl_init("https://registry.hub.docker.com/v1/repositories/perconalab/pmm-server/tags");
  curl_setopt($ch, CURLOPT_FAILONERROR, true);
#  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
#  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
  curl_setopt($ch, CURLOPT_TIMEOUT, 30);
  $res=curl_exec($ch);
  if (!$res)
    return false;
  curl_close($ch);
  $ret=json_decode($res,TRUE);
  return $ret;
} 

/* Get the latest numeric pmm tag */
function get_pmm_latest_tag()
{
 $ret=get_pmm_tags();
 if ($ret)
 {
   $names=array_column($ret,'name');
   arsort($names);
   foreach($names as $n) 
   {
    if (is_numeric($n))
      return $n;
   }
 }
 return false;
}

/* Deploy PMM Server */
function deploy_pmm($pmm_image,$pmm_password,$pmm_server_type)
{
  $params=array();
  $params["pmmimage"]=$pmm_image;
  $params["pmmpassword"]=$pmm_password;
  $pmm=provision_linode("PMM2Server",$pmm_server_type,625153,$params);
  if (!$pmm)
    return false;
  $r=wait_pmm_linode($pmm,1800);
  if (!$r)
    return false;
  $pmm_ip=$pmm[0]["ipv4"][0];
  echo("Deployed PMM Server: $pmm_ip\n");
  return $pmm_ip;
}


/* Deploy "standard" MySQL test environment with 4 DB nodes and 3 App nodes */
function deploy_mysql_test_environment($pmm_ip)
{
  global $pmm_password;

  $db_ips=array();
  for($i=1; $i<=4; $i++)
  {
    $params=array();
    $params["pmmserver"]=$pmm_ip;
    $params["pmmpassword"]=$pmm_password;
    $dbs[$i]=provision_linode("mysql$i","g6-nanode-1",426435,$params);
  }

  /* Wait for all DB Nodes to become available */
  for($i=1; $i<=4; $i++)
  {
    $r=wait_linode($dbs[$i],3306,1800);
    if(!$r)
      die("Failed to deploy DB Node mysql$i\n");
    $ip=$dbs[$i][0]["ipv4"][0];
    $dbs_ips[$i]=$ip;
    echo("Provisioned MySQL DB Instance $i on $ip\n");
  }

  $sleep=900;
  echo("Sleeping $sleep seconds for database preparation to be completed\n");
  sleep($sleep);

  /* Deploy Client Nodes */
  $clients=array();
  $params=array();
  $params["pmmserver"]=$pmm_ip;
  $params["pmmpassword"]=$pmm_password;
  for($j=1; $j<=4;$j++)
  {
    $params["db$j"]=$dbs_ips[$j];
  }


  /* Provision Clients */ 
  for($i=1; $i<=3; $i++)
  {
    $clients[$i]=provision_linode("client$i","g6-nanode-1",427130,$params);
  }

  /* Wait for them to become available */
  for($i=1; $i<=3; $i++)
  {
    $r=wait_linode($clients[$i],22,1800);
    if(!$r)
      die("Failed to deploy Client Node  client$i\n");
    $ip=$clients[$i][0]["ipv4"][0];
    echo("Provisioned Client Instance $i on $ip\n");
  } 
  return true;

}


/* Deploy "standard" Mixed test environment with MySQL and PostgreSQL Nodes */
function deploy_mixed_test_environment($pmm_ip)
{
  global $pmm_password;

  $mysql_ips=array();
  for($i=1; $i<=4; $i++)
  {
    $params=array();
    $params["pmmserver"]=$pmm_ip;
    $params["pmmpassword"]=$pmm_password;
    $mysqls[$i]=provision_linode("mysql$i","g6-nanode-1",426435,$params);
  }

  $pg_ips=array();
  for($i=1; $i<=4; $i++)
  {
    $params=array();
    $params["pmmserver"]=$pmm_ip;
    $params["pmmpassword"]=$pmm_password;
    $params["pgver"]="14";
    $params["extn"]="pg_stat_monitor";
    $pgs[$i]=provision_linode("pg$i","g6-nanode-1",949673,$params);
  }



  /* Wait for all MySQL  Nodes to become available */
  for($i=1; $i<=4; $i++)
  {
    $r=wait_linode($mysqls[$i],3306,1800);
    if(!$r)
      die("Failed to deploy DB Node mysql$i\n");
    $ip=$mysqls[$i][0]["ipv4"][0];
    $mysql_ips[$i]=$ip;
    echo("Provisioned MySQL DB Instance $i on $ip\n");
  }

  /* Wait for all PostgreSQL  Nodes to become available */
  for($i=1; $i<=4; $i++)
  {
    $r=wait_linode($pgs[$i],5432,1800);   /* 5432 - PostgreSQL TCP Port */
    if(!$r)
      die("Failed to deploy DB Node mysql$i\n");
    $ip=$pgs[$i][0]["ipv4"][0];
    $pg_ips[$i]=$ip;
    echo("Provisioned PostgreSQL DB Instance $i on $ip\n");
  }



  $sleep=900;
  echo("Sleeping $sleep seconds for database preparation to be completed\n");
  sleep($sleep);

  /* Deploy Client Nodes */
  $clients=array();
  $params=array();
  $params["pmmserver"]=$pmm_ip;
  $params["pmmpassword"]=$pmm_password;
  for($j=1; $j<=4;$j++)
  {
    $params["mysql$j"]=$mysql_ips[$j];
  }
  for($j=1; $j<=4;$j++)
  {
    $params["pg$j"]=$pg_ips[$j];
  }


  /* Provision Clients */ 
  for($i=1; $i<=3; $i++)
  {
    $clients[$i]=provision_linode("client$i","g6-nanode-1",950149,$params);
  }

  /* Wait for them to become available */
  for($i=1; $i<=3; $i++)
  {
    $r=wait_linode($clients[$i],22,1800);
    if(!$r)
      die("Failed to deploy Client Node  client$i\n");
    $ip=$clients[$i][0]["ipv4"][0];
    echo("Provisioned Client Instance $i on $ip\n");
  } 
  return true;

}






?>
