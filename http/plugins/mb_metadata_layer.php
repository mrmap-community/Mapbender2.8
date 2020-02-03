<?php
	require_once dirname(__FILE__) . "/../../core/globalSettings.php";

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

<div id="choose">
	<fieldset class="">
		<p>
			choose layer
		</p>
	</fieldset>
</div>

<div id="layer">
	<fieldset class="">
		<input name="layer_name" id="layer_name" type="hidden"/>

		<legend>Layer Level Metadata: <img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Possibility to adapt and add informations in the separate WMS-Layer Metadata. The modified Metadata will be stored in the database of the GeoPortal.rlp, outwardly these metadata overwrite the original Service-Metadata.");?>'}" src="../img/questionmark.png" alt="" /></legend>
		<p>
			<label for="wms_title"><?php echo _mb("Show original Service Metadata from last update");?></label>
			<img class="original-metadata-layer" src="../img/book.png" alt="" />
			<img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("The original WMS-Metadata from the last update could be recovered or updated, so that the original Service-Metadata will be shown outward again.");?>'}" src="../img/questionmark.png" alt="" />
		</p>
		<p id = "layer_id_p" name = "layer_id_p">
			<span class="metadata_span"></span>
		</p>
		<p id = "layer_id_p2" name = "layer_id_p2">
			<label for="layer_id"><?php echo _mb("Number of Layer (Registry)");?>:</label>
			<input readonly="readonly" name="layer_id" id="layer_id"/>
		</p>
		<p>
	    	<label for="layer_title"><?php echo _mb("Layer Title (WMS)");?>:</label>
			<img class="metadata_img" title="<?php echo _mb("Inspire");?>" src="../img/misc/inspire_eu_klein.png" alt="" />
	    	<input disabled="disabled" name="layer_title" id="layer_title" class="required" />
		</p>
		<p>
	    	<label for="layer_abstract"><?php echo _mb("Layer Abstract (WMS)");?>:</label>
			<img class="metadata_img" title="<?php echo _mb("Inspire");?>" src="../img/misc/inspire_eu_klein.png" alt="" />
	    	<input disabled="disabled" name="layer_abstract" id="layer_abstract"/>
		</p>
		<p>
	    	<label for="layer_keyword"><?php echo _mb("Layer Keywords (WMS)");?>:</label>
			<span class="metadata_span"></span>
		   	<input disabled="disabled" name="layer_keyword" id="layer_keyword"/>
		</p>
		<p>
		<label for="inspire_download"><?php echo _mb("Enable INSPIRE Download Service for predefined dataset.");?>:</label>
      			<input name="inspire_download" id="inspire_download" type="checkbox"/>
			<img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("If this function is enabled, the registry will generate a INSPIRE Download Service fascade for the specific WMS layer. The WMS has to support GeoTIFF as ouput format and must support at minimum  1000x1000 pixel for a single GetMap request. The fascade is generated from the information of the coupled metadataset and the WMS itself. If the extent is more than 1000x1000 pixel, the whole dataset will be splitted into single tiles of 1000x1000 pixel.");?>'}" src="../img/questionmark.png" alt="" />
		</p>
		<p>
		    <div id="buttons">
			<fieldset>
		    	<p>
				<label><?php echo _mb("Add Information about the underlying data");?></label>
				<img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Linking the WMS layer with a metadata set (coupled resource), which describes the underlying information of the representation in more detail (eg actuality / quality). It can be done by linking to an already existing metadata set (e.g. in a catalogue) or by generating a simple metadata file using mapbender.");?>'}" src="../img/questionmark.png" alt="" />
			</p>
			<p>
				<label><?php echo _mb("Table of coupled Metadata");?>:</label>
				<table id="metadataTable">
				</table>
			</p>
		    </fieldset>
		    </div>
		</p>
	</fieldset>
</div>
<div id="preview">
	<fieldset class="">
		<legend><?php echo _mb("Preview");?></legend>
		<div id="previewTabs">
			<ul>
				<li><a href="#previewTabs-1">Bild erstellen</a></li>
				<li><a href="#previewTabs-2">Bild hochladen</a></li>
			</ul>
			<div id="previewTabs-1">
				<div id="map"></div>
				<div id="toolbar_upper"></div>
				<div id="toolbar_lower"></div>
			</div>
			<div id="previewTabs-2">
				<div id="previewImgUpload">
					<form id="previewImgForm" action='../php/mb_metadata_uploadLayerPreview.php' enctype="multipart/form-data" target="upload_iframe" method="POST">
                        <input id="previewReloadButton" type="button" value="Preview neuladen">
                        <iframe name="upload_iframe" width="220" height="220" style="border:0;padding:0"></iframe>
						<input type="file" name="image">
                        <br>
						<input id="previewUploadButton" type="button" value="Upload">
                        <input id="previewDeleteButton" class="hasPreviewImage" type="button" value="Löschen">
						<input id="previewSourceId" type="hidden" name="source_id">
                        <input id="previewType" type="hidden" name="type">
                        <input id="previewAction" type="hidden" name="upload_action">
					</form>
				</div>
				<div id="textarea">
				</div>
			</div>

		</div>
	</fieldset>
</div>
<div id="classification">
	<fieldset class="">
		<legend><?php echo _mb("Classification");?></legend>
		<p>
		    <label for="layer_md_topic_category_id" class="label_classification"><?php echo _mb("ISO Topic Category");?>:</label>
			<img class="metadata_img" title="<?php echo _mb("Inspire");?>" src="../img/misc/inspire_eu_klein.png" alt="" />
			<select disabled="disabled" class="metadata_selectbox" id="layer_md_topic_category_id" name="layer_md_topic_category_id" size="2" multiple="multiple">
<?php
	$sql = "SELECT md_topic_category_id AS id, md_topic_category_code_en AS name FROM md_topic_category";
	echo displayCategories($sql);
?>
			</select>
			<img id="resetIsoTopicCats" title="<?php echo _mb("Reset selection");?>" src="../img/cross.png" style="cursor:pointer;"/>
		</p>
		<p>
		    <label for="layer_inspire_category_id" class="label_classification"><?php echo _mb("INSPIRE Category");?>:</label>
			<img class="metadata_img" title="<?php echo _mb("Inspire");?>" src="../img/misc/inspire_eu_klein.png" alt="" />
			<select disabled="disabled" class="metadata_selectbox" id="layer_inspire_category_id" name="layer_inspire_category_id" size="2" multiple="multiple">
<?php
	$sql = "SELECT inspire_category_id AS id, inspire_category_code_en AS name FROM inspire_category";
	echo displayCategories($sql);
?>
			</select>
			<img id="resetInspireCats" title="<?php echo _mb("Reset selection");?>" src="../img/cross.png" style="cursor:pointer;"/>
		</p>
		<p>
		    <label for="layer_custom_category_id" class="label_classification"><?php echo _mb("Custom Category");?>:</label>
			<span class="metadata_span"></span>
			<select disabled="disabled" class="metadata_selectbox" id="layer_custom_category_id" name="layer_custom_category_id" size="2" multiple="multiple">
<?php
	$sql = "SELECT custom_category_id AS id, custom_category_code_en AS name FROM custom_category";
	echo displayCategories($sql);
?>
			</select>
			<img id="resetCustomCats" title="<?php echo _mb("Reset selection");?>" src="../img/cross.png" style="cursor:pointer;"/>
		</p>
	</fieldset>
</div>
