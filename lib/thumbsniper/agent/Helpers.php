<?php

/*
 * Copyright (C) 2015  Thomas Schulte <thomas@cupracer.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace ThumbSniper\agent;


class Helpers
{
    public static function isDomainExists($domain)
    {
        $ascii = idn_to_ascii($domain);

        if($ascii && (checkdnsrr($ascii, 'A') || checkdnsrr($ascii, 'CNAME') || checkdnsrr($ascii, 'AAAA') || gethostbyname($ascii)))
        {
            return true;
        }
        else
        {
            return false;
        }
    }


    public static function isIpAddress($host)
    {
        if(filter_var($host, FILTER_VALIDATE_IP))
        {
            return true;
        }else
        {
            return false;
        }
    }
}
