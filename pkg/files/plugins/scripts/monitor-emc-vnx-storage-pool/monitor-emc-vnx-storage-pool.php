<?php

$TIMESTAMP=date("Y-m-d H:i:s");
$OPTIONS = getopt("m:");
$ARRAY=array();
$ARRAYPROPERTIES=array();
$POOLS=array();
$RGS=array();

if ($OPTIONS[m] == "test") {
    //for local testing against static XML
    $TESTSET = "PrimaryArray_VNX5300";
    $PFILENAME="$TESTSET.storagepool.xml";
    $RFILENAME="$TESTSET.rg.xml";
    $POUTPUT="vnxmonitor.$TESTSET.storagepool.OUT.xml";
    $ROUTPUT="vnxmonitor.$TESTSET.rg.OUT.xml";
    $XMLOUT="vnxmonitor.$TESTSET.OUT.xml";
    $FULLPCOMMAND="type $PFILENAME > $POUTPUT";
    $FULLRCOMMAND="type $RFILENAME > $ROUTPUT";
    }
else {
    //actually connect to VNX via the navisecli client and get the XML
    $STORAGE_PROC_HOSTNAME=getenv('UPTIME_STORAGE_PROC_HOSTNAME');
    $USERNAME=getenv('UPTIME_USERNAME');
    $PASSWORD=getenv('UPTIME_PASSWORD');
	$NAVIPATH=getenv('UPTIME_NAVIPATH');

    $NAVIPATH = make_sure_path_has_double_quotes($NAVIPATH);

    $POUTPUT="vnxmonitor.$STORAGE_PROC_HOSTNAME.storagepool.OUT.xml";
    $XMLOUT="vnxmonitor.$STORAGE_PROC_HOSTNAME.OUT.xml";
    $PCOMMAND="$NAVIPATH -User $USERNAME -Password $PASSWORD -Scope 0 -h $STORAGE_PROC_HOSTNAME -XML storagepool -list";
    $FULLPCOMMAND="$PCOMMAND > $POUTPUT";
    }  

if (file_exists($POUTPUT)) {
    shell_exec("del $POUTPUT");}

    // Read through the XML and get details about the various storage pools
    shell_exec($FULLPCOMMAND);
    if (empty($POUTPUT)) {
        print "Error obtaining XML file. /n";}
        
    $PXML=simplexml_load_file($POUTPUT);
    $PPARAMS=$PXML->MESSAGE->SIMPLERSP->METHODRESPONSE->PARAMVALUE;
    $POOL=array();
    foreach($PPARAMS as $NODE) { 
        $ATTRIBUTE=$NODE->attributes()->NAME;
        $VALUE = $NODE->VALUE;
        if ($ATTRIBUTE=="Pool ID") {
            $POOLID=(float)$VALUE;
            $POOL["Storage_Pool_ID"] = (string)$POOLID;
            $TPOOLS=$TPOOLS+1;
        } elseif ($ATTRIBUTE=="Raw Capacity (GBs)") {
            $PRAWCAP_G=$VALUE;
            $POOL["Raw_Capacity_GBs"] = (string)$PRAWCAP_G;
        } elseif ($ATTRIBUTE=="User Capacity (GBs)") {
            $PUSRCAP_G=$VALUE;
            $POOL["User_Capacity_GBs"] = (string)$PUSRCAP_G;
        } elseif ($ATTRIBUTE=="Available Capacity (GBs)") {
            $PAVAILCAP_G=$VALUE;
            $POOL["Available_Capacity_GBs"] = (string)$PAVAILCAP_G;
        } elseif ($ATTRIBUTE=="Percent Full") {
            $PPFULL=$VALUE;
            $POOL["Percent_Full"] = (string)$PPFULL;
        } elseif ($ATTRIBUTE=="Total Subscribed Capacity (GBs)") {
            $PSUBCAP_G=$VALUE;
            $POOL["Total_Subscribed_Capacity_GBs"] = (string)$PSUBCAP_G;
        } elseif ($ATTRIBUTE=="Raw Capacity (Blocks)") {
            $PRAWCAP=(float)$VALUE;
            $POOL["Raw_Capacity_Blocks"] = (string)$PRAWCAP;
        } elseif ($ATTRIBUTE=="User Capacity (Blocks)") {
            $PUSRCAP=(float)$VALUE;
            $POOL["User_Capacity_Blocks"] = (string)$PUSRCAP;
        } elseif ($ATTRIBUTE=="Available Capacity (Blocks)") {
            $PAVAILCAP=(float)$VALUE;
            $POOL["Available_Capacity_Blocks"] = (string)$PAVAILCAP;
        } elseif ($ATTRIBUTE=="Total Subscribed Capacity (Blocks)") {
            $PSUBCAP=(float)$VALUE;
            $POOL["Total_Subscribed_Capacity_Blocks"] = (string)$PSUBCAP;
        } elseif ($ATTRIBUTE=="LUNs") {
            //array_push($POOLS, $POOL);
            $POOLS["Resource_Pool_$POOLID"] = $POOL;
            unset($POOL);
            $CURPOOL=$CURPOOL+1;
            if ($CURPOOL = ($LASTPOOL + 1)){
                $TPRAWCAP_G=$TPRAWCAP_G+$PRAWCAP_G;
                $TPUSRCAP_G=$TPUSRCAP_G+$PUSRCAP_G;
                $TPAVAILCAP_G=$TPAVAILCAP_G+$PAVAILCAP_G;
                $TPSUBCAP_G=$TPSUBCAP_G+$PSUBCAP_G;
                $TPPFULL=$TPPFULL+$PPFULL;
                $TPRAWCAP=$TPRAWCAP+$PRAWCAP;
                $TPUSRCAP=$TPUSRCAP+$PUSRCAP;
                $TPAVAILCAP=$TPAVAILCAP+$PAVAILCAP;
                $TPSUBCAP=$TPSUBCAP+$PSUBCAP;
                $LASTPOOL=$LASTPOOL+1;
            }
        }
    }


    function make_sure_path_has_double_quotes($path)
    {
        //we really only need to double quote the path on windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            if (preg_match('/^(["\']).*\1$/m', $path))
            {
                return $path;
            }
            else
            {
                return '"' . $path . '"';
            }
        }
        else
        {
            return $path;
        }
    }

    
    // Output the metrics for each storage pool as ranged data.
    // ie.
    // Raw_Capacity_GBs.Storage_Pool_ID VALUE
    // User_Capacity_GBs.Storage_Pool_ID VALUE
    // etc
    foreach($POOLS as $cur_pool) {
        $pool_id = $cur_pool['Storage_Pool_ID'];
        foreach ($cur_pool as $k => $v) {
            if ($k != "Storage_Pool_ID") {
                echo $pool_id . "." . $k . " " . $v . "\n";
            }
        }

    }

?>