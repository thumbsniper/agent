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



abstract class Settings
{
    static private $cacheDir = '/tmp';
    static private $logType = 'otnagt';

    static private $userAgentName;
    static private $userAgentUrl;

    static private $convertPath = '/usr/bin/convert';
    static private $timeoutPath = '/usr/bin/timeout';

    static private $wkhtmlPath = '/usr/bin/wkhtmltoimage';
    static private $cutycaptPath = '/usr/bin/cutycapt';

    static private $masterFiletype = 'png';

    static private $imageFiletypes = array(
        'plain' => 'jpeg',
        'fade1' => 'png',
        'fade2' => 'png',
        'button1' => 'png',
        'curly' => 'png',
        'blur1' => 'png',
        'blur2' => 'png',
        'tornpaper1' => 'png',
        'polaroid1' => 'png'
    );

    static private $effectsBuiltin = array(
        'plain',
        'button1',
        'curly',
        'blur1',
        'blur2',
        'tornpaper1',
        'polaroid1'
    );

    static private $effectsExtraCommands = array(
        'fade1' => DIRECTORY_ROOT . '/scripts/3Dreflection',
        'fade2' => DIRECTORY_ROOT . '/scripts/3Dreflection_rightToLeft'
    );

    static private $domain;

    static private $apiAgentSecret;
    static private $apiUrlTargetNormal;
    static private $apiUrlTargetLongrun;
    static private $apiUrlImage;
    static private $apiUrlTargetCommitNormal;
    static private $apiUrlTargetCommitLongrun;
    static private $apiUrlImageCommit;
    static private $apiUrlTargetFailureNormal;
    static private $apiUrlTargetFailureLongrun;

    static private $screenWidth = 1024;
    static private $screenHeight = 768;
    static private $imageQuality = 94;

    static private $httpProxyUrl;
    static private $urlAvailabilityCheckTimeout = 10; // 10 seconds


    /**
     * @return mixed
     */
    public static function getDomain()
    {
        return self::$domain;
    }

    /**
     * @param mixed $domain
     */
    public static function setDomain($domain)
    {
        self::$domain = $domain;
    }

    /**
     * @return mixed
     */
    public static function getApiUrlTargetFailureNormal()
    {
        return self::$apiUrlTargetFailureNormal;
    }

    /**
     * @param mixed $apiUrlTargetFailureNormal
     */
    public static function setApiUrlTargetFailureNormal($apiUrlTargetFailureNormal)
    {
        self::$apiUrlTargetFailureNormal = $apiUrlTargetFailureNormal;
    }

    /**
     * @return mixed
     */
    public static function getApiUrlTargetFailureLongrun()
    {
        return self::$apiUrlTargetFailureLongrun;
    }

    /**
     * @param mixed $apiUrlTargetFailureLongrun
     */
    public static function setApiUrlTargetFailureLongrun($apiUrlTargetFailureLongrun)
    {
        self::$apiUrlTargetFailureLongrun = $apiUrlTargetFailureLongrun;
    }

    /**
     * @return mixed
     */
    public static function getApiUrlImage()
    {
        return self::$apiUrlImage;
    }

    /**
     * @param mixed $apiUrlImage
     */
    public static function setApiUrlImage($apiUrlImage)
    {
        self::$apiUrlImage = $apiUrlImage;
    }

    /**
     * @return mixed
     */
    public static function getApiUrlImageCommit()
    {
        return self::$apiUrlImageCommit;
    }

    /**
     * @param mixed $apiUrlImageCommit
     */
    public static function setApiUrlImageCommit($apiUrlImageCommit)
    {
        self::$apiUrlImageCommit = $apiUrlImageCommit;
    }

    /**
     * @return mixed
     */
    public static function getApiUrlTargetCommitNormal()
    {
        return self::$apiUrlTargetCommitNormal;
    }

    /**
     * @return mixed
     */
    public static function getApiUrlTargetCommitLongrun()
    {
        return self::$apiUrlTargetCommitLongrun;
    }

    /**
     * @param mixed $apiUrlTargetCommitNormal
     */
    public static function setApiUrlTargetCommitNormal($apiUrlTargetCommitNormal)
    {
        self::$apiUrlTargetCommitNormal = $apiUrlTargetCommitNormal;
    }

    /**
     * @param mixed $apiUrlTargetCommitLongrun
     */
    public static function setApiUrlTargetCommitLongrun($apiUrlTargetCommitLongrun)
    {
        self::$apiUrlTargetCommitLongrun = $apiUrlTargetCommitLongrun;
    }


    /**
     * @return mixed
     */
    public static function getApiUrlTargetLongrun()
    {
        return self::$apiUrlTargetLongrun;
    }

    /**
     * @param mixed $apiUrlTargetLongrun
     */
    public static function setApiUrlTargetLongrun($apiUrlTargetLongrun)
    {
        self::$apiUrlTargetLongrun = $apiUrlTargetLongrun;
    }

    /**
     * @return mixed
     */
    public static function getApiUrlTargetNormal()
    {
        return self::$apiUrlTargetNormal;
    }

    /**
     * @param mixed $apiUrlTargetNormal
     */
    public static function setApiUrlTargetNormal($apiUrlTargetNormal)
    {
        self::$apiUrlTargetNormal = $apiUrlTargetNormal;
    }

    /**
     * @return string
     */
    public static function getCacheDir()
    {
        return self::$cacheDir;
    }

    /**
     * @return string
     */
    public static function getConvertPath()
    {
        return self::$convertPath;
    }

    /**
     * @return string
     */
    public static function getMasterFiletype()
    {
        return self::$masterFiletype;
    }

    /**
     * @return array
     */
    public static function getImageFiletypes()
    {
        return self::$imageFiletypes;
    }


    /**
     * @return string
     */
    public static function getImageFiletype($effect)
    {
        if(!array_key_exists($effect, self::$imageFiletypes))
        {
            return false;
        }else
        {
            return self::$imageFiletypes[$effect];
        }
    }

    /**
     * @return int
     */
    public static function getImageQuality()
    {
        return self::$imageQuality;
    }

    /**
     * @return string
     */
    public static function getLogType()
    {
        return self::$logType;
    }

    /**
     * @return int
     */
    public static function getScreenHeight()
    {
        return self::$screenHeight;
    }

    /**
     * @return int
     */
    public static function getScreenWidth()
    {
        return self::$screenWidth;
    }

    /**
     * @return string
     */
    public static function getTimeoutPath()
    {
        return self::$timeoutPath;
    }

    /**
     * @return mixed
     */
    public static function getUserAgentName()
    {
        return self::$userAgentName;
    }

    /**
     * @param mixed $userAgentName
     */
    public static function setUserAgentName($userAgentName)
    {
        self::$userAgentName = $userAgentName;
    }

    /**
     * @return mixed
     */
    public static function getUserAgentUrl()
    {
        return self::$userAgentUrl;
    }

    /**
     * @param mixed $userAgentUrl
     */
    public static function setUserAgentUrl($userAgentUrl)
    {
        self::$userAgentUrl = $userAgentUrl;
    }

    /**
     * @return string
     */
    public static function getUserAgent()
    {
        if(empty(self::$userAgentName) || empty(self::$userAgentUrl))
        {
            return false;
        }else
        {
            return self::$userAgentName . " (" . self::$userAgentUrl . ")";
        }
    }

    /**
     * @return mixed
     */
    public static function getHttpProxyUrl()
    {
        return self::$httpProxyUrl;
    }

    /**
     * @param mixed $httpProxyUrl
     */
    public static function setHttpProxyUrl($httpProxyUrl)
    {
        self::$httpProxyUrl = $httpProxyUrl;
    }

    /**
     * @return int
     */
    public static function getUrlAvailabilityCheckTimeout()
    {
        return self::$urlAvailabilityCheckTimeout;
    }

    /**
     * @param int $urlAvailabilityCheckTimeout
     */
    public static function setUrlAvailabilityCheckTimeout($urlAvailabilityCheckTimeout)
    {
        self::$urlAvailabilityCheckTimeout = $urlAvailabilityCheckTimeout;
    }

    /**
     * @return mixed
     */
    public static function getApiAgentSecret()
    {
        return self::$apiAgentSecret;
    }

    /**
     * @param mixed $apiAgentSecret
     */
    public static function setApiAgentSecret($apiAgentSecret)
    {
        self::$apiAgentSecret = $apiAgentSecret;
    }

    /**
     * @return string
     */
    public static function getWkhtmlPath()
    {
        return self::$wkhtmlPath;
    }

    /**
     * @return string
     */
    public static function getCutycaptPath()
    {
        return self::$cutycaptPath;
    }


    public static function getEffectsExtraCommand($effect)
    {
        if(array_key_exists($effect, self::$effectsExtraCommands))
        {
            if(is_executable(self::$effectsExtraCommands[$effect]))
            {
                return self::$effectsExtraCommands[$effect];
            }
        }

        return null;
    }

    /**
     * @return array
     */
    public static function getEffectsBuiltin()
    {
        return self::$effectsBuiltin;
    }

    /**
     * @return array
     */
    public static function getEffectsExtraCommands()
    {
        return self::$effectsExtraCommands;
    }
}
