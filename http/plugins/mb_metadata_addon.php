<?php
	require_once dirname(__FILE__) . "/../../core/globalSettings.php";
	require_once(dirname(__FILE__) . "/../../conf/mimetype.conf");

	function displayCategories ($sql) {
		if (Mapbender::session()->get("mb_lang") === "de") {
			$sql = str_replace("category_code_en", "category_code_de", $sql);
		}
		$str = "";
		$res = db_query($sql);
		while ($row = db_fetch_assoc($res)) {
			$str .= "<option value='" . $row["id"] . "'>" . 
				htmlentities($row["name"], ENT_QUOTES, CHARSET) . 
				"</option>";
		}
		return $str;
	}
	function displayCustomCategories ($sql) {
	    if (Mapbender::session()->get("mb_lang") === "de") {
	        $sql = str_replace("custom_category_description_en", "custom_category_description_de", $sql);
	    }
	    $str = "";
	    $res = db_query($sql);
	    while ($row = db_fetch_assoc($res)) {
	        $str .= "<option value='" . $row["id"] . "'>" .
	   	        htmlentities($row["name"], ENT_QUOTES, CHARSET) .
	   	        "</option>";
	    }
	    return $str;
	}
?>

<script type="text/javascript">
$(function() {
	$("#tabs").tabs({ active: 1 })
});

if (typeof regionData === 'undefined') {
    var regionData = [
        { "name": "Land Hessen", "minx": 7.7724673, "miny": 49.3952723, "maxx": 10.2364142, "maxy": 51.6577888 },
        { "name": "Kreisfreie Stadt Darmstadt", "minx": 8.5581615, "miny": 49.795668, "maxx": 8.7498738, "maxy": 49.9538042 },
        { "name": "Kreisfreie Stadt Frankfurt am Main", "minx": 8.4727605, "miny": 50.0153529, "maxx": 8.8004049, "maxy": 50.2271424 },
        { "name": "Kreisfreie Stadt Offenbach am Main", "minx": 8.7224425, "miny": 50.0468179, "maxx": 8.8427322, "maxy": 50.1373991 },
        { "name": "Landeshauptstadt Wiesbaden", "minx": 8.1106718, "miny": 49.9932374, "maxx": 8.3861621, "maxy": 50.1518097 },
        { "name": "Landkreis Bergstraße", "minx": 8.3547184, "miny": 49.3952723, "maxx": 8.9516627, "maxy": 49.7549725 },
        { "name": "Landkreis Darmstadt-Dieburg", "minx": 8.5198584, "miny": 49.7222217, "maxx": 9.050527, "maxy": 50.0019389 },
        { "name": "Landkreis Groß-Gerau", "minx": 8.2907728, "miny": 49.7167083, "maxx": 8.6303116, "maxy": 50.0838616 },
        { "name": "Hochtaunuskreis", "minx": 8.315463, "miny": 50.1556196, "maxx": 8.7123607, "maxy": 50.4151701 },
        { "name": "Main-Kinzig-Kreis", "minx": 8.779774, "miny": 50.0671687, "maxx": 9.7488228, "maxy": 50.4635019 },
        { "name": "Main-Taunus-Kreis", "minx": 8.3176451, "miny": 49.9965095, "maxx": 8.6027339, "maxy": 50.2027387 },
        { "name": "Odenwaldkreis", "minx": 8.7844421, "miny": 49.4705521, "maxx": 9.1505795, "maxy": 49.8563463 },
        { "name": "Landkreis Offenbach", "minx": 8.591374, "miny": 49.9465204, "maxx": 9.0513656, "maxy": 50.1333751 },
        { "name": "Rheingau-Taunus-Kreis", "minx": 7.7724673, "miny": 49.9718445, "maxx": 8.4114872, "maxy": 50.2960947 },
        { "name": "Wetteraukreis", "minx": 8.5141314, "miny": 50.1621976, "maxx": 9.2903794, "maxy": 50.4998794 },
        { "name": "Landkreis Gießen", "minx": 8.5192258, "miny": 50.4158743, "maxx": 9.1344183, "maxy": 50.7093332 },
        { "name": "Lahn-Dill-Kreis", "minx": 8.1098772, "miny": 50.3951233, "maxx": 8.6513325, "maxy": 50.8829177 },
        { "name": "Landkreis Limburg-Weilburg", "minx": 7.9640111, "miny": 50.2636458, "maxx": 8.4673504, "maxy": 50.5996859 },
        { "name": "Landkreis Marburg-Biedenkopf", "minx": 8.3546475, "miny": 50.6737656, "maxx": 9.150879, "maxy": 50.9968235 },
        { "name": "Vogelsbergkreis", "minx": 8.9059715, "miny": 50.3854959, "maxx": 9.6501385, "maxy": 50.8366276 },
        { "name": "Kreisfreie Stadt Kassel", "minx": 9.3510229, "miny": 51.2603806, "maxx": 9.5700842, "maxy": 51.369403 },
        { "name": "Landkreis Fulda", "minx": 9.4272478, "miny": 50.3561445, "maxx": 10.0830766, "maxy": 50.8095215 },
        { "name": "Landkreis  Hersfeld-Rotenburg", "minx": 9.4328317, "miny": 50.7174394, "maxx": 10.0648471, "maxy": 51.085492 },
        { "name": "Landkreis Kassel", "minx": 9.092857, "miny": 51.1704232, "maxx": 9.761612, "maxy": 51.6577888 },
        { "name": "Schwalm-Eder-Kreis", "minx": 8.9728688, "miny": 50.7728747, "maxx": 9.7811284, "maxy": 51.2576257 },
        { "name": "Landkreis Waldeck-Frankenberg", "minx": 8.4732746, "miny": 50.9354961, "maxx": 9.2218375, "maxy": 51.5189199 },
        { "name": "Werra-Meißner-Kreis", "minx": 9.6237862, "miny": 50.992088, "maxx": 10.2364142, "maxy": 51.4210554 },
]};

// Function to populate the multiselect-box with options
function populateRegionOptions() {
        let selectBox = document.getElementById('regions');
        regionData.forEach((region) => {
                let option = document.createElement('option');
                option.value = JSON.stringify(region);
                option.textContent = region.name;
                selectBox.appendChild(option);
        });
}

// Function to calculate the extent of selected regions
function calculateExtent() {
        let selectBox = document.getElementById('regions');
        let selectedOptions = Array.from(selectBox.selectedOptions);

        if (selectedOptions.length === 0) {
                // If no options are selected, use the default extent
                document.getElementById('west').value = -180;
                document.getElementById('south').value = -90;
                document.getElementById('east').value = 180;
                document.getElementById('north').value = 90;
                return; // Exit early
        }

        let extent = { minx: Infinity, miny: Infinity, maxx: -Infinity, maxy: -Infinity };

        selectedOptions.forEach((option) => {
        let region = JSON.parse(option.value);
        extent.minx = Math.min(extent.minx, region.minx);
        extent.miny = Math.min(extent.miny, region.miny);
        extent.maxx = Math.max(extent.maxx, region.maxx);
        extent.maxy = Math.max(extent.maxy, region.maxy);
        });

        // Set the input fields with the bounding box coordinates
        document.getElementById('west').value = extent.minx;
        document.getElementById('south').value = extent.miny;
        document.getElementById('east').value = extent.maxx;
        document.getElementById('north').value = extent.maxy;
}

// Call the function to populate the multiselect-box on page load

populateRegionOptions();

</script>
<div class="demo">

<fieldset class="ui-widget" id="metadataUrlEditor" name="metadataUrlEditor" type="hidden" style="display: none">
<input name="kindOfMetadataAddOn" id="kindOfMetadataAddOn" type="hidden" value="" />
	<fieldset id="addonChooser" name="addonChooser" style="display: none">
		<legend><?php echo _mb("Choose kind of coupled metadata");?></legend>
		<p>
			<table><tr><td><img class='clickable' src='../img/osgeo_graphics/geosilk/link_mae.png' title='linkage' onclick='$("#addonChooser").css("display","none");$("#link_editor").css("display","block");$("#kindOfMetadataAddOn").attr("value","link");' /></td><td><?php echo _mb("Add URL to existing Metadataset");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Here someone can add a url to an existing metadata record, which is available over www. The record can either be harvested and pushed to the own catalogue service or it is only used as a link. This links are pushed into the service metadata record and the new capabilities document.");?>'}" src="../img/questionmark.png" alt="" /></td></tr>
			<tr><td><img  class='clickable' src='../img/gnome/edit-select-all.png' title='metadata'  onclick='$("#addonChooser").css("display","none");$("#simple_metadata_editor").css("display","block");$("#kindOfMetadataAddOn").attr("value","metadataset");' /></td><td><?php echo _mb("Add a simple metadata record which is mostly generated from given layer information");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("With this option someone can generate a very simple metadata record for the data which is distributed thru the wms layer. The record fulfills only the INSPIRE Metadata Regulation! Most of the needed data is pulled from the service, layer and group information of the owner of the service. The metadate will be created on the fly. It is not stored in the database!");?>'}" src="../img/questionmark.png" alt="" /></td></tr>
			<tr><td><img  class='clickable' onclick='initUploadForm();' src='../img/button_blue_red/up_mae.png' id='uploadImage' title='upload'  /></td><td><?php echo _mb("Add a simple metadata record from a local file");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("With this option someone can upload an existing metadata record and couple it to the layer. The uploaded data is pushed to the catalogue and will be available for searching. The uploaded data is not fully controlled and validated. It cannot be edited but must be replaced with a new record if needed!");?>'}" src="../img/questionmark.png" alt="" /></td></tr>
			<tr id="internalLinkage"><td><img  class='clickable' src='../img/osgeo_graphics/geosilk/link_mae.png' title='linkage' onclick='getOwnedMetadata();$("#addonChooser").css("display","none");$("#internal_link").css("display","block");$("#kindOfMetadataAddOn").attr("value","internallink");' /></td><td><?php echo _mb("Linkage to existing internal Metadataset");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Here someone can add a linkage to an existing internal metadata record, which is also available over www. This link is also pushed into the service metadata record and the new capabilities document.");?>'}" src="../img/questionmark.png" alt="" /></td></tr>
			</table>
		</p>
	</fieldset>
	<!--fieldset for save link form-->
	<fieldset id="link_editor" name="link_editor" type="hidden" style="display: none">
		<legend><?php echo _mb("Link Editor");?></legend>
		<input name="link" id="link" />
		<p>
			<label for="export2csw"><?php echo _mb("Harvest link target and export to CSW");?></label>
      			<input name="export2csw" id="export2csw" type="checkbox" checked="checked"/>
		</p>
		
	</fieldset>
	<!--fieldset for internal link form-->
	<fieldset id="internal_link" name="internal_link" type="hidden" style="display: none">
		<legend><?php echo _mb("Internal Link");?></legend>
		<select name="internal_relation" id="internal_relation" /></select>
		
	</fieldset>
	<!--fieldset for upload metadata form-->
	<fieldset id="metadata_upload" name="metadata_upload" type="hidden" style="display: none">
		<legend><?php echo _mb("Metadata upload");?></legend>
		<input name="metadatafile" id="metadatafile" type="file"/>
	</fieldset>
	<!--div for custom tree selector-->
	<!--<div id="custom_tree_selector_dialog" name="custom_tree_selector_dialog" type="hidden" style="display: none">
		<legend><?php //echo _mb("Select categories from hierarchical tree");?></legend>
		<input type="text" id="plugins4_q" value="" class="input" style="margin:0em auto 1em auto; display:block; padding:4px; border-radius:4px; border:1px solid silver;">
		<div id="jstree_custom_categories_div"></div>
	</div>-->
	<!--fieldset for metadata form-->
	<fieldset id="simple_metadata_editor" name="simple_metadata_editor" type="hidden"  style="display: none">
	<div id="tabs">
		<ul>
			<li><a href="#tabs-1"><?php echo _mb("Identification");?></a></li>
			<li><a href="#tabs-2"><?php echo _mb("Classification");?></a></li>
			<li><a href="#tabs-3"><?php echo _mb("Temporal extent");?></a></li>
			<li><a href="#tabs-4"><?php echo _mb("Quality");?></a></li>
			<li><a href="#tabs-5"><?php echo _mb("Spatial Extent");?></a></li>
			<li><a href="#tabs-6"><?php echo _mb("Download");?></a></li>
			<!--<li><a href="#tabs-7"><?php echo _mb("Covering Area");?></a></li>-->
			<li><a href="#tabs-8"><?php echo _mb("Licenses/Constraints");?></a></li>
			<li><a href="#tabs-9"><?php echo _mb("Responsible Party");?></a></li>
			<li><a href="#tabs-10"><?php echo _mb("Preview");?></a></li>
			<li><a href="#tabs-11"><?php echo _mb("Others");?></a></li>
		</ul>
		<!--<legend><?php echo _mb("Simple metadata editor");?></legend>-->
		<div id="tabs-1">
		<fieldset>
			<legend><?php echo _mb("Resource title");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("This a characteristic, and often unique, name by which the resource is known.");?>'}" src="../img/questionmark.png" alt="" /></legend>
			<input class="required" name="title" id="title"/>
		</fieldset>
		<fieldset>
			<legend><?php echo _mb("Resource alternate title");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Optional alternate title. Only one entry is supported at the moment!");?>'}" src="../img/questionmark.png" alt="" /></legend>
			<input name="alternate_title" id="alternate_title"/>
		</fieldset>
		<fieldset>
			<legend><?php echo _mb("Resource abstract");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("This is a brief narrative summary of the content of the resource.");?>'}" src="../img/questionmark.png" alt="" /></legend>
			<input class="required" name="abstract" id="abstract"/>
		</fieldset>
		<fieldset id="data_format">
		<legend><?php echo _mb("Encoding");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Description of the computer language construct(s) specifying the representation of data objects in a record, file, message, storage device or transmission channel.");?>'}" src="../img/questionmark.png" alt="" /></legend>
		<select class="required format_selectbox" id='format' name='format'>
			<!--Format List from conf file -->
			<?php 	$str ="";
				foreach ($formats as $format) {
					$str .= "<option value='" . $format . "'>"._mb($format)."</option>";
					
				}
				echo $str;
			?>
		</select>
		</fieldset>
		<fieldset id="charset">
			<legend><?php echo _mb("Character Encoding");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("The character encoding used in the data set. This element is mandatory only if an encoding is used that is not based on UTF-8.");?>'}" src="../img/questionmark.png" alt="" /></legend>
			<select class="required charset_selectbox" id='inspire_charset' name='inspire_charset'>
				<option value="utf8"><?php echo _mb("utf8");?></option>
				<option value="latin1"><?php echo _mb("latin1");?></option>
			</select>
		</fieldset>
		<fieldset id="referencesystem">
			<legend><?php echo _mb("Coordinate Reference System");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Description of the coordinate reference system(s) used in the data set.");?>'}" src="../img/questionmark.png" alt="" /></legend>
<?php
	if (defined('SRS_ARRAY')) {
			$srs_array = explode(",", SRS_ARRAY);
			echo '<select class="required ref_system_selectbox" name="ref_system" id="ref_system">';
			foreach ($srs_array as $epsg) {
				echo "<option value='" . "EPSG:" .$epsg . "'>" . _mb("EPSG:".$epsg) . "</option>";
			}
			echo "</select>";
		
	} else {
		echo '<input name="ref_system" id="ref_system"/>';
	}
?>
		</fieldset>
	</div>
	<div id="tabs-2">
	<fieldset id="md_classification">
		<legend><?php echo _mb("Classification");?></legend>
		<fieldset>
			<legend><?php echo _mb("Keywords");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Define a list of comma separated keywords which helps to classify the dataset.");?>'}" src="../img/questionmark.png" alt="" /></legend>
			<input class="" name="keywords" id="keywords"/>
		</fieldset>
		<p>
		    <label for="md_md_topic_category_id" class="label_classification"><?php echo _mb("ISO Topic Category");?>:</label>
			<img class="metadata_img" title="<?php echo _mb("Inspire");?>" src="../img/misc/inspire_eu_klein.png" alt="" />
			<select class="metadata_selectbox" id="md_md_topic_category_id" name="md_md_topic_category_id" size="2" multiple="multiple">
<?php
	$sql = "SELECT md_topic_category_id AS id, md_topic_category_code_en AS name FROM md_topic_category";
	echo displayCategories($sql);
?>
			</select>
			<img id="resetIsoTopicCatsMd" title="<?php echo _mb("Reset selection");?>" src="../img/cross.png" style="cursor:pointer;"/>
		</p>
		<p>
		    <label for="md_inspire_category_id" class="label_classification"><?php echo _mb("INSPIRE Category");?>:</label>
			<img class="metadata_img" title="<?php echo _mb("Inspire");?>" src="../img/misc/inspire_eu_klein.png" alt="" />
			<select class="metadata_selectbox" id="md_inspire_category_id" name="md_inspire_category_id" size="2" multiple="multiple">
<?php
	$sql = "SELECT inspire_category_id AS id, inspire_category_code_en AS name FROM inspire_category";
	echo displayCategories($sql);
?>
			</select>
			<img id="resetInspireCatsMd" title="<?php echo _mb("Reset selection");?>" src="../img/cross.png" style="cursor:pointer;"/>
		</p>
		<p>
		    <label for="md_custom_category_id" class="label_classification"><?php echo _mb("Custom Category");?>:</label>
			<span class="metadata_span"></span>
			<select  class="metadata_selectbox" id="md_custom_category_id" name="md_custom_category_id" size="2" multiple="multiple">
<?php
	$sql = "SELECT custom_category_id AS id, custom_category_code_en AS name FROM custom_category";
	echo displayCategories($sql);
	//new 2020-08-26 - use custom_category_description uinstead of ...code
	//$sql = "SELECT custom_category_id AS id, custom_category_description_en AS name FROM custom_category WHERE deletedate IS NULL";
	//echo displayCustomCategories($sql);
?>
			</select>
			<img id="resetCustomCatsMd" title="<?php echo _mb("Reset selection");?>" src="../img/cross.png" style="cursor:pointer;"/>
			<!-- <img id="startCustomTreeSelector" title="<?php //echo _mb("Select from hierachical structure");?>" src="../img/expanded_folder.png" style="cursor:pointer;" onclick='openCustomTreeSelector()';/>-->
			
		</p>
	</fieldset>
	</div>
	<div id="tabs-3">
	<fieldset id="tempref" name="tempref">
		<legend><?php echo _mb("TEMPORAL REFERENCE");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("This metadata element addresses the requirement to have information on the temporal dimension of the data as referred to in Article 8(2)(d) of Directive 2007/2/EC. At least one of the metadata elements referred to in points 5.1 to 5.4 shall be provided. The value domain of the metadata elements referred to in points 5.1 to 5.4 is a set of dates. Each date shall refer to a temporal reference system and shall be expressed in a form compatible with that system. The default reference system shall be the Gregorian calendar, with dates expressed in accordance with ISO 8601.");?>'}" src="../img/questionmark.png" alt="" /></legend>
		<fieldset id="timespan" name="timespan">
			<legend><?php echo _mb("Temporal extent");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("The temporal extent defines the time period covered by the content of the resource. This time period may be expressed as any of the following: - an individual date, - an interval of dates expressed through the starting date and end date of the interval, - a mix of individual dates and intervals of dates.");?>'}" src="../img/questionmark.png" alt="" /></legend>
			<p><?php echo _mb("from");?>:</p><input class="required hasdatepicker" name="tmp_reference_1" id="tmp_reference_1"/><br>
			<p><?php echo _mb("to");?>:</p><input class="required hasdatepicker" name="tmp_reference_2" id="tmp_reference_2"/>
		</fieldset>
		<fieldset id="cyclicupdate" name="cyclicupdate">
			<legend><?php echo _mb("Maintenance and update frequency");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Frequency with which changes and additions are made to the resource after the initial resource is completed. Notice: This value may change the value of the end date of temporal extent. The end date will be computed automatically from the current timestamp if a cyclic update is defined!");?>'}" src="../img/questionmark.png" alt="" /></legend>
				<select class="required cyclic_selectbox" id='update_frequency' name='update_frequency'>
<!-- B.5.18 MD_MaintenanceFrequencyCode <<CodeList>> of ISO19115 -->					<option value="continual"><?php echo _mb("continual");?></option>
					<option value="daily"><?php echo _mb("daily");?></option>
					<option value="weekly"><?php echo _mb("weekly");?></option>
					<option value="fortnightly"><?php echo _mb("fortnightly");?></option>
					<option value="monthly"><?php echo _mb("monthly");?></option>
					<option value="quarterly"><?php echo _mb("quarterly");?></option>
					<option value="biannually"><?php echo _mb("biannually");?></option>
					<option value="annually"><?php echo _mb("annually");?></option>
					<option value="asNeeded"><?php echo _mb("as needed");?></option>
					<option value="irregular"><?php echo _mb("irregular");?></option>

					<option value="notPlanned"><?php echo _mb("not planned");?></option>

					<option value="unknown"><?php echo _mb("unknown");?></option>
				</select>
		</fieldset>
	</fieldset>
	</div>
	<div id="tabs-4">
		<fieldset>
			<legend><?php echo _mb("Lineage");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("This is a statement on process history and/or overall quality of the spatial data set. Where appropriate it may include a statement whether the data set has been validated or quality assured, whether it is the official version (if multiple versions exist), and whether it has legal validity. The value domain of this metadata element is free text.");?>'}" src="../img/questionmark.png" alt="" /></legend>
			<input class="required" name="lineage" id="lineage"/>
		</fieldset>
		<fieldset id="spatialres">
		<legend><?php echo _mb("Spatial resolution");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Spatial resolution refers to the level of detail of the data set. It shall be expressed as a set of zero to many resolution distances (typically for gridded data and imagery-derived products) or equivalent scales (typically for maps or map-derived products). An equivalent scale is generally expressed as an integer value expressing the scale denominator. A resolution distance shall be expressed as a numerical value associated with a unit of length.");?>'}" src="../img/questionmark.png" alt="" /></legend>
				<input class="required radioRes" name="spatial_res_type" id="spatial_res_type" type="radio"/ value="groundDistance" checked="checked"><?php echo _mb("Ground distance in [m]");?><br>
				<input class="required radioRes" name="spatial_res_type" id="spatial_res_type" type="radio"/ value="scaleDenominator"><?php echo _mb("Scale denominator [1:X]");?><br>
			<label><?php echo _mb("Value of resolution");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Value of spatial resolution in [m] or scale denominator");?>'}" src="../img/questionmark.png" alt="" /></label>
			<input class="required" name="spatial_res_value" id="spatial_res_value"/>
		</fieldset>
		<!-- Dropdown List of CRS which are defined in mapbender.conf. For those CRS the layer extents will be computed by mapbender - this is relevant for INSPIRE.-->
		<fieldset id="interoperability">
			<legend><?php echo _mb("INSPIRE interoperability");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Flag which show, that the provided dataset should be compliant to the Interoperability Regulation. This means, that the data itself is provided in the INSPIRE data model.");?>'}" src="../img/questionmark.png" alt="" /></legend>
			<input name="inspire_interoperability" id="inspire_interoperability" type="checkbox"/>
		</fieldset>
		<fieldset id="consistance">
		<legend><?php echo _mb("Topological Consistency");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Correctness of the explicitly encoded topological characteristics of the data set as described by the scope. This element is mandatory only if the data set includes types from the Generic Network Model and does not assure centreline topology (connectivity of centrelines) for the network.");?>'}" src="../img/questionmark.png" alt="" /></legend>
		<input name="inspire_top_consistence" id="inspire_top_consistence" type="checkbox"/>
		</fieldset>
	
	</div>
	<div id="tabs-5">
		<fieldset>
			<legend><?php echo _mb("Bounding Box");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("This is the bounding box of the dataset given in geographic coordinates in WGS84 (EPSG:4326). The bounding box may be inherited by the calling service contents bounding box (layer/featuretype) or defined by the registrating person later on.");?>'}" src="../img/questionmark.png" alt="" /></legend>
		<label for="west">
			<?php echo _mb("West [decimal degrees]");?>
			<input class="required" name="west" id="west"/>
		</label><br />
		<label for="south">
			<?php echo _mb("South [decimal degrees]");?>
			<input class="required" name="south" id="south"/>
		</label><br />
		<label for="east">
			<?php echo _mb("East [decimal degrees]");?>
			<input class="required" name="east" id="east"/>
		</label><br />
		<label for="north">
			<?php echo _mb("North [decimal degrees]");?>
			<input class="required" name="north" id="north"/>
		</label>
		</fieldset>
		<fieldset>
		<legend>Select Regions<img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("");?>Wählen Sie eine Verwaltungseinheit aus - Land oder Landkreis. Wenn Sie mehrere Landkreise auswählen, wird die Ausdehnung entsprechend berechnet und eingefügt'}" src="../img/questionmark.png" alt="" /></legend>
		<select multiple id="regions" name="regions">
		<!-- Options will be added dynamically using JavaScript at the top -->
		</select>
		<button type="button" onclick="calculateExtent()">Calculate Extent</button>
	    </fieldset>
		<fieldset>
			<legend><?php echo _mb("User defined region");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("You can define your own bounding box or region if you upload an gml geometry object. Only bbox and polygons are accepted at the moment!");?>'}" src="../img/questionmark.png" alt="" /></legend>
			<table id='geometryuploadtable' name='geometryuploadtable'><tr><td><img id="uploadgmlimage" name= "uploadgmlimage" onclick='initUploadGmlForm();' src='../img/button_blue_red/up.png' id='uploadImage' title='upload'  /></td><td><?php echo _mb("Upload a surronding geometry for this dataset");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Help for geometry upload possibility");?>'}" src="../img/questionmark.png" alt="" /><img class="delete_polygon" id="delete_existing_polygon" onclick="" name="delete_existing_polygon" src='../img/cross.png' type="hidden" style="display: none" title="<?php echo _mb("Delete actual polygon");?>"/></td></tr></table>
		</fieldset>
		<fieldset>
			<legend><?php echo _mb("Extent on map");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Here you can see the extent and a possibly given surrounding polygon on an overview map.");?>'}" src="../img/questionmark.png" alt="" /></legend>
			<img id="extent_preview" src="" title="<?php echo _mb("Preview for Extent - if available");?>" alt="<?php echo _mb("Preview for Extent - if available");?>"/>
		</fieldset>
	</div>
	<div id="tabs-6">
		<fieldset>
		<label for="downloadlink">
			<?php echo _mb("Download link to dataset");?>
		</label>
		<input class="" name="downloadlink" id="downloadlink"/><br>
		<p>
			<?php echo _mb("Enable INSPIRE DLS");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Activating this checkbox will enable the generation of an INSPIRE DLS based on ATOM feed for the given link!");?>'}" src="../img/questionmark.png" alt="" />
			<input name="inspire_download" id="inspire_download" type="checkbox"/>
		</p>
		</fieldset>
		<fieldset>
			<legend><?php echo _mb("Further links (json)");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("JSON field to support special further links.");?>'}" src="../img/questionmark.png" alt="" /></legend>
			<textarea name="further_links_json" id="further_links_json" rows="4" cols="50"/>
		</fieldset>
	</div>
	<!-- <div id="tabs-7">
		<fieldset>
			<legend><?php echo _mb("Relevant area (km<sup>2</sup> - integer)");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("INSPIRE Monitoring: Area which is to be covered by the spatial dataset expressed in km<sup>2</sup>.");?>'}" src="../img/questionmark.png" alt="" /></legend>
			<input name="inspire_whole_area" id="inspire_whole_area"/>
		</fieldset>
		<fieldset>
			<legend><?php echo _mb("Actual area (km<sup>2</sup> - integer)");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("INSPIRE Monitoring: Area which is covered by the spatial dataset expressed in km<sup>2</sup>.");?>'}" src="../img/questionmark.png" alt="" /></legend>
			<input name="inspire_actual_coverage" id="inspire_actual_coverage"/>
		</fieldset>
	</div>-->
	<div id="tabs-8">
	<p>
		<label for="fees_md"><?php echo _mb("Conditions applying to access and use");?>:</label>
    		<input name="fees_md" id="fees_md"/><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("INSPIRE 8.1: conditions applying to access and use");?>'}" src="../img/questionmark.png" alt="" />
	</p>
	<p>
		<label for="accessconstraints_md"><?php echo _mb("Limitations on public access");?>:</label>
		<input name="accessconstraints_md" id="accessconstraints_md"/><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("INSPIRE 8.2: limitations on public access");?>'}" src="../img/questionmark.png" alt="" />

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
	</p>
	
<?php
		$sql = "SELECT termsofuse_id, name FROM termsofuse";
		$res = db_query($sql);
		$termsofuse = array();
		while ($row = db_fetch_assoc($res)) {
			$termsofuse[$row["termsofuse_id"]] = $row["name"];
		}
?>
		<p>
			<label for="md_termsofuse"><?php echo _mb("MD predefined license");?>:</label>
    			<select class="termsofuse_selectbox" name="md_termsofuse" id="md_termsofuse" onChange="var chosenoption=this.options[this.selectedIndex];$('#mb_md_showMetadataAddon').mapbender().fillLicence(chosenoption.value);">
				<option value='0'>...</option>
<?php
			foreach ($termsofuse as $key => $value) {
				echo "<option value='" . $key . "'>" . htmlentities($value, ENT_QUOTES, CHARSET) . "</option>";
			}
?>
			</select>
    			<img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Selection of predefined licences.");?>'}" src="../img/questionmark.png" alt="" />
		</p>
	<p id="license_info_md">
	<img id="licence_symbol_md">&nbsp;<a id='licence_descriptionlink_md' target='_blank'><a/>&nbsp;<img id="open_symbol_md">
	</p>
	<p id="license_source_md">
		<label for="md_license_source_note"><?php echo _mb("Source note if license require it");?>:</label>
      		<input name="md_license_source_note" id="md_license_source_note" type="text"/>
		<img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Source note that should be mentioned if such an information is required by the license.");?>'}" src="../img/questionmark.png" alt="" />
	</p>
	<p>
	</div>
	<div id="tabs-9">
		<label for="check_overwrite_responsible_party"><?php echo _mb("Overwrite responsible party information");?>:</label>
		<input name="check_overwrite_responsible_party" id="check_overwrite_responsible_party" type="checkbox"/><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("INSPIRE 9.1: Information about the responsible party (name, email). This is normally pulled directly from the mapbender registry on the fly. If you want to give other information, you can do it here!");?>'}" src="../img/questionmark.png" alt="" />
		<p>
		<label id="label_responsible_party_name" for="responsible_party_name"><?php echo _mb("Responsible party name");?>:</label>
      		<input name="responsible_party_name" id="responsible_party_name" type="text"/>
		</p>
		<p>
		<label id="label_responsible_party_email"  for="responsible_party_email"><?php echo _mb("Responsible party email");?>:</label>
      		<input name="responsible_party_email" id="responsible_party_email" type="text"/>
		</p>
	</div>
	<div id="tabs-10">
		<fieldset>
			
			<!--<table id='previewuploadtable' name='previewuploadtable'><tr><td><img id="upload_preview_image" name= "upload_preview_image" onclick='initUploadImageForm();' src='../img/button_blue_red/up.png' title='Upload'  /></td><td><?php echo _mb("Upload a preview image for the dataset. Does only make sense for raster files.");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Help for upload preview image");?>'}" src="../img/questionmark.png" alt="" /><img class="delete_preview_image" id="delete_existing_preview_image" onclick="" name="delete_existing_preview_image" src='../img/cross.png' type="hidden" style="display: none" title="<?php echo _mb("Delete actual preview image");?>"/></td></tr></table>-->
				<input name="preview_image" id="preview_image" type="text"/><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Here you can give a url to an image that should be shown");?>'}" src="../img/questionmark.png" alt="" />
		</fieldset>
	</div>
	<div id="tabs-11">
		<fieldset>
			<legend><?php echo _mb("Searchability");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Here you can define, if the metadata should be searchable in the geoportal catalogue.");?>'}" src="../img/questionmark.png" alt="" /></legend>
			<input name="searchable" id="searchable" type="checkbox"/>
		</fieldset>
		<fieldset>
			<legend><?php echo _mb("Metadata export");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Harvest link target and export to CSW");?>'}" src="../img/questionmark.png" alt="" /></legend>
			<input name="export2csw2" id="export2csw2" type="checkbox"/>
		</fieldset>
	</div>
	</div><!--accordion-->
	</div><!--demo-->
	</fieldset>

</fieldset>

