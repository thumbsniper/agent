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



class RobotsChecker
{
    public function __construct()
    {
    } // function



    public static function isAllowed($url) {
        /*
	 * Original PHP code by Chirp Internet: www.chirp.com.au
	 * Adapted to include 404 and Allow directive checking by Eric at LinkUp.com
	 * Please acknowledge use of this code by including this header.
	 * Found here: http://www.the-art-of-web.com/php/parse-robots/
         *
         * Modified by Thomas Schulte
         */

        // parse url to retrieve host and path
        $parsed = parse_url($url);
        $parsed['path'] = !empty($parsed['path']) ? $parsed['path'] : "/";

        $agents = array(preg_quote('*'), preg_quote(Settings::getUserAgent(), '/'));
        $agents = implode('|', $agents);

        // location of robots.txt file, only pay attention to it if the server says it exists
        if(function_exists('curl_init'))
        {
            $handle = curl_init("http://{$parsed['host']}/robots.txt");
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($handle, CURLOPT_FOLLOWLOCATION, 1); // fuer Redirects
            curl_setopt($handle, CURLOPT_TIMEOUT, 30);
            curl_setopt($handle, CURLOPT_USERAGENT, Settings::getUserAgent());

            if(Settings::getHttpProxyUrl())
            {
                curl_setopt($handle, CURLOPT_PROXY, Settings::getHttpProxyUrl());
            }

            $response = curl_exec($handle);
            $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

            if($httpCode == 200)
            {
                $robotstxt = explode("\n", $response);
            }else
            {
                $robotstxt = false;
            }

            curl_close($handle);
        } else
        {
            $robotstxt = @file("http://{$parsed['host']}/robots.txt");
        }

        // if there isn't a robots, then we're allowed in
        if(empty($robotstxt))
        {
            return true;
        }

        $rules = array();
        $ruleApplies = false;

        foreach($robotstxt as $line)
        {
            // skip blank lines
            $line = trim($line);
            if(!$line || strlen($line) == 0)
            {
                continue;
            }

            // skip comments
            if(substr($line, 0, 1) == '#')
            {
                continue;
            }

            // following rules only apply if User-agent matches $useragent or '*'
            if(preg_match('/^\s*User-agent: (.*)/i', $line, $match))
            {
                $ruleApplies = preg_match("/($agents)/i", $match[1]);
                continue;
            }

            if($ruleApplies)
            {
                list($type, $rule) = explode(':', $line, 2);
                $type = trim(strtolower($type));

                // add rules that apply to array for testing
                $rules[] = array(
                    'type' => $type,
                    'match' => preg_quote(trim($rule), '/'),
                );
            }
        }

        $isAllowed = true;
        $currentStrength = 0;

        foreach($rules as $rule)
        {
            // check if page hits on a rule
            if(preg_match("/^{$rule['match']}/", $parsed['path']))
            {
                // prefer longer (more specific) rules and Allow trumps Disallow if rules same length
                $strength = strlen($rule['match']);
                if($currentStrength < $strength)
                {
                    $currentStrength = $strength;
                    $isAllowed = ($rule['type'] == 'allow') ? true : false;
                } elseif($currentStrength == $strength && $rule['type'] == 'allow')
                {
                    $currentStrength = $strength;
                    $isAllowed = true;
                }
            }
        }

        return $isAllowed;
    }
}
