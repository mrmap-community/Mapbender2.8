-- neue Gui "Template" anlegen
INSERT INTO gui (gui_id, gui_name, gui_description, gui_public) VALUES ('Template','Template','GUI combining most of the Mapbender functionality',1);
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','jq_datatables',1,1,'Includes the jQuery plugin datatables, use like this
$(selector).datatables(options)','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','../plugins/jq_datatables.js','../extensions/dataTables-1.7.5/media/js/jquery.dataTables.min.js,../extensions/dataTables-1.7.5/extras/TableTools-2.0.0/media/js/TableTools.min.js','','','http://www.datatables.net/');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','jq_datatables','defaultCss','../extensions/dataTables-1.5/media/css/demo_table_jui.css','','file/css');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','wmcTemplate',1,1,'Loads the WMC Template from the database','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','../php/mb_loadWmcTemplate.php','','','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','wmcTemplate','defaultCss','../extensions/dataTables-1.5/media/css/demo_table_jui.css','','file/css');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','jq_upload',1,1,'','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../plugins/jq_upload.js','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','jq_validate',1,0,'The jQuery validation plugin','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','../javascripts/jq_validate.js','../extensions/jquery-validate/jquery.validate.min.js','','','http://docs.jquery.com/Plugins/Validation');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','jq_validate','css','label.error { float: none; color: red; padding-left: .5em; vertical-align: top; }','','text/css');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','sessionWmc',1,1,'','Please confirm','div','','',NULL ,NULL,NULL ,NULL,NULL ,'','','div','../plugins/mb_sessionWmc.js','','','mapframe1','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','sessionWmc','displayTermsOfUse','1','','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','jq_ui_widget',1,1,'jQuery UI widget','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/jquery-ui-1.8.16.custom/development-bundle/ui/jquery.ui.widget.js','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','body',1,1,'body (obligatory)','','div','','',NULL ,NULL,NULL ,550,NULL ,'position:relative !important;overflow:visible;','','div','','../extensions/wz_jsgraphics.js,geometry.js,../extensions/RaphaelJS/raphael-1.4.7.min.js','','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','body','css_class_bg','body{ background-color: #30741F; }','to define the color of the body','text/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','body','css_file_body','../css/mapbender.css','file/css','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','body','css_file_feedtree','../css/feedtree.css','file/css','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','body','css_file_wfstree','../css/wfsconftree.css','file/css','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','body','css_mapviewer','../css/map_sl.css','','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','body','favicon','../img/favicon.ico','favicon','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','body','includeWhileLoading','../include/gui1_splash.php','','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','body','jq_ui_theme','../extensions/jquery-ui-1.7.2.custom/css/smoothness/jquery-ui-1.7.2.custom.css','UI Theme from Themeroller','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','body','jquery_datatables','../extensions/dataTables-1.5/media/css/demo_table_jui.css','','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','body','popupcss','../css/popup.css','file css','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','body','print_css','../css/print_div.css','','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','body','tablesortercss','../css/tablesorter.css','file css','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','body','use_load_message','true','','php_var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','mapframe1',1,1,'Frame for a map','','div','','',215,95,650,500,3,'overflow:hidden;background-color:#ffffff','','div','../plugins/mb_map.js','../../lib/history.js,map_obj.js,map.js,wms.js,wfs_obj.js,initWmcObj.php','','','http://www.mapbender.org/index.php/Mapframe');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','mapframe1','skipWmsIfSrsNotSupported','0','if set to 1, it skips the WMS request if the current SRS is not supported by the WMS; if set to 0, the WMS is always queried. Default is 0, because of backwards compatibility','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','mapframe1','slippy','1','1 = Activates an animated, pseudo slippy map','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','mapframe1','wfsConfIdString','94','','text/css');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','jq_ui',1,1,'The jQuery UI core','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/jquery-ui-1.8.16.custom/development-bundle/ui/jquery.ui.core.js','','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','jq_ui','css','../extensions/jquery-ui-1.7.2.custom/css/ui-lightness/jquery-ui-1.7.2.custom.css','','file/css');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','loadwmc',1,1,'load workspace from WMC','Laden eines WebMapContext Dokumentes','img','../img/button_blue_red/wmc_load_off.png','onmouseover=''this.src = this.src.replace(/_off/,"_over");''  onmouseout=''this.src = this.src.replace(/_over/, "_off");''',145,235,24,24,1,'','','','mod_loadwmc.php','popup.js','mapframe1','jq_ui_dialog,jq_ui_tabs,jq_upload,jq_datatables','http://www.mapbender.org/index.php/LoadWMC');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','loadwmc','deleteWmc','1','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','loadwmc','editWmc','0','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','loadwmc','loadFromSession','1','','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','loadwmc','publishWmc','0','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','loadwmc','saveWmcTarget','savewmc','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','loadwmc','showPublic','0','','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','wfsConfTree',1,1,'','Such- und Downloadmodule','ul','','',10,480,1,1,NULL ,'','','ul','../plugins/wfsConfTree.js','','mapframe1','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','wfsConfTree','wfs_spatial_request_conf_filename','wfs_default.conf','','php_var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','savewmc',1,1,'save workspace as WMC','Ausschnitt als WebMapContext Dokument speichern','img','../img/button_blue_red/wmc_save_off.png','',115,235,24,24,1,'','','','mod_savewmc.php','','mapframe1','jq_ui_dialog','http://www.mapbender.org/index.php/SaveWMC');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','savewmc','lzwCompressed','false','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','savewmc','overwrite','1','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','savewmc','saveInSession','1','','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','toolbar',1,0,'This toolbar appends all its target elements to its container','Toolbar','div','','class=''mb-toolbar'' style=''background:url(../img/Mapbender_logo_and_text_200x50.png) no-repeat 10px 10px''',NULL ,NULL,NULL ,NULL,NULL ,'','','div','../plugins/mb_toolbar.js','','measure_widget','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','toolbar','css','.mb-toolbar ul, .mb-toolbar li {
   display: inline;
}

.mb-toolbar ul {
   float: right;
}

.mb-toolbar li {
   margin:2px
}','','text/css');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','lzw_compression',1,1,'','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','lzw.js','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','i18n',1,0,'Internationalization module, collects data from all elements and sends them to the server in a single POST request. The strings are translated via gettext only.','Internationalization','div','','',NULL ,NULL,NULL ,NULL,NULL ,'','','div','../plugins/mb_i18n.js','','','','http://www.mapbender.org/Gettext');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','setScaleHint',1,0,'set scaleHint for mapframes','','div','','',1,81,1,1,0,'visibility:hidden;','','div','mod_scaleHint.php','','mapframe1,100,10000000','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','mousewheelZoom',2,1,'adds mousewheel zoom to map module (target)','Mousewheel zoom','div','','',NULL ,NULL,NULL ,NULL,NULL ,'','','div','mod_mousewheelZoom.js','../extensions/jquery.mousewheel.min.js','mapframe1','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','mousewheelZoom','factor','3','The factor by which the map is zoomed on each mousewheel unit','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','setPOI2Scale',2,1,'zoom to a poi (get-parameter)','','div','','',1,81,1,1,NULL ,'visibility:hidden','','div','mod_setPOI2Scale.php','','mapframe1','','http://www.mapbender.org/index.php/Mod_setPoi2Scale');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','setPOI2Scale','mod_setPOI2Scale_defScale','5000','default scale','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','changePassword_button',2,0,'button: change password of logged user','Passwort ändern','img','../img/button_blink_red/change_password_off.png','onclick="window.open(''../php/mod_changePassword.php?sessionID'','''',''width=300, height=300, menubar=no,toolbar=no,location=no,status=no,resizable=yes'');" border=''0'' onmouseover=''this.src="../img/button_blink_red/change_password_over.png"'' onmouseout=''this.src="../img/button_blink_red/change_password_off.png"'' ',750,140,24,24,1,'cursor:hand','','','','','','','http://www.mapbender.org/index.php/ChangePassword');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','help',2,0,'button help','Hilfe','img','../img/button_blink_red/help_off.png','onmouseover=''this.src = this.src.replace(/_off/,"_over");''  onmouseout=''this.src = this.src.replace(/_over/, "_off");'' onclick=''window.open("http://www.mapbender.org/index.php/Using_Mapbender","Hilfe","width=800, height=800, resizable=yes,scrollbars=yes, menubar=yes, toolbar=yes, location=yes")''',790,140,24,24,1,'','','','','','','','http://www.mapbender.org');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','help1',2,0,'button help','Hilfe','img','../img/button_blink_red/help_off.png','onmouseover=''this.src = this.src.replace(/_off/,"_over");''  onmouseout=''this.src = this.src.replace(/_over/, "_off");'' onclick=''window.open("http://www.mapbender.org/index.php/Using_Mapbender","Hilfe","width=800, height=800, resizable=yes,scrollbars=yes, menubar=yes, toolbar=yes, location=yes")''',790,140,24,24,1,'','','','','','','','http://www.mapbender.org/index.php/help');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','addWMSfromList',2,0,'add a WMS to the running application from a list','WMS aus Liste hinzufügen','img','../img/button_blink_red/addlist_off.png','onclick=''window.open("../javascripts/mod_addWMSfromList.php?sessionID","printWin","width=500, height=600, left=300, resizable=yes, scrollbars=yes")''  onmouseover=''this.src = this.src.replace(/_off/,"_over");''  onmouseout=''this.src = this.src.replace(/_over/, "_off");'' ',595,140,24,24,1,'','','','','mod_addWMSgeneralFunctions.js','treeGDE,mapframe1','loadData','http://www.mapbender.org/index.php/Add_WMS_from_list');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','addCSW',2,0,'search via a CSW Client','Katalogsuche','img','../img/button_gray/csw_off.png','',288,105,24,24,1,'','','','mod_searchCSW_ajax_button.php','mod_addWMSgeneralFunctions.js','treeGDE,mapframe1','loadData','http://www.mapbender.org/index.php/AddCSW');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','legend',2,1,'legend','Legende','div','','',0,80,NULL ,NULL,600,'','','div','../javascripts/mod_legendDiv.php','','mapframe1','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','legend','checkbox_on_off','false','display or hide the checkbox to set the legend on/off','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','legend','css_file_legend','../css/legend.css','','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','legend','legendlink','false','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','legend','showgroupedlayertitle','true','show the title of the grouped layers in the legend','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','legend','showlayertitle','true','show the layer title in the legend','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','legend','showwmstitle','true','show the wms title in the legend','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','legend','stickylegend','false','parameter to decide wether the legend should stick on the mapframe1','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','legend','reverseLegend','false','parameter to decide wether the legend should be in the reverse direction','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','resultList_Zoom',2,1,'zoom functionality for resultList','','div','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','../plugins/mb_resultList_Zoom.js','','resultList,mapframe1','','http://www.mapbender.org/resultList_Zoom');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','addGeoRSS',2,0,'add a GeoRSS Feed to a running application','GeoRSS hinzufügen','img','../img/georss_logo_off.png','onclick=''loadGeoRSSByForm()''  onmouseover=''this.src = this.src.replace(/_off/,"_over");''  onmouseout=''this.src = this.src.replace(/_over/, "_off");''',115,145,24,24,1,'','','','mod_georss.php','popupballon.js,usemap.js,geometry.js,../extensions/wz_jsgraphics.js','mapframe1','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','addGeoRSS','loadGeorssFromSession','1','','php_var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','addWMSfromfilteredList_ajax',2,0,'add a WMS to the running application from a filtered list','WMS von gefilteter Liste hinzufügen','img','../img/button_gray/add_filtered_list_off.png','',620,140,24,24,1,'','','','mod_addWmsFromFilteredList_button.php','mod_addWMSgeneralFunctions.js,popup.js','treeGDE,mapframe1','loadData','http://www.mapbender.org/index.php/Add_WMS_from_filtered_list');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','addWMSfromfilteredList_ajax','capabilitiesInput','1','load wms by capabilities url','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','addWMSfromfilteredList_ajax','option_dball','1','1 enables option "load all configured wms from db"','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','addWMSfromfilteredList_ajax','option_dbgroup','0','1 enables option "load configured wms by group"','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','addWMSfromfilteredList_ajax','option_dbgui','0','1 enables option "load configured wms by gui"','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','mapbender',2,1,'Mapbender-Logo','','div','','onclick="javascript:window.open(''http://www.mapbender.org'','''','''');"',-59,81,1,1,30,'font-size : 10px;font-weight : bold;font-family: Arial, Helvetica, sans-serif;color:white;cursor:help;','<span>Ma</span><span style="color: blue;">P</span><span style="color: red;">b</span><span>ender</span><script type="text/javascript"> mb_registerSubFunctions("mod_mapbender()"); function mod_mapbender(){ document.getElementById("mapbender").style.left = parseInt(document.getElementById("mapframe1").style.left) + parseInt(document.getElementById("mapframe1").style.width) - 90; document.getElementById("mapbender").style.top = parseInt(document.getElementById("mapframe1").style.top) + parseInt(document.getElementById("mapframe1").style.height) -1; } </script>','div','','','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','setBackground',2,0,'switch background-wms','Hintegrundkarte auswählen','form','','action="window.location.href"',15,270,0,0,1,'','<select style="font-family: Arial, sans-serif; font-size:12" title="Set background" name="mod_setBackground_list" onchange="mod_setBackground_change(this)"><option value="0"></option></select>','form','mod_setBackground.php','','mapframe1','','http://www.mapbender.org/index.php/SetBackground');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','copyright',2,1,'a Copyright in the map','Copyright','div','','',0,80,NULL ,NULL,NULL ,'','','div','mod_copyright.php','','mapframe1','','http://www.mapbender.org/index.php/Copyright');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','copyright','mod_copyright_text','mapbender.org','define a copyright text which should be displayed','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','sandclock',2,1,'displays a sand clock while waiting for requests','','div','','',0,80,0,0,0,'','','div','mod_sandclock.js','','mapframe1','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','sandclock','mod_sandclock_image','../img/sandclock.gif','define a sandclock image ','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','treeConfGDE',2,0,'configurable directory tree','Karten','iframe','../php/mod_treefolderClient.php?sessionID','frameborder = "0" ',0,260,250,500,0,'visibility:visible','','iframe','mod_treeConf.js','','mapframe1','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','tabs',2,1,'vertical tabs to handle iframes','','div','','',5,295,195,20,3,'font-family: Arial,Helvetica;font-weight:bold;','','div','mod_tab.php','','','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','tabs','tab_frameHeight[0]','230','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','tabs','tab_frameHeight[1]','230','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','tabs','tab_frameHeight[2]','230','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','tabs','tab_ids[1]','wfsConfTree','','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','tabs','tab_ids[2]','feeds','','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','tabs','open_tab','0','define which tab should be opened when a gui is opened','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','tabs','tab_ids[0]','treeGDE','','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','tabs','tab_prefix','  ','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','tabs','expandable','0','1 = expand the tabs to fit the document vertically, default is 0','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','tabs','tab_style','position:absolute;visibility:visible;cursor:pointer;','','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','highlightPOI',2,0,'highlight 1 to n pois in your gui with a Symbol and a special text','','div','','',0,80,NULL ,NULL,NULL ,'','','div','mod_highlightPOI.php','','mapframe1','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','highlightPOI','poi_height','25','height of the poi_image','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','highlightPOI','poi_image','../img/redball.gif','image to use to mark the poi(s)','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','highlightPOI','poi_style','background-color:white;font-weight: bold;color:blue;font-family:Arial;','style to display the poi text','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','highlightPOI','poi_width','25','width of the poi_image','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','metadata',2,0,'shows informations about the wms and the requests of the gui','Anzeige von WMS Informationen','img','../img/button_blink_red/metadata_off.png','onClick="window.location.href=''javascript:mod_displayObj()''" border=''0'' onmouseover=''this.src="../img/button_blink_red/metadata_over.png"'' onmouseout=''this.src="../img/button_blink_red/metadata_off.png"'' ',703,98,24,24,1,'','','','mod_displayObj.js','','','','http://www.mapbender.org/index.php/Metadata');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','jq_ui_mouse',2,1,'jQuery UI mouse','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/jquery-ui-1.8.16.custom/development-bundle/ui/jquery.ui.mouse.js','','jq_ui_widget','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','treeGDE_1',2,0,'directory tree, checkbox for visible, checkbox for querylayer, no immediate refreshing, with nested layers','Karten','iframe','../html/mod_sync_treefolder_1.html','frameborder = "0" ',0,260,250,500,0,'visibility:visible','','iframe','','','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','navFrame',2,1,'navigation mapborder','Navigationsfenster','div','','',0,0,0,0,10,'font-size:1px;','','div','mod_navFrame.php','','mapframe1','','http://www.mapbender.org/index.php/NavFrame');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','addWMS',2,1,'add a WMS to the running application','WebMapService hinzufügen','img','../img/button_blue_red/add_off.png','',115,205,24,24,1,'','','','mod_addWMS.php','mod_addWMSgeneralFunctions.js','treeGDE,mapframe1','loadData','http://www.mapbender.org/index.php/AddWMS');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','back',2,1,'History.back()','Zurück','img','../img/button_blue_red/back_off_disabled.png','',145,85,24,24,1,'','','','mod_back.php','','mapframe1,overview','','http://www.mapbender.org/index.php/Back');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','changeEPSG',2,1,'change EPSG, Postgres required, overview is targed for full extent','Kartenprojektion ändern','select','','',5,265,196,26,1,'','<option value="">undefined</option>','select','mod_changeEPSG.php','','overview','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','changeEPSG','projections','EPSG:4326;Geographic Coordinates,EPSG:31466;Gauss-Krueger 2,EPSG:31467;Gauss-Krueger 3,EPSG:31468;Gauss-Krueger 4,EPSG:31469;Gauss-Krueger 5,EPSG:25832;UTM zone 32N','','php_var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','overview',2,0,'OverviewFrame','Übersichtskarte','div','','',5,85,110,115,2,'overflow:hidden;background-color:#ffffff','<div id="overview_maps" style="position:absolute;left:0px;right:0px;"></div>','div','../plugins/mb_overview.js','../../lib/history.js,map_obj.js,map.js,wms.js,wfs_obj.js,initWmcObj.php','mapframe1','mapframe1','http://www.mapbender.org/index.php/Overview');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','overview','overview_wms','','wms that shows up as overview','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','overview','skipWmsIfSrsNotSupported','0','if set to 1, it skips the WMS request if the current SRS is not supported by the WMS; if set to 0, the WMS is always queried. Default is 0, because of backwards compatibility','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','wfs_gazetteer',2,0,'gazetteer using wfs','Suche','iframe','../php/mod_wfs_gazetteer.php?sessionID&color=255,0,255','frameborder = "0" ',-50,570,250,300,0,'visibility:visible','','iframe','','','mapframe1,overview','wfs','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','center1',2,0,'Center button','Kartenmittelpunkt setzen','img','../img/button_blue_red/center_off.png','onmouseover = "mb_regButton(''init_gui1_center'')" ',310,140,24,24,1,'','','','mod_center1.php','','mapframe1','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','setBackground_all',2,0,'switch all background-wms','Hintegrundkarte auswählen','form','','',157,270,40,20,1,'','<input type=''checkbox'' onclick=''mod_setBackground_all_init(this)''> <font face="Arial, sans-serif" size="2">all</font>','form','mod_setBackground_all.php','','mapframe1','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','logout',2,0,'Logout','Abmelden','img','../img/button_blue_red/logout_off.png','onClick="window.location.href=''../php/mod_logout.php?sessionID''" border=''0'' onmouseover=''this.src="../img/button_blink_red/logout_over.png"'' onmouseout=''this.src="../img/button_blink_red/logout_off.png"'' ',704,126,24,24,1,'','','','','','','','http://www.mapbender.org/index.php/Logout');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','logout','logout_location','http://www.mapbender.org/','webside to show after logout','php_var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','repaint',2,0,'refresh a mapobject','Neu laden oder Tastatur: Leertaste','img','../img/button_blink_red/repaint_off.png','',360,140,24,24,1,'','','','mod_repaint.js','','mapframe1','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','loadData',2,1,'IFRAME to load data','','iframe','../html/mod_blank.html','frameborder = "0" ',0,80,1,1,0,'visibility:visible','','iframe','','','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','setBBOX',2,1,'set Extent for mapframe and overviewframe','','div','','',0,80,0,0,0,'','','div','mod_setBBOX1.php','','mapframe1','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','resultList',2,1,'','Result List','div','','',NULL ,NULL,NULL ,NULL,NULL ,'','','div','mod_ResultList.js','../../lib/resultGeometryListController.js, ../../lib/resultGeometryListModel.js','mapframe1','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','resultList','position','[600,50]','position of the result list dialog','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','resultList','resultListHeight','400','height of the result list dialog','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','resultList','resultListTitle','Search results','title of the result list dialog','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','resultList','resultListWidth','600','width of the result list dialog','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','resultList','tableTools','[
      {
	      "sExtends": "xls",
	      "sButtonText": "Export to CSV",
	      "sFileName": "result.csv"
      }
]','set the initialization options for tableTools','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','resultList_DetailPopup',2,1,'Detail Popup For resultList','','div','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','../plugins/mb_resultList_DetailPopup.js','','resultList','','http://www.mapbender.org/resultList_DetailPopup');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','resultList_DetailPopup','detailPopupHeight','250','height of the result list detail popup','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','resultList_DetailPopup','detailPopupTitle','Details','title of the result list detail popup','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','resultList_DetailPopup','detailPopupWidth','350','width of the result list detail popup','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','resultList_DetailPopup','openLinkFromSearch','0','open link directly if feature attr is defined as link','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','resultList_DetailPopup','position','[700,300]','position of the result list detail popup','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','resultList_Highlight',2,1,'highlighting functionality for resultList','','div','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','../plugins/mb_resultList_Highlight.js','','resultList,mapframe1,overview','','http://www.mapbender.org/resultList_Highlight');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','resultList_Highlight','maxHighlightedPoints','500','max number of points to highlight','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','resultList_Highlight','resultHighlightColor','#ff0000','color of the highlighting','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','resultList_Highlight','resultHighlightLineWidth','2','width of the highlighting line','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','resultList_Highlight','resultHighlightZIndex','100','zindex of the highlighting','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','georss',2,1,'','','div','','',NULL ,NULL,NULL ,NULL,NULL ,'','','div','','../../lib/mb.ui.displayFeatures.js','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','scalebar',2,1,'scalebar','Maßstabsleiste','div','','',0,80,NULL ,NULL,NULL ,'','','div','mod_scalebar.js','','mapframe1','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','printPDF',2,1,'pdf print','Druck','div','','',860,90,200,380,5,'','<div id="printPDF_working_bg"></div><div id="printPDF_working"><img src="../img/indicator_wheel.gif" style="padding:10px 0 0 10px">Generating PDF</div><div id="printPDF_input"><form id="printPDF_form" action="../print/printFactory.php"><div id="printPDF_selector"></div><div class="print_option"><input type="hidden" id="map_url" name="map_url" value=""/><input type="hidden" id="overview_url" name="overview_url" value=""/><input type="hidden" id="map_scale" name="map_scale" value=""/><input type="hidden" name="measured_x_values" /><input type="hidden" name="measured_y_values" /><br /></div><div class="print_option" id="printPDF_formsubmit"><input id="submit" type="submit" value="Print"><br /></div></form><div id="printPDF_result"></div></div>','div','../plugins/mb_print.php','../../lib/printbox.js,../extensions/jquery-ui-1.7.2.custom/development-bundle/external/bgiframe/jquery.bgiframe.js,../extensions/jquery.form.min.js,../extensions/wz_jsgraphics.js','mapframe1','','http://www.mapbender.org/index.php/Print');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','printPDF','mbPrintConfig','{"Format wählen": "Dummy_A4.json","A4 Hochformat": "Hochformat_A4.json","A4 Querformat": "Querformat_A4.json","A3 Hochformat": "Hochformat_A3.json","A3 Querformat": "Querformat_A3.json"}','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','printPDF','unlink','true','delete print pngs after pdf creation','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','printPDF','logRequests','false','log wms requests for debugging','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','printPDF','logType','file','log mode can be set to file or db','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','printPDF','timeout','90000','define maximum milliseconds to wait for print request finished','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','printPDF','legendColumns','2','define number of columns on legendpage','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','printPDF','printLegend','true','define whether the legend should be printed or not','php_var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','measure',2,0,'Measure','Messen','img','../img/button_blue_red/measure_off.png','onmouseover = "mb_regButton(''init_mod_measure'')"',145,175,24,24,1,'','','','mod_measure.php','','mapframe1','','http://www.mapbender.org/index.php/Measure');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','print1',2,0,'start print GUI','Druck','img','../img/button_blue_red/print_off.png','onclick=''window.open("../print/mod_printPDF.php?target=mapframe1&sessionID","printWin","width=300, height=400, resizable=yes ")''  onmouseover=''this.src = this.src.replace(/_off/,"_over");''  onmouseout=''this.src = this.src.replace(/_over/, "_off");'' ',555,140,24,24,1,'','','','','','mapframe1','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','forward',2,0,'History.forward()','Nach vorne','img','../img/button_blue_red/forward_off_disabled.png','',175,205,24,24,1,'','','','mod_forward.php','','mapframe1,overview','','http://www.mapbender.org/index.php/Forward');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','deleteSessionWmc',2,1,'delete Session Wmc','Kartenansicht zurücksetzen','img','../img/button_blue_red/repaint_off.png','onclick=''$("#sessionWmc").mapbender().deleteWmc();''
onmouseover="this.src = this.src.replace(/_off/,''_over'');" onmouseout="this.src = this.src.replace(/_over/, ''_off'');"',175,85,24,24,NULL ,'','','','','','mapframe1','sessionWmc','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','featureInfo1',2,1,'FeatureInfoRequest','Datenabfrage','img','../img/button_blue_red/query_off.png','',175,145,24,24,1,'','','','mod_featureInfo.php','','mapframe1','','http://www.mapbender.org/index.php/FeatureInfo');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','featureInfo1','featureInfoPopupHeight','200','height of the featureInfo dialog popup','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','featureInfo1','featureInfoPopupPosition','[100,100]','position of the featureInfoPopup','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','featureInfo1','featureInfoPopupWidth','270','width of the featureInfo dialog popup','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','featureInfo1','featureInfoLayerPopup','false','display featureInfo in dialog popup','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','feeds',2,1,'','GeoRSS Feeds','ul','','',1,81,1,1,NULL ,'','','ul','../plugins/feedTree.js','','mapframe1','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','measure_widget',2,1,'Measure','Messwerkzeug','img','../img/button_blue_red/measure_off.png','',175,175,24,24,1,'','','','../plugins/mb_measure_widget.php','../widgets/w_measure.js,../extensions/RaphaelJS/raphael-1.4.7.min.js','mapframe1','jq_ui_dialog,jq_ui_widget','http://www.mapbender.org/index.php/Measure');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','measure_widget','lineStrokeDefault','#C9F','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','measure_widget','lineStrokeSnapped','#F30','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','measure_widget','lineStrokeWidthDefault','3','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','measure_widget','lineStrokeWidthSnapped','5','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','measure_widget','measurePointDiameter','7','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','measure_widget','opacity','0.4','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','measure_widget','pointFillDefault','#CCF','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','measure_widget','pointFillSnapped','#F90','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','measure_widget','polygonFillDefault','#FFF','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','measure_widget','polygonFillSnapped','#FC3','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','measure_widget','polygonStrokeWidthDefault','1','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','measure_widget','polygonStrokeWidthSnapped','5','','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','pan1',2,1,'pan','Ausschnitt verschieben','img','../img/button_blue_red/pan_off.png','',145,145,24,24,1,'','','','mod_pan.js','','mapframe1','','http://www.mapbender.org/index.php/Pan');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','scaleText',2,1,'Scale-description field','Maßstab per Texteingabe','form','','action="window.location.href" onsubmit="return mod_scaleText()" ',5,235,30,30,NULL ,'','<input type="text" style="width:100px;">','form','mod_scaleText.php','','mapframe1','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','showCoords_div',2,1,'displays coordinates by onmouseover','Koordinaten anzeigen','img','../img/button_blue_red/coords_off.png','onmouseover = "mb_regButton(''init_mod_showCoords_div'')" ',145,175,24,24,1,'','','','mod_coords_div.php','','mapframe1','','http://www.mapbender.org/index.php/ShowCoords_div');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','zoomFull',2,1,'zoom to full extent button','Auf gesamte Karte zoomen','img','../img/button_blue_red/zoomFull_off.png','',115,85,24,24,2,'','','','mod_zoomFull.js','','mapframe1','','http://www.mapbender.org/index.php/ZoomFull');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','zoomIn1',2,1,'zoomIn button','In die Karte hineinzoomen','img','../img/button_blue_red/zoomIn2_off.png','',145,115,24,24,1,'','','','mod_zoomIn1.js','','mapframe1','','http://www.mapbender.org/index.php/ZoomIn');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','zoomOut1',2,1,'zoomOut button','Aus der Karte herauszoomen','img','../img/button_blue_red/zoomOut2_off.png','',115,115,24,24,1,'','','','mod_zoomOut1.js','','mapframe1','','http://www.mapbender.org/index.php/ZoomOut');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','treeGDE',2,1,'new treegde2 - directory tree, checkbox for visible, checkbox for querylayer
for more infos have a look at http://www.mapbender.org/index.php/TreeGDE2','Kartenebenen','div','','class="ui-widget"',12,224,152,300,2,'visibility:hidden;overflow:auto','','div','../html/mod_treefolderPlain.php','jsTree.js','mapframe1','mapframe1','http://www.mapbender.org/index.php/TreeGde');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','treeGDE','showstatus','true','show status in folderimages','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','treeGDE','alerterror','true','alertbox for wms loading error','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','treeGDE','css','../css/treeGDE2.css','','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','treeGDE','enlargetreewidth','400','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','treeGDE','ficheckbox','true','checkbox for featureInfo requests','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','treeGDE','handlesublayer','true','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','treeGDE','imagedir','../img/tree_new','image directory','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','treeGDE','localizetree','false','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','treeGDE','metadatalink','true','link for layer-metadata','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','treeGDE','openfolder','false','initial open folder','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','treeGDE','switchwms','true','enables/disables all layer of a wms','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','treeGDE','wmsbuttons','false','wms management buttons','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','treeGDE','menu','wms_up,wms_down,opacity_up,opacity_down,remove,layer_up,layer_down,zoom,hide','context menu elements','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','selArea1',2,1,'zoombox','Ausschnitt mit Box aufziehen','img','../img/button_blue_red/selArea_off.png','',175,115,24,24,1,'','','','mod_selArea.js','mod_box1.js','mapframe1','','http://www.mapbender.org/index.php/SelArea1');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','dependentDiv',2,1,'displays infos in a sticky div-tag','','div','','',-59,81,1,1,NULL ,'visibility:visible;position:absolute;font-size: 11px;font-family: "Arial", sans-serif;','','div','mod_dependentDiv.php','','mapframe1','','http://www.mapbender.org/index.php/DependentDiv');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','dragMapSize',2,1,'drag & drop Mapsize','Kartenausschnitt verschieben','div','','class="ui-state-default"',-59,81,15,15,2,'font-size:1px; cursor:move;','<span class="ui-icon ui-icon ui-icon-arrow-4"></span>','div','mod_dragMapSize.php','','mapframe1','','http://www.mapbender.org/index.php/DragMapSize');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','scaleSelect',2,1,'Scale-Selectbox','Maßstabsauswahl','select','','',5,205,105,20,1,'','<option value = ''''>Scale</option> <option value=''100''>1 : 100</option> <option value=''250''>1 : 250</option> <option value=''500''>1 : 500</option> <option value=''1000''>1 : 1000</option> <option value=''2500''>1 : 2500</option> <option value=''5000''>1 : 5000</option> <option value=''10000''>1 : 10000</option> <option value=''25000''>1 : 25000</option> <option value=''30000''>1 : 30000</option> <option value=''50000''>1 : 50000</option> <option value=''75000''>1 : 75000</option> <option value=''100000''>1 : 100000</option> <option value=''200000''>1 : 200000</option> <option value=''300000''>1 : 300000</option> <option value=''400000''>1 : 400000</option> <option value=''500000''>1 : 500000</option> <option value=''600000''>1 : 600000</option> <option value=''700000''>1 : 700000</option> <option value=''800000''>1 : 800000</option> <option value=''900000''>1 : 900000</option> <option value=''1000000''>1 : 1000000</option>','select','../plugins/mb_selectScale.js','','mapframe1','','http://www.mapbender.org/ScaleSelect');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','jq_ui_position',2,1,'jQuery UI position','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/jquery-ui-1.8.16.custom/development-bundle/ui/jquery.ui.position.js','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','resizeMapsize',2,1,'resize_mapsize','Vollbild','img','../img/button_blue_red/resizemapsize_off.png','onmouseover="this.src = this.src.replace(/_off/,''_over'');" onmouseout="this.src = this.src.replace(/_over/, ''_off'');"',115,145,24,24,NULL ,'','','','../geoportal/mod_resize_mapsize.js','','mapframe1','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','resizeMapsize','adjust_height','-285','to adjust the height of the mapframe on the bottom of the window','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','resizeMapsize','adjust_width','-115','to adjust the width of the mapframe on the right side of the window','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','resizeMapsize','resize_option','auto','auto (autoresize on load), button (resize by button)','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','WMS_preferences',2,1,'configure the preferences of each loaded wms','WMS Einstellungen','img','../img/button_blue_red/preferences_off.png','onclick=''window.open("../php/mod_WMSpreferences.php?sessionID","","width=400, height=600, left=300, resizable=yes, scrollbars=yes")''  onmouseover=''this.src = this.src.replace(/_off/,"_over");''  onmouseout=''this.src = this.src.replace(/_over/, "_off");'' ',145,205,24,24,1,'','','','','','mapframe1,treeGDE','','http://www.mapbender.org/index.php/WMS_preferences');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','header_blue',2,1,'header','','img','../img/bg_blue_sl.jpg','',-1,-1,NULL ,75,NULL ,'width:100%','','','','','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','wmcTemplateLogo',2,1,'Add a Logo to the wmc template','','img','','',0,10,NULL ,NULL,5,'font-size:14px;color:white;margin-top:5px;border:2px;padding:4px;max-height:48px;max-width:135px;','','','','','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','layout_1',3,1,'layout, background for buttons','','div','','',383,173,670,28,0,'background-color:#FFFFFF;','','div','','','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','jq_ui_droppable',4,1,'jQuery UI droppable','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/jquery-ui-1.8.16.custom/development-bundle/ui/jquery.ui.droppable.js','','jq_ui,jq_ui_widget,jq_ui_mouse,jq_ui_draggable','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','mapframe1_zoomBar',4,0,'zoom bar - slider to handle navigation, define zoom level with an element var','Zoom to scale','div','','',30,180,NULL ,200,100,'','','div','../plugins/mb_zoomBar.js','','mapframe1','mapframe1, jq_ui_slider','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','mapframe1_zoomBar','defaultLevel','1','define the default level for application start','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','mapframe1_zoomBar','level','[2500,5000,10000,50000,100000,500000,1000000,3000000,5000000,10000000]','define an array of levels for the slider (element_var has to be defined)','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','renderGML',4,1,'renders a gml contained in $_SESSION[''GML'']','','div','','',NULL ,NULL,NULL ,NULL,1,'','','','../javascripts/mod_renderGML.php','','overview,mapframe1','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','mapframe1_navigation',4,0,'Adds navigation arrows on top of the map','Navigation','div','','',20,110,NULL ,NULL,100,'','','div','../plugins/mb_navigation.js','','mapframe1','mapframe1','http://www.mapbender.org/Navigation');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','jq_ui_button',4,1,'jQuery UI button','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/jquery-ui-1.8.16.custom/development-bundle/ui/jquery.ui.button.js','','jq_ui,jq_ui_widget','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','jq_ui_draggable',5,1,'Draggable from the jQuery UI framework','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/jquery-ui-1.8.16.custom/development-bundle/ui/jquery.ui.draggable.js','','jq_ui,jq_ui_mouse,jq_ui_widget','http://jqueryui.com/demos/draggable/');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','wmcTemplateTitle',5,1,'add a Titel to the wmc template','','div','','',200,10,200,100,5,'font-size:30px;color:white;margin-top:5px;border:2px;padding:4px;','','div','','','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','jq_ui_slider',5,1,'slider from the jQuery UI framework','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/jquery-ui-1.8.16.custom/development-bundle/ui/minified/jquery.ui.slider.min.js','','jq_ui,jq_ui_mouse,jq_ui_widget','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','jq_ui_tabs',5,1,'horizontal tabs from the jQuery UI framework','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/jquery-ui-1.8.16.custom/development-bundle/ui/jquery.ui.tabs.js','','jq_ui,jq_ui_widget','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','jq_ui_resizable',5,1,'Resizable from the jQuery UI framework','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','../plugins/jq_ui_resizable.js','../extensions/jquery-ui-1.8.16.custom/development-bundle/ui/jquery.ui.resizable.js','','jq_ui,jq_ui_mouse,jq_ui_widget','http://jqueryui.com/demos/resizable/');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','overviewToggle',5,0,'','','div','','class="ui-widget-header ui-corner-all"',NULL ,NULL,NULL ,NULL,NULL ,'display:none;height:24px;width:35px;vertical-align: middle;text-align:right','<img style="position:absolute;top:0px;left:0px" src="../img/ovtoggle.png" /><span style="margin-left: auto; margin-right: 0;" class="ui-icon ui-icon-triangle-1-e"></span>','div','../javascripts/mod_overviewToggle.js','','legend','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','jq_ui_dialog',5,1,'Module to manage jQuery UI dialog windows with multiple options for customization.','','div','','',-1,79,15,15,NULL ,'','','div','','../extensions/jquery-ui-1.8.16.custom/development-bundle/ui/jquery.ui.dialog.js','','jq_ui,jq_ui_widget,jq_ui_button,jq_ui_draggable,jq_ui_mouse,jq_ui_position,jq_ui_resizable','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','wmcTemplateLinkList1',6,1,'Add a Link to the link list at the wmc template','','a','','href="" target="_new"',550,10,NULL ,NULL,5,'font-size:14px;color:white;margin-top:5px;border:2px;padding:4px;','','a','','','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','wmcTemplateLink1',6,1,'Add a Link to the wmc template','','a','','href="#" target="_new"',800,10,NULL ,NULL,5,'font-size:14px;color:white;margin-top:5px;border:2px;padding:4px;','','a','','','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','wmcTemplateLinkList3',6,1,'Add a Link to the link list at the wmc template','','a','','href="#" target="_new"',550,40,NULL ,NULL,5,'font-size:14px;color:white;margin-top:5px;border:2px;padding:4px;','','a','','','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','wmcTemplateLinkList2',6,1,'Add a Link to the link list at the wmc template','','a','','href="#" target="_new"',550,25,NULL ,NULL,5,'font-size:14px;color:white;margin-top:5px;border:2px;padding:4px;','','a','','','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','wmcTemplateLinkDownload',6,1,'Add a Download Link to the  wmc template','','a','','href="" target="_new"',5,640,NULL ,NULL,5,'font-size:14px;color:black;margin-top:5px;border:2px;padding:4px;','','a','','','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','popup',6,1,'popup replacement','','div','','',NULL ,NULL,NULL ,NULL,NULL ,'','','div','popup.js','','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','printPdfButton',7,1,'popup','PDF drucken','img','../img/button_blue_red/print_off.png','',175,205,24,24,NULL ,'','','','../plugins/mb_button.js','','printPDF','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','legendButton',7,1,'popup','Legende','img','../img/button_blue_red/select_choose_off.png','',175,235,24,24,NULL ,'','','','../plugins/mb_button.js','','legend','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','coordsLookUpButton',7,1,'popup','Koordinatensuche','img','../img/button_blue_red/user_off.png','',115,175,24,24,NULL ,'','','','../plugins/mb_button.js','','coordsLookup','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('Template','coordsLookup',10,1,'','Koordinatensuche','div','','',1000,80,NULL ,NULL,NULL ,'z-index:9999;','','div','mod_coordsLookup.php','','mapframe1','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','coordsLookup','perimeters','[50,200,1000,10000]','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('Template','coordsLookup','projections','EPSG:4326;Geographic Coordinates,EPSG:31466;Gauss-Krueger 2,EPSG:31467;Gauss-Krueger 3,EPSG:31468;Gauss-Krueger 4,EPSG:31469;Gauss-Krueger 5,EPSG:25832;UTM zone 32N','','php_var');

-- neue Gui "Template" dem root-User (ID: 1) zuweisen, damit sie in der Administration erscheint
INSERT INTO gui_mb_user (fkey_gui_id, fkey_mb_user_id,mb_user_type) VALUES ('Template',1,'owner');



-- Anlegen der neuen Tabelle mb_user_wmc_template

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

SET search_path = public, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = false;

CREATE TABLE mb_user_wmc_template (
    el_id integer NOT NULL,
    fkey_wmc_id integer NOT NULL,
    target character varying(40) NOT NULL,
    type character varying(20) NOT NULL,
    key character varying(20) NOT NULL,
    value character varying(500) NOT NULL
);


ALTER TABLE mb_user_wmc_template OWNER TO postgres;

CREATE SEQUENCE mb_user_wmc_extension_element_el_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

ALTER TABLE mb_user_wmc_extension_element_el_id_seq OWNER TO postgres;

ALTER SEQUENCE mb_user_wmc_extension_element_el_id_seq OWNED BY mb_user_wmc_template.el_id;

ALTER TABLE ONLY mb_user_wmc_template ALTER COLUMN el_id SET DEFAULT nextval('mb_user_wmc_extension_element_el_id_seq'::regclass);

ALTER TABLE ONLY mb_user_wmc_template
    ADD CONSTRAINT mb_user_wmc_extension_element_pkey PRIMARY KEY (el_id);

ALTER TABLE ONLY mb_user_wmc_template
    ADD CONSTRAINT mb_user_wmc_extension_element_fkey_wmc_id_fkey FOREIGN KEY (fkey_wmc_id) REFERENCES mb_user_wmc(wmc_serial_id) ON UPDATE CASCADE ON DELETE CASCADE;


-- Anlegen der neuen Admin-Gui für WMC-Template: admin_wmc_template
INSERT INTO gui (gui_id, gui_name, gui_description, gui_public) VALUES ('admin_wmc_template','admin_wmc_template','Administration for WMC metadata',1);
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','body_old',0,1,'body (obligatory)','','body','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','','','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','body_old','favicon','../img/favicon.ico','favicon','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','body_old','includeWhileLoading','','show splash screen while the application is loading','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','body_old','jq_datatables_css_ui','../extensions/dataTables-1.5/media/css/demo_table_jui.min.css','css-file for jQuery datatables','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','body_old','jq_ui_theme','../extensions/jquery-ui-1.7.2.custom/css/smoothness/jquery-ui-1.7.2.custom.css','UI Theme from Themeroller','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','body_old','use_load_message','true','show splash screen while the application is loading','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','body_old','jq_datatables_css','../extensions/dataTables-1.5/media/css/site_jui.css','css-file for jQuery datatables','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','body_old','css_class_bg','body{ background-color: #ffffff; margin: 5 5 5 5 }
* {
        font-family: Verdana;
        font-size: 96%;
}

label {
        width: 250px;
        height: 40px;
        float: left;
}

p {
        clear: both;
}

input {
        font-weight: bold;
        vertical-align: top;
        width: 250px;
        height: 25px;
}

.metadata_span {
        padding-right: 30px;
        vertical-align: top;
}

.metadata_img {
        width: 25px;
        height: 25px;
        vertical-align: top;
}

.metadata_selectbox {
        height: 130px;
        width: 250px;
        vertical-align: top;
}

div#choose {
        float: left;
        width: 20%;
        height:100%;
}

div#layer {
        margin-left: 20%;
        width: 80%;
}

div#preview {
        width: 30%;
        padding-top: 5px;
        float: left;
}

div#classification {
        margin-left: 50%;
        padding-top: 5px;
        width: 50%;
}

div#save {
        margin-left: 45em;
}

div#buttons {
        float: left;
}

div#selectbox {
        margin-left: 312px;
        padding-top: 20px;
}','to define the color of the body','text/css');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','i18n',1,1,'Internationalization module, collects data from all elements and sends them to the server in a single POST request. The strings are translated via gettext only.','Internationalization','div','','',NULL ,NULL,NULL ,NULL,NULL ,'','','div','../plugins/mb_i18n.js','','','','http://www.mapbender.org/Gettext');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','jq_jstree',1,1,'jsTree','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/jsTree.v.0.9.9a2/jquery.tree.js','','','http://www.jstree.com/');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','jq_jstree_checkbox',1,0,'jsTree checkbox plugin','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/jsTree.v.0.9.9a2/plugins/jquery.tree.checkbox.js','','','http://www.jstree.com/');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','mb_helpDialog',1,1,'','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','mb_helpDialog.js','','jq_ui_dialog, jq_metadata','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','jq_layout',1,1,'','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/jquery.layout.all-1.2.0/jquery.layout.min.js','','','http://layout.jquery-dev.net');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','jq_metadata',1,1,'Metadata plugin','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/jquery.metadata.2.1/jquery.metadata.min.js','','','http://plugins.jquery.com/project/metadata');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','jq_validate',1,1,'The jQuery validation plugin','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','../javascripts/jq_validate.js','../extensions/jquery-validate/jquery.validate.min.js','','','http://docs.jquery.com/Plugins/Validation');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','jq_validate','css','label.error { float: none; color: red; padding-left: .5em; vertical-align: top; }','','text/css');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','jq_ui_effects',1,1,'Effects from the jQuery UI framework','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','jq_ui_effects.php','../extensions/jquery-ui-1.8.16.custom/development-bundle/ui/minified/jquery.effects.core.min.js','','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','jq_ui_effects','blind','1','1 = effect active','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','jq_ui_effects','bounce','0','1 = effect active','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','jq_ui_effects','clip','0','1 = effect active','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','jq_ui_effects','drop','0','1 = effect active','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','jq_ui_effects','explode','0','1 = effect active','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','jq_ui_effects','fold','1','1 = effect active','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','jq_ui_effects','highlight','0','1 = effect active','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','jq_ui_effects','pulsate','0','1 = effect active','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','jq_ui_effects','scale','0','1 = effect active','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','jq_ui_effects','shake','0','1 = effect active','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','jq_ui_effects','slide','0','1 = effect active','php_var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','jq_ui_effects','transfer','0','1 = effect active','php_var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','jq_ui',1,1,'The jQuery UI core','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/jquery-ui-1.8.16.custom/development-bundle/ui/minified/jquery.ui.core.min.js','','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','jq_ui','css','../extensions/jquery-ui-1.7.2.custom/css/ui-lightness/jquery-ui-1.7.2.custom.css','','file/css');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','jq_datatables',1,1,'Includes the jQuery plugin datatables, use like this
$(selector).datatables(options)','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','../plugins/jq_datatables.js','../extensions/dataTables-1.7.5/media/js/jquery.dataTables.min.js,../extensions/dataTables-1.7.5/extras/TableTools-2.0.0/media/js/TableTools.min.js','','','http://www.datatables.net/');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','jq_datatables','defaultCss','../extensions/dataTables-1.5/media/css/demo_table_jui.css','','file/css');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','body',1,1,'body (obligatory)','','div','','',NULL ,NULL,NULL ,500,NULL ,'position: relative !important','','div','','','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','mapframe1',1,0,'frame for a map','','div','','',230,55,200,200,3,'border:1px solid black;overflow:hidden;background-color:#ffffff;display:none;','','div','../plugins/mb_map.js','../../lib/history.js,map_obj.js,map.js,wms.js,wfs_obj.js,initWms.php','','','http://www.mapbender.org/index.php/Mapframe');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','mapframe1','skipWmsIfSrsNotSupported','0','if set to 1, it skips the WMS request if the current SRS is not supported by the WMS; if set to 0, the WMS is always queried. Default is 0, because of backwards compatibility','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','mapframe1','slippy','0','1 = Activates an animated, pseudo slippy map','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','mb_md_wmc_template_submit',1,1,'','','div','','',NULL ,NULL,NULL ,NULL,NULL ,'','<span style=''float:right''><input type=''button'' value=''Clear Template''><input type=''submit'' value=''Save Template''></span>','div','../plugins/mb_template_wmc_submit.js','','','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','mb_md_wmc_template_submit','inputs','[
    {
        "method": "enable",
        "linkedTo": [
            {
                "id": "mb_md_wmc_template_select",
                "event": "selected"
            } 
        ] 
    }
]','','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','mb_pane',1,1,'','','div','','',NULL ,NULL,NULL ,NULL,NULL ,'height:100%;width:100%','','div','../plugins/mb_pane.js','','','jq_layout','http://layout.jquery-dev.net');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','mb_pane','center','mb_tabs_horizontal','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','mb_pane','css','../extensions/jquery.layout.all-1.2.0/jquery.layout.css','','file/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','mb_pane','south','mb_md_wmc_template_submit','','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','jq_ui_widget',2,1,'jQuery UI widget','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/jquery-ui-1.8.16.custom/development-bundle/ui/minified/jquery.ui.widget.min.js','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','scaleSelect',2,0,'Scale-Selectbox','Auswahl des Maßstabes','select','','',555,25,100,24,1,'','<option value = ''''>Scale</option> <option value=''100''>1 : 100</option> <option value=''250''>1 : 250</option> <option value=''500''>1 : 500</option> <option value=''1000''>1 : 1000</option> <option value=''2500''>1 : 2500</option> <option value=''5000''>1 : 5000</option> <option value=''10000''>1 : 10000</option> <option value=''25000''>1 : 25000</option> <option value=''30000''>1 : 30000</option> <option value=''50000''>1 : 50000</option> <option value=''75000''>1 : 75000</option> <option value=''100000''>1 : 100000</option> <option value=''200000''>1 : 200000</option> <option value=''300000''>1 : 300000</option> <option value=''400000''>1 : 400000</option> <option value=''500000''>1 : 500000</option> <option value=''600000''>1 : 600000</option> <option value=''700000''>1 : 700000</option> <option value=''800000''>1 : 800000</option> <option value=''900000''>1 : 900000</option> <option value=''1000000''>1 : 1000000</option>','select','../plugins/mb_selectScale.js','','mapframe1','','http://www.mapbender.org/ScaleSelect');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','selArea1',2,0,'zoombox','Ausschnitt wählen','img','../img/button_gray/selArea_off.png','',295,10,24,24,3,'display: none;','','','mod_selArea.js','mod_box1.js','mapframe1','','http://www.mapbender.org/index.php/SelArea1');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','zoomFull',2,0,'zoom to full extent button','gesamte Karte anzeigen','img','../img/button_gray/zoomFull_off.png','',320,10,24,24,2,'display: none;','','','mod_zoomFull.js','','mapframe1','','http://www.mapbender.org/index.php/ZoomFull');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','zoomIn1',2,0,'zoomIn button','In die Karte hineinzoomen','img','../img/button_gray/zoomIn2_off.png','',220,10,24,24,1,'','','','mod_zoomIn1.js','','mapframe1','','http://www.mapbender.org/index.php/ZoomIn');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','zoomOut1',2,0,'zoomOut button','Aus der Karte herauszoomen','img','../img/button_gray/zoomOut2_off.png','',245,10,24,24,1,'','','','mod_zoomOut1.js','','mapframe1','','http://www.mapbender.org/index.php/ZoomOut');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','mb_md_wmc_preview_save',2,0,'','','img','../img/button_gray/wmc_save_off.png','',NULL ,NULL,NULL ,NULL,NULL ,'display: none;','','','../plugins/mb_metadata_saveWmcPreview.js','','mapframe1','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','mb_md_wmc_preview_save','inputs','[
    {   
        "method":"setWmc",
        "title":"set the current Wmc",
        "linkedTo": [
            {   
                "id":"mb_md_wmc_select",
                "event":"selected",
                "attr":"wmcId"
            }   
        ]   

    }   
     
]','','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','loadwmc',2,0,'load workspace from WMC','laden eines Web Map Context Dokumentes','img','../img/button_blink_red/wmc_load_off.png','onmouseover=''this.src = this.src.replace(/_off/,"_over");''  onmouseout=''this.src = this.src.replace(/_over/, "_off");''',895,60,24,24,1,'display:none;','','','mod_loadwmc.php','popup.js','mapframe1','jq_ui_dialog,jq_ui_tabs,jq_upload,jq_datatables','http://www.mapbender.org/index.php/LoadWMC');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','loadwmc','deleteWmc','0','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','loadwmc','inputs','[
    {   
        "method": "load",
        "linkedTo": [
            {   
                "id": "mb_md_wmc_select",
                "event": "selected",
                "attr": "wmcId"
            }   
        ]   
    }
]','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','loadwmc','publishWmc','1','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','loadwmc','saveWmcTarget','savewmc','target for savewmc ','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','mb_md_wmc_showOriginal',2,0,'Show original metadata','Show original metadata','div','','',NULL ,NULL,NULL ,NULL,NULL ,'display:none;','','div','../plugins/mb_metadata_wmc_showOriginal.js','','','jq_ui_dialog','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','mb_md_wmc_showOriginal','differentFromOriginalCss','.differentFromOriginal{
background-color:#FFFACD;
}','','text/css');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','mb_md_wmc_showOriginal','inputs','[
    {
        "method": "init",
        "title": "initialize",
        "linkedTo": [
            {
                "id": "mb_md_wmc_edit",
                "event": "showOriginalMetadata",
                "attr": "data" 
            }
        ] 
    }
]','','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','mb_md_wmc_template_edit',2,1,'Edit WMC template','Edit WMC Template','div','','',NULL ,NULL,NULL ,NULL,NULL ,'overflow:auto','','div','../plugins/mb_template_wmc_edit.js','','','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','mb_md_wmc_template_edit','inputs','[
    {
        "method": "init",
        "title": "initialize",
        "linkedTo": [
            {
                "id": "mb_md_wmc_template_select",
                "event": "selected",
                "attr": "wmcId" 
            } 
        ] 
    },
    {
        "method": "serialize",
        "linkedTo": [
            {
                "id": "mb_md_wmc_template_submit",
                "event": "submit",
                "attr": "callback" 
            } 
        ] 
    },
    {
        "method": "fill",
        "linkedTo": [
            {
                "id": "mb_md_wmc_showOriginal",
                "event": "replaceMetadata",
                "attr": "data" 
            } 
        ] 
    }
]','','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','mb_md_wmc_template_select',2,1,'Select a WMC','Select WMC','div','','',NULL ,NULL,NULL ,NULL,NULL ,'','<table class=''display''></table>','div','../plugins/mb_template_wmc_select.js','','','jq_datatables','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','jq_ui_position',2,1,'jQuery UI position','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/jquery-ui-1.8.16.custom/development-bundle/ui/minified/jquery.ui.position.min.js','','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','changeEPSG',2,0,'change EPSG, Postgres required, overview is targed for full extent','Projektion ändern','select','','',432,25,107,24,1,'','<option value="">undefined</option>
<option value="EPSG:4326">EPSG:4326</option>
<option value="EPSG:31466">EPSG:31466</option>
<option value="EPSG:31467">EPSG:31467</option>
<option value="EPSG:31468">EPSG:31468</option>
<option value="EPSG:31469">EPSG:31469</option>','select','mod_changeEPSG.js','','overview','','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','pan1',2,0,'pan','Ausschnitt verschieben','img','../img/button_gray/pan_off.png','',270,10,24,24,1,'','','','mod_pan.js','','mapframe1','','http://www.mapbender.org/index.php/Pan');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','mb_tabs_horizontal',3,1,'Puts existing elements into horizontal tabs, using jQuery UI tabs. List the elements comma-separated under target, and make sure they have a title.','','div','','',NULL ,NULL,NULL ,NULL,NULL ,'','<ul></ul><div class=''ui-layout-content''></div>','div','../plugins/mb_tabs_horizontal.js','','mb_md_wmc_template_select,mb_md_wmc_template_edit','jq_ui_tabs','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','mb_tabs_horizontal','inputs','[
    {
        "type": "id",
        "method": "select",
        "title": "Select a tab",
        "linkedTo": [
            {
                "id": "mb_md_wmc_template_select",
                "event": "selected",
                "value": "mb_md_wmc_template_edit" 
            } 
        ] 
    }
] ','','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','mb_md_featuretype',3,0,'Edit featuretype metadata','Edit featuretype metadata','div','','',NULL ,NULL,NULL ,NULL,NULL ,'','','div','../plugins/mb_metadata_featuretype.js','','','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','mb_md_featuretype','inputs','[
    {
        "method": "init",
        "title": "initialize wfs data",
        "linkedTo": [
            {
                "id": "mb_md_wmc_select",
                "event": "selected",
                "attr": "wmcId" 
            } 
        ] 
    },
    {
        "method": "serialize",
        "linkedTo": [
            {
                "id": "mb_md_wmc_submit",
                "event": "submit",
                "attr": "callback" 
            } 
        ] 
    },
    {
        "method": "fill",
        "linkedTo": [
            {
                "id": "mb_md_wmc_showOriginal",
                "event": "replaceMetadata",
                "attr": "data" 
            } 
        ] 
    }
] ','','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','jq_ui_mouse',3,1,'jQuery UI mouse','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/jquery-ui-1.8.16.custom/development-bundle/ui/minified/jquery.ui.mouse.min.js','','jq_ui_widget','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','jq_ui_button',4,1,'jQuery UI button','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/jquery-ui-1.8.16.custom/development-bundle/ui/minified/jquery.ui.button.min.js','','jq_ui,jq_ui_widget','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','mb_md_wmc_preview',4,0,'Allows selection of a preview image of a wmc','','div','','',NULL ,NULL,NULL ,NULL,NULL ,'','','div','../plugins/mb_metadata_layerPreview.js','mod_addWMSgeneralFunctions.js','','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','mb_md_wmc_preview','inputs','[
    {   
        "method": "init",
        "title": "initialize",
        "linkedTo": [
            {   
                "id": "mb_md_wmc_select",
                "event": "selected"
            }   
        ]   
    }
]','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','mb_md_wmc_preview','map','mapframe1','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','mb_md_wmc_preview','toolbarLower','[''changeEPSG'',''scaleSelect'']','','var');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','mb_md_wmc_preview','toolbarUpper','[''zoomFull'',''zoomOut1'',''zoomIn1'',''selArea1'',''pan1'',''mb_md_wmc_preview_save'']','','var');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','jq_ui_droppable',4,1,'jQuery UI droppable','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/jquery-ui-1.8.16.custom/development-bundle/ui/minified/jquery.ui.droppable.min.js','','jq_ui,jq_ui_widget,jq_ui_mouse,jq_ui_draggable','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','jq_ui_draggable',5,1,'Draggable from the jQuery UI framework','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/jquery-ui-1.8.16.custom/development-bundle/ui/minified/jquery.ui.draggable.min.js','','jq_ui,jq_ui_mouse,jq_ui_widget','http://jqueryui.com/demos/draggable/');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','jq_ui_tabs',5,1,'horizontal tabs from the jQuery UI framework','','','','',NULL ,NULL,NULL ,NULL,NULL ,'','','','','../extensions/jquery-ui-1.8.16.custom/development-bundle/ui/minified/jquery.ui.tabs.min.js','','jq_ui,jq_ui_widget','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','jq_ui_dialog',5,1,'Module to manage jQuery UI dialog windows with multiple options for customization.','','div','','',-1,-1,NULL ,NULL,NULL ,'','','div','','../extensions/jquery-ui-1.8.16.custom/development-bundle/ui/minified/jquery.ui.dialog.min.js','','jq_ui,jq_ui_widget,jq_ui_button,jq_ui_draggable,jq_ui_mouse,jq_ui_position,jq_ui_resizable','');
INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,e_url) VALUES ('admin_wmc_template','switchLocale_noreload',8,0,'changes the locale without reloading the client','Sprache auswählen','div','','',900,20,50,30,5,'','<form id="form_switch_locale" action="window.location.href" name="form_switch_locale" target="parent"><select id="language" name="language" onchange="validate_locale()"></select></form>','div','mod_switchLocale_noreload.php','','','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES ('admin_wmc_template','switchLocale_noreload','languages','de,en,bg,gr,nl,hu,it,fr,es,pt','','var');

-- Zuweisen der Gui admin_wmc_template zu User root
INSERT INTO gui_mb_user (fkey_gui_id, fkey_mb_user_id,mb_user_type) VALUES ('admin_wmc_template',1,'owner');

-- Hinzufügen der neuen Elemente für Erstellung WMC-Templates zu Gui Administration_DE
INSERT INTO gui_element(fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element, e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires, e_url) VALUES('Administration_DE','wmc_template_collection',2,1,'Put existing divs in new div object. List the elements comma-separated under target, and make sure they have a title.','','div','','',0,30,200,30,NULL ,'','','div','../plugins/mb_div_collection.js','','wmc_template,wmc_template_icon','','');
INSERT INTO gui_element(fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element, e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires, e_url) VALUES('Administration_DE','wmc_template',2,1,'wmc template editor','Templateeditor WMC','a','','href = "../frames/index.php?guiID=admin_wmc_template"
',80,15,NULL ,NULL ,NULL ,'font-family: Arial, Helvetica, sans-serif; font-size : 12px; text-decoration : none; color: #808080;','Template Editor','a','','','','','');
INSERT INTO gui_element(fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element, e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires, e_url) VALUES('Administration_DE','wmc_template_icon',2,1,'icon','','img','../img/gnome/preferences-other.png','',0,10,NULL ,NULL ,2,'','','','','','','','');
UPDATE gui_element SET e_target = 'wmc_metadata_collection,wmc_template_collection' WHERE fkey_gui_id = 'Administration_DE' AND e_id = 'menu_wmc'; 


