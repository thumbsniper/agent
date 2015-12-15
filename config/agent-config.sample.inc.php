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

date_default_timezone_set("Europe/Berlin");

require 'vendor/autoload.php';

use ThumbSniper\agent\Settings;



Settings::setDomain('example.com');
Settings::setApiAgentSecret('secret');
Settings::setUserAgentName("CHANGEME");
Settings::setUserAgentUrl("http://example.com");

Settings::setApiUrlTargetNormal('http://api.' . Settings::getDomain() . '/v3/agent/' . Settings::getApiAgentSecret() . '/master/job/normal/');
Settings::setApiUrlTargetLongrun('http://api.' . Settings::getDomain() . '/v3/agent/' . Settings::getApiAgentSecret() . '/master/job/longrun/');
Settings::setApiUrlImage('http://api.' . Settings::getDomain() . '/v3/agent/' . Settings::getApiAgentSecret() . '/thumbnail/job/');
Settings::setApiUrlTargetCommitNormal('http://api.' . Settings::getDomain() . '/v3/agent/' . Settings::getApiAgentSecret() . '/master/commit/normal/');
Settings::setApiUrlTargetCommitLongrun('http://api.' . Settings::getDomain() . '/v3/agent/' . Settings::getApiAgentSecret() . '/master/commit/longrun/');
Settings::setApiUrlImageCommit('http://api.' . Settings::getDomain() . '/v3/agent/' . Settings::getApiAgentSecret() . '/thumbnail/commit/');
Settings::setApiUrlTargetFailureNormal('http://api.' . Settings::getDomain() . '/v3/agent/' . Settings::getApiAgentSecret() . '/master/failure/normal/');
Settings::setApiUrlTargetFailureLongrun('http://api.' . Settings::getDomain() . '/v3/agent/' . Settings::getApiAgentSecret() . '/master/failure/longrun/');

//Settings::setHttpProxyUrl('http://proxy:3128');
