<?php
require_once dirname(__FILE__) . "/../../core/globalSettings.php";
require_once(dirname(__FILE__) . "/../../conf/mimetype.conf");
require_once(dirname(__FILE__) . "/../classes/class_user.php");
require_once(dirname(__FILE__) . "/../classes/class_administration.php");

$admin = new administration();
$mapviewers = $admin->listMapviewerTypes();
 
$mapbenderUrl = $admin->getMapviewerInvokeUrl(1);

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
	let extent = selectedOptions.reduce((acc, option) => {
		let region = JSON.parse(option.value);
		acc.minx = Math.min(acc.minx, region.minx);
		acc.miny = Math.min(acc.miny, region.miny);
		acc.maxx = Math.max(acc.maxx, region.maxx);
		acc.maxy = Math.max(acc.maxy, region.maxy);
		return acc;
	}, { minx: Infinity, miny: Infinity, maxx: -Infinity, maxy: -Infinity });
	// Set the input fields with the bounding box coordinates
	document.getElementById('west').value = extent.minx;
	document.getElementById('south').value = extent.miny;
	document.getElementById('east').value = extent.maxx;
	document.getElementById('north').value = extent.maxy;
}

// Call the function to populate the multiselect-box on page load
populateRegionOptions();

//var mapbenderUrl = "<?php echo $mapbenderUrl;?>";
</script>
<div class="demo" id="mainDiv" mapbender_url="<?php echo $mapbenderUrl;?>">
    <!--fieldset for metadata form-->
    <fieldset id="simple_metadata_editor" name="simple_metadata_editor" type="hidden">
    <div id="tabs">
	<ul>
	    <li><a href="#tabs-1"><?php echo _mb("Identification");?></a></li>
	    <li><a href="#tabs-2"><?php echo _mb("Reference / Address");?></a></li>
	    <li><a href="#tabs-3"><?php echo _mb("Classification");?></a></li>
	    <!-- Relevant for applications? -->
	    <li><a href="#tabs-4"><?php echo _mb("Temporal extent");?></a></li>
	    <!--<li><a href="#tabs-5"><?php echo _mb("Further information");?></a></li>-->
	    <li><a href="#tabs-5"><?php echo _mb("Spatial Extent");?></a></li>
            <!-- for applications with user authorization some special metadata is needed -->
	    <li><a href="#tabs-6"><?php echo _mb("Terms of Use / Constraints");?></a></li>
	    <li><a href="#tabs-7"><?php echo _mb("Responsible Party");?></a></li>
	    <li><a href="#tabs-8"><?php echo _mb("Preview");?></a></li>
	    <li><a href="#tabs-9"><?php echo _mb("Others");?></a></li>
	</ul>
	<div id="tabs-1">
	    <fieldset>
		<legend><?php echo _mb("Resource title");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("This a characteristic, and often unique, name by which the resource is known.");?>'}" src="../img/questionmark.png" alt="" /></legend>
		<input class="required" name="title" id="title"/>
	    </fieldset>
	    <!-- Radio button for type of application -->
	    <fieldset id="fieldset_application_type">
		<legend><?php echo _mb("Application type");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Choose the type of the application. If the application is external, a link to the application should be given. If it is an internal GUI, you can choose if the GUI istself or a GUI in combination with a own WMC should build that application. More can be configured in the Reference / Address tab.");?>'}" src="../img/questionmark.png" alt="" /></legend>
		 <input type="radio" id="app_type_external" name="application_type" value="app_type_external" checked="checked"><label for="app_type_external"><?php echo _mb("External application");?></label><br>
		 <input type="radio" id="app_type_gui" name="application_type" value="app_type_gui"><label for="app_type_gui"><?php echo _mb("Mapbender GUI");?></label><br>
		 <input type="radio" id="app_type_gui_wmc" name="application_type" value="app_type_gui_wmc"><label for="app_type_gui_wmc"><?php echo _mb("Mapbender GUI/WMC");?></label><br>
	    </fieldset>
	    <fieldset>
		<legend><?php echo _mb("Resource abstract");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("This is a brief narrative summary of the content of the resource."); echo " "._mb("HTML encoding may be used!");?>'}" src="../img/questionmark.png" alt="" /></legend>
		<textarea rows="7" cols="50" class="required" name="abstract" id="abstract"/>
	    </fieldset>

	    <fieldset id="referencesystem">
		<legend><?php echo _mb("Coordinate Reference System");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Description of the coordinate reference system which is used by default in the application.");?>'}" src="../img/questionmark.png" alt="" /></legend>
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
	    <?php $user = new User();?>
	    <?php $guis = $user->getApplicationsByPermission(false, 2);?>
	    <fieldset id="gui_select_fieldset">
	        <legend><?php echo _mb("GUI ID");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("ID of mapbenders internal GUI which should be used to build the application metadata.");?>'}" src="../img/questionmark.png" alt="" /></legend>
	        <select class="gui_selectbox" id='fkey_gui_id' name='fkey_gui_id'>
	            <!--List of own and accessable guis -->
	            <?php $str = "";
		    $str .= "<option value=''>"._mb('...')."</option>";
	            foreach ($guis as $gui) {
	                $str .= "<option value='" . $gui . "'>".$gui."</option>";	
	            }
	            echo $str;
	            ?>
	        </select>
	    </fieldset>
	    <?php $wmcs = $user->getWmcInfoByOwner(false);?>
	    <fieldset id="wmc_select_fieldset">
	        <legend><?php echo _mb("My public WebMapContext documents");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("ID of my WMC that should be loaded in the GUI above to define an special application.");?>'}" src="../img/questionmark.png" alt="" /></legend>
	        <select class="wmc_selectbox" id='fkey_wmc_serial_id' name='fkey_wmc_serial_id'>
	            <!--List of own wmc -->
	            <?php $str = "";
		    $str .= "<option value=''>"._mb('...')."</option>";
	            foreach ($wmcs as $wmc) {
	                $str .= "<option value='" . $wmc->wmc_serial_id . "' title='".$wmc->abstract."'>".$wmc->wmc_title." (".$wmc->wmc_serial_id.")"."</option>";	
	            }
	            echo $str;
	            ?>
	        </select>
	    </fieldset>
	    <!-- -->
	    <fieldset id="default_viewer_fieldset">
		<legend><?php echo _mb("Default Viewer component");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Please choose a default viewer component, that will be used to generate the link to the internal application.");?>'}" src="../img/questionmark.png" alt="" /></legend>
		<select class="default_viewer_selectbox" id='fkey_mapviewer_id' name='fkey_mapviewer_id'>
	            <!--List of own wmc -->
	            <?php $str = "";
		    //$str .= "<option value=''>"._mb('...')."</option>";
		    $mapviewers = json_decode($mapviewers);
	            foreach ($mapviewers as $mapviewer) {
	                $str .= "<option value='" . $mapviewer->id . "' title='".$mapviewer->description."'>".$mapviewer->name." (".$mapviewer->id.")"."</option>";	
	            }
	            echo $str;
	            ?>
	        </select>
	    </fieldset>
	    <fieldset id="address_link_fieldset">
		<legend><?php echo _mb("Linkage");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Link to the application");?>'}" src="../img/questionmark.png" alt="" /></legend>
		<input name="link" id="link" type="text"/>
	    </fieldset>
	    <fieldset id="preview_link_fieldset">
		<legend><?php echo _mb("Application preview");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Open application in new tab.");?>'}" src="../img/questionmark.png" alt="" /></legend>
		<a id="preview_link" target="_blank">currently not set</a>
	    </fieldset>
	    <!--<fieldset>
		<legend><?php echo _mb("Lineage");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("This is a statement on process history and/or overall quality of the spatial data set. Where appropriate it may include a statement whether the data set has been validated or quality assured, whether it is the official version (if multiple versions exist), and whether it has legal validity. The value domain of this metadata element is free text.");?>'}" src="../img/questionmark.png" alt="" /></legend>
		<input class="required" name="lineage" id="lineage"/>
	    </fieldset>-->
	</div>
	<div id="tabs-3">
	    <fieldset id="md_classification">
		<legend><?php echo _mb("Classification");?></legend>
		<fieldset id="keywords_fieldset">
		    <legend><?php echo _mb("Keywords");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Define a list of comma separated keywords which helps to classify the dataset.");?>'}" src="../img/questionmark.png" alt="" /></legend>
		    <input class="" name="keywords" id="keywords"/>
		</fieldset>
		<fieldset id="topic_categories_fieldset">
		    <label for="md_md_topic_category_id" class="label_classification"><?php echo _mb("ISO Topic Category");?>:</label>
		    <img class="metadata_img" title="<?php echo _mb("Inspire");?>" src="../img/misc/inspire_eu_klein.png" alt="" />
		    <select class="metadata_selectbox" id="md_md_topic_category_id" name="md_md_topic_category_id" size="2" multiple="multiple">
			<?php
			    $sql = "SELECT md_topic_category_id AS id, md_topic_category_code_en AS name FROM md_topic_category";
			    echo displayCategories($sql);
			?>
		    </select>
		    <img id="resetIsoTopicCatsMd" title="<?php echo _mb("Reset selection");?>" src="../img/cross.png" style="cursor:pointer;"/>
		</fieldset>
		<fieldset id="inspire_categories_fieldset">
		    <label for="md_inspire_category_id" class="label_classification"><?php echo _mb("INSPIRE Category");?>:</label>
			<img class="metadata_img" title="<?php echo _mb("Inspire");?>" src="../img/misc/inspire_eu_klein.png" alt="" />
			<select class="metadata_selectbox" id="md_inspire_category_id" name="md_inspire_category_id" size="2" multiple="multiple">
<?php
	$sql = "SELECT inspire_category_id AS id, inspire_category_code_en AS name FROM inspire_category";
	echo displayCategories($sql);
?>
			</select>
			<img id="resetInspireCatsMd" title="<?php echo _mb("Reset selection");?>" src="../img/cross.png" style="cursor:pointer;"/>
		</fieldset>
		<fieldset id="custom_categories_fieldset">
		    <label for="md_custom_category_id" class="label_classification"><?php echo _mb("Custom Category");?>:</label>
		    <span class="metadata_span"></span>
		    <select class="metadata_selectbox" id="md_custom_category_id" name="md_custom_category_id" size="2" multiple="multiple">
			<?php
			    $sql = "SELECT custom_category_id AS id, custom_category_code_en AS name FROM custom_category";
			    echo displayCategories($sql);
			?>
		    </select>
		    <img id="resetCustomCatsMd" title="<?php echo _mb("Reset selection");?>" src="../img/cross.png" style="cursor:pointer;"/>
		</fieldset>
	    </fieldset>
	</div>
	<div id="tabs-4">
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
		    <!-- B.5.18 MD_MaintenanceFrequencyCode <<CodeList>> of ISO19115 -->
		        <option value="continual"><?php echo _mb("continual");?></option>
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
	    <p>
		<label for="fees_md"><?php echo _mb("Conditions applying to access and use");?>:</label>
    		<input name="fees_md" id="fees_md"/><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("INSPIRE 8.1: conditions applying to access and use");?>'}" src="../img/questionmark.png" alt="" />
	    </p>
	    <p>
		<label for="accessconstraints_md"><?php echo _mb("Limitations on public access");?>:</label>
		<input name="accessconstraints_md" id="accessconstraints_md"/><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("INSPIRE 8.2: limitations on public access");?>'}" src="../img/questionmark.png" alt="" />
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
	<div id="tabs-7">
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
	    <!-- add option to define other organization than the editors primary group -->
	    <fieldset>
		<legend><?php echo _mb("Metadata Point of contact (registry)");?>: <img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Information about the organization which is responsible for contributing the metadata. The information will be automaticcaly generated from the mapbender database mb_group object. The information came from the primary group of the service owner or from a group which has authorized the owner to publish metadata in their name.");?>'}" src="../img/questionmark.png" alt="" /></legend>

<?php
//selectbox for organizations which allows the publishing of metadatasets for the specific user
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
    		    <select name="fkey_mb_group_id" id="fkey_mb_group_id">
			<option value="">...</option>
<?php
	foreach ($metadataGroup as $key => $value) {
		echo "<option value='" . $key . "'>" . htmlentities($value, ENT_QUOTES, CHARSET) . "</option>";
	}
?>
		    </select>
    	            <img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Selection of different organizations which authorized you to publish metadata in their name.");?>'}" src="../img/questionmark.png" alt="" />
	       </p>
	    </fieldset><!-- end of selection for the different organizations -->
	</div>
	<div id="tabs-8">
	    <!-- Radio button for type of application -->
	    <fieldset id="fieldset_preview_type">
		<legend><?php echo _mb("Preview type");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Choose the type of preview. It maybe an url to an external image or you can upload an image to the mapbender registry.");?>'}" src="../img/questionmark.png" alt="" /></legend>
		 <input type="radio" id="preview_type_external" name="preview_type" value="preview_type_external" checked="checked"><label for="preview_type_external"><?php echo _mb("Link to external image");?></label><br>
		 <input type="radio" id="preview_type_upload" name="preview_type" value="preview_type_upload"><label for="preview_type_upload"><?php echo _mb("Upload local image");?></label><br>
	    </fieldset>
	    <fieldset id="preview_image_fieldset">
		<!-- TODO alternative give the option to upload an image -->
<fieldset id="preview_image_fieldset_url">
		<input name="preview_image" id="preview_image" type="text"/><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Here you can give a url to an image that should be shown");?>'}" src="../img/questionmark.png" alt="" />
</fieldset>
<fieldset id="preview_image_fieldset_upload">
		<table id='preview_upload_table' name='preview_upload_table'><tr><td><img id="uploadPreviewImg" name="uploadPreviewImg" onclick='initUploadPreviewForm();' src='../img/button_blue_red/up.png' title='upload'  /></td><td><?php echo _mb("Upload a preview image for the dataset/application");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Help for uploading a preview image");?>'}" src="../img/questionmark.png" alt="" /><img class="delete_preview" id="delete_existing_preview" onclick="" name="delete_existing_preview" src='../img/cross.png' type="hidden" style="display: none" title="<?php echo _mb("Delete actual preview");?>"/></td></tr></table>
</fieldset>
	    </fieldset>
	    <fieldset id="preview_fieldset">
		<legend><?php echo _mb("Preview image");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Image for the preview.");?>'}" src="../img/questionmark.png" alt="" /></legend>
		<img id="image_preview" src="" title="<?php echo _mb("Image for the preview.");?>" alt="<?php echo _mb("Image for the preview.");?>"/>
	    </fieldset>
	</div>
	<div id="tabs-9">
	    <fieldset>
		<legend><?php echo _mb("Searchability");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Here you can define, if the metadata should be searchable in the geoportal catalogue.");?>'}" src="../img/questionmark.png" alt="" /></legend>
		<input name="searchable" id="searchable" type="checkbox"/>
	    </fieldset>
	    <fieldset>
		<legend><?php echo _mb("Metadata export");?><img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Harvest link target and export to CSW");?>'}" src="../img/questionmark.png" alt="" /></legend>
		<input name="export2csw" id="export2csw" type="checkbox"/>
            </fieldset>
	</div>
    </div><!--tabs-->
    </fieldset>
</div><!--demo-->

