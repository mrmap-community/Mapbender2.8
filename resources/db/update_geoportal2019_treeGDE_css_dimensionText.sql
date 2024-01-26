UPDATE gui_element_vars SET var_value = '/* INSERT treeGDE-> elementVar -> css(text/css) */
.treeGDE3_tr {
 background-color: #555;
 border-bottom: 1px solid #efefef;
 
}
.treeGDE3_tr b {
 color: #fff;
 margin: 0px 5px 0px 0px;
 
}

.treeGDE3_tr td:first-child {
  box-shadow: unset;
}
.treeGDE3_tr td:last-child {
  width: 100%;
}
.treeGDE3_tr td:first-child img.action {
  padding-left: 5px;
  cursor:pointer;
}
.treeGDE3_tr td:last-child img.action {
  cursor:pointer;  
}
.treeGDE3_tr td:last-child input {
  margin: 0 7px 0 0;
  vertical-align: middle;
}
#treeContainer {
  width: 388px;
  min-width: 388px;
  max-width: calc(100vw - 65px);
  box-shadow: 0px 5px 10px -2px rgb(201, 202, 202);
  max-height: calc(100vh - 147px);
  overflow: auto;
  resize: horizontal;
  background: url("../img/geoportal2019/greysquare.jpg") no-repeat right bottom;
}
#treeContainer table{
  width:100%;
}
#contextMenu tr {
  background-color: #333 !important;
}
#contextMenu table td:last-child {
  width: unset !important;
}
#contextMenu {
  box-shadow: 2px 2px 10px 0px black;
  }
.menu td:last-child img {
  margin-bottom: -6px;
  margin-right: 11px;
}

.treegde2019 {
  width: 13px;
  margin-bottom: -6px;
  margin-right: 5px;
}

.node {
  max-width: calc(100% - 65px);
  display: inline-block;
  vertical-align: middle;
  padding: 5px 0;
}

#contextMenu td a.node {
  max-width: calc(100% - 50px);
}

#treeContainer img {
  border: unset;
  vertical-align: middle;
  margin-top:0px !important;
  margin-right:3px !important;
  margin-bottom:0px !important;
  margin-left:0px !important;
}

#treeContainer td {
  white-space: normal;
  height: 36px;
}

#root_id td {
  height:0px;
}

.dimensiontext {
  font-size: 12px;
  font-family: Arial, Helvetica, sans-serif;
  color: #fff;
  text-decoration: none;
  padding-right: 4px;
  line-height: 36px;
}

#imgErrorDialog {background-color: rgba(213, 31, 40, 0.8);min-height: auto !important;}
#imgErrorDialog p.errormsgtitle {font-weight: bold;margin:0}
#imgErrorDialog p.errormsglayername {margin:0;padding:5px;}
.alerterrordialog {
    background: none !important;
    border: none !important;
    box-shadow: unset !important;
}
.alerterrordialog .ui-dialog-titlebar {
    margin-bottom: -22px;
    height: 0;
    border: none;
}
.alerterrordialog .ui-state-hover {
    background: none !important;
    border-color:#222;
}
/* END INSERT treeGDE-> elementVar -> css(text/css) */' 
WHERE fkey_gui_id = 'Geoportal-Hessen-2019' AND fkey_e_id = 'treeGDE' AND var_name = 'css';