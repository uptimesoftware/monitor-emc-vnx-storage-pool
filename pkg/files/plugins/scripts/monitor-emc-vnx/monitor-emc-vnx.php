<?php

$TIMESTAMP=date("Y-m-d H:i:s");
$OPTIONS = getopt("m:");
$ARRAY=array();
$ARRAYPROPERTIES=array();
$POOLS=array();
$RGS=array();

if ($OPTIONS[m] == "test") {
    $TESTSET = PrimaryArray_VNX5300;
    $PFILENAME="$TESTSET.storagepool.xml";
    $RFILENAME="$TESTSET.rg.xml";
    $POUTPUT="vnxmonitor.$TESTSET.storagepool.OUT.xml";
    $ROUTPUT="vnxmonitor.$TESTSET.rg.OUT.xml";
    $XMLOUT="vnxmonitor.$TESTSET.OUT.xml";
    $FULLPCOMMAND="type $PFILENAME > $POUTPUT";
    $FULLRCOMMAND="type $RFILENAME > $ROUTPUT";
    }
else {
    $STORAGE_PROC_HOSTNAME=getenv('UPTIME_STORAGE_PROC_HOSTNAME');
    $USERNAME=getenv('UPTIME_USERNAME');
    $PASSWORD=getenv('UPTIME_PASSWORD');
	$NAVIPATH=getenv('UPTIME_NAVIPATH');
    $POUTPUT="vnxmonitor.$STORAGE_PROC_HOSTNAME.storagepool.OUT.xml";
    $ROUTPUT="vnxmonitor.$STORAGE_PROC_HOSTNAME.rg.OUT.xml";
    $RTESTOUT="vnxmonitor.$STORAGE_PROC_HOSTNAME.rg.TESTOUT.xml";
    $XMLOUT="vnxmonitor.$STORAGE_PROC_HOSTNAME.OUT.xml";
    //$NAVIPATH=escapeshellarg('C:\Program Files (x86)\EMC\Navisphere CLI\naviseccli.exe');
    $PCOMMAND="$NAVIPATH -User $USERNAME -Password $PASSWORD -Scope 0 -h $STORAGE_PROC_HOSTNAME -XML storagepool -list";
    $RCOMMAND="$NAVIPATH -User $USERNAME -Password $PASSWORD -Scope 0 -h $STORAGE_PROC_HOSTNAME -XML getrg";
    $FULLPCOMMAND="$PCOMMAND > $POUTPUT";
    $FULLRCOMMAND="$RCOMMAND > $ROUTPUT";
    }  

if (file_exists($POUTPUT)) {
    shell_exec("del $POUTPUT");}
if (file_exists($ROUTPUT)) {
    shell_exec("del $ROUTPUT");}

// Get data for storage pools

//try {    
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

    $POOLS["Total_Raw_Capacity_GBs"] = $TPRAWCAP_G;
    $POOLS["Total_User_Capacity_GBs"] = $TPUSRCAP_G;
    $POOLS["Total_Available_Capacity_GBs"] = $TPAVAILCAP_G;
    $POOLS["Total_Raw_Capacity_Blocks"] = (string)$TPRAWCAP;
    $POOLS["Total_User_Capacity_Blocks"] = (string)$TPUSRCAP;
    $POOLS["Total_Available_Capacity_Blocks"] = (string)$TPAVAILCAP;
    
    $ARRAY["Resource_Pools"] = $POOLS;
    
    // Get data for Raid Groups
    
    shell_exec($FULLRCOMMAND);
    if (empty($ROUTPUT)) {
        print "Error obtaining XML file. /n";}
    
    $RXML = simplexml_load_file($ROUTPUT);
    $RPARAMS = $RXML->MESSAGE->SIMPLERSP->METHODRESPONSE->PARAMVALUE->VALUE[0];
    $RG=array();
    foreach($RPARAMS as $NODE) { 
        $ATTRIBUTE=$NODE->attributes()->NAME;
        $VALUE=$NODE->VALUE;
        if ($ATTRIBUTE=="RaidGroup ID") {
            $RGID=(float)$VALUE;
            $RG["RaidGroup_ID"] = (string)$RGID;
            $TRGS=$TRGS+1;
        } elseif ($ATTRIBUTE=="RaidGroup Type") {
            $RGTYPE=(string)$VALUE;
            $RG["RaidGroup_Type"] = (string)$RGTYPE;
            if ($RGTYPE=="hot_spare") {
                $BADRG=TRUE;
            } elseif ($RGTYPE!=="hot_spare") {
                $BADRG=FALSE;
            }
        } elseif ($ATTRIBUTE=="Raw Capacity (Blocks)") {
            $RRAWCAP=(float)$VALUE;
            $RG["Raw_Capacity_Blocks"] = (string)$RRAWCAP;
            $RRAWCAP_G=blocks2GBs($RRAWCAP);
            $RG["Raw_Capacity_GBs"] = (string)$RRAWCAP_G;
        } elseif ($ATTRIBUTE=="Logical Capacity (Blocks)") {
            $RLOGCAP=(float)$VALUE;
            $RG["Logical_Capacity_Blocks"] = (string)$RLOGCAP;
            $RLOGCAP_G=blocks2GBs($RLOGCAP);
            $RG["Logical_Capacity_GBs"] = (string)$RLOGCAP_G;
        } elseif ($ATTRIBUTE=="Free Capacity (Blocks,non-contiguous)") {
            $RFREECAP=(float)$VALUE;
            $RG["Free_Capacity_Blocks_non-contiguous"] = (string)$RFREECAP;
            $RFREECAP_G=blocks2GBs($RFREECAP);
            $RG["Free_Capacity_GBs"] = (string)$RFREECAP_G;
        } elseif ($ATTRIBUTE=="Legal RAID types") {
            $RGS["Raid_Group_$RGID"] = $RG;
            unset($RG);
            $CURRG=$CURRG+1;
            if ($CURRG = ($LASTRG + 1) AND ($BADRG != TRUE)) {
                $RTRAWCAP=$RTRAWCAP+$RRAWCAP;
                $RTRAWCAP_G=$RTRAWCAP_G+$RRAWCAP_G;
                $RTLOGCAP=$RTLOGCAP+$RLOGCAP;
                $RTLOGCAP_G=$RTLOGCAP_G+$RLOGCAP_G;
                $RTFREECAP=$RTFREECAP+$RFREECAP;
                $RTFREECAP_G=$RTFREECAP_G+$RFREECAP_G;
                $LASTRG=$LASTRG+1;
            }
        }
    }
    
    $RGS["Total_Raw_Capacity_Blocks"] = (string)$RTRAWCAP;
    $RGS["Total_Raw_Capacity_GBs"] = (string)$RTRAWCAP_G;
    $RGS["Total_Logical_Capacity_Blocks"] = (string)$RTLOGCAP;
    $RGS["Total_Logical_Capacity_GBs"] = (string)$RTLOGCAP_G;
    $RGS["Total_Free_Capacity_Blocks"] = (string)$RTFREECAP;
    $RGS["Total_Free_Capacity_GBs"] = (string)$RTFREECAP_G;
    
    $ARRAY["Raid_Groups"] = $RGS;

    // Get/set total capacity figures  
    $TRAWCAP = $TPRAWCAP + $RTRAWCAP;
    $TRAWCAP_G = $TPRAWCAP_G + $RTRAWCAP_G;
    $TFREECAP = $TPAVAILCAP + $RTFREECAP;
    $TFREECAP_G = $TPAVAILCAP_G + $RTFREECAP_G;
    
    $ARRAY["Total_Raw_Capacity_Blocks"] = (string)$TRAWCAP;
    $ARRAY["Total_Raw_Capacity_GBs"] = (string)$TRAWCAP_G;
    $ARRAY["Total_Free_Capacity_Blocks"] = (string)$TFREECAP;
    $ARRAY["Total_Free_Capacity_GBs"] = (string)$TFREECAP_G;
    $ARRAY["Total_Number_of_Storage_Pools"] = (string)$TPOOLS;
    $ARRAY["Total_Number_of_Raid_Groups"] = (string)$TRGS;
    $ARRAY["Date_Stamp"] = (string)$TIMESTAMP;
    
    //print_r ($ARRAY);
    
    // Build array and XML from results (for up.time gadget)
    
    assocArrayToXML('root',$ARRAY,$XMLOUT);
    
    function assocArrayToXML($root_element_name,$ar,$file) 
    { 
        $xml = new SimpleXMLElement("<?xml version=\"1.0\"?><{$root_element_name}></{$root_element_name}>"); 
        $f = create_function('$f,$c,$a',' 
                foreach($a as $k=>$v) { 
                    if(is_array($v)) { 
                        $ch=$c->addChild($k); 
                        $f($f,$ch,$v); 
                    } else { 
                        $c->addChild($k,$v); 
                    } 
                }'); 
        $f($f,$xml,$ar);
        return $xml->asXML($file);    
    }           

    //Function to convert block sizes into GB
    function blocks2GBs($blocks) {
        $gb = ($blocks * 512) / 1024 / 1024 / 1024 ;
        return round(($gb),2);
    }
    
    // Output all variable for up.time
    
    //print "\n";
    //print "TPOOLS $TPOOLS";
    //print "\n";
    print "TPRAWCAP_G $TPRAWCAP_G \n";
    //print "TPUSRCAP_G $TPUSRCAP_G \n";
    print "TPAVAILCAP_G $TPAVAILCAP_G \n";
    //print "TPSUBCAP_G $TPSUBCAP_G \n";
    //print "TPPFULL $TPPFULL \n";
    //print "TPRAWCAP $TPRAWCAP \n";
    //print "TPUSRCAP $TPUSRCAP \n";
    //print "TPAVAILCAP $TPAVAILCAP \n";
    //print "TPSUBCAP $TPSUBCAP \n";
    //print "\n";
    //print "TRGS $TRGS\n";
    //print "RTRAWCAP $RTRAWCAP\n";
    //print "RTLOGCAP $RTLOGCAP\n";
    //print "RTFREECAP $RTFREECAP\n";
    print "RTRAWCAP_G $RTRAWCAP_G\n";
    //print "RTLOGCAP_G $RTLOGCAP_G\n";
    print "RTFREECAP_G $RTFREECAP_G\n";
    //print "\n";
    //print "TRAWCAP $TRAWCAP\n";
    //print "TFREECAP $TFREECAP\n";
    print "TRAWCAP_G $TRAWCAP_G\n";
    print "TFREECAP_G $TFREECAP_G\n";

//}
//catch (Exception $e) {
//    print "Caught exception '$e->getMessage()' \n";
//    }
?>