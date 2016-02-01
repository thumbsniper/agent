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

use ThumbSniper\shared\Target;
use ThumbSniper\shared\Image;


class Generator
{
    private $id;

    /** @var Target */
    private $target;

    private $mode;
    private $snipe_timeout = NULL;
    public $target_next = NULL;


    function __construct($mode = "normal")
    {
        $this->mode = $mode;

        switch ($this->mode) {
            case "normal":
                $this->snipe_timeout = "60s";
                $this->target_next = Settings::getApiUrlTargetNormal();
                break;

            case "longrun":
                $this->snipe_timeout = "120s";
                $this->target_next = Settings::getApiUrlTargetLongrun();
                break;

            case "image":
                $this->target_next = Settings::getApiUrlImage();
                break;
        }
    }


    public function setTarget(Target $target)
    {
        $this->target = $target;
        return true;
    }


    private function logger($content)
    {
        $now = time();
        $date = date("Y-m-d H:i:s", $now);

        if ($this->id) {
            echo $date . " [" . $this->id . "] " . $content . "\n";
        } else {
            echo $date . " " . $content . "\n";
        }
    }


    private function getCutyCaptCommand($output)
    {
        if ($this->target->isJavaScriptEnabled()) {
            $javascript = "on";
        } else {
            $javascript = "off";
        }

        $cmd = Settings::getTimeoutPath() . " " . $this->snipe_timeout . " " . Settings::getCutycaptPath() . "\
			--url=\"" . $this->target->getUrl() . "\" \
			--out=\"" . $output . "\" \
			--min-width=" . Settings::getScreenWidth() . " \
			--min-height=" . Settings::getScreenHeight() . " \
			--plugins=on \
			--max-wait=60000 \
			--js-can-open-windows=no \
			--js-can-access-clipboard=no \
			--private-browsing=off \
			--java=off \
			--user-agent=\"" . Settings::getUserAgent() . "\" \
			--javascript=" . $javascript . ' ';

        if(Settings::getHttpProxyUrl())
        {
            $cmd.= '--http-proxy=' . Settings::getHttpProxyUrl() . ' ';
        }

        return $cmd;
    }


    private function getWkhtmlCommand($output)
    {
        if ($this->target->isJavaScriptEnabled()) {
            $javascript = "enable";
        } else {
            $javascript = "disable";
        }

        $cmd = Settings::getTimeoutPath() . " " . $this->snipe_timeout . " " . Settings::getWkhtmlPath() . "\
			--width " . Settings::getScreenWidth() . " \
			--height " . Settings::getScreenHeight() . " \
			--crop-w " . Settings::getScreenWidth() . " \
			--crop-h " . Settings::getScreenHeight() . " \
			--format " . Settings::getMasterFiletype() . " \
			--enable-plugins \
			--" . $javascript . "-javascript \
			--load-error-handling abort \
			--disable-local-file-access \
			--custom-header 'User-Agent' '" . Settings::getUserAgent() . "' \
			--custom-header-propagation \
			--use-xserver --javascript-delay 300 ";

        if(Settings::getHttpProxyUrl())
        {
            $cmd.= '--proxy "' . Settings::getHttpProxyUrl() . '" ';
        }

        $cmd.= $this->target->getUrl() . " " . $output; // . " > /dev/null 2>&1";

        return $cmd;
    }

    public function shoot()
    {
        if (!$this->target) {
            $this->logger("no target!");
            return false;
        } else {
            $this->id = $this->target->getId();
        }

        $this->logger("target: " . $this->target->getUrl() . " (" . $this->target->getId() . ")");

        $urlparts = parse_url($this->target->getUrl());

        if (!Helpers::isIpAddress($urlparts['host']) && !Helpers::isDomainExists($urlparts['host'])) {
            $this->logger("Host " . $urlparts['host'] . " has no DNS RR");
            // setting robotsAllowed and tsRobotsCheck to null to show the API that there was really an error
            $this->target->setRobotsAllowed(null);
            $this->target->setTsRobotsCheck(null);
            $this->failure("Host has no DNS resource record");
            return false;
        }

        $robotsAllowed = RobotsChecker::isAllowed($this->target->getUrl());

        if (!$robotsAllowed) {
            $this->logger("FORBIDDEN by robots.txt");
            $this->target->setRobotsAllowed(false);
            $this->target->setTsRobotsCheck(time());
            $this->failure("Access to URL is forbidden by /robots.txt");
            return false;
        } else {
            $this->logger("allowed by robots.txt");
            $this->target->setRobotsAllowed(true);
            $this->target->setTsRobotsCheck(time());
        }

        $urlAvailable = $this->isUrlAvailable($this->target->getUrl(), true);
        if(!$urlAvailable['success'])
        {
            $this->logger("URL unavailable: " . $urlAvailable['message']);
            $this->failure("URL is unavailable: " . $urlAvailable['message']);
            return false;
        }else {
            if(array_key_exists("mime", $urlAvailable) && $urlAvailable['mime']) {
                $this->target->setMimeType($urlAvailable['mime']);
            }
        }

        if (!is_dir(Settings::getCacheDir())) {
            die(Settings::getCacheDir() . " missing");
        }

        $tmpDir = Settings::getCacheDir() . "/" . getmypid() . "." . $this->target->getFileNameBase();

        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755);
        }

        $output = $tmpDir . "/" . $this->target->getFileName();

        $this->logger("loading website");

        $this->target->setSnipeDuration(NULL);

        $exec_cmd = false;
        $this->logger("javascript " . $this->target->isJavaScriptEnabled());

        switch ($this->target->getWeapon()) {
            case "cutycapt":
                $exec_cmd = $this->getCutyCaptCommand($output);
                break;

            case "wkhtml":
                $exec_cmd = $this->getWkhtmlCommand($output);
                break;
        }

        $this->logger("weapon: " . $this->target->getWeapon());
        $this->logger("output: " . $output);

        $exec_out = array();
        $exec_rc = null;

        $exec_start = time();
        //FIXME: scheinbar wird ein Fehler beim exec nicht korrekt ausgewertet und führt zum commit von fehlerhaften targets
        exec($exec_cmd, $exec_out, $exec_rc);
        $exec_end = time();

        if ($exec_rc != 0 || !file_exists($output)) {
            $this->logger("shoot failed");
            $this->failure("no output retrieved by image generator");
            $this->cleanup($tmpDir);
            return false;
        }

        //TODO: Die verwendete CutyCapt-Version hat keine max-width/max-height-Parameter,
        //daher werden Websites immer komplett geladen (auch die sehr langen).
        // Daher wird das erzeugte Bild nachträglich verkleinert, bevor es committed wird.
        // Die Lösung könnte dieser CutyCapt-Fork sein: https://github.com/RevContent/cbcutycapt

        $this->logger("cropping image result to " . Settings::getScreenWidth() . "x" . Settings::getScreenHeight());
        $crop_exec_cmd = Settings::getConvertPath() . " -crop " . Settings::getScreenWidth() . "x" . Settings::getScreenHeight() . "+0+0 " . $output . " " . $output;
        $crop_exec_out = array();
        $crop_exec_rc = null;
        exec($crop_exec_cmd, $crop_exec_out, $crop_exec_rc);

        if($this->target->isCensored())
        {
            $this->logger("censoring master image");

            $censored = $tmpDir . "/" . $this->target->getFileName() . '_censored';
            $convert_exec_cmd = Settings::getConvertPath() . " -blur 0x3 " . $output . " " . $censored;
            //FIXME: out + rc hinzufuegen,
            exec($convert_exec_cmd);
            $output = $censored;
        }


        $this->target->setMasterImage($this->getMasterImageData($output));
        //$this->target->setTsLastUpdated(time());
        $this->target->setSnipeDuration($exec_end - $exec_start);

        $this->commit();
        $this->cleanup($tmpDir);
    }


    private function saveMasterImageData($masterImageData_base64, $fileName)
    {
        $fh = fopen($fileName, "w");
        fwrite($fh, base64_decode($masterImageData_base64));
        fclose($fh);
        chmod($fileName, 0644);
    }


    private function getMasterImageData($output)
    {
        $imageData = NULL;
        $base64 = NULL;

        if ($fp = fopen($output, "rb", 0)) {
            $imageData = fread($fp, filesize($output));
            fclose($fp);

            $base64 = chunk_split(base64_encode($imageData));
        }

        return $base64;
    }


    public function convert()
    {
        if($this->target instanceof Target && $this->target->getId())
        {
            $this->id = $this->target->getId();
        }
        $this->logger("target: " . $this->target->getUrl());

        $tmpDir = Settings::getCacheDir() . "/" . getmypid() . "." . $this->target->getFileNameBase();

        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755);
        }

        $output = $tmpDir . "/" . $this->target->getFileName();
        $this->saveMasterImageData($this->target->getMasterImage(), $output);

        //TODO: check if masterImage exists

        $crop_exec_cmd = Settings::getConvertPath() . " -crop " . Settings::getScreenWidth() . "x" . Settings::getScreenHeight() . "+0+0 " . $output . " " . $output;
        $crop_exec_out = array();
        $crop_exec_rc = null;
        exec($crop_exec_cmd, $crop_exec_out, $crop_exec_rc);

        $outputFade1 = $tmpDir . "/" . $this->target->getFileNameBase() . "_fade1." . Settings::getImageFiletype("fade1");
        $outputFade2 = $tmpDir . "/" . $this->target->getFileNameBase() . "_fade2." . Settings::getImageFiletype("fade2");
        $outputButton1 = $tmpDir . "/" . $this->target->getFileNameBase() . "_button1." . Settings::getImageFiletype("button1");
        $outputCurly = $tmpDir . "/" . $this->target->getFileNameBase() . "_curly." . Settings::getImageFiletype("curly");
        $outputBlur1 = $tmpDir . "/" . $this->target->getFileNameBase() . "_blur1." . Settings::getImageFiletype("blur1");
        $outputBlur2 = $tmpDir . "/" . $this->target->getFileNameBase() . "_blur2." . Settings::getImageFiletype("blur2");
        $outputTornPaper1 = $tmpDir . "/" . $this->target->getFileNameBase() . "_tornpaper1." . Settings::getImageFiletype("tornpaper1");
        $outputPolaroid1 = $tmpDir . "/" . $this->target->getFileNameBase() . "_polaroid1." . Settings::getImageFiletype("polaroid1");

        $newImages = array();

        try {
            /** @var $image Image */
            foreach ($this->target->getImages() as $image) {

                $this->logger("convert: " . $image->getEffect() . " - " . $image->getWidth());

                $this->id = $image->getId();

                switch ($image->getEffect()) {
                    case "fade1":
                        if (!file_exists($outputFade1)) {
                            $this->logger("effect fade1 " . $outputFade1);
                            $effect_exec_cmd = Settings::getEffectsExtraCommand('fade1') . " -f 23 -g 15 " . $output . " " . $outputFade1;
                            //FIXME: out + rc hinzufuegen
                            exec($effect_exec_cmd);
                        }
                        $useOutput = $outputFade1;
                        break;

                    case "fade2":
                        if (!file_exists($outputFade2)) {
                            $this->logger("effect fade2 " . $outputFade2);
                            $effect_exec_cmd = Settings::getEffectsExtraCommand('fade2') . " -f 23 -g 15 " . $output . " " . $outputFade2;
                            //FIXME: out + rc hinzufuegen
                            exec($effect_exec_cmd);
                        }
                        $useOutput = $outputFade2;
                        break;

                    case "button1":
                        if (!file_exists($outputButton1)) {
                            $this->logger("effect button1 " . $outputButton1);
                            $effect_exec_cmd = Settings::getConvertPath() . " " . $output . " -colorspace sRGB" .
                                " -fill gray55 -colorize 100% -raise 40 -normalize -blur 0x40 " . $outputButton1 . ".overlay";
                            exec($effect_exec_cmd);
                            $effect_exec_cmd = Settings::getConvertPath() . " " . $output . " " . $outputButton1 . ".overlay" .
                                " -colorspace sRGB -compose hardlight -composite " . $outputButton1;
                            exec($effect_exec_cmd);
                        }
                        $useOutput = $outputButton1;
                        break;


                    case "curly":
                        if (!file_exists($outputCurly)) {
                            $this->logger("effect curly " . $outputCurly);
                            $effect_exec_cmd = Settings::getConvertPath() . " " . $output . " -colorspace sRGB" .
                                " -alpha off -fill white -colorize 100% " .
                                " -draw 'fill black polygon 0,0 0,15 15,0 fill white circle 15,15 15,0' " .
                                " \( +clone -flip \) -compose Multiply -composite " .
                                " \( +clone -flop \) -compose Multiply -composite " .
                                " -background gray50 -alpha Shape " . $outputCurly . ".overlay";
                            exec($effect_exec_cmd);
                            $effect_exec_cmd = Settings::getConvertPath() . " " . $outputCurly . ".overlay" .
                                " -bordercolor None -border 1x1 -alpha Extract -blur 0x120  -shade 130x30 -alpha On " .
                                " -background gray50 -alpha background -auto-level " .
                                " -function polynomial  3.5,-5.05,2.05,0.3 " .
                                " \( +clone -alpha extract  -blur 0x2 \) " .
                                " -channel RGB -compose multiply -composite " .
                                " +channel +compose -chop 1x1 " . $outputCurly . ".lighting";
                            exec($effect_exec_cmd);
                            $effect_exec_cmd = Settings::getConvertPath() . " " . $output . " -colorspace sRGB" .
                                " -alpha Set " . $outputCurly . ".lighting " .
                                " \( -clone 0,1 -alpha Opaque -compose Hardlight -composite \) " .
                                " -delete 0 -compose In -composite " . $outputCurly;
                            exec($effect_exec_cmd);
                        }
                        $useOutput = $outputCurly;
                        break;


                    case "blur1":
                        if (!file_exists($outputBlur1)) {
                            $this->logger("effect blur1 " . $outputBlur1);
                            $effect_exec_cmd = Settings::getConvertPath() . " " . $output . " -colorspace sRGB" .
                                " -alpha set -virtual-pixel transparent -channel A -morphology Distance Euclidean:1,80\! +channel " . $outputBlur1;
                            exec($effect_exec_cmd);
                        }
                        $useOutput = $outputBlur1;
                        break;
                    case "blur2":
                        if (!file_exists($outputBlur2)) {
                            $this->logger("effect blur2 " . $outputBlur2);
                            $effect_exec_cmd = Settings::getConvertPath() . " " . $output . " -colorspace sRGB" .
                                " -alpha set -virtual-pixel transparent -channel A -blur 0x40 -level 50%,100% +channel " . $outputBlur2;
                            exec($effect_exec_cmd);
                        }
                        $useOutput = $outputBlur2;
                        break;
                    case "tornpaper1":
                        if (!file_exists($outputTornPaper1)) {
                            $this->logger("effect tornpaper1 " . $outputTornPaper1);
                            $effect_exec_cmd = Settings::getConvertPath() . " " . $output . " -colorspace sRGB" .
                                " \( +clone -alpha extract -virtual-pixel black -spread 90 -blur 0x3 -threshold 50% -spread 1 -blur 0x.7 \)" .
                                " -alpha off -compose Copy_Opacity -composite " . $outputTornPaper1;
                            exec($effect_exec_cmd);
                        }
                        $useOutput = $outputTornPaper1;
                        break;
                    case "polaroid1":
                        if (!file_exists($outputPolaroid1)) {
                            $this->logger("effect polaroid1 " . $outputPolaroid1);
                            $effect_exec_cmd = Settings::getConvertPath() . " " . $output . " -colorspace sRGB" .
                                " -bordercolor snow -background black +polaroid " . $outputPolaroid1;
                            exec($effect_exec_cmd);
                        }
                        $useOutput = $outputPolaroid1;
                        break;
                    default:
                        //TODO: $image->effect = "plain";
                        $useOutput = $output;
                        break;
                }

                $imageVariant = $tmpDir . "/" . $this->target->getFileNameBase() . $image->getFileNameSuffix() . "." . Settings::getImageFiletype($image->getEffect());
                $convert_exec_cmd = Settings::getConvertPath() . " " . $useOutput . " -resize " . " " . $image->getWidth() . " " . $imageVariant;
                $this->logger("resize " . $image->getEffect() . " " . $imageVariant);
                //FIXME: out + rc hinzufuegen,
                exec($convert_exec_cmd);

                $imageData = NULL;

                if ($fp = fopen($imageVariant, "rb", 0)) {
                    $imageData = fread($fp, filesize($imageVariant));
                    fclose($fp);
                    $base64 = chunk_split(base64_encode($imageData));
                    $image->setImageData($base64);
                    $image->setTsLastUpdated(time());
                    $imageSize = getimagesize($imageVariant);
                    $image->setHeight($imageSize[1]);
                    $this->logger("height: " . $image->getHeight());
                    $newImages[] = $image;
                }
            }
        } catch (\Exception $e) {
            return false;
        }

        if ($newImages) {
            $this->target->setImages($newImages);
            //TODO: commit also if masterImage was successful?
            $this->commitImage();
        } else {
            //FIXME: failure
        }

        return $this->cleanup($tmpDir);
    }


    private function cleanup($dir)
    {
        $files = array_diff(scandir($dir), array('.', '..'));

        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->cleanup("$dir/$file") : unlink("$dir/$file");
        }

        return rmdir($dir);
    }


    private function commit()
    {
        $this->logger("sending results (commit)");

        switch($this->mode)
        {
            case "normal":
                $apiUrl = Settings::getApiUrlTargetCommitNormal();
                break;

            case "longrun":
                $apiUrl = Settings::getApiUrlTargetCommitLongrun();
                break;

            default:
                return false;
        }
        $this->sendResults($apiUrl);
    }


    private function commitImage()
    {
        $this->logger("sending results (commitImage)");
        $this->sendResults(Settings::getApiUrlImageCommit());
    }


    private function failure($errorMessage)
    {
        $this->logger("sending results (failure)");
        $this->target->setLastErrorMessage($errorMessage);

        switch($this->mode)
        {
            case "normal":
                $apiUrl = Settings::getApiUrlTargetFailureNormal();
                break;

            case "longrun":
                $apiUrl = Settings::getApiUrlTargetFailureLongrun();
                break;

            default:
                return false;
        }

        $this->sendResults($apiUrl);
    }


    private function sendResults($url)
    {
        $target_serialized = serialize($this->target);
        $jsonData = base64_encode($target_serialized);

        $postData = "data=" . urlencode($jsonData);
//		$postData = "data=" . $jsonData;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);

        if(Settings::getHttpProxyUrl())
        {
            curl_setopt($ch, CURLOPT_PROXY, Settings::getHttpProxyUrl());
        }

//		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
//		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
//		curl_setopt($ch, CURLOPT_CAINFO, $sslCaCert);
//		curl_setopt($ch, CURLOPT_SSLKEY, $sslKey);
//		curl_setopt($ch, CURLOPT_SSLCERT, $sslCert);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));

        $this->logger("serverresponse:" . curl_exec($ch));

        curl_close($ch);
    }


    private function isUrlAvailable($url, $nobody)
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, Settings::getUserAgent());
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        curl_setopt($ch, CURLOPT_NOBODY, $nobody);

        curl_setopt($ch, CURLOPT_TIMEOUT, Settings::getUrlAvailabilityCheckTimeout()); //timeout in seconds
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        if(Settings::getHttpProxyUrl())
        {
            curl_setopt($ch, CURLOPT_PROXY, Settings::getHttpProxyUrl());
        }

        curl_exec($ch);

        if (!curl_errno($ch) && curl_getinfo($ch, CURLINFO_HTTP_CODE) && curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200)
        {
            $this->logger("curl return code for URL: " . curl_getinfo($ch, CURLINFO_HTTP_CODE));

            $mime = null;
            $charset = null;

            /* Get the MIME type and character set */
            preg_match( '@([-\w/+]+)(;\s+charset=(\S+))?@i', curl_getinfo($ch, CURLINFO_CONTENT_TYPE), $matches );
            if(isset($matches[1])) {
                $mime = $matches[1];
            }
            if(isset($matches[3])) {
                $charset = $matches[3];
            }


            $result = array(
                'success' => true,
                'message' => null,
                'mime' => $mime
            );
        }elseif((curl_getinfo($ch, CURLINFO_HTTP_CODE) == 405 || curl_getinfo($ch, CURLINFO_HTTP_CODE) == 403) && $nobody) {
            $this->logger("retrying check with body content");
            $result = $this->isUrlAvailable($url, false);
        }else{
            $this->logger("curl failed while trying to access " . $url . ": " . curl_getinfo($ch, CURLINFO_HTTP_CODE));
            if(curl_error($ch)) {
                $this->logger("curl error -> " . curl_error($ch));
            }
            $result = array(
                'success' => false,
                'message' => curl_getinfo($ch, CURLINFO_HTTP_CODE) . " " . curl_error($ch)
            );
        }

        curl_close($ch);

        return $result;
    }


    public function getFeaturedEffects()
    {
        $features = Settings::getEffectsBuiltin();

        if(!is_array($features))
        {
            $features = array();
        }

        foreach(array_keys(Settings::getEffectsExtraCommands()) as $effect)
        {
            $cmd = Settings::getEffectsExtraCommand($effect);
            if($cmd)
            {
                $features[] = $effect;
            }
        }

        return $features;
    }
}
