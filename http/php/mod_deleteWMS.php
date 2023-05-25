<?php
# $Id: mod_deleteWMS.php 9547 2016-07-15 12:08:04Z armin11 $
# http://www.mapbender.org/index.php/DeleteWMS
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
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.


$e_id="deleteWMS";
require_once(dirname(__FILE__)."/../php/mb_validatePermission.php");
require_once dirname(__FILE__) . "/../classes/class_iso19139.php";
require_once(dirname(__FILE__) . "/../classes/class_propagateMetadata.php");
/*  
 * @security_patch irv done
 */ 
//security_patch_log(__FILE__,__LINE__);


$wmsList = $_POST["wmsList"];
$del = $_POST["del"];

require_once(dirname(__FILE__)."/../classes/class_administration.php");
require_once(dirname(__FILE__)."/../classes/class_georss_factory.php");


function getWmsMetadataUrl ($wmsId) {
	return LOGIN."/../../mapbender/php/mod_showMetadata.php?resource=wms&id=".$wmsId;
}


?>

<!DOCTYPE HTML>

<html>
<head>
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="pragma" content="no-cache">
<meta http-equiv="expires" content="0">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php
echo '<meta http-equiv="Content-Type" content="text/html; charset='.CHARSET.'">';	
?>
<title>deleteWMS</title>
<?php
include '../include/dyn_css.php';
?>
<link rel="stylesheet" href="../extensions/bootstrap-3.3.6-dist/css/bootstrap.min.css" type="text/css">
<style type="text/css">
.optionsbox {border: 1px solid #ccc;padding: 15px;border-radius: 4px;background-color: #efefef;margin-top: 30px;margin-bottom: 30px;}
.optionsbox-header {display: inline-block;max-width: 100%;margin-bottom: 5px;font-weight: 700;}
</style>
<script type="text/javascript">

function validate()
{
   var ind = document.form1.wmsList.selectedIndex;
   if(ind > -1) {
     var permission =  confirm("delete: " + document.form1.wmsList.options[ind].text + " ?");
     if(permission === true) {
        document.form1.del.value = 1;
        document.form1.submit();
     }
   }
}

function suggest_deletion(email_str) 
{
   var ind = document.form1.wmsList.selectedIndex;
   if(ind > -1)
	 {
     var permission =  confirm("A mail will be sent to the owners of '" + document.form1.wmsList.options[ind].text + "', suggesting its deletion.");
     if(permission === true) {
        document.form2.suggest.value = 1;
        document.form2.wms_name.value = document.form1.wmsList.options[ind].text;
        document.form2.owners.value = email_str;
        document.form2.submit();
     }
   }
}

</script>
</head>
<body>
<div class="container-fluid" style="padding-top:15px;padding-bottom:15px;">
<?php
require_once(dirname(__FILE__)."/../classes/class_administration.php");
$admin = new administration();

$error_msg='';

//if mail form has been filled in and sent
if ($_POST["mail"]) {
	if (!$admin->isValidEmail($_POST["replyto"])) {
		$error_msg .= "The reply-to address is not valid! Please correct it.";
	}
	else {
		$toAddr = array();
		$toName = array();	
		$namesAndAddresses = explode(":::" , $_POST["owners"]);
		for ($i=0; $i<count($namesAndAddresses)-1; $i++) {
			$nameAndAddress = explode(";;;", $namesAndAddresses[$i]);
			$toAddr[$i] = $nameAndAddress[0]; 	
			$toName[$i] = $nameAndAddress[1]; 	
		}

		$error = '';
		for ($i=0; $i<count($toAddr); $i++) {
			if (!$admin->sendEmail($_POST["replyto"], $_POST["from"], $toAddr[$i], $toName[$i], "[Mapbender] A user has suggested a WMS for deletion", $_POST["comment"], $error)) {
				if ($error) {
					$error_msg .= $error . " ";
				}
			}
		}
		
	   if (!$error_msg) {
	      echo "<script language='javascript'>";
	      echo "alert('Other owners have been informed!');";
	      echo "</script>";
	   }
	}
}


// if deletion has been suggested, or there's an error in the form, display mail form (again)
if ($_POST["suggest"] || $error_msg){

	if ($error_msg) {
      echo "<script language='javascript'>";
      echo "alert('$error_msg');";
      echo "</script>";
	}

	$wms = $_POST["wms_name"];

	if (!$_POST["from"]) 
		$fromName = Mapbender::session()->get("mb_user_name");
	else
		$fromName = $_POST["from"];
		
	if (!$_POST["replyto"]) 
		$email = $admin->getEmailByUserId(Mapbender::session()->get("mb_user_id"));
	else
		$email = $_POST["replyto"];
		
	if (!$_POST["comment"]) 
		$text = "The WMS " . $wms . " has been suggested for deletion. If you agree, remove it from your GUIs. If not, you can contact the user who suggested the deletion and discuss it.";
	else
		$text = $_POST["comment"];
	
		
	echo "<form type='invisible' name='form3' action='" . $self ."' method='post'>";
	echo "<div class='form-group>";
		echo "<label for='exampleInputOwner1'>Sender</label>";
		echo "<input class='form-control' type='text' id='exampleInputOwner1' name='from' size=50 value = '".$fromName."' readonly>";
	echo "</div><br>";

	echo "<div class='form-group>";
		echo "<label for='exampleInputEmail1'>To</label>";
		echo "<input class='form-control' type='text' id='exampleInputEmail1' ame='replyto' size=50 value = '" . $email. "' readonly>";
	echo "</div><br>";

	echo "<div class='form-group>";
		echo "<label for='exampleInputText1'>Email Text</label>";
		echo "<textarea class='form-control' id='exampleInputText1' name='comment' cols=38 rows=10>" . $text . "</textarea>";
	echo "</div><br>";
	echo "<div class='container'>";
						echo "<div class='row'>";
							echo "<div class='col1'>";
								echo "<input class='btn btn-primary' type='submit' name='mail' value='Send Email'>";
							echo "</div>";
							echo "<div class='col1'>";
								echo "<a id='deleteWMS' href='../php/mod_deleteWMS.php?&amp;guiID=Administration_DE&amp;elementID=deleteWMS' title='Set the form' class=''><input class='button_cancel btn btn-primary col-xs-3 form-control  btn-block' type='button' value='cancel'></a>";
							echo "</div>";
						echo "</div>";
					echo "</div>";
	

	echo "<input type='hidden' name='owners' value='" . $_POST["owners"] . "'>";
	echo "</form>";
	//mail($email, $fromName, $text);	
}
else {	
	// delete WMS
	if($del){
		$sql = "select * from gui_wms where fkey_wms_id = $1 ";
		$v = array($wmsList);
		$t = array('i');
		$res = db_prep_query($sql,$v,$t);
		$cnt = 0;
	 	 while($row = db_fetch_array($res))
	  	 {
	  	 		 $sql = "UPDATE gui_wms set gui_wms_position = (gui_wms_position -1) ";
	  			 $sql .= "WHERE fkey_gui_id = $1 ";
	  			 $sql .= " AND gui_wms_position > $2 ";
	  			 $v = array($row["fkey_gui_id"],$row["gui_wms_position"]);
	  			 $t = array('s','i');
	  			 $res1 = db_prep_query($sql,$v,$t);			
	    		 $cnt++;				
	 	 }
		$sql = "SELECT wms_title, wms_abstract FROM wms WHERE wms_id = $1";
	   $v = array($wmsList);
	   $t = array('i');
	   $res = db_prep_query($sql,$v,$t);
	   if ($res) {
	   		$row = db_fetch_array($res);
			$wms_title = $row["wms_title"];
			$wms_abstract = $row["wms_abstract"];
	   }
		
	//Before the wms will be deleted, the metadataUrls and dataUrls from the Layers must be deleted!
	//The other things will be done by class_wms!
	//***
	/*$sql = "DELETE FROM mb_metadata WHERE metadata_id IN (SELECT metadata_id FROM mb_metadata INNER JOIN";
	$sql .= " (SELECT * from ows_relation_metadata WHERE fkey_layer_id IN ";
	$sql .= " (SELECT layer_id FROM layer WHERE fkey_wms_id = $1) )";
 	$sql .= " as relation ON ";
	$sql .= " mb_metadata.metadata_id = relation.fkey_metadata_id AND mb_metadata.origin = 'capabilities')";
	
	$v = array($wmsList);
	$t = array('i');
	$res = db_prep_query($sql,$v,$t);*/
	//***
	$sql = "DELETE FROM datalink WHERE datalink_id IN (SELECT datalink_id FROM datalink INNER JOIN";
	$sql .= " (SELECT * from ows_relation_data WHERE fkey_layer_id IN ";
	$sql .= " (SELECT layer_id FROM layer WHERE fkey_wms_id = $1) )";
 	$sql .= " as relation ON ";
	$sql .= " datalink.datalink_id = relation.fkey_datalink_id AND datalink.datalink_origin = 'capabilities')";
	
	$v = array($wmsList);
	$t = array('i');
	$res = db_prep_query($sql,$v,$t);
	//before delete the wms get the published layers to delete their metadata afterwards
	//select all layer which are searchable
	$sql = "SELECT layer_id, uuid FROM layer WHERE fkey_wms_id = $1 and layer_searchable = 1";
	$v = array($wmsList);
	$t = array('i');
	$res = db_prep_query($sql,$v,$t);
	$layerArray = array();
	while($row = db_fetch_array($res)){
		$layerArray[] = array(
				"id" => $row['layer_id'],
				"uuid" => $row['uuid']
		);
	}
	//***
	   $sql = "DELETE FROM wms WHERE wms_id = $1";
	   $v = array($wmsList);
	   $t = array('i');
	   $res = db_prep_query($sql,$v,$t);
	   
	   if ($res) {
			//
			// update GeoRSS feed
			//
			$geoRssFactory = new GeoRssFactory();
			$geoRss = $geoRssFactory->loadOrCreate(GEO_RSS_FILE);
			$geoRssItem = new GeoRssItem();
			$geoRssItem->setTitle("DELETED WMS: ".$wms_title." (".$wmsList.")");
			$geoRssItem->setDescription($wms_abstract);
			$geoRssItem->setUrl(getWMSMetadataUrl($wmsList));
			//$timestamp = ($timestamp==null) ? time() : $timestamp;
			$timestamp = date(DATE_RSS,time());
			$geoRssItem->setPubDate($timestamp);
			$geoRss->appendTop($geoRssItem);
			$geoRss->saveAsFile();	
			//delete metadata of connected catalogue
			//Propagate information for each new layer to csw if configured
			$layerUuid = array();
			foreach ($layerArray as $layer) {
					$layerUuid[] = $layer['uuid'];
			} 
			$propagation = new propagateMetadata();
			$result = $propagation->doPropagation("layer", false, "delete", $layerUuid);
		}
	}
	// display WMS List
	$wms_id_own = $admin->getWmsByOwner(Mapbender::session()->get("mb_user_id"),true);
	
	if (count($wms_id_own)>0){
		$v = array();
		$t = array();
		$sql = "Select * from wms WHERE wms_id IN (";
		for($i=0; $i<count($wms_id_own); $i++){
		 if($i>0){ $sql .= ",";}
		 $sql .= "$".($i+1);
		 array_push($v,$wms_id_own[$i]);
		 array_push($t,'i');
		}
		$sql .= ") ORDER BY wms_title";
		$res = db_prep_query($sql,$v,$t);
		$cnt = 0;
		
		
		echo "<form id='form1' name='form1' action='" . $self ."' method='post'>";
		echo "<div class='optionsbox' style='margin-top:0'><label for='guiList'><strong>Wählen Sie Ihren WMS aus</strong></label>
			<select class='form-control' name='wmsList' onchange='submit()'>";
			echo "<option id='edit-item' value='' selected>...</option>";
		while($row = db_fetch_array($res))
		{
			$wmsvalue = $row["wms_id"];
			//mark previously selected WMS <==> text = " selected" 
			if ($wmsvalue == $wmsList) {
				$text = "selected";
			}
			else {
				$text = "";
			}
		   
		   echo "<option id='edit-item' value='".$wmsvalue."'" . $text . ">".$row["wms_title"]."</option>";
		   $cnt++;
		}
		echo "</select></div>";
		
		//
		//
		// If WMS is selected, show more info
		//
		//
		if($wmsList)
		{   
		    $sql = "SELECT layer_id FROM layer WHERE fkey_wms_id = $1 AND layer_pos=0";
		
			// Show WMS Information
			echo "<div class='panel panel-default'><div class='panel-heading'><strong>Informationen</strong></div><table class='table table-bordered'>";
			// Show GetCapabilities of chosen WMS
			$sql = "SELECT wms_id,wms_getcapabilities,wms_abstract FROM wms WHERE wms_id = $1";
			$v = array($wmsList);
			$t = array('i');
			$res = db_prep_query($sql,$v,$t);
			$cnt = 0;
			while($row = db_fetch_array($res))
			{
				echo "<tr><td>WMS ID</td><td>" . $row["wms_id"] . "</td></tr>";
				echo "<tr><td>GetCapabilities</td><td>" . $row["wms_getcapabilities"] . "</td></tr>";
				echo "<tr><td>Beschreibung</td><td>" . $row["wms_abstract"] . "</td></tr>";
				
				$cnt++;
			}
			echo "</table></div>";
			

                        // Show GUIs using chosen WMS
                        $sql = "SELECT gui_wms.fkey_gui_id,mb_user.mb_user_name,mb_user.mb_user_email
FROM gui_wms 
INNER JOIN gui_mb_user ON gui_wms.fkey_gui_id = gui_mb_user.fkey_gui_id
INNER JOIN mb_user ON mb_user.mb_user_id = gui_mb_user.fkey_mb_user_id WHERE gui_wms.fkey_wms_id = $1 AND gui_mb_user.mb_user_type = 'owner'";
                        $v = array($wmsList);
                        $t = array('i');
                        $res = db_prep_query($sql,$v,$t);

                        // show WMS-ID for better identifiability
                        echo "<div class='panel panel-default'>
                                <div class='panel-heading'><strong>WMS wird in folgenden Anwendungen / Containern verwendet</strong></div>
                                        <table class='table table-bordered table-striped'>
						<thead>
							<tr>
								<th>Name (gui_id)</th>
								<th>Besitzer (owner)</th>
								<th>Email</th>
							</tr>
						</thead>";
                        $cnt = 0;
                        while($row = db_fetch_array($res))
                        {
                                echo "<tr><td>" .$row["fkey_gui_id"]. "</td><td>" .$row["mb_user_name"]. "</td><td>" .$row["mb_user_email"]. "</td></tr>";
                                $cnt++;

                        }
                        if ($cnt == 0) {
                                echo "<tr><td>-</td><td>-</td><td>-</td></tr>";
                        }
                        echo "</table></div>";


			$owner = $admin->getOwnerByWms($wmsList);
			if ($owner && count($owner)>0) {
				for($i=0; $i<count($owner); $i++){
				}
			}
			else echo "<i>- none -</i>";
	
			//previously, a WMS could only be deleted if it was owned by a single owner
			//if(count($owner)==1 && $owner[0] == Mapbender::session()->get("mb_user_name")){
			
			//now, any owner can delete, any non-owner can suggest deletions
			//if a wms has no owner, anyone can delete
	    		if($owner && in_array(Mapbender::session()->get("mb_user_id"), $owner) && count($owner) == 1) {
	    			echo "<input class='button_del btn btn-danger' type='button' value='delete' onclick='validate()'>";
	    		}
	    		elseif ($owner && in_array(Mapbender::session()->get("mb_user_id"), $owner) && count($owner) > 1) {
	    			
	    			// delete suggestion button only appears when mailing is enabled in mapbender.conf
	    			if ($use_php_mailing) {
	    			
	    				// prepare email-addresses and usernames of all owners
	    				$owner_ids = $owner;
	    				$owner_mail_addresses = array();
	    				
	    				$j=0;
	    				for ($i=0; $i<count($owner_ids); $i++) {
	    					$adr_tmp = $admin->getEmailByUserId($owner_ids[$i]);
	    					if (in_array($adr_tmp, $owner_mail_addresses) && $adr_tmp) {
	    						$owner_mail_addresses[$j] = $adr_tmp;
	    						$email_str .= $owner_mail_addresses[$j] . ";;;" . $owner[$i] . ":::";
	    						$j++;
	    					} 
	    				}
	    				print_r($owner_ids);
	    				print_r($owner_mail_addresses); 
	    				
	    			}
				else {
					//echo "<script language='javascript'>";
					//echo "alert('You are not allowed to delete this WMS!');";
					//echo "</script>";
				}
			//echo "<input class='button_del btn btn-info' type='button' value='suggest deletion' onclick='suggest_deletion(\"" . $email_str . "\")'> ";
				echo "<div class='panel panel-danger'><div class='panel-heading'>Löschen nicht erlaubt!</div><div class='panel-body'><p>Diese Ressource der GDI-Hessen wird von weiteren Benutzern in Anwendungen des Geoportals verwendet.</p><p><strong>Bitte Informieren Sie diese Benutzer über Ihre geplante Löschung</strong>, so dass diese eine Alternative verwenden.</p></div></div>
	<div class='alert alert-danger' role='alert'>
	  <span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true'></span>
	  <span class='sr-only'>Error:</span>
Die weiteren Benutzer müssen die Ressource aus ihren Geoportal Anwendungen entfernen - nur dann kann der Service endgültig aus der GDI-Hessen entfernt werden.</div>";
				echo "<a id='deleteWMS' href='../php/mod_deleteWMS.php?&amp;guiID=Administration_DE&amp;elementID=deleteWMS' title='Set the form'><input class='button_cancel btn btn-default' type='button' value='cancel'></a> ";
			}
		}
	}else{
		echo "There are no wms available for this user.<br>";
	}
}
?>
<input type='hidden' name='del'>
</form>
<?php 
echo "<form name='form2' action='" . $self ."' method='post'>";
?>
<input type='hidden' name='suggest' value='0'>
<input type='hidden' name='wms_name' value=''>
<input type='hidden' name='owners' value=''>
</form>
</div>
</body>
</html>
