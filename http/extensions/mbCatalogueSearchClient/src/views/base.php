<div class="searchbar -js-input-div">
    <input type="text" class="simple-search-field -js-simple-search-field" placeholder="<?php echo $params['placeholder']; ?>">
    <div class="simple-search-autocomplete -js-simple-search-autocomplete"></div>
    <button class="search--submit -js-search-start">Suchen</button>
</div>

<div class="block"></div>

<div id='-js-loading' class='centered hide'>
         <div class='loading'>Loading...</div>
</div>

<ul class="search-tabs -js-tabs">
<?php $num = 0;
foreach ($params['searchitems'] as $key => $value) {  ?>
    <li data-id="-js-content-<?php echo $key.'-'.$num; ?>" class="-js-tab-item tab-item<?php echo $num===0 ? ' active' : ''; $num++; ?>"><?php echo $value['title']; ?></li>
<?php } ?>
</ul>

<?php $num = 0;
foreach ($params['searchitems'] as $key => $value) {
$formFile = __DIR__ . '/../search/' . $key . '/search-form.php'; ?>
<div id="-js-content-<?php echo $key.'-'.$num; ?>" data-source="<?php echo $key?>" class="-js-content search-content search-content-<?php echo $key?><?php echo $num===0 ? ' active' : ''; $num++; ?>">
  <span class="-js-extended-search-header extended-search-header -js-accordion">
      <span class="accordion icon closed fs-20px"></span>
      Erweiterte Suche
  </span>
  <?php $this->parseView($formFile, array('name' => $key, 'value' => $value)); ?>
  <div class="-js-result"></div>
</div>
<?php } ?>
