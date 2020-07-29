<?php
	require_once dirname(__FILE__) . "/../../core/globalSettings.php";
	require_once dirname(__FILE__) . "/../classes/class_user.php";
?>

<fieldset>
	<input name="wms_id" id="wms_id" type="hidden"/>

	<legend><?php echo _mb("Service Level Metadata");?>: <img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Possibility to adapt and add informations in the separate WMS-Layer Metadata. The modified Metadata is stored in the database of the GeoPortal.rlp, outwardly these metadata overwrite the original Service-Metadata.");?>'}" src="../img/questionmark.png" alt="" /></legend>
	<p>
		<label for="wms_title"><?php echo _mb("Show original Service Metadata from last update");?></label>
		<img class="original-metadata-wms" src="../img/book.png" alt="" />
		<img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("The original WMS-Metadata from the last update could be recovered or updated, so that the original Service-Metadata will be shown outward again.");?>'}" src="../img/questionmark.png" alt="" />
	</p>
	<p>
		<label for="wms_title"><?php echo _mb("WMS Title (OWS)");?>:</label>
		<input name="wms_title" id="wms_title" class="required"/>
		<img class="metadata_img" title="<?php echo _mb("INSPIRE 1.1: resource title");?>" src="../img/misc/inspire_eu_klein.png" alt="" />
	</p>
	<p>
    	<label for="wms_abstract"><?php echo _mb("WMS Abstract (OWS)");?>:</label>
    	<input name="wms_abstract" id="wms_abstract"/>
    	<img class="metadata_img" title="<?php echo _mb("INSPIRE 1.2: resource abstract");?>" src="../img/misc/inspire_eu_klein.png" alt="" />
	<img class="help-dialog" title="<?php echo _mb("Help for INSPIRE Abstract");?>" help="{text:'<?php echo _mb("INSPIRE demands some information about the spatial resolution in the abstract tag of the capabilities document. Please insert some words about it.");?>'}" src="../img/questionmark.png" alt="" />
	</p>
	<p>
		<label for="wms_keywords"><?php echo _mb("WMS Keywords (OWS)");?>:</label>
    	<input readonly="readonly" name="wms_keywords" id="wms_keywords"/>
    	<img class="metadata_img" title="<?php echo _mb("INSPIRE 3: keyword");?>" src="../img/misc/inspire_eu_klein.png" alt="" />
	</p>
	<p>
		<label for="fees"><?php echo _mb("WMS Fees (OWS)");?>:</label>
    	<input name="fees" id="fees"/>
    	<img class="metadata_img" title="<?php echo _mb("INSPIRE 8.1: conditions applying to access and use");?>" src="../img/misc/inspire_eu_klein.png" alt="" />
	</p>
	<p>
		<label for="accessconstraints"><?php echo _mb("WMS AccessConstraints (OWS)");?>:</label>
		<input name="accessconstraints" id="accessconstraints"/>
    	<img class="metadata_img" title="<?php echo _mb("INSPIRE 8.2: limitations on public access");?>" src="../img/misc/inspire_eu_klein.png" alt="" />
	</p>
	<?php
            //read inspire_LimitationsOnPublicAccess.json as key value pair 
			if (file_exists(dirname(__FILE__)."/../../conf/inspire_LimitationsOnPublicAccess.json")) {
				$languageCode = Mapbender::session()->get("mb_lang");
				$languageCode = "de"; //test
				$configObject = json_decode(file_get_contents("../../conf/inspire_LimitationsOnPublicAccess.json"));
				echo '<p>';
				echo '        <label for="accessconstraints_md_inspire">'._mb("Predefined options (e.g. INSPIRE)").'</label>';
			    echo '        <select class="accessconstraints_md_inspire_selectbox" name="md_accessconstraints_inspire" id="md_accessconstraints_inspire" onChange="var chosenoption=this.options[this.selectedIndex];$(\'#mb_md_showMetadataAddon\').mapbender().selectPredefinedAccessConstraints(chosenoption.value);">';
				echo '            <option value="0">...</option>';
				foreach ($configObject->codelist as $accessconstraintsCodelist) {
					echo "            <option value='" . $accessconstraintsCodelist->code . "'>" . htmlentities($accessconstraintsCodelist->title->{$languageCode}, ENT_QUOTES, CHARSET) . "</option>";
				}
				echo '        </select>';
				$helptext = "";
				foreach ($configObject->codelist as $accessconstraintsCodelist) {
					$helptext .= "<b>".$accessconstraintsCodelist->title->{$languageCode} ."</b>: ".$accessconstraintsCodelist->description->{$languageCode}."<br>";
				}
				//echo '        <img class="help-dialog" title="'._mb("Help").'" help="{text:\''._mb("Selection of predefined INSPIRE access constraints.").'\'}" src="../img/questionmark.png" alt="" />';
				echo '        <img class="help-dialog" title="'._mb("Help").'" help="{text:\''.$helptext.'\'}" src="../img/questionmark.png" alt="" />';
				
				echo '        </p>';				
			}
?>
<?php
	$sql = "SELECT termsofuse_id, name FROM termsofuse";
	$res = db_query($sql);
	$termsofuse = array();
	while ($row = db_fetch_assoc($res)) {
		$termsofuse[$row["termsofuse_id"]] = $row["name"];
	}
?>
	<p>
		<label for="wms_termsofuse"><?php echo _mb("WMS Predefined Licence (Registry)");?>:</label>
    	<select name="wms_termsofuse" id="wms_termsofuse" onChange="var chosenoption=this.options[this.selectedIndex];$('#mb_md_edit').mapbender().fillLicence(chosenoption.value);">
			<option value='0'>...</option>
<?php
	foreach ($termsofuse as $key => $value) {
		echo "<option value='" . $key . "'>" . htmlentities($value, ENT_QUOTES, CHARSET) . "</option>";
	}
?>
		</select>
    	<img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Selection of predefined licences.");?>'}" src="../img/questionmark.png" alt="" />
	</p>
	<p id="license_info">
		<img id="licence_symbol">&nbsp;<a id='licence_descriptionlink' target='_blank'><a/>&nbsp;<img id="open_symbol">
	</p>
	<p id="license_source">
		<label for="wms_license_source_note"><?php echo _mb("Source note if license require it (Registry)");?>:</label>
      		<input name="wms_license_source_note" id="wms_license_source_note" type="text"/>
		<img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Source note that should be mentioned if such an information is required by the license.");?>'}" src="../img/questionmark.png" alt="" />
	</p>
	<p>
		<label for="wms_network_access"><?php echo _mb("Restricted Network Access (Registry)");?>:</label>
      		<input name="wms_network_access" id="wms_network_access" type="checkbox"/>
	</p>
	<p>
		<label for="wms_max_imagesize"><?php echo _mb("Maximal amount of pixels (Registry)");?>:</label>
      		<input name="wms_max_imagesize" id="wms_max_imagesize" type="text"/>
		<img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("The amount of pixels is related to 1 dimension. It is asumed, that the image is square! If your server may serve a picture size of 2000x1000px please set this value to 1000. This value is needed to print bigger maps and allow INSPIRE download services of predefined datasets. If no value is given, the portal asumes a minimum of 1000px!");?>'}" src="../img/questionmark.png" alt="" />
	</p>
	<p>
		<label for="inspire_annual_requests"><?php echo _mb("Annually requests to service  (Registry)");?>:</label>
      		<input class="digits" name="inspire_annual_requests" id="inspire_annual_requests" type="text"/>
		<img class="metadata_img" title="<?php echo _mb("INSPIRE Monitoring: Annually requests to View Service");?>" src="../img/misc/inspire_eu_klein.png" alt="" />
		<img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Annually amount of requests to INSPIRE View Service. This value will be used to build the INSPIRE Monitoring information from mapbender registry!");?>'}" src="../img/questionmark.png" alt="" />
	</p>
	<p>
		<label for="wms_bequeath_licence_info"><?php echo _mb("Bequeath licence info from service to coupled dataset metadata (Registry)");?>:</label>
      		<input name="wms_bequeath_licence_info" id="wms_bequeath_licence_info" type="checkbox"/>
		<img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Bequeath licence information from this service to all coupled dataset metadata. This function activates the dataset metadata proxy and the url of the original metadata will be exchanged with a geoportal proxy url. Check this to have licences of service and dataset in sync. This maybe usefull for a better exchange of geo-metadata with open data catalogues/portals. <b>Important:</b> The service have to be updated after changing this flag!");?>'}" src="../img/questionmark.png" alt="" />
	</p>
	<p>
		<label for="wms_bequeath_contact_info"><?php echo _mb("Bequeath contact info from service to coupled dataset metadata (Registry)");?>:</label>
      		<input name="wms_bequeath_contact_info" id="wms_bequeath_contact_info" type="checkbox"/>
		<img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Bequeath contact information from this service to all coupled dataset metadata. This function activates the dataset metadata proxy and the url of the original metadata will be exchanged with a geoportal proxy url. Check this to have the contact information of service and dataset in sync. It will use the contact information of the geoportal organization role system instead of the contact information which originates from the dataset metadata. This maybe usefull for a better exchange of geo-metadata with open data catalogues/portals. <b>Important:</b> The service have to be updated after changing this flag!");?>'}" src="../img/questionmark.png" alt="" />
	</p>
</fieldset>
<fieldset>
	<legend><?php echo _mb("WMS Provider Section (OWS)");?></legend>
	<p>
		<label for="contactperson"><?php echo _mb("Contact Individual Name (OWS)");?>:</label>
    	<input name="contactperson" id="contactperson"/>
	</p>
	<p>
		<label for="contactposition"><?php echo _mb("Contact Position Name (OWS)");?>:</label>
    	<input name="contactposition" id="contactposition"/>
	</p>
	<p>
		<label for="contactvoicetelephone"><?php echo _mb("Contact Phone Voice (OWS)");?>:</label>
    	<input name="contactvoicetelephone" id="contactvoicetelephone"/>
	</p>
	<p>
		<label for="contactfacsimiletelephone"><?php echo _mb("Contact Phone Fax (OWS)");?>:</label>
    	<input name="contactfacsimiletelephone" id="contactfacsimiletelephone"/>
	</p>
	<p>
		<label for="contactorganization"><?php echo _mb("Contact Organisation (WMS)");?>:</label>
    	<input name="contactorganization" id="contactorganization"/>
    	<img  class="metadata_img" title="<?php echo _mb("INSPIRE 9.1: responsible party name");?>" src="../img/misc/inspire_eu_klein.png" alt="" />
	</p>
	<p>
		<label for="address"><?php echo _mb("Contact Address (WMS)");?>:</label>
    	<input name="address" id="address"/>
	</p>
	<p>
		<label for="city"><?php echo _mb("Contact City (WMS)");?>:</label>
     	<input name="city" id="city"/>
	</p>
	<p>
	  	<label for="stateorprovince"><?php echo _mb("Contact State or Province (WMS) - ISO 3166-II");?>:</label>
      	<input name="stateorprovince" id="stateorprovince"/>
	</p>
	<p>
	  	<label for="postcode"><?php echo _mb("Contact Post Code (WMS)");?>:</label>
      	<input name="postcode" id="postcode"/>
	</p>
	<p>
	  	<label for="country"><?php echo _mb("Contact Post Country (WMS) - ISO 3166");?>:</label>
      	<input name="country" id="country"/>
	</p>
	<p>
	  	<label for="contactelectronicmailaddress"><?php echo _mb("Contact Electronic Mail Address (WMS)");?>:</label>
      	<input name="contactelectronicmailaddress" id="contactelectronicmailaddress" class="required email"/>
     	<img class="metadata_img" title="<?php echo _mb("INSPIRE 9.1: responsible party email");?>" src="../img/misc/inspire_eu_klein.png" alt="" />
	</p>
	<p>
	  	<label for="wms_timestamp_create"><?php echo _mb("Date of first registration (Registry)");?>:</label>
      	<input readonly="readonly" name="wms_timestamp_create" id="wms_timestamp_create"/>
      	<img class="metadata_img" title="<?php echo _mb("INSPIRE 5.2: date of publication");?>" src="../img/misc/inspire_eu_klein.png" alt="" />
	</p>
	<p>
	  	<label for="wms_timestamp"><?php echo _mb("Date of last revision (Registry)");?>:</label>
      	<td><input readonly="readonly" name="wms_timestamp" id="wms_timestamp"/>
      	<img class="metadata_img" title="<?php echo _mb("INSPIRE 10.2: metadata date");?>" src="../img/misc/inspire_eu_klein.png" alt="" />
	</p>
</fieldset>
<fieldset>
	<legend><?php echo _mb("Metadata Point of contact (registry)");?>: <img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Information about the organization which is responsible for contributing the metadata. The information will be automaticcaly generated from the mapbender database mb_group object. The information came from the primary group of the service owner or from a group which has authorized the owner to publish metadata in their name.");?>'}" src="../img/questionmark.png" alt="" /></legend>

<?php
//selectbox for organizationswhich allows the publishing of metadatasets for the specific user
	$sql = "SELECT fkey_mb_group_id, mb_group_name FROM (SELECT fkey_mb_group_id FROM mb_user_mb_group WHERE fkey_mb_user_id = $1 AND (mb_user_mb_group_type = 3 OR mb_user_mb_group_type = 2)) AS a LEFT JOIN mb_group ON a.fkey_mb_group_id = mb_group.mb_group_id";
	$user = new User();
	$userId = $user->id;
	$v = array($userId);
	$t = array('i');
	$res = db_prep_query($sql,$v,$t);
	$metadataGroup = array();
	while ($row = db_fetch_assoc($res)) {
		$metadataGroup[$row["fkey_mb_group_id"]] = $row["mb_group_name"];
	}
?>
	<p>
		<label for="mb_group_name"><?php echo _mb("Organization responsible for metadata");?>:</label>
    	<select name="fkey_mb_group_id" id="fkey_mb_group_id" onChange="var chosenoption=this.options[this.selectedIndex];$('#mb_md_edit').mapbender().fillMdContact(chosenoption.value);">
			<option value="0">...</option>
<?php
	foreach ($metadataGroup as $key => $value) {
		echo "<option value='" . $key . "'>" . htmlentities($value, ENT_QUOTES, CHARSET) . "</option>";
	}
?>
		</select>
    	<img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Selection of different organizations which authorized you to publish metadata in their name.");?>'}" src="../img/questionmark.png" alt="" />
	</p>
<!-- end of selection for the different organizations -->

	<p>
		<label for="mb_group_title"><?php echo _mb("Title");?>:</label>
		<input readonly="readonly" name="mb_group_title" id="mb_group_title"/>
		<img class="metadata_img" title="<?php echo _mb("INSPIRE 10.1: metadata point of contact name");?>" src="../img/misc/inspire_eu_klein.png" alt="" />
	</p>
	<p>
		<label for="mb_group_address"><?php echo _mb("Address");?>:</label>
		<input readonly="readonly" name="mb_group_address" id="mb_group_address"/>
	</p>
	<p>
		<label for="mb_group_postcode"><?php echo _mb("Postcode");?>:</label>
		<input readonly="readonly" name="mb_group_postcode" id="mb_group_postcode"/>
	</p>
	<p>
		<label for="mb_group_city"><?php echo _mb("City");?>:</label>
		<input readonly="readonly" name="mb_group_city" id="mb_group_city"/>
	</p>
	<p>
		<label for="mb_group_voicetelephone"><?php echo _mb("Telephone");?>:</label>
		<input readonly="readonly" name="mb_group_voicetelephone" id="mb_group_voicetelephone"/>
	</p>
	<p>
		<label for="mb_group_email"><?php echo _mb("Email");?>:</label>
		<input readonly="readonly" name="mb_group_email" id="mb_group_email"/>
		<img class="metadata_img" title="<?php echo _mb("INSPIRE 10.1: metadata point of contact email");?>" src="../img/misc/inspire_eu_klein.png" alt="" />
	</p>
	<p>
		<label for="mb_group_logo_path"><?php echo _mb("Logo url");?>:</label>
		<input readonly="readonly" name="mb_group_logo_path" id="mb_group_logo_path"/>
	</p>

</fieldset>
