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

if(!isset($argv[1]))
{
        return false;
}

define('DIRECTORY_ROOT', dirname(__DIR__));

require_once(DIRECTORY_ROOT . '/vendor/autoload.php');
require_once(DIRECTORY_ROOT . '/config/config.inc.php');

use ThumbSniper\agent\Generator;
use ThumbSniper\agent\Settings;
use ThumbSniper\shared\Target;


function get_url($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);

    if(Settings::getHttpProxyUrl())
    {
        curl_setopt($ch, CURLOPT_PROXY, Settings::getHttpProxyUrl());
    }

        $content = curl_exec($ch);
        curl_close($ch);

        return $content;
}

/////////


$generator = new Generator($argv[1]);

$jsonTargetData = get_url($generator->target_next);

$target = null;
$sleep = 0;

if(is_numeric($jsonTargetData))
{
    $sleepDuration = intval($jsonTargetData);
    echo "No pending jobs. Set sleep to " . $sleepDuration . " seconds\n";

    if($sleepDuration > 0)
    {
        $sleep = $sleepDuration;
    }
}elseif(!empty($jsonTargetData)) {
    $target_serialized = base64_decode($jsonTargetData, true);
    $target = unserialize($target_serialized);

    if($target instanceof Target) {
        $generator->setTarget($target);

        if ($argv[1] == "normal" || $argv[1] == "longrun") {
            $generator->shoot();
        }

        if ($argv[1] == "image") {
            $generator->convert();
        }
    }else {
        echo "Invalid target. Set sleep to a random time (fallback)\n";
        $sleep = mt_rand(5, 30);
    }
}else {
    echo "Unknown server response. Set sleep to a random time (fallback)\n";
    $sleep = mt_rand(5, 30);
}

if($sleep > 0)
{
    echo "sleeping for " . $sleep . " seconds.\n";
	sleep($sleep);
}
