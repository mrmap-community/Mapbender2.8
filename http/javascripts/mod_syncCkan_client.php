<?php
/**
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2, or (at your option)
* any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
*/
require_once(dirname(__FILE__) . '/../../core/globalSettings.php');
require_once(dirname(__FILE__).'/../../conf/ckan.conf');

if (defined("CKAN_SERVER_PORT") && CKAN_SERVER_PORT !== '') {
    $ckanApiUrl = CKAN_SERVER_IP.":".CKAN_SERVER_PORT;
} else {
    $ckanApiUrl = CKAN_SERVER_IP;
}
if (defined("CKAN_API_VERSION") && CKAN_API_VERSION !== '') {
    $ckanApiVersion = CKAN_API_VERSION;
} else {
    $ckanApiVersion = 3;
}
if (defined("CKAN_API_PROTOCOL") && CKAN_API_PROTOCOL !== '') {
    $ckanApiProtocol = CKAN_API_PROTOCOL;
} else {
    $ckanApiProtocol = 'https';
}
$ckanBaseUrl = $ckanApiProtocol.'://'.$ckanApiUrl.'/api/'.$ckanApiVersion.'/';
$ckanShowOrgaUrl = $ckanBaseUrl.'action/organization_show?id=';
//TODO - do authorization later!
//require_once(dirname(__FILE__)."/../php/mb_validatePermission.php");
$compareTimestamps = false;
//Parse parameters
if (isset($_REQUEST["compareTimestamps"]) & $_REQUEST["compareTimestamps"] != "") {
	$testMatch = $_REQUEST["compareTimestamps"];	
 	if (!($testMatch == 'false' or $testMatch == 'true')){ 
		echo 'Parameter <b>compareTimestamps</b> is not valid (false, true).<br/>'; 
		die(); 		
 	}
	switch ($testMatch) {
		case "false":
			$compareTimestamps = false;
			break;
		case "true":
			$compareTimestamps = true;
			break;
	}
	$testMatch = NULL;
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<?php
echo '<meta http-equiv="Content-Type" content="text/html; charset='.CHARSET.'">';
$title = _mb('Ckan sync module');	
?>
<title><?php echo $title;?></title>
<!--<script src="../extensions/jquery-1.12.0.min.js"></script> -->
<!--TODO use newer jquery/ui libs! -->
<script src="../extensions/jquery-ui-1.8.16.custom/js/jquery-1.6.2.min.js"></script>
<script src="../extensions/jquery-ui-1.8.16.custom/js/jquery-ui-1.8.16.custom.min.js"></script>
<!--<script src="../extensions/jquery-ui-1.12.1/external/jquery.js"></script> -->
<!--<script src="../extensions/jquery-ui-1.12.1/jquery-ui.min.js"></script> -->
<!--<link rel="stylesheet" href="../extensions/jquery-ui-1.12.1/jquery-ui.min.css"> -->
<link rel="stylesheet" href="../extensions/jquery-ui-1.8.16.custom/css/ui-lightness/jquery-ui-1.8.16.custom.css">
<script>
//https://stackoverflow.com/questions/29298462/c-curcss-is-not-a-function-bug-from-jquery
/*jQuery.curCSS = function(element, prop, val) {
    return jQuery(element).css(prop, val);
};*/

function showCatalogues(compareTimestamps, syncDepartment, operation, orgaId) {
	/*for (csw of csw_catalogues) {
		console.log(csw);
	}*/
	//alert(syncDepartment);
	$("#load_catalogue_statistics").css("display","block");
	$.ajax({
  		url: '../php/mod_syncCkan_server.php',
  		type: "post",
		async: true,
		data: {compareTimestamps: compareTimestamps, syncDepartment: syncDepartment, operation: operation, orgaId: orgaId},
       		dataType: "json",
  		success: function(result) {
			if (result.success == true) {
				//alert(JSON.stringify(result.result));
    				$( "#show_catalogues" ).dialog({
					height: 200,
      					width: 550,
      					modal: true,
       					close: function(event, ui)
        					{
            						//$(this).destroy().remove();
							$("#csw_sync_status_table tr:gt(0)").remove();
        				}
				});
				//$( "#show_catalogues" ).
				if (operation == 'listCatalogues') {
				//initialize table
				for (orga of result.result.result.external_csw) {
					//read length of delete, update, create into variables
					if ("undefined" !== typeof(orga.delete)) {var p_delete = (orga.delete).length;} else {var p_delete = 0;}
					if ("undefined" !== typeof(orga.update)) {var p_update = (orga.update).length;} else {var p_update = 0;}
					//alert(JSON.stringify(orga.update));
					//alert(typeof(orga.update));
					if ("undefined" !== typeof(orga.create)) {var p_create = (orga.create).length;} else {var p_create = 0;}
					$('#csw_sync_status_table tr:last').after('<tr id=\'csw_r_id_'+orga.id+'\'>'+'<td id=\'csw_ckanorgauuid_'+orga.id+'\'><a target=\'_blank\' href=\'<?php echo $ckanShowOrgaUrl;?>'+orga.ckan_orga_ident+'\'>'+orga.ckan_orga_ident+' ('+orga.count_ckan_packages+')'+'</a></td>'+'<td id=\'csw_orgatitle_'+orga.id+'\'>'+'<a target=\'_blank\' href=\'../php/mod_showOrganizationInfo.php?id='+orga.id+'\'>'+orga.title+' ('+orga.count_csw_packages+')'+'</a>'+'</td>'+'<td id=\'csw_to_delete_'+orga.id+'\'>'+p_delete+'</td>'+'<td id=\'csw_to_update_'+orga.id+'\'>'+p_update+'</td>'+'<td id=\'csw_to_create_'+orga.id+'\'>'+p_create+'</td>'+'</tr>');
					//add button for start syncing if something may be done ;-)
					if ((p_delete+p_update+p_create) > 0) {
						//alert(p_delete+p_update+p_create);
						//$form .= "<button class=\"btn btn-primary\" type=\"button\" id=\"maintenance_button\" onclick=\"callServer('".$resourceType."','".$maintenanceFunction."',$('#resource_id_list').val(),"."1".");\">";
						//the first orga.id is the csw id in case of csw sync!!!!!!!
						$('#csw_r_id_'+orga.id+' td:last').after('<td id=\'csw_button_column_'+orga.id+'\'><button class=\"btn btn-primary\" type=\"button\" id=\"csw_sync_button_'+orga.id+'\" onclick=\"showCatalogues('+compareTimestamps+','+orga.id+',\'syncCsw\','+orgaId+');\">Start sync via CSW</button></td>');
					} else {
						//alert(p_delete+p_update+p_create+' - lower or equal zero');
						//deactivated button
						$('#csw_r_id_'+orga.id+' td:last').after('<td id=\'csw_button_column_'+orga.id+'\'><button class=\"btn btn-primary\" type=\"button\" id=\"csw_sync_button_'+orga.id+'\" disabled>Nothing to do</button></td>');
					}
				}
		
				if (operation == 'syncCsw')
					if (result.success == true) {
						//update values
						$('#csw_to_delete_'+result.result.orga_id).text(parseInt($('#csw_to_delete_'+result.result.orga_id).text()) - result.result.numberOfDeletedPackages);
						$('#csw_to_update_'+result.result.orga_id).text(parseInt($('#csw_to_update_'+result.result.orga_id).text()) - result.result.numberOfUpdatedPackages);
						$('#csw_to_create_'+result.result.orga_id).text(parseInt($('#csw_to_create_'+result.result.orga_id).text()) - result.result.numberOfCreatedPackages);
						//check if something more is to be done
						if ((parseInt($('#csw_to_delete_'+result.result.orga_id).text()) + parseInt($('#csw_to_update_'+result.result.orga_id).text()) + parseInt($('#csw_to_create_'+result.result.orga_id).text())) == 0) {
							//alert('drop button');
							$('#csw_button_column_'+result.result.orga_id).remove();
						} else {
							//alert((parseInt($('#to_delete_'+result.result.orga_id).text()) + parseInt($('#to_update_'+result.result.orga_id).text()) + parseInt($('#to_create_'+result.result.orga_id).text())));
						}
						//$("#csw_sync_status_table tr").remove();
						//$("#csw_sync_status_table").empty();
						alert("Packages created: "+result.result.numberOfCreatedPackages+" - Packages updated: "+numberOfUpdatedPackages+" - Packages deleted: "+numberOfDeleteddPackages);
					}
					$("#sync_single_csw").css("display","none");
				}
				
				//set title
				//show button with information about the catalogues and the buttons!
				//show table
				//foreach catalogue show line
				//show button to sync	
			} else {
				alert(result.error.message);
			}	
			$("#load_catalogue_statistics").css("display","none");
			//close dialog
			//$( "#show_catalogues" ).dialog('close');
 		}
	});
	return false;
}

function callServer(compareTimestamps, syncDepartment) {
	if (syncDepartment == 0) {
		$("#get_orga_list_info").css("display","block");
	} else {
		$("#sync_single_orga").css("display","block");
	}
	$.ajax({
  		url: '../php/mod_syncCkan_server.php',
  		type: "post",
		async: true,
		data: {compareTimestamps: compareTimestamps, syncDepartment: syncDepartment},
       		dataType: "json",
  		success: function(result) {
			if (result.success == true) {
				if (syncDepartment == 0) {
					//re-initialize table
					$("#get_orga_list_info").css("display","none");
					for (orga of result.result.geoportal_organization) {
						//read length of delete, update, create into variables
						if ("undefined" !== typeof(orga.delete)) {var p_delete = (orga.delete).length;} else {var p_delete = 0;}
						if ("undefined" !== typeof(orga.update)) {var p_update = (orga.update).length;} else {var p_update = 0;}
						//alert(JSON.stringify(orga.update));
						//alert(typeof(orga.update));
						if ("undefined" !== typeof(orga.create)) {var p_create = (orga.create).length;} else {var p_create = 0;}
						$('#sync_status_table tr:last').after('<tr id=\'r_id_'+orga.id+'\'>'+'<td id=\'mborgatitle_'+orga.id+'\'>'+'<a target=\'_blank\' href=\'../php/mod_showOrganizationInfo.php?id='+orga.id+'\'>'+orga.title+' ('+orga.count_geoportal_packages+')'+'</a>'+'</td>'+'<td id=\'ckanorgauuid_'+orga.id+'\'><a target=\'_blank\' href=\'<?php echo $ckanShowOrgaUrl;?>'+orga.ckan_orga_ident+'\'>'+orga.ckan_orga_ident+' ('+orga.count_ckan_packages+')'+'</a></td>'+'<td id=\'to_delete_'+orga.id+'\'>'+p_delete+'</td>'+'<td id=\'to_update_'+orga.id+'\'>'+p_update+'</td>'+'<td id=\'to_create_'+orga.id+'\'>'+p_create+'</td>'+'</tr>');
						//add button for start syncing if something may be done ;-)
						if ((p_delete+p_update+p_create) > 0) {
							//$form .= "<button class=\"btn btn-primary\" type=\"button\" id=\"maintenance_button\" onclick=\"callServer('".$resourceType."','".$maintenanceFunction."',$('#resource_id_list').val(),"."1".");\">";
							$('#r_id_'+orga.id+' td:last').after('<td id=\'button_column_'+orga.id+'\'><button class=\"btn btn-primary\" type=\"button\" id=\"sync_button_'+orga.id+'\" onclick=\"callServer('+compareTimestamps+','+orga.id+');\"><?php echo _mb('Start sync'); ?></button></td>');
						} else {
							//deactivated button
							$('#r_id_'+orga.id+' td:last').after('<td id=\'button_column_'+orga.id+'\'><button class=\"btn btn-primary\" type=\"button\" id=\"sync_button_'+orga.id+'\" disabled><?php echo _mb('Nothing to do'); ?></button></td>');
						}
						if (orga.csw_catalogues !== null) {
							//csw_cataloguesObject = Object.assign({}, orga.csw_catalogues);
							//csw_cataloguesObject = {};
							/*var i = 0;
							for (csw of orga.csw_catalogues) {
								csw_cataloguesObject[i] = csw;
							}*/
							$('#r_id_'+orga.id+' td:last').after('<td id=\'external_button_column_'+orga.id+'\'><button class=\"btn btn-primary\" type=\"button\" id=\"external_sync_button_'+orga.id+'\" onclick=\"showCatalogues('+compareTimestamps+','+orga.id+',\'listCatalogues\','+orga.id+');\"><?php echo _mb('Show catalogue status'); ?></button></td><td></td>');
							/*console.log(typeof(orga.csw_catalogues[0].organisation_filter));
							console.log(orga.csw_catalogues[0].organisation_filter);
							for (csw of orga.csw_catalogues) {
								console.log(csw);
						
							}*/
						}
						//onclick=\"showCatalogues('+compareTimestamps+','+orga+');\"
					}
				} else {
					//update row for specific organization
					if (result.success == true) {
						//update values
						$('#to_delete_'+result.result.orga_id).text(parseInt($('#to_delete_'+result.result.orga_id).text()) - result.result.numberOfDeletedPackages);
						$('#to_update_'+result.result.orga_id).text(parseInt($('#to_update_'+result.result.orga_id).text()) - result.result.numberOfUpdatedPackages);
						$('#to_create_'+result.result.orga_id).text(parseInt($('#to_create_'+result.result.orga_id).text()) - result.result.numberOfCreatedPackages);
						//check if something more is to be done
						if ((parseInt($('#to_delete_'+result.result.orga_id).text()) + parseInt($('#to_update_'+result.result.orga_id).text()) + parseInt($('#to_create_'+result.result.orga_id).text())) == 0) {
							//alert('drop button');
							$('#button_column_'+result.result.orga_id).remove();
						} else {
							//alert((parseInt($('#to_delete_'+result.result.orga_id).text()) + parseInt($('#to_update_'+result.result.orga_id).text()) + parseInt($('#to_create_'+result.result.orga_id).text())));
						}
					}
					$("#sync_single_orga").css("display","none");
						
				}
				//alert(JSON.stringify(result.result));
			} else {
				alert(result.error.message);
			}
 		}
	});
	return false;
}
</script>
<style type="text/css">
.loading_symbol {
    -webkit-animation:spin 4s linear infinite;
    -moz-animation:spin 4s linear infinite;
    animation:spin 4s linear infinite;
}
@-moz-keyframes spin { 100% { -moz-transform: rotate(360deg); } }
@-webkit-keyframes spin { 100% { -webkit-transform: rotate(360deg); } }
@keyframes spin { 100% { -webkit-transform: rotate(360deg); transform:rotate(360deg); } }
</style>
<?php include '../include/dyn_css.php'; ?>
</head>
<body onload="callServer(<?php if ($compareTimestamps == false) {echo "false";} else {echo "true";} ?>,<?php if ($syncDepartment == false) {echo 0;} else {echo $syncDepartment;} ?>)">
<div id="title"><?php echo $title; ?></div>
<div id="get_orga_list_info" style="display: none;"><p><img class="loading_symbol" src="../img/loader_lightblue.gif" style="margin-left: auto; margin-right: auto;"/><?php echo _mb("Getting organization info ..."); ?></p></div>
<div id="sync_single_orga" style="display: none;"><p><img class="loading_symbol" src="../img/loader_lightblue.gif" style="margin-left: auto; margin-right: auto;"/><?php echo _mb("Syncing metadata ..."); ?></p></div>
<div id="sync_single_csw" style="display: none;"><p><img class="loading_symbol" src="../img/loader_lightblue.gif" style="margin-left: auto; margin-right: auto;"/><?php echo _mb("Syncing metadata via CSW ..."); ?></p></div>
<div id="show_catalogues" style="display: none;">
<form id="csw_sync_status_form">
    <table id ="csw_sync_status_table">
        <tr id="csw_sync_status_table_row_header">
            <th><?php echo _mb('Ckan instance'); ?></th>
            <th><?php echo _mb('External CSW'); ?></th>
            <th><?php echo "# "._mb('delete'); ?></th>
            <th><?php echo "# "._mb('update'); ?></th>
	    <th><?php echo "# "._mb('create'); ?></th>
	    <th><?php echo _mb('Action'); ?></th>
        </tr>
    </table>
</form>
</div>
<div id="load_catalogue_statistics" style="display: none;"><p><img class="loading_symbol" src="../img/loader_lightblue.gif" style="margin-left: auto; margin-right: auto;"/><?php echo _mb("Loading catalogue statistics ..."); ?></p></div>
<div id="sync_single_catalogue" style="display: none;"><p><img class="loading_symbol" src="../img/loader_lightblue.gif" style="margin-left: auto; margin-right: auto;"/><?php echo _mb("Syncing catalogue metadata ..."); ?></p></div>

<form id="sync_status_form">
    <table id ="sync_status_table">
        <tr id="sync_status_table_row_header">
            <th><?php echo _mb('Mapbender group'); ?></th>
            <th><?php echo _mb('Ckan organization'); ?></th>
            <th><?php echo "# "._mb('delete'); ?></th>
            <th><?php echo "# "._mb('update'); ?></th>
	    <th><?php echo "# "._mb('create'); ?></th>
	    <th><?php echo _mb('Action'); ?></th>	    
	    <th><?php echo _mb('External catalogues'); ?></th>
        </tr>
    </table>
</form>
</body>
</html>
