<map version="freeplane 1.7.0">
<!--To view this file, download free mind mapping software Freeplane from http://freeplane.sourceforge.net -->
<node TEXT="OWS Context" FOLDED="false" ID="ID_1336604653" CREATED="1638462759071" MODIFIED="1638462804247" STYLE="oval">
<font SIZE="18"/>
<hook NAME="MapStyle" background="#3c3f41">
    <properties edgeColorConfiguration="#808080ff,#00ddddff,#dddd00ff,#dd0000ff,#00dd00ff,#dd0000ff,#7cddddff,#dddd7cff,#dd7cddff,#7cdd7cff,#dd7c7cff,#7c7cddff" show_note_icons="true" fit_to_viewport="false"/>

<map_styles>
<stylenode LOCALIZED_TEXT="styles.root_node" STYLE="oval" UNIFORM_SHAPE="true" VGAP_QUANTITY="24.0 pt">
<font SIZE="24"/>
<stylenode LOCALIZED_TEXT="styles.predefined" POSITION="right" STYLE="bubble">
<stylenode LOCALIZED_TEXT="default" ICON_SIZE="12.0 pt" COLOR="#cccccc" STYLE="fork">
<font NAME="SansSerif" SIZE="10" BOLD="false" ITALIC="false"/>
</stylenode>
<stylenode LOCALIZED_TEXT="defaultstyle.details"/>
<stylenode LOCALIZED_TEXT="defaultstyle.attributes">
<font SIZE="9"/>
</stylenode>
<stylenode LOCALIZED_TEXT="defaultstyle.note" COLOR="#cccccc" BACKGROUND_COLOR="#3c3f41" TEXT_ALIGN="LEFT"/>
<stylenode LOCALIZED_TEXT="defaultstyle.floating">
<edge STYLE="hide_edge"/>
<cloud COLOR="#f0f0f0" SHAPE="ROUND_RECT"/>
</stylenode>
</stylenode>
<stylenode LOCALIZED_TEXT="styles.user-defined" POSITION="right" STYLE="bubble">
<stylenode LOCALIZED_TEXT="styles.topic" COLOR="#18898b" STYLE="fork">
<font NAME="Liberation Sans" SIZE="10" BOLD="true"/>
</stylenode>
<stylenode LOCALIZED_TEXT="styles.subtopic" COLOR="#cc3300" STYLE="fork">
<font NAME="Liberation Sans" SIZE="10" BOLD="true"/>
</stylenode>
<stylenode LOCALIZED_TEXT="styles.subsubtopic" COLOR="#669900">
<font NAME="Liberation Sans" SIZE="10" BOLD="true"/>
</stylenode>
<stylenode LOCALIZED_TEXT="styles.important">
<icon BUILTIN="yes"/>
</stylenode>
</stylenode>
<stylenode LOCALIZED_TEXT="styles.AutomaticLayout" POSITION="right" STYLE="bubble">
<stylenode LOCALIZED_TEXT="AutomaticLayout.level.root" COLOR="#dddddd" STYLE="oval" SHAPE_HORIZONTAL_MARGIN="10.0 pt" SHAPE_VERTICAL_MARGIN="10.0 pt">
<font SIZE="18"/>
</stylenode>
<stylenode LOCALIZED_TEXT="AutomaticLayout.level,1" COLOR="#ff3300">
<font SIZE="16"/>
</stylenode>
<stylenode LOCALIZED_TEXT="AutomaticLayout.level,2" COLOR="#ffb439">
<font SIZE="14"/>
</stylenode>
<stylenode LOCALIZED_TEXT="AutomaticLayout.level,3" COLOR="#99ffff">
<font SIZE="12"/>
</stylenode>
<stylenode LOCALIZED_TEXT="AutomaticLayout.level,4" COLOR="#bbbbbb">
<font SIZE="10"/>
</stylenode>
<stylenode LOCALIZED_TEXT="AutomaticLayout.level,5"/>
<stylenode LOCALIZED_TEXT="AutomaticLayout.level,6"/>
<stylenode LOCALIZED_TEXT="AutomaticLayout.level,7"/>
<stylenode LOCALIZED_TEXT="AutomaticLayout.level,8"/>
<stylenode LOCALIZED_TEXT="AutomaticLayout.level,9"/>
<stylenode LOCALIZED_TEXT="AutomaticLayout.level,10"/>
<stylenode LOCALIZED_TEXT="AutomaticLayout.level,11"/>
</stylenode>
</stylenode>
</map_styles>
</hook>
<hook NAME="AutomaticEdgeColor" COUNTER="1" RULE="ON_BRANCH_CREATION"/>
<node TEXT="Structure" POSITION="right" ID="ID_309460557" CREATED="1638462851038" MODIFIED="1638462861154">
<edge COLOR="#00dddd"/>
<node TEXT="Context" ID="ID_1558211489" CREATED="1638462899282" MODIFIED="1638463106028">
<node TEXT="specReference :URI" ID="ID_552872691" CREATED="1638463119668" MODIFIED="1638463302394"/>
<node TEXT="language :CharacterString" ID="ID_205466167" CREATED="1638463132568" MODIFIED="1638463313231"/>
<node TEXT="id :CharacterString" ID="ID_1379824310" CREATED="1638463141692" MODIFIED="1638463324445"/>
<node TEXT="title :CharacterString" ID="ID_745876084" CREATED="1638463149115" MODIFIED="1638463338813"/>
<node TEXT="abstract :CharacterString [0..1]" ID="ID_528525610" CREATED="1638463162756" MODIFIED="1638463571721"/>
<node TEXT="updateDate :CharacterString [0..1]" ID="ID_1445532914" CREATED="1638463169198" MODIFIED="1638463613399"/>
<node TEXT="author :CharacterString [0..*]" ID="ID_1100804899" CREATED="1638463178426" MODIFIED="1638463625807"/>
<node TEXT="publisher :CharacterString [0..1]" ID="ID_113709025" CREATED="1638463186578" MODIFIED="1638463636726"/>
<node TEXT="creator :Creator [0..1]" ID="ID_1506423064" CREATED="1638463195959" MODIFIED="1638463645140">
<node TEXT="creatorApplication :CreatorApplication [0..1]" ID="ID_169989940" CREATED="1638464565749" MODIFIED="1638464596721">
<node TEXT="title :CharacterString [0..1]" ID="ID_1083614962" CREATED="1638464625826" MODIFIED="1638464632164"/>
<node TEXT="uri :URI [0..1]" ID="ID_817133293" CREATED="1638464633474" MODIFIED="1638464639428"/>
<node TEXT="version :Version [0..1]" ID="ID_1967269756" CREATED="1638464642838" MODIFIED="1638464647378"/>
</node>
<node TEXT="creatorDisplay :CreatorDisplay [0..1]" ID="ID_1320302537" CREATED="1638464604191" MODIFIED="1638464607259">
<node TEXT="pixelWidth :int [0..1]" ID="ID_1094370194" CREATED="1638464656841" MODIFIED="1638464662407"/>
<node TEXT="pixelHeight :int [0..1]" ID="ID_154922183" CREATED="1638464666423" MODIFIED="1638464670698"/>
<node TEXT="mmPerPixel :double [0..1]" ID="ID_1836003212" CREATED="1638464674429" MODIFIED="1638464677998"/>
<node TEXT="extension :Any [0..*]" ID="ID_1703942006" CREATED="1638464675195" MODIFIED="1638464683541"/>
</node>
<node TEXT="extension :Any [0..*]" ID="ID_1960263210" CREATED="1638464608639" MODIFIED="1638464615141"/>
</node>
<node TEXT="rights :CharacterString [0..1]" ID="ID_1696683034" CREATED="1638463203926" MODIFIED="1638463656027"/>
<node TEXT="areaOfInterest :GM_Envelope [0..1]" ID="ID_594407403" CREATED="1638463211110" MODIFIED="1638463666352"/>
<node TEXT="timeIntervalOfInterest :TM_GeometricPrimitive [0..1]" ID="ID_888065859" CREATED="1638463230012" MODIFIED="1638463681351"/>
<node TEXT="keyword :CharacterString [0..*]" ID="ID_1382229468" CREATED="1638463244974" MODIFIED="1638463690260"/>
<node TEXT="extension :Any [0..*]" ID="ID_1642856716" CREATED="1638463256481" MODIFIED="1638463699914">
<node TEXT="projections :Projection [0..*]" LOCALIZED_STYLE_REF="defaultstyle.note" ID="ID_1194522236" CREATED="1638465642374" MODIFIED="1638466750940"><richcontent TYPE="NOTE">

<html>
  <head>
    
  </head>
  <body>
    <p>
      <a href="https://github.com/dlr-eoc/ukis-frontend-libraries/blob/master/projects/services-ogc/README.md" target="_blank">UKIS Frontend DLR</a>
    </p>
    <p>
      Draft from DLR ukis frontend - array of projections with corresponding bboxes
    </p>
  </body>
</html>
</richcontent>
</node>
</node>
<node TEXT="Resource [0..*] (ordered)" ID="ID_1653820487" CREATED="1638463280103" MODIFIED="1638464225110">
<node TEXT="id :CharacterString" ID="ID_1151991015" CREATED="1638463415892" MODIFIED="1638463424698"/>
<node TEXT="title :CharacterString" ID="ID_1042725716" CREATED="1638463427289" MODIFIED="1638463435298"/>
<node TEXT="abstract: CharacterString [0..1]" ID="ID_1559690728" CREATED="1638463441579" MODIFIED="1638463499044"/>
<node TEXT="updateDate :TM_Date [0..1]" ID="ID_995115094" CREATED="1638463452508" MODIFIED="1638463474522"/>
<node TEXT="author :CharacterString [0..*]" ID="ID_1524160767" CREATED="1638463521739" MODIFIED="1638463535837"/>
<node TEXT="publisher :CharacterString [0..1]" ID="ID_1953018081" CREATED="1638463712902" MODIFIED="1638463721255"/>
<node TEXT="rights :CharacterString [0..1]" ID="ID_476193815" CREATED="1638463722361" MODIFIED="1638463735106"/>
<node TEXT="geospatialExtent :GM_Envelope [0..1]" ID="ID_89308731" CREATED="1638463743277" MODIFIED="1638463746437"/>
<node TEXT="temporalExtent :TM_GeometricPrimitive [0..1]" ID="ID_1657191912" CREATED="1638463756032" MODIFIED="1638463759366"/>
<node TEXT="contentDescription :Any [0..1]" ID="ID_1801142417" CREATED="1638463761022" MODIFIED="1638463772051"/>
<node TEXT="preview :URI [0..*]" ID="ID_104434207" CREATED="1638463788168" MODIFIED="1638463806047"/>
<node TEXT="contentByRef :URI [0..*]" ID="ID_1609766955" CREATED="1638463807179" MODIFIED="1638463815966"/>
<node TEXT="offering :Offering [0..*]" ID="ID_1349763609" CREATED="1638463818589" MODIFIED="1638463825887">
<node TEXT="code :URI" ID="ID_333064136" CREATED="1638463925958" MODIFIED="1638463939782">
<node TEXT="http://www.opengis.net/spec/owc/1.0/req/wms" ID="ID_1209782973" CREATED="1638464781764" MODIFIED="1638464785100"/>
<node TEXT="http://www.opengis.net/spec/owc/1.0/req/wfs" ID="ID_1783319329" CREATED="1638464787186" MODIFIED="1638464800624"/>
<node TEXT="http://www.opengis.net/spec/owc/1.0/req/kml" ID="ID_422104887" CREATED="1638464819981" MODIFIED="1638464823460"/>
</node>
<node TEXT="operation :Operation [0..*]" ID="ID_1593705731" CREATED="1638463940825" MODIFIED="1638463949889">
<node TEXT="code :CharacterString" ID="ID_571104941" CREATED="1638463998758" MODIFIED="1638464006951"/>
<node TEXT="method :CharacterString" ID="ID_1209525967" CREATED="1638464008637" MODIFIED="1638464016290"/>
<node TEXT="type :CharacterString" ID="ID_1025108573" CREATED="1638464017643" MODIFIED="1638464031510"/>
<node TEXT="requestURL :URI" ID="ID_1509687949" CREATED="1638464023413" MODIFIED="1638464040051"/>
<node TEXT="request :Content [0..1]" ID="ID_1557462224" CREATED="1638464041664" MODIFIED="1638464049305">
<node TEXT="type :CharacterString" ID="ID_1598115858" CREATED="1638464080776" MODIFIED="1638464087049"/>
<node TEXT="URL :URI [0..1]" ID="ID_856687695" CREATED="1638464087568" MODIFIED="1638464093280"/>
<node TEXT="content :Any [0..1]" ID="ID_1378370646" CREATED="1638464094607" MODIFIED="1638464102729"/>
<node TEXT="extension :Any [0..*]" ID="ID_670249429" CREATED="1638464103151" MODIFIED="1638464111504"/>
</node>
<node TEXT="result :Any [0..1]" ID="ID_1803438698" CREATED="1638464042456" MODIFIED="1638464060345"/>
<node TEXT="extension :Any [0..*]" ID="ID_1187470594" CREATED="1638464065762" MODIFIED="1639296241407">
<font SIZE="9"/>
<node TEXT="active :Boolean [0..1]" ID="ID_787230339" CREATED="1638465055713" MODIFIED="1638466424299"><richcontent TYPE="NOTE">

<html>
  <head>
    
  </head>
  <body>
    <p>
      Controls the behaviour of the UI - should the operation be activated (show/select/...)
    </p>
  </body>
</html>
</richcontent>
</node>
</node>
</node>
<node TEXT="content :Content [0..*]" ID="ID_41073945" CREATED="1638463950671" MODIFIED="1638463958541"/>
<node TEXT="styleSet :StyleSet [0..*]" ID="ID_1015490905" CREATED="1638463959135" MODIFIED="1638463966382">
<node TEXT="name :CharacterString" ID="ID_1625936444" CREATED="1638464115776" MODIFIED="1638464123169"/>
<node TEXT="title :CharacterString" ID="ID_849090634" CREATED="1638464130148" MODIFIED="1638464135831"/>
<node TEXT="abstract :CharacterString [0..1]" ID="ID_205036499" CREATED="1638464130935" MODIFIED="1638464140110"/>
<node TEXT="default :Boolean [0..1]" ID="ID_559766972" CREATED="1638464140744" MODIFIED="1638464145908"/>
<node TEXT="legendURL :URI [0..*]" ID="ID_46961650" CREATED="1638464150918" MODIFIED="1638464154479"/>
<node TEXT="content :Content [0..1]" ID="ID_1570331260" CREATED="1638464159435" MODIFIED="1638464164122"/>
<node TEXT="extension :Any [0..*]" ID="ID_1123583309" CREATED="1638464167249" MODIFIED="1638464170388"/>
</node>
<node TEXT="extension :Any [0..*]" ID="ID_1080498245" CREATED="1638463972065" MODIFIED="1638463977633">
<node TEXT="dimension :Array[0..*]" ID="ID_549606523" CREATED="1638465024601" MODIFIED="1638466266410"><richcontent TYPE="NOTE">

<html>
  <head>
    
  </head>
  <body>
    <p>
      <a href="https://portal.ogc.org/files/?artifact_id=8618" target="_blank">https://portal.ogc.org/files/?artifact_id=8618</a>
    </p>
    <p>
      Defined already in OGC WMC Spec - see xsd snippet
    </p>
  </body>
</html>
</richcontent>
<node TEXT="name :CharacterString" ID="ID_979410768" CREATED="1638465154679" MODIFIED="1638465182424"/>
<node TEXT="units :CharacterString" ID="ID_971397837" CREATED="1638465185695" MODIFIED="1638465204722"/>
<node TEXT="unitSymbol :CharacterString" ID="ID_865110770" CREATED="1638465210245" MODIFIED="1638465230878"/>
<node TEXT="default :CharacterString" ID="ID_1678860525" CREATED="1638465232940" MODIFIED="1638465273119"/>
<node TEXT="multipleValues :Boolean" ID="ID_1277190094" CREATED="1638465239993" MODIFIED="1638465959830"/>
<node TEXT="nearestValue :Boolean" ID="ID_96520695" CREATED="1638465243868" MODIFIED="1638465965936"/>
<node TEXT="extent :CharacterString" ID="ID_947467749" CREATED="1638465252654" MODIFIED="1638465300661"/>
<node TEXT="userValue :CharacterString" ID="ID_962368157" CREATED="1638465257087" MODIFIED="1638465309114"/>
<node TEXT="current :Boolean" ID="ID_49679142" CREATED="1638465315857" MODIFIED="1638465971207"/>
</node>
</node>
</node>
<node TEXT="active :Boolean [0..1]" ID="ID_1242842076" CREATED="1638463833585" MODIFIED="1638463836665"/>
<node TEXT="keyword :CharacterString [0..*]" ID="ID_1763863206" CREATED="1638463845108" MODIFIED="1638463847981"/>
<node TEXT="maxScaleDenominator :double [0..1]" ID="ID_1491505728" CREATED="1638463854343" MODIFIED="1638463857230"/>
<node TEXT="minScaleDenominator :double [0..1]" ID="ID_194722423" CREATED="1638463864249" MODIFIED="1638463867296"/>
<node TEXT="folder :CharacterString [0..1]" ID="ID_959871620" CREATED="1638463876028" MODIFIED="1638463878929"/>
<node TEXT="extension :Any [0..*]" ID="ID_926244730" CREATED="1638463885964" MODIFIED="1638463888708">
<node TEXT="opacity :Integer [0..1]" ID="ID_1789945227" CREATED="1638465596859" MODIFIED="1638466337423"><richcontent TYPE="NOTE">

<html>
  <head>
    
  </head>
  <body>
    <p>
      Possibility to define the opacity of the layer - can be steered by the client!
    </p>
  </body>
</html>
</richcontent>
</node>
</node>
<node TEXT="resourceMetadata :MD_Metadata [0..*]" ID="ID_330498360" CREATED="1638464363081" MODIFIED="1638464476656"/>
</node>
<node TEXT="contextMetadata :MD_Metadata [0..*]" ID="ID_450806492" CREATED="1638464323695" MODIFIED="1638464504965"/>
</node>
</node>
</node>
</map>
