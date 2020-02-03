<?php

/* 
 *  Copyright (C) 2017 WhereGroup
 * 
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2, or (at your option)
 *  any later version.
 * 
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 * 
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 * Description of mb_split
 * Wrapper for the php7 deprecated split function to use in arbitrary PHP 
 * environments.
 * @author Tobias Rieck tobias.rieck@benndorf.de
 */
function mbw_split() {
    
    $phpversion = phpversion();
    $pars = func_get_args();
    $result = null;
    if (strpos($phpversion, "5.") === 0) {
        switch ($phpversion[2]) {
            case "0":
            case "1":
            case "2":
               $result =  split($pars);
               break;
            default:
                if ($pars[2] == null) {
                    $result = explode($pars[0],$pars[1]);
                }
                else {
                    $result = explode($pars[0],$pars[1],$pars[2]);
                }
        }
    }
    else if (strpos($phpversion, "7.") === 0) {
        if ($pars[2] == null) {
            $result = explode($pars[0],$pars[1]);
        }
        else {
            $result = explode($pars[0],$pars[1],$pars[2]);
        }
    }
    return $result;
}
