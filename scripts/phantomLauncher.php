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

define('DIRECTORY_ROOT', dirname(__DIR__));

require_once(DIRECTORY_ROOT . '/vendor/autoload.php');
require_once(DIRECTORY_ROOT . '/config/agent-config.inc.php');

use ThumbSniper\agent\Settings;

$cmd = '/opt/phantomjs/bin/phantomjs /opt/thumbsniper/scripts/render.js 3 '
    . '"' . Settings::getApiUrlTargetPhantom() . '" '
    . '"' . Settings::getApiUrlTargetCommitPhantom() . '" '
    . '"' . Settings::getApiUrlTargetFailurePhantom() . '"';

$descriptorspec = array(
    0 => array("pipe", "r"),   // stdin is a pipe that the child will read from
    1 => array("pipe", "w"),   // stdout is a pipe that the child will write to
    2 => array("pipe", "w")    // stderr is a pipe that the child will write to
);

flush();
$process = proc_open($cmd, $descriptorspec, $pipes, realpath('./'), array());
$exitCode = null;

if (is_resource($process)) {
    try {
        while ($s = fgets($pipes[1])) {
            print $s;
            flush();
        }
    }catch (Exception $e) {
        print $e . "\n";
    }
    
    fclose($pipes[0]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);
}


if ($exitCode != 0) {
    $this->logger("PhantomJS failed");
    exit(1);
}else {
    exit(0);
}
