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

$exec_cmd = '/opt/phantomjs/bin/phantomjs /opt/thumbsniper/scripts/render.js 10 ' 
    . '"' . Settings::getApiUrlTargetPhantom() . '" '
    . '"' . Settings::getApiUrlTargetCommitPhantom() . '" '
    . '"' . Settings::getApiUrlTargetFailurePhantom() . '"';

$exec_out = array();
$exec_rc = null;

exec($exec_cmd, $exec_out, $exec_rc);

print_r($exec_out);

if ($exec_rc != 0) {
    $this->logger("shoot failed");
    exit(1);
}else {
    exit(0);
}

