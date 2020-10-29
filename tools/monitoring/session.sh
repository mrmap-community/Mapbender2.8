#!/bin/bash
#
########
#
##########################################################################
########

PATH="/usr/bin:/usr/sbin:/bin:/sbin"


print_gpl() {
    echo "This program is free software; you can redistribute it and/or mo
dify"
    echo "it under the terms of the GNU General Public License as publishe
d by"
    echo "the Free Software Foundation; either version 2 of the License, o
r"
    echo "(at your option) any later version."
    echo ""
    echo "This program is distributed in the hope that it will be useful,"
    echo "but WITHOUT ANY WARRANTY; without even the implied warranty of"
    echo "MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the"
    echo "GNU General Public License for more details."
    echo ""
    echo "You should have received a copy of the GNU General Public Licens
e"
    echo "along with this program; if not, write to the Free Software"
    echo "Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110
-1301  USA"
}


n=`ls -l /data/mapbender/http/tmp/wmc | awk '{print $8}' | wc -l`
d=`date +%Y%m%d`
t=`date +%H:%M:%S`
if [ -z $n ];
then
echo "$d ; $t ; Zahl der Nutzer nicht feststellbar!" >> /data/monitoring/session.log.csv
exit 0 
fi

echo "$d ; $t ; $n" >> /data/monitoring/session.log.csv



exit 0 
