# You can change this to a different version of Wordpress available at
# https://hub.docker.com/_/wordpress
FROM wordpress:latest

RUN apt-get update && apt-get install -y magic-wormhole

RUN usermod -s /bin/bash www-data
RUN chown www-data:www-data /var/www
USER www-data:www-data

RUN printf 'upload_max_filesize = 2048M\npost_max_size = 2048M\nmemory_limit = 512M\nmax_execution_time = 300\n' > /usr/local/etc/php/conf.d/uploads.ini
