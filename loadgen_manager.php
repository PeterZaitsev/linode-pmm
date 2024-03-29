<?php

/* This is Driver for PMM_MySQL_LoadGen script which can deploy variety of its options 
   It requires running PMM2 Server */

require 'linode.php';
require 'set_variables.php';

/* Script will deploy all permutations so limit multiple settings to 1-2 options only */

#$types=array("g6-standard-2"=>1);
$types=array("g6-nanode-1"=>1);
$mysqls=array("ps8"=>1,"ps57"=>1,"ps56"=>0);
$benchmarks=array("tpcc"=>0,"stq"=>1);
$tables=array("1"=>1);
$tpccscales=array("1"=>0,"10"=>0,"1000000"=>1);  /* Distinct queries */
$threads=array("1"=>0,"10"=>1,"10000"=>0);       /* Schemas */
$rates=array("1000"=>1);                          /* Target QPS */
$querysources=array("slowlog"=>1,"perfschema"=>0);
  

$id=1;
foreach ($types as $tp=>$tp_cnt) 
 for ($tp_i=0;$tp_i<$tp_cnt;$tp_i++) 
  foreach ($mysqls as $m=>$m_cnt) 
   for($m_i=0;$m_i<$m_cnt;$m_i++) 
    foreach($benchmarks as $b=>$b_cnt)
     for($b_i=0;$b_i<$b_cnt;$b_i++)
      foreach($tables as $tbl=>$tbl_cnt)
       for($tbl_i=0;$tbl_i<$tbl_cnt;$tbl_i++)
        foreach($tpccscales as $sc=>$sc_cnt)
         for($sc_i=0;$sc_i<$sc_cnt;$sc_i++)
          foreach($threads as $th=>$th_cnt)
           for($th_i=0;$th_i<$th_cnt;$th_i++)
            foreach($rates as $rt=>$rt_cnt)
             for($rt_i=0;$rt_i<$rt_cnt;$rt_i++)
              foreach($querysources as $qs=>$qs_cnt)
               for($qs_i=0;$qs_i<$qs_cnt;$qs_i++)
               {             
                $name="$m-$id-$tp-$b-tb$tbl-s$sc-th$th-r$rt-$qs";
                #shorten long linode type names
                $name=str_replace(array('g6-standard','g6-nanode'),array('s','n'),$name);
                $name=substr($name,0,30-strlen($prefix)).'x';  /*32 length limit at linode plus end with letter*/                
                $params=array();
                $params['pmmserver']=$pmm_server_addr;
                $params['pmmpassword']=$pmm_password;
                $params['mysql']=$m;
                $params['benchmark']=$b;
                $params['tables']=$tbl;
                $params['tpccscale']=$sc;
                $params['threads']=$th;
		$params['rate']=$rt;
                $params['querysource']=$qs;
                $r=provision_linode($name,$tp,572208,$params);
                $ip=$r[0]["ipv4"][0];
                echo("Starting $name  as $ip\n");                
                $id++;              
               }

?>