<?php
	require_once dirname(__FILE__) . "/../../core/globalSettings.php";
?>
<div id="wmc">
<fieldset class="wmc-template">
	<input name="wmc_id" id="wmc_id" type="hidden"/>

    <legend><?php echo _mb("WMC Template for");?>: <span id="wmc_title"></span>
    <!-- <img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("Possibility to adapt and add informations in the separate WMC-Featuretype Metadata. The modified Metadata is stored in the database of the GeoPortal.rlp, outwardly these metadata overwrite the original Service-Metadata.");?>'}" src="../img/questionmark.png"></img> -->
    </legend>
    <fieldset data-target="wmcTemplateTitle">
        <legend><?php echo _mb("Frame Title");?>: 
       <!--<img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("INFO TITLE");?>'}" src="../img/questionmark.png"></img> --> 
        </legend>
        <p data-type="text">
            <label for="wmcTemplateTitle-text"><?php echo _mb("Frame Title");?>:</label>
            <input name="text" id="wmcTemplateTitle-text"/>
        </p>
    </fieldset>
    <fieldset data-target="wmcTemplateLogo">
        <legend><?php echo _mb("Frame Logo");?>: 
        <!-- <img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("INFO LOGO");?>'}" src="../img/questionmark.png"></img> -->
        </legend>
        <p data-type="attr">
            <label for="wmcTemplateLogo-src"><?php echo _mb("Logo Source");?> (URL):</label>
            <input name="src" id="wmcTemplateLogo-src"/>
        </p>
        <!--p data-type="attr">
            <label for="wmcTemplateLogo-title"><?php echo _mb("Tooltip");?> :</label>
            <input name="title" id="wmcTemplateLogo-title"/>
        </p-->
    </fieldset>
    <fieldset data-target="wmcTemplateLinkList1">
        <legend><?php echo _mb("Link List1");?>: 
        <!-- <img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("INFO LINK LIST1");?>'}" src="../img/questionmark.png"></img> -->
        </legend>
        <p data-type="attr">
            <label for="wmcTemplateLinkList1-href"><?php echo _mb("Link");?> (Href):</label>
            <input name="href" id="wmcTemplateLinkList1-href"/>
        </p>
        <p data-type="text">
            <label for="wmcTemplateLinkList1-text"><?php echo _mb("Text");?> :</label>
            <input name="text" id="wmcTemplateLinkList1-text"/>
        </p>
        <!--p data-type="attr">
            <label for="wmcTemplateLinkList1-title"><?php #echo _mb("Tooltip");?> :</label>
            <input name="title" id="wmcTemplateLinkList1-title"/>
        </p-->
    
    </fieldset>
    <fieldset data-target="wmcTemplateLinkList2">
        <legend><?php echo _mb("Link List2");?>: 
        <!-- <img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("INFO LINK LIST2");?>'}" src="../img/questionmark.png"></img> -->
        </legend>
        <p data-type="attr">
            <label for="wmcTemplateLinkList2-href"><?php echo _mb("Link");?> (Href):</label>
            <input name="href" id="wmcTemplateLinkList2-href"/>
        </p>
        <p data-type="text">
            <label for="wmcTemplateLinkList2-text"><?php echo _mb("Text");?> :</label>
            <input name="text" id="wmcTemplateLinkList2-text"/>
        </p>
        <!--p data-type="attr">
            <label for="wmcTemplateLinkList2-title"><?php #echo _mb("Tooltip");?> :</label>
            <input name="title" id="wmcTemplateLinkList2-title"/>
        </p-->
    </fieldset>
    <fieldset data-target="wmcTemplateLinkList3">
        <legend><?php echo _mb("Link List3");?>: 
        <!-- <img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("INFO LINK LIST3");?>'}" src="../img/questionmark.png"></img> -->
        </legend>
        <p data-type="attr">
            <label for="wmcTemplateLinkList3-href"><?php echo _mb("Link");?> (Href):</label>
            <input name="href" id="wmcTemplateLinkList3-href"/>
        </p>
        <p data-type="text">
            <label for="wmcTemplateLinkList3-text"><?php echo _mb("Text");?> :</label>
            <input name="text" id="wmcTemplateLinkList3-text"/>
        </p>
        <!--p data-type="attr">
            <label for="wmcTemplateLinkList3-title"><?php #echo _mb("Tooltip");?> :</label>
            <input name="title" id="wmcTemplateLinkList3-title"/>
        </p-->
    </fieldset>
    <fieldset data-target="wmcTemplateLinkDownload">
        <legend><?php echo _mb("Link Download");?>: 
        <!-- <img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("INFO LINK DOWNLOAD");?>'}" src="../img/questionmark.png"></img> -->
        </legend>
        <p data-type="attr">
            <label for="wmcTemplateLinkDownload-href"><?php echo _mb("Link");?> (Href):</label>
            <input name="href" id="wmcTemplateLinkDownload-href"/>
        </p>
        <p data-type="text">
            <label for="wmcTemplateLinkDownload-text"><?php echo _mb("Text");?> :</label>
            <input name="text" id="wmcTemplateLinkDownload-text"/>
        </p>
        <!--p data-type="attr">
            <label for="wmcTemplateLinkDownload-title"><?php #echo _mb("Tooltip");?> :</label>
            <input name="title" id="wmcTemplateLinkDownload-title"/>
        </p-->
    </fieldset>
    <fieldset data-target="wmcTemplateLink1">
        <legend><?php echo _mb("Template Link1");?>: 
        <!-- <img class="help-dialog" title="<?php echo _mb("Help");?>" help="{text:'<?php echo _mb("INFO EXT LINK1");?>'}" src="../img/questionmark.png"></img> -->
        </legend>
        <p data-type="attr">
            <label for="wmcTemplateLink1-href"><?php echo _mb("Link");?> (Href):</label>
            <input name="href" id="wmcTemplateLink1-href"/>
        </p>
        <p data-type="text">
            <label for="wmcTemplateLink1-text"><?php echo _mb("Text");?> :</label>
            <input name="text" id="wmcTemplateLink1-text"/>
        </p>
        <!--p data-type="attr">
            <label for="wmcTemplateLink1-title"><?php #echo _mb("Tooltip");?> :</label>
            <input name="title" id="wmcTemplateLink1-title"/>
        </p-->
    </fieldset>
    <p>&nbsp;</p>
</fieldset>
</div>
