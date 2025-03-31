FROM php:7.1-fpm

RUN apt-get update \
	    && apt-get install -y git libxml2-dev zlib1g-dev xvfb libfontconfig wkhtmltopdf libgmp-dev re2c libmhash-dev libmcrypt-dev file \
	    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo \
	pdo_mysql \
	zip \
	bcmath \
	sockets

RUN apt-get update \
	&& apt-get install -y libfreetype6-dev libjpeg62-turbo-dev libmcrypt-dev libpng-dev \
	&& rm -rf /var/lib/apt/lists/* \
	&& docker-php-ext-install -j$(nproc) iconv mcrypt gd \
	&& docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
	&& docker-php-ext-install -j$(nproc) gd

# GMP
RUN apt-get install -y libgmp-dev file \
	&& ln -s /usr/include/x86_64-linux-gnu/gmp.h /usr/local/include/ \
	&& docker-php-ext-configure gmp \
	&& docker-php-ext-install gmp

# xdebug
RUN pecl install xdebug && docker-php-ext-enable xdebug

# php.ini
COPY php.ini /usr/local/etc/php/

RUN mkdir -p /mnt/jarvis-file-bundle/local && chown -R www-data:www-data /mnt/jarvis-file-bundle/
VOLUME /mnt/jarvis-file-bundle/local/

# install composer
COPY install-composer.sh /tmp/install-composer.sh
RUN chmod +x /tmp/install-composer.sh \
	&& bash /tmp/install-composer.sh
VOLUME /root/.composer
