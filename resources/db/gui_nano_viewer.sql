INSERT INTO gui (gui_id, gui_name, gui_description, gui_public) VALUES ('oebvi_viewer','oebvi_viewer','small_client_light',1);
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','wfsConfTree',1,1,'','Suchoptionen','ul','','title="Suchoptionen"',NULL ,NULL,NULL ,NULL,NULL ,'','','ul','../plugins/wfsConfTree_multi.js','','mapframe1','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','wfsConfTree','FLST_Form_css','/* INSERT wfsconftree -> elementVar -> FLST_Form_css(text/css) */


#wfsForm {
font-family: Helvetica, Roboto,Arial,sans-serif;
letter-spacing: 1px;
}
div.helptext {
  left: 0;
  top: 0;
  min-height: 100%;
  min-width: 100%;
  box-sizing: border-box;
}
div.helptext p {
  margin: 0;
}
div.helptext a {
  display: block;
}
#progressWheel table {
background-color: rgba(205,205,205,0.8);
width: 100%;
height: 100%;
top: 0;
left: 0;
position: absolute;
}
#progressWheel img {
width: 45px;
left: 40%;
position: absolute;
top: 40%;
}

/* END INSERT wfsconftree -> elementVar -> FLST_Form_css(text/css) */','','text/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','wfsConfTree','specialButtonFlst','/* INSERT wfsconftree -> elementVar -> wfsconftree MenuButton(text/css) */
#menuitem_flst{
   padding: 0px 0px 0px 43px;
   text-decoration: none;
   background-image: url(../img/geoportal2019/search_white.svg);
   background-repeat: no-repeat;
   background-position: left+19px center;
}
.menuitem_flst_on {
   color: #333 !important;
   background-image: url(../img/geoportal2019/search_over.svg) !important;
   background-color: #EEE !important;
   border-bottom: 1px solid red !important;
}
#menuitem_flst:hover{
   color: #333;
   background-image: url(../img/geoportal2019/search_over.svg);
   background-color: #EEE !important;
}
.open, .search {
border:none !important;
}

.wfsconf ul li {
  line-height: 47px;
  border-top: 1px solid transparent !important;
}

/* END INSERT wfsconftree -> elementVar -> wfsconftree MenuButton(text/css) */','','text/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','wfsConfTree','wfs_spatial_request_conf_filename','wfs_default.conf','','php_var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','mobile_Map',1,1,'','','div','','',NULL ,NULL,NULL ,NULL,NULL ,'','','div','../plugins/mb_mobile.js','','mapframe1','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','layout_logo_bottomleft',1,1,'layout Logo unten links ','Hier gelangen Sie zum Geoportal Hessen','img','../img/GeoportalHessen.png','onclick="javascript:window.open(''http://test.geoportal.hessen.de'','''','''');"',NULL ,NULL,NULL ,NULL,5,'position:fixed;width:70px;bottom:5px;left:5px;background-color:rgba(255,255,255,0);cursor:pointer;','','','','','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','jq_datatables',1,1,'Includes the jQuery plugin datatables, use like this $(selector).datatables(options)','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','../plugins/jq_datatables.js','../extensions/dataTables-1.7.5/media/js/jquery.dataTables.min.js,../extensions/dataTables-1.7.5/extras/TableTools-2.0.0/media/js/TableTools.min.js','','','http://www.datatables.net/');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','jq_datatables','defaultCss','../extensions/dataTables-1.7.5/media/css/demo_table_jui.css','','file/css');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','sessionWmc',1,1,'','Please confirm','div','','',NULL ,NULL,NULL ,NULL,NULL ,'','','div','../plugins/mb_sessionWmc.js','','','mapframe1','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','sessionWmc','displayTermsOfUse','1','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','sessionWmc','specialCondition','<fieldset><p>Mit der weiteren Nutzung des Geoportals Hessen akzeptieren Sie unsere <a class="external-link"  target="_parent" href="../../article/Impressum/#Nutzungsbedingungen">Nutzungsbedingungen</a>.</p></fieldset>','<fieldset><p>Fill specialCondition</p></fieldset>','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','sessionWmc','specialConditionCSS','a.external-link{
font-size:inherit;
line-height:inherit;
font-family:inherit;
color:#D51F28;
Background:url("../img/extlink.png")right center no-repeat, URL("../img/bullet_red.png")left center no-repeat;
padding:0 13px 0 9px;
}
a.external-link:hover{
text-decoration:underline;
}
','','text/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','sessionWmc','tou_css','#sessionWmc_constraint_form tbody tr {display:block;}
  #sessionWmc_constraint_form tbody td {display:block;padding: 0 0 15px 3px;} 
  #sessionWmc_constraint_form fieldset {font-size: 0.88em;line-height:  165%;}','','text/css');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','jq_ui',1,1,'The jQuery UI core','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/jquery-ui-1.8.16.custom/js/jquery-ui-1.8.16.custom.min.js','','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','jq_ui','css','../extensions/jquery-ui-1.7.2.custom/css/ui-lightness/jquery-ui-1.7.2.custom.css','','file/css');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','body',1,1,'body (obligatory)Javascripts: ../geoportal/mod_revertGuiSessionSettings.php
','','body','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/wz_jsgraphics.js,geometry.js,../extensions/RaphaelJS/raphael-1.4.7.min.js,../extensions/spectrum-min.js,../extensions/uuid.js,../extensions/tokml.js,../extensions/togpx.js','','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','body','buttonsCSS','/* INSERT body -> elementVar -> buttonCSS(text/css) */
.myOnClass,.myOverClass{background-color:#EEE !important;color:#333 !important;}
.myOnClass{border-bottom: 1px solid #d62029 !important;}
#Div_collection2 img{border-bottom:1px solid transparent;}
#zoomFull:hover,#zoomOut1:hover,#zoomIn1:hover {background-color: #EEE;cursor:pointer;}
/* END INSERT body -> elementVar -> buttonCSS(text/css) */','','text/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','body','cacheGuiHtml','false','','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','body','css_file_wfstree_single','../css/wfsconftree2.css','','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','body','favicon','../img/favicon.png','favicon','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','body','fontsize','.ui-widget{font-size:0.9em !important}','','text/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','body','includeWhileLoading','../include/geoportal_logo_splash.php','','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','body','jq_ui_autocomplete_css','../css/jquery.ui.autocomplete.2019.css','file/css','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','body','jq_ui_effects_transfer','.ui-effects-transfer { z-index:1003; border: 2px dotted gray; } ','','text/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','body','jq_ui_theme','../extensions/jquery-ui-1.7.2.custom/css/smoothness/jquery-ui-1.7.2.custom.css','UI Theme from Themeroller','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','body','popupcss','../css/popup.css','file css','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','body','print_css','../geoportal/print_div.css','','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','body','tablesortercss','../css/tablesorter.css','file css','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','body','ui-dialog-override-css','/* INSERT body -> elementVar -> ui-dialog-override-css(text/css) */
.ui-widget {
font-size:0.9em !important}
.ui-dialog {
position:absolute;
padding-top:unset !important;
padding-right:unset !important;
padding-bottom:1.2em !important;
padding-left:unset !important;
box-shadow: 0 5px 10px -2px rgb(201, 202, 202);

}
.ui-dialog-content{
max-width: 80vw;
max-height: 70vh;
}
.ui-dialog {
max-width: 85vw;
max-height: 85vh;
}

.ui-corner-all {
    -moz-border-radius: unset;
    -webkit-border-radius: unset;
}
.ui-widget-content {
    border: 1px solid #aaa;
}

.ui-widget {
    font-family: Helvetica,Arial,sans-serif;
    font-size: 1.1em;
    letter-spacing: 1px;
}

.ui-widget-header {
    border-top: none;
    border-right: none;
    border-left: none;
    border-bottom: 1px solid #aaaaaa;
    background: none;
}
#loadwmc_wmclist, #kml-from-wmc {
padding:0.1em !important;
}
/* END INSERT body -> elementVar -> ui-dialog-override-css(text/css) */','','text/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','body','use_load_message','true','','php_var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','loadwmc',1,1,'load workspace from WMC
SRC: ../img/button_hessen/wmcload_off.png
Attributes: onmouseover=''this.src = this.src.replace(/_off/,"_over");''  onmouseout=''this.src = this.src.replace(/_over/, "_off");'' Requires entfernt: jq_ui_tabs,','Meine Themen verwalten','div','','',NULL ,NULL,NULL ,NULL,NULL ,'display:none;','','','mod_loadwmc.php','popup.js','mapframe1','jq_ui,jq_upload,jq_datatables','http://www.mapbender.org/index.php/LoadWMC');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','loadwmc','allowResize','true','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','loadwmc','deleteWmc','1','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','loadwmc','dialogHeight','500','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','loadwmc','dialogWidth','350','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','loadwmc','editWmc','0','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','loadwmc','loadFromSession','0','','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','loadwmc','mobileUrl','1','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','loadwmc','mobileUrlNewWindow','0','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','loadwmc','publishWmc','0','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','loadwmc','reinitializeLoadWmc','true','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','loadwmc','saveWmcTarget','savewmc','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','loadwmc','showPublic','0','','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','changeEPSG',1,1,'change EPSG, Postgres required, overview is targed for full extent
position:fixed;bottom:15px;left:15px;','Kartenprojektion ändern','select','','',15,NULL ,186,24,1000,'padding:3px;font-size:12px;border:solid 1px #ABADB3;display:none;','<option value="">undefined</option>','select','mod_changeEPSG.php','../extensions/proj4js/lib/proj4js-compressed.js','overview,mapframe1','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','changeEPSG','projections','EPSG:4326;Geographic Coordinates,EPSG:25832;UTM zone 32N,EPSG:31467;Gauss-Krueger 3','','php_var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','jq_upload',1,1,'','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../plugins/jq_upload.js','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','Div_collection2',1,1,'NAVIGATION Put existing divs in new div object. List the elements comma-separated under target, and make sure they have a title. Target entfernt: logo,app_metadata','','div','','',NULL ,NULL,NULL ,51,100,'width:100%;background-color:rgba(255,255,255,0.97);position:relative;top:0em;right: 0;box-shadow: 0 5px 10px -2px rgb(201, 202, 202);display:inline-block;','','div','../plugins/mb_div_collection.js','','jsonAutocompleteGazetteer,toolbar','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','Div_collection2','css','#Div_collection2:hover{z-index:1050 !important}','','text/css');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','mousewheelZoom',2,1,'adds mousewheel zoom to map module (target)','Mousewheel zoom','div','','',NULL ,NULL,NULL ,NULL,NULL ,'','','div','mod_mousewheelZoom.js','../extensions/jquery.mousewheel.min.js','mapframe1','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','mousewheelZoom','factor','2','The factor by which the map is zoomed on each mousewheel unit','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','doubleclickZoom',2,1,'adds doubleclick zoom to map module (target). Deactivates the browser contextmenu!!!','Doubleclick zoom','div','','',NULL ,NULL,NULL ,NULL,NULL ,'','','div','mod_doubleclickZoom.js','','mapframe1','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','dependentDiv',2,1,'displays infos in a sticky div-tag
font-size: 11px;font-family: "Arial", sans-serif;visibility:visible;','','div','','',NULL ,NULL,NULL ,NULL,300,'position:relative;','','div','mod_dependentDiv.php','','overview','','http://www.mapbender.org/index.php/DependentDiv');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','dependentDiv','CSScoordsDiv','','','text/css');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','zoomFull',2,1,'zoom to full extent button','Auf gesamte Karte zoomen','img','../img/geoportal2019/globe_off.svg','',NULL ,NULL,NULL ,NULL,103,'','','','mod_zoomFull.js','','mapframe1','','http://www.mapbender.org/index.php/ZoomFull');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','zoomIn1',2,1,'zoomIn button','In die Karte hineinzoomen','img','../img/geoportal2019/plus_off.svg','',NULL ,NULL,NULL ,NULL,103,'','','','mod_zoomIn1.js','','mapframe1','','http://www.mapbender.org/index.php/ZoomIn');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','resizeMapsize',2,1,'resize_mapsize -auto-','','div','','',1,1,1,1,NULL ,'div','','','mod_resize_mapsize.js','','mapframe1','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','resizeMapsize','adjust_height','','to adjust the height of the mapframe on the bottom of the window','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','resizeMapsize','adjust_width','','to adjust the width of the mapframe on the right side of the window','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','resizeMapsize','resize_option','auto','auto (autoresize on load), button (resize by button)','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','loadData',2,1,'IFRAME to load data','','iframe','../html/mod_blank.html','frameborder = "0" ',0,0,1,1,NULL ,'visibility:visible','','iframe','','','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','mapframe1',2,1,'frame for a map
','','div','','',0,53,690,527,2,'overflow:hidden;background-color:#ffffff','','div','../plugins/mb_map.js','../../lib/history.js,map_obj.js,map.js,wms.js,wfs_obj.js,initWmcObj.php','','','http://www.mapbender.org/index.php/Mapframe');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','mapframe1','skipWmsIfSrsNotSupported','1','if set to 1, it skips the WMS request if the current SRS is not supported by the WMS; if set to 0, the WMS is always queried. Default is 0, because of backwards compatibility','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','mapframe1','slippy','1','1 = Activates an animated, pseudo slippy map','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','mapframe1','wfsConfIdString','379,381','','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','overview',2,1,'OverviewFrame','Übersichtskarte','div','','',NULL ,NULL,200,200,101,'margin:10px;overflow:hidden;background-color:#ffffff;right:0;bottom:22px;position:absolute;top:unset;left:unset;','<div id="overview_maps" style=""></div>','div','../plugins/mb_overview.js','map_obj.js,map.js,wms.js,wfs_obj.js,initWmcObj.php','mapframe1','mapframe1','http://www.mapbender.org/index.php/Overview');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','overview','overview_wms','0','wms that shows up as overview','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','overview','skipWmsIfSrsNotSupported','0','if set to 1, it skips the WMS request if the current SRS is not supported by the WMS; if set to 0, the WMS is always queried. Default is 0, because of backwards compatibility','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','vis_timeline',2,1,'VIS Core','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/vis/dist/vis.js','','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','vis_timeline','file_vis_css','../extensions/vis/dist/vis.css','','file/css');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','zoomOut1',2,1,'zoomOut button','Aus der Karte herauszoomen','img','../img/geoportal2019/minus_off.svg','',NULL ,NULL,NULL ,NULL,103,'','','','mod_zoomOut1.js','','mapframe1','','http://www.mapbender.org/index.php/ZoomOut');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','selArea1',2,1,'ABHÄNGIGKEITEN
zoombox
<img..>../img/button_hessen/zoomArea4_off.png','Ausschnitt durch Aufziehen einer Fläche vergrößern','A','','',NULL ,NULL,NULL ,NULL,NULL ,'','<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24.000001" height="19" width="19">
<path d="M15.139163 1041.2225c-.392993 1.7467-2.217698 2.2202-2.217698 2.2202l7.392464 7.4007 2.217697-2.2201z" fill-rule="evenodd" stroke="none" stroke-width="1.56905377" stroke-linejoin="round" transform="matrix(1.04595 0 0 1.0464 -.49952522 -1076.3057)" fill="currentColor"></path>
<path d="M1.1597145 1037.5041c0 4.4746 3.6230358 8.1021 8.0922782 8.1021 4.4692413 0 8.0922783-3.6275 8.0922783-8.1021 0-4.4747-3.623037-8.1023-8.0922783-8.1023-4.4692424 0-8.0922782 3.6276-8.0922782 8.1023z" fill="none" stroke="currentColor" stroke-width="1.56905377" stroke-linecap="round" stroke-linejoin="round" stroke-dashoffset="7" transform="matrix(1.04595 0 0 1.0464 -.49952522 -1076.3057)"></path>
<g fill="none" stroke="currentColor" stroke-width="2.9000001" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9.1501655 1032.8214v8.6M13.450165 1037.1214H4.8501655" overflow="visible" transform="matrix(1.04595 0 0 1.0464 -.39301842 -1075.90534813)"></path>
</g>
<path d="M18.72204773 16.33189514l-2.0919064-2.092796-2.09190744 2.092796 2.09190744 2.092796z" fill-rule="evenodd" fill="currentColor"></path>
</svg>
Auswahl vergrößern','A','mod_selArea.js','mod_box1.js','mapframe1','','http://www.mapbender.org/index.php/SelArea1');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','resultList_Highlight',2,1,'highlighting functionality for resultList works only with overview enabled','','div','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','../plugins/mb_resultList_Highlight.js','','resultList,mapframe1,overview','','http://www.mapbender.org/resultList_Highlight');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','resultList_Highlight','maxHighlightedPoints','500','max number of points to highlight','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','resultList_Highlight','resultHighlightColor','#ff0000','color of the highlighting','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','resultList_Highlight','resultHighlightLineWidth','2','width of the highlighting line','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','resultList_Highlight','resultHighlightZIndex','999','zindex of the highlighting','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','resultList_Zoom',2,1,'zoom functionality for resultList','','div','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','../plugins/mb_resultList_Zoom.js','','resultList,mapframe1','','http://www.mapbender.org/resultList_Zoom');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','jsonAutocompleteGazetteer',2,1,'Client for json webservices like geonames.orgposition:fixed;top:0.5em;left: 1em;','Gazetteer','div','','title="Nach Addressen suchen"',NULL ,NULL,NULL ,NULL,NULL ,'float:right;position:absolute;right:0;background-color:white;','','div','../plugins/mod_jsonAutocompleteGazetteer2019.php','','mapframe1','','http://www.mapbender.org/index.php/mod_jsonAutocompleteGazetteer.php');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','jsonAutocompleteGazetteer','css','/* INSERT jsonAutocompleteGazetteer -> elementVar -> css(text/css) */

#geographicName {
max-width: calc(-100px + 100vw);
}

body ul.ui-autocomplete {
max-height: calc(-52px + 100vh);
max-width: calc(100vw - 63px);
}

/* END INSERT jsonAutocompleteGazetteer -> elementVar -> css(text/css) */','','text/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','jsonAutocompleteGazetteer','gazetteerUrl','https://test.geoportal.hessen.de/mapbender/geoportal/gaz_geom_mobile.php','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','jsonAutocompleteGazetteer','helpText','','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','jsonAutocompleteGazetteer','isGeonames','false','','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','pan1',2,1,'pan','Ausschnitt verschieben','img','../img/geoportal2019/move_off.svg','',NULL ,NULL,NULL ,NULL,3,'cursor:pointer;','','','mod_pan.js','','mapframe1','','http://www.mapbender.org/index.php/Pan');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','pan1','panCSS','','','text/css');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','featureInfo1',2,1,'FeatureInfoRequest','Datenabfrage','img','../img/geoportal2019/infoabfrage_off.svg','',NULL ,NULL,NULL ,NULL,3,'cursor:pointer;','Datenabfrage','','mod_featureInfo.php','','mapframe1','','http://www.mapbender.org/index.php/FeatureInfo');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','featureInfo1','featureInfoCollectLayers','true','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','featureInfo1','featureInfoDrawClick','true','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','featureInfo1','featureInfoLayerPopup','false','display featureInfo in dialog popup','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','featureInfo1','featureInfoLayerPreselect','true','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','featureInfo1','featureInfoPopupHeight','600','height of the featureInfo dialog popup','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','featureInfo1','featureInfoPopupPosition','[20,80]','position of the featureInfoPopup [left,top]','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','featureInfo1','featureInfoPopupWidth','550','width of the featureInfo dialog popup','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','featureInfo1','featureInfoShowKmlTreeInfo','false','only if kmltree included in gui','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','featureInfo1','reverseInfo','true','Reorder featureInfo result','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','sandclock',2,1,'displays a sand clock while waiting for requests','','div','','',0,0,NULL ,NULL,NULL ,'','','div','mod_loading.js','','mapframe1','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','sandclock','css','.loader-line {
  width: 200px;
  height: 2px;
  position: relative;
  overflow: hidden;
  -webkit-border-radius: 20px;
  -moz-border-radius: 20px;
  border-radius: 20px;
}

.loader-line:before {
  content: "";
  position: absolute;
  left: -50%;
  height: 2px;
  width: 40%;
  background-color:#137673;
-webkit-animation: lineAnim 1s linear infinite;
            -moz-animation: lineAnim 1s linear infinite;
            animation: lineAnim 1s linear infinite;
            -webkit-border-radius: 20px;
            -moz-border-radius: 20px;
            border-radius: 20px;
        }

        @keyframes lineAnim {
            0% {
                left: -40%;
            }
            50% {
                left: 20%;
                width: 40%;
            }
            100% {
                left: 100%;
                width: 100%;
            }
        }
}','','text/css');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','resultList',2,1,'position defined in elementVar','Result List','div','','',NULL ,NULL,NULL ,NULL,NULL ,'','','div','mod_ResultList.js','../../lib/resultGeometryListController.js, ../../lib/resultGeometryListModel.js','mapframe1','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','resultList','position','[120,119]','position of the result list dialog','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','resultList','resultListHeight','350','height of the result list dialog','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','resultList','resultListTitle','Suchergebnisse','title of the result list dialog','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','resultList','resultListWidth','500','width of the result list dialog','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','resultList','tableTools666','[{ "sExtends": "xls",        "sButtonText": "Export to CSV",   "sFileName": "result.csv"  }]','set the initialization options for tableTools','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','toggleModule',3,1,'','','div','','',1,1,1,1,2,'','','div','mod_toggleModule.php','','pan1','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','copyright',5,1,'a Copyright in the map','Copyright','div','','',0,0,NULL ,NULL,100,'','','div','mod_termsOfUse.php','','mapframe1','','http://www.mapbender.org/index.php/Copyright');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','copyright','css','#mapframe1_copyright div{
color:unset;
background-color:rgba(255,255,255,0.8);
right:0px !important;
bottom:0px !important;
padding:1px 8px;
z-index:1001 !important;}
#mapframe1_copyright div:hover{
background-color:rgba(255,255,255,1);}
#mapframe1_copyright div a{
font-family:Helvetica,Roboto,Arial,sans-serif;
font-size: 12px;}
#mapframe1_copyright div a:hover{}','','text/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','copyright','mod_copyright_text','Nutzungsbedingungen','define a copyright text which should be displayed','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','kml',5,1,'','','div','','',NULL ,NULL,NULL ,NULL,NULL ,'','','div','','../../lib/mb.ui.displayKmlFeatures.js','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','overviewToggle',5,1,'2019','Übersichtskarte','div','','class="overviewToggleClosed"',NULL ,NULL,NULL ,NULL,100,'display:flex;align-items:center;position:absolute;right:0px;bottom:20px;background-color:#EEE;border-top:2px solid #DDD;border-left:2px solid #DDD;border-bottom:2px solid #DDD;display:none;','<svg width="17" height="18" viewBox="0 0 18 18" fill="none"  xmlns="http://www.w3.org/2000/svg">
<path d="M16.0142 11.6191L14.6042 13.0291L9.01416 7.43914L3.42416 13.0291L2.01416 11.6191L9.01416 4.61914L16.0142 11.6191Z" stroke="currentColor" stroke-width="2"/>
</svg>','div','../javascripts/mod_overviewToggle2019.js','','overview','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','overviewToggle','css','/* INSERT overviewToggle -> elementVar -> css(text/css) */
.overviewToggleClosed {
z-index:100 !important;
}

.overviewToggleClosed svg {
float: right;
transform: rotate(-90deg);
}
.overviewToggleOpened svg {
float: left;
transform: rotate(90deg);
}
.overviewToggleOpened, .overviewToggleClosed {
color:#777;padding:5px;
}
.overviewToggleOpened:hover, .overviewToggleClosed:hover {
color:#333;
}

/* END INSERT overviewToggle -> elementVar -> css(text/css) */
','','text/css');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','toolbar',5,1,'This toolbar NAVIGATION appends all its target elements to its container
~modified js~','Navigation','div ','','class=''mb-toolbar''',NULL ,NULL,NULL ,NULL,NULL ,'','','div','../plugins/mb_toolbar.js','','wfsConfTree','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','toolbar','css','.mb-toolbar {
padding:0px;
margin:0px;
float:left;
line-height:51px;
}
.mb-toolbar li {
border-left: 2px solid #DDD;
float:left;
}


.dialogopen {
border-right: 2px solid #DDD;
font-family: Helvetica,Roboto,Arial,sans-serif;
text-decoration: none;
color: #777;
font-weight: 700;
letter-spacing: 1px;
padding: 0px 15px 0px 35px;
background-image: url(../img/geoportal2019/search.svg);
background-repeat: no-repeat;
background-position: left+15px center;
cursor: pointer;
display: block;

}


/*.mb-toolbar ul{
display:block;
float:left;
margin: 4px 7px 0px 0px;
}*/

.mb-toolbar ul {
margin:0px;
padding:0px;
list-style-type: none;
}

#toolbar img {
padding: 10px;
display: block;
}','','text/css');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('oebvi_viewer','toolbar3',6,1,'This toolbar ZOOM TOOLS TOP RIGHT appends all its target elements to its container','Werkzeuge','div','','class=''mb-toolbar3'' ',NULL ,NULL,NULL ,NULL,NULL ,'','','div','../plugins/mb_toolbar.js','','zoomIn1,zoomFull,zoomOut1,selArea1,pan1,featureInfo1','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('oebvi_viewer','toolbar3','css','/* INSERT toolbar3 -> elementVar -> css(text/css) */
.mb-toolbar3 {
position: fixed;
z-index: 101;
right: 0px;
top: 75px;
box-shadow: 0 5px 10px -2px rgb(201, 202, 202);
}
.mb-toolbar3 ul, .mb-toolbar3 li {
display:block;
margin:0;
padding:0;
}

#toolbar3 ul li {
font-size: 0;
}

#toolbar3 ul li:first-child img{
/*border-top: 2px solid #DDD;*/
}
#toolbar3 ul li:last-child img{
border-bottom: 2px solid transparent;
}
.mb-toolbar3 img, .mb-toolbar3 svg {
padding: 15px;
border-bottom: 1px solid #DDD;
border-left: 1px solid #DDD;
background-color:rgba(255,255,255,0.98)
}

#selArea1 {
display:block;
}

#selArea1.myOnClass svg {
border-bottom: 1px solid #d62029;
background-color: #EEE !important;
color: #333 !important;
}

#selArea1.myOnClass {
border-bottom: unset !important;

}

#selArea1 svg {
padding: 16px 14px 15px 17px;
border-left: 1px solid #DDD;
color: #777;
background-color: rgba(255,255,255,0.98);}
#selArea1 svg:hover {color: #333;background-color: #EEE;}

#pan1 {
padding: 13px;
}

#featureInfo1 {
padding: 11px 10px 8px 10px;
}

/* END INSERT toolbar3 -> elementVar -> css(text/css) */','','text/css');
