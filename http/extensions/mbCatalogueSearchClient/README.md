# Entwicklungsumgebung

## Installation

Als Buildsystem wird `gulp` genutzt.

```bash
# Install Node.js
curl -sL https://deb.nodesource.com/setup_4.x | sudo bash -
sudo apt-get install nodejs

# Install gulp
sudo npm install -g gulp

# Clone project
git clone git@repo.wheregroup.com:mapbender2/geoportal-suche.git
cd geoportal-suche

npm install
```

## Nutzung

```bash
cd geoportal-suche
gulp
```



## Sonstiges

* http://www.mapbender2.org/SearchInterface
* http://www.geoportal.rlp.de/
* [Aufruf](http://www.geoportal.rlp.de/mapbender/php/mod_callMetadata.php?searchText=e&outputFormat=json&resultTarget=webclient&searchResources=dataset&searchId=123)  
* [Autocomplete](http://www.geoportal.rlp.de/mapbender/geoportal/mod_getCatalogueKeywordSuggestion.php?searchText=wald&maxResults=15)  
