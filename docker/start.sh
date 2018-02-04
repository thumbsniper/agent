#!/bin/bash

if [ -z "$TAGENT_QUEUE" ]; then
	echo 'queue name is missing.'
	exit 1
fi

BASEDIR="/opt/thumbsniper"

export DISPLAY=:1000


while :; do
    if [ "${TAGENT_QUEUE}" == "phantom" ]; then
        echo "starting new PhantomJS instance"
        /usr/bin/php -f /opt/thumbsniper/scripts/phantomLauncher.php
    elif [ "${TAGENT_QUEUE}" == "normal" ] || [ "${TAGENT_QUEUE}" == "longrun" ]; then
		XVFBPID=$(pidof Xvfb)
        if [ -z "$XVFBPID" ]; then
            echo "xvfb is not running"
            /usr/bin/Xvfb -noreset -screen scrn 1600x1200x24 -extension RANDR -extension GLX ${DISPLAY} &
            sleep 3
		fi

		XVFBPID=$(pidof Xvfb)
		if [ -n "$XVFBPID" ]; then
            timeout 120 /usr/bin/php -f /opt/thumbsniper/scripts/generatorLauncher.php ${TAGENT_QUEUE}
			echo "finished the generator command"
		else
			echo "xvfb is still no there, yet"
        fi
    else
		timeout 120 /usr/bin/php -f /opt/thumbsniper/scripts/generatorLauncher.php ${TAGENT_QUEUE}
    fi
done

echo "exiting now"
