#!/bin/bash
#
# expecting three input params
# $1 - the name of the release branch at 
# 
#      https://svn.osgeo.org/mapbender/branches, for example 2.4.5
# $2 - the SVN username for https://svn.osgeo.org/mapbender

#
#set constants
#

# the name of the folder where the build process happens
TODAY=`date +%Y-%m-%d_%H.%M.%S`

# create folder for build
svn export ../ ../mapbender_latest/


# compress JS files
#echo "compressing JS ..."
#cd $FOLDER 2>/dev/null
#for file in `find -type f -name *.js`
#do
#	echo "minifying $file ..."
#	ORIG=$file"_orig"
#	mv $file $ORIG 2>/dev/null
#	php5 ../jsmin.php $ORIG > $file
#	rm $ORIG 2>/dev/null
#done


# zip build folder
ZIP_FILENAME="mapbender_latest.zip"
cd ../
zip -rq $ZIP_FILENAME mapbender_latest

# remove build folder
rm -rf ../mapbender_latest/