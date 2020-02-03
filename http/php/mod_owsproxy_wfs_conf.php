<?php
# $Id: mod_owsproxy_conf.php 8786 2014-02-28 12:57:18Z verenadiewald $
# http://www.mapbender.org/index.php/mod_owsproxy_wfs_conf.php
# Copyright (C) 2002 CCGIS 
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2, or (at your option)
# any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc.,  59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.

include(dirname(__FILE__)."/../php/mb_validateSession.php");
include(dirname(__FILE__)."/../classes/class_administration.php");
$admin = new administration();
$ownwfs = $admin->getWfsByWfsOwner($_SESSION["mb_user_id"]);
#need admin functions: getWmsLogTag, getWmsPrice, setWmsLogTag, setWmsPrice
#can set log only when proxy on, can set price only when log on
#read out the request 
 function array2str($array, $pre = '', $pad = '', $sep = ', ')  
 {  
     $str = '';  
     if(is_array($array)) {  
         if(count($array)) {  
             foreach($array as $v) {  
                 $str .= $pre.$v.$pad.$sep;  
             }  
             $str = substr($str, 0, -strlen($sep));  
         }  
     } else {  
         $str .= $pre.$array.$pad;  
     }  
   
     return $str;  
 }  

function validateint($inData) {
  $intRetVal = 0;

  $IntValue = intval($inData);
  $StrValue = strval($IntValue);
  if($StrValue == $inData) {
    $intRetVal = $IntValue;
  }

  return $intRetVal;
}




if(isset($_REQUEST["save"])){
#if(isset($_REQUEST["wms_id"]) && isset($_REQUEST["status"])){

	#$admin->setWMSOWSstring(intval($_REQUEST["wms_id"]),$_REQUEST["status"]);
#print_r($_POST);

#TODO deactivate proxy, logs and prices for all owned wms
#TODO
#$wms_list=array2str($ownwms);
#print $wms_list;



//$admin->unsetWmsProxy($wms_list);#TODO not delete owsproxy urls but update each entry! -> this would not delete log or usage of proxy!!!
#serialize ownwms - to list and do sql
#for the definitions in the form set the params
#sequentialy read out the post variables
    
if(isset($_POST["save"])) {

}


foreach ($_POST as $var => $value) {
	#select those which are relevant (hidden fields for proxy and log)
	#identify them
	$parts=explode("_",$var);
	#echo "All vars: <br>";
	#echo $var." = ".$value."<br>";
	$value=validateint($value);
	if ($parts[0]!= 'status' && $parts[2]=='price'){ #for the pricing in the textfield
		if($parts[0] == "wfs") {
            		$admin->setWfsPrice(intval($value),intval($parts[1]));
        	}
	}
	#check the hidden fields if some log should be set 
	if ($parts[2]=='log' && $parts[0]=='status'){
		#set the log value to 1 or 0
        if($parts[2]=='log'){
            $admin->setWfsLogTag(intval($parts[1]),$value);
        }
		#if value is 0 then set the price to 0 to. there would be is no sense to have a price set - maybe change this behavior
		if ($value==0) {
			if($parts[2]=='log'){
                		$admin->setWfsPrice(intval($value),intval($parts[1]));
            		}
		}
		#echo "log: $var = $value<br>";
	}
	#check proxy fields
	if ($parts[0]=='status' && $parts[2]=='proxy'){ 
		#echo ("proxy settings identified: WMS_ID: ".intval($parts[1])." Value: ".$value."<br>");	
		#echo ("Is active?: ");
		#if ($admin->getWMSOWSstring(intval($parts[1]))=="") {echo " no";} else {echo " yes";}
		#echo ("<br>");
		#check if proxy should be activated and is not set
		if ($value==1 && $admin->getWFSOWSstring(intval($parts[1])) == "") {
			#activate it!
			$admin->setWFSOWSstring(intval($parts[1]),$value);
			#echo "Activate Proxy for ".intval($parts[1])."<br>";
		}
		#check if active proxy should be deactivated
		if ($value==0 && $admin->getWFSOWSstring(intval($parts[1])) !== "") {
			#deactivate it
			$admin->setWFSOWSstring(intval($parts[1]),$value);
			#echo "Deactivate Proxy for ".intval($parts[1])."<br>";
		}
	}

}
	
}
?>
<html>
<head>
<?php
echo '<meta http-equiv="Content-Type" content="text/html; charset='.CHARSET.'">';	
?>
<title>OWS Security Proxy</title>
<style type="text/css">

body{
	font-family: Arial, Helvetica, sans-serif;	
}
</style>
<script language="JavaScript" type="text/javascript">

</script>
  
</head>
<body>

<table>
<?php
#$ownwmsconf['proxy']=array();
#$ownwmsconf['log']=array();
#$ownwmsconf['price']=array();
#TODO Get root layer id for showing metadata! - function should be in admin class


echo "<form  method=\"post\" action=\"".$_SERVER["SCRIPT_NAME"]."\">
    <i>Warning: Toggle proxy changes the url of the secured services!</i><br>
    <table border='1'>
        <tr valign = bottom>
            <td>WFS ID</td>
            <td>WFS Title</td>
            <td>Proxy</td>
            <td>GetFeature Log</td>
            <td>GetFeature Price(cent/feature)</td>
            <td>Show detailed Usage</td>
        </tr>";

for($i=0; $i<count($ownwfs); $i++){
	#read out current values in db
	if($admin->getWFSOWSstring($ownwfs[$i]) == false){ $status_proxy = 0 ;} else {$status_proxy = 1;};
	if($admin->getWfsLogTag($ownwfs[$i]) == 1){$status_log=1;} else {$status_log=0;};
	if ($admin->getWfsPrice($ownwfs[$i]) != 0 ){$status_price=$admin->getWfsPrice($ownwfs[$i]);} else {$status_price=0;};
	$auth = $admin->getAuthInfoOfWFS($ownwfs[$i]);
	if($auth['auth_type'] == ''){$status_auth = 0;} else {$status_auth = 1;};
	echo "<tr>";
	echo "<td>".$ownwfs[$i]."</td>";
	echo "<td";
	if($status_auth == 1){echo " bgcolor=\"#FF0000\"";};
	echo ">".$admin->getWfsTitleByWfsId($ownwfs[$i]);
	echo "<td>";
	#for owsproxy	
	echo "<input type='checkbox' id='wfs_".$ownwfs[$i]."_proxy' name='wfs_".$ownwfs[$i]."_proxy' 
        onclick='if(this.checked){
            document.getElementById(\"wfs_\"+".$ownwfs[$i]."+\"_log\").disabled=false;
            
            document.getElementById(\"wfs_\"+".$ownwfs[$i]."+\"_price\").disabled=true;
        
            document.getElementById(\"status_\"+".$ownwfs[$i]."+\"_proxy\").value=\"1\";
        }else{
            document.getElementById(\"wfs_\"+".$ownwfs[$i]."+\"_log\").checked=false;
            document.getElementById(\"wfs_\"+".$ownwfs[$i]."+\"_log\").disabled=true;
            
            document.getElementById(\"wfs_\"+".$ownwfs[$i]."+\"_price\").disabled=true;
            document.getElementById(\"wfs_\"+".$ownwfs[$i]."+\"_price\").value=\"0\";
            
            document.getElementById(\"status_\"+".$ownwfs[$i]."+\"_proxy\").value=\"0\";
            document.getElementById(\"status_\"+".$ownwfs[$i]."+\"_log\").value=\"0\";
            
        }'";
	
	
	#default
	if($status_proxy == 1){ echo " checked";  } else {echo " unchecked"; };  //if a proxy string is set
	if($status_auth == 1){ echo " disabled";};
	echo ">";
	#initialize hidden field for status proxy:

	echo "<input type=\"hidden\" name=\"status_".$ownwfs[$i]."_proxy\" id=\"status_".$ownwfs[$i]."_proxy\" value=".$status_proxy.">";

	echo "</td>";
	#for logging
	echo "<td><input type='checkbox' id='wfs_".$ownwfs[$i]."_log' name='wfs_".$ownwfs[$i]."_log' onclick='if(this.checked){document.getElementById(\"wfs_\"+".$ownwfs[$i]."+\"_price\").disabled=false;document.getElementById(\"status_\"+".$ownwfs[$i]."+\"_log\").value=\"1\"}else{document.getElementById(\"wfs_\"+".$ownwfs[$i]."+\"_price\").disabled=true;document.getElementById(\"wfs_\"+".$ownwfs[$i]."+\"_price\").value=\"0\";document.getElementById(\"status_\"+".$ownwfs[$i]."+\"_log\").value=\"0\";document.getElementById(\"status_\"+".$ownwfs[$i]."+\"_price\").value=\"0\"}'";

	
	#default
	if($status_proxy==0){ echo "disabled "; };
	if($status_log == 1){ echo " checked";  };//if a log tag is set -> to activate
	echo ">";
	#initialize hidden field for status log:
	
	echo "<input type=\"hidden\" name=\"status_".$ownwfs[$i]."_log\" id=\"status_".$ownwfs[$i]."_log\" value=".$status_log.">";

	echo "</td>";
	#for pricing
	echo "<td><input id='wfs_".$ownwfs[$i]."_price' name='wfs_".$ownwfs[$i]."_price' type='text' size='5' value='";
	echo $status_price;
	echo "' ";
	#default
	if($status_log != 1){ echo "disabled";  } else { echo "enabled";  };
	//if($admin->getWmsPrice($ownwms[$i]) != 0){ echo "disabled";  }
	echo ">";
	#initialize hidden field for status price:
	
	echo "<input type=\"hidden\" name=\"status_".$ownwfs[$i]."_price\" id=\"status_".$ownwfs[$i]."_price\" value=".$status_price.">";


	echo "</td>";
    
    
    
    
    
    
    
if($status_log == 1){
echo "<td><input type=button value='Show Usage' onclick=\"var newWindow = window.open('../php/mod_UsageShow.php?wfsid=".$ownwfs[$i]."','wfs','width=800,height=700,scrollbars');newWindow.href.location='Usage of Service: ".$wfs_id."'\"></td>";
}
echo "</tr>";

	
}
echo "</table>";
echo "<br>";
echo "<table><tr><td bgcolor=\"#FF0000\">Service with authentication information</td></tr></table>";


echo "<input type='submit' name='save' value='save'  ></form>";
?>
</table>
</form>
</body>
</html>
