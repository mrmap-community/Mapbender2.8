UPDATE gui_element_vars SET var_value = '../include/geoportal_logo_splash.php' WHERE fkey_gui_id = 'Geoportal-Hessen-2019' AND fkey_e_id = 'body' AND var_name = 'includeWhileLoading';
INSERT INTO gui_element(fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element, e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires, e_url) VALUES('Geoportal-Hessen-2019','applicationMetadata',1,1,'','Application info','div','','',NULL ,NULL ,NULL ,NULL ,NULL ,'','','div','../plugins/mod_applicationMetadata.php','','','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES('Geoportal-Hessen-2019', 'applicationMetadata', 'displayTermsOfUse', '1', '' ,'var');
INSERT INTO gui_element(fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element, e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires, e_url) VALUES('Geoportal-Hessen-2019','app_metadata',1,1,'','','div','','class="toggleAppMetadata" title="App-Metadaten"',NULL ,NULL ,NULL ,NULL ,NULL ,'','<div id="appMetadataLogo"><img src="../img/GeoportalHessen.png"></div><div id="appMetadataTitle">Geoportal-Hessen</div><svg style="transform:rotate(180deg);" width="17" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16.0142 11.6191L14.6042 13.0291L9.01416 7.43914L3.42416 13.0291L2.01416 11.6191L9.01416 4.61914L16.0142 11.6191Z" stroke="currentColor" stroke-width="2"></path></svg><div id="appMetadataContainer" style="overflow-y: auto;line-height: 15px;font-size: 12px;font-weight:1;">Standardkartenviewer des Geoportal-Hessen</div>','div','../plugins/mb_appMetadataContainer.js','','','','');
INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES('Geoportal-Hessen-2019', 'app_metadata', 'css', '/* INSERT app_metadata -> elementVar -> css(text/css) */

#app_metadata {
float: left;
position: relative;
box-sizing: border-box;
font-family: Helvetica,Roboto,Arial,sans-serif;
color: #777;
font-style: normal;
font-weight:700;
letter-spacing: 1px;
padding-right:10px;
line-height: 50px;
display: block;
cursor:pointer;
margin-left: 20px;
border-left: 2px solid transparent;
border-right: 2px solid transparent;
height:51px;
}

#app_metadata:hover {
color:#333;
background-color: #EEE;
}

.appMetadataContainerOpened svg {
transform: rotate(0deg) !important;
}

#app_metadata svg {
padding: 0px 5px;
margin: 16px auto;
}
#app_metadata svg, #appMetadataLogo, #appMetadataTitle {
position:relative;
float:left;
}

#appMetadataLogo {
width:100px;
top:0;
bottom:0;
position:absolute;
}
#appMetadataLogo img {
max-height:49px;
max-width:100px;
border:none;
margin: auto 0;
top: 0;
bottom: 0;
position: absolute;
right: 0;
}

#appMetadataTitle{
max-width: calc(100vw - 664px);
max-height: 50px;
overflow:hidden;
margin-left:110px;
}

#appMetadataContainer{
margin-top: 52px;
width: 452px;
padding: 5px;
list-style-type: none;
position: absolute;
display: none;
max-height:calc(100vh - 56px);
box-shadow: 0px 5px 10px -2px rgb(201, 202, 202);
overflow: hidden;
background-color:white;
border-left: 2px solid #DDD;
border-right: 2px solid #DDD;
margin-left: -3px;
box-sizing: border-box;
border-bottom:2px solid #DDD;
overflow-y: auto;
font-size:12px;
font-weight:normal;
line-height:15px;
}
#appMetadataContainer br {margin-top: 5px;display: block;}

.appMetadataContainerOpened {
background-color: #EEE;
color:#333 !important;
border-left: 2px solid #DDD !important;
border-right: 2px solid #DDD !important;
}

@media (max-width: 968px) {
#app_metadata{line-height:25px;font-weight:normal;}
#appMetadataTitle{width: calc(100vw - 672px);}
#appMetadataContainer{width: calc(100vw - 519px);min-width:238px;}
}
@media (max-width: 820px) {
#appMetadataTitle{display:none;}
#app_metadata svg{float:right !important;}
#app_metadata{width:150px;}
#appMetadataContainer{width:238px;}
}

@media (max-width: 675px) {
#app_metadata{display:none;}
}

/* END INSERT app_metadata-> elementVar -> css(text/css) */', '' ,'text/css');

UPDATE gui_element SET e_target = 'mapsContainer,toolbarContainer,toolbar,app_metadata,jsonAutocompleteGazetteer' WHERE fkey_gui_id = 'Geoportal-Hessen-2019' AND e_id = 'Div_collection2';
