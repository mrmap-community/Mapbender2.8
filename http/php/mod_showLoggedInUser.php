<?php
# $Id: mod_showLoggedInUser.php 9179 2023-06-06 07:08:02Z armin11 $
# http://www.mapbender.org/index.php/Administration
# Copyright (C) 2002 CCGIS 
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2, or (at your option)
# any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.

require_once(dirname(__FILE__)."/../php/mb_validateSession.php");
require_once(dirname(__FILE__) . "/../classes/class_user.php");

$outputFormat = 'html';
$allowedOutputFormats = ['json', 'html', 'text'];
//parse outputFormat
if (isset($_REQUEST["outputFormat"]) && $_REQUEST["outputFormat"] != "") {
    $testMatch = $_REQUEST["outputFormat"];
    if (!in_array($testMatch, $allowedOutputFormats)){
        $resultObj['message'] ='Parameter outputFormat is not valid '.implode(',', $allowedOutputFormats);
        $resultObj['result'] = null;
        header('Content-Type: application/json');
        echo json_encode($resultObj);
        die();
    }
    $outputFormat = $testMatch;
    $testMatch = NULL;
}

$loggedInUserId = Mapbender::session()->get("mb_user_id");
$user = new User((integer)$loggedInUserId);
if ($user->isPublic()) {
    $userName = 'Anonymous';
    $loggedIn = false;
} else {
    $userName = $user->name;
    $userEmail = $user->email;
    $loggedIn = true;
}

switch ($outputFormat) {
    case "html":
        $html = "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">" . "\n";
        $html .= "<html>" . "\n";
        $html .= "<head>" . "\n";
        $html .= "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=" . CHARSET . "\">";	
        $html .= "<title>" . _mb('Show User') . "</title>" . "\n";
        $html .= "</head>" . "\n";
        $html .= "<body leftmargin=\"5\" topmargin=\"0\">" . "\n";
        if ($user == false) {
            $html .= "<div class='text4'>" . _mb('User not identified!') . "</div>" . "\n";
        } else {
            if ($loggedIn) {
                $html .= "<div class='text4'>" . _mb('Logged in user') . ": " . $userName . " <" . $userEmail .  ">" . "</div>" . "\n";
            } else {
                $html .= "<div class='text4'>" . _mb('Logged in user') . ": " . "<img width='20' height='20' src='../img/anonymous.png'/>" . $userName . " < public_user >"  . "</div>" . "\n";
            }
        }
        $html .= "</body>" . "\n";
        $html .= "</html>" . "\n";
        echo $html;
        break;
    case "json":
        if ($user == false) {
            $resultObj['message'] = "No logged in user found!";
            $resultObj['result']['logged_in'] = false;
        } else {
            if ($loggedIn) {
                $resultObj['result']['username'] = $userName;
                $resultObj['result']['email'] = $userEmail;
                $resultObj['result']['logged_in'] = true;
                $resultObj['result']['anonymous'] = false;
                $resultObj['message'] = "Logged in user";
            } else {
                $resultObj['result']['username'] = $userName;
                $resultObj['result']['logged_in'] = false;
                $resultObj['result']['anonymous'] = true;
                $resultObj['message'] = "Anonymous user";
            }
        }
        header('Content-Type: application/json');
        echo json_encode($resultObj);
        die();
        break;
    case "text":
        if ($user == false) {
            echo _mb('User not identified!');
        } else {
            if ($loggedIn) {
                echo _mb('Logged in user') . ": " . $userName . " <" . $userEmail . ">";
            } else {
                echo _mb('Logged in user') . ": " . $userName. " < public_user >";
            }
        }
        die();
}
