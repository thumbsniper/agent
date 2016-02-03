FROM opensuse:13.2

MAINTAINER Thomas Schulte <thomas@cupracer.de>

CMD ["/start.sh"]

RUN zypper --non-interactive in curl

RUN rpm --import \
	http://download.opensuse.org/repositories/home:/TERROR-FX:/thumbsniper:/utilities/openSUSE_13.2/repodata/repomd.xml.key \
	http://download.opensuse.org/repositories/Publishing/openSUSE_13.2/repodata/repomd.xml.key \
	http://download.opensuse.org/repositories/server:/php/openSUSE_13.2/repodata/repomd.xml.key \
	http://download.opensuse.org/repositories/server:/php:/applications/openSUSE_13.2/repodata/repomd.xml.key \
	http://download.opensuse.org/repositories/server:/php:/extensions/openSUSE_13.2/repodata/repomd.xml.key

RUN /usr/bin/zypper ar --refresh http://download.opensuse.org/repositories/home:/TERROR-FX:/thumbsniper:/utilities/openSUSE_13.2/home:TERROR-FX:thumbsniper:utilities.repo
RUN /usr/bin/zypper ar --refresh http://download.opensuse.org/repositories/Publishing/openSUSE_13.2/Publishing.repo
RUN /usr/bin/zypper ar --refresh http://download.opensuse.org/repositories/server:/php/openSUSE_13.2/server:php.repo
RUN /usr/bin/zypper ar --refresh http://download.opensuse.org/repositories/server:/php:/applications/openSUSE_13.2/server:php:applications.repo
RUN /usr/bin/zypper ar --refresh http://download.opensuse.org/repositories/server:/php:/extensions/openSUSE_13.2/server:php:extensions.repo

RUN zypper --non-interactive ref && zypper --non-interactive in \
	bc \
	ca-certificates-mozilla \
	cutycapt \
        git \
	glibc-extra \
	glibc-locale \
	ImageMagick \
	libQtWebKit4 \
	php-composer \
	php5 \
	php5-bz2 \
	php5-ctype \
	php5-curl \
	php5-exif \
	php5-gd \
	php5-gettext \
	php5-imagick \
	php5-intl \
	php5-json \
	php5-mbstring \
	php5-openssl \
	php5-zip \
	php5-zlib \
	wkhtmltopdf \
	xorg-x11-server \
	dejavu-fonts \
	efont-unicode-bitmap-fonts \
	ghostscript-fonts-other \
	ghostscript-fonts-std \
	google-droid-fonts \
	intlfonts \
	intlfonts-euro-bitmap-fonts \
	liberation-fonts \
	patterns-openSUSE-fonts_opt \
	xorg-x11-fonts

RUN /usr/sbin/groupadd -g 999 tagent
RUN /usr/sbin/useradd -u 999 -g 999 -c tagent -m -s /bin/bash tagent

COPY etc/php5 /etc/php5

RUN git clone https://github.com/thumbsniper/agent.git /opt/thumbsniper && cd /opt/thumbsniper && git checkout v0.9.2

RUN composer --working-dir=/opt/thumbsniper update

COPY config/agent-config.inc.php /opt/thumbsniper/config/agent-config.inc.php
COPY start.sh /start.sh

RUN zypper --non-interactive up

USER tagent

