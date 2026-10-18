# You can change this to a different version of Wordpress available at
# https://hub.docker.com/_/wordpress
FROM wordpress:latest

RUN apt-get update && apt-get install -y magic-wormhole

# PHP limits: large uploads for backup imports, memory sized for the 512 MB Starter plan
RUN printf 'upload_max_filesize = 2048M\npost_max_size = 2048M\nmemory_limit = 256M\nmax_execution_time = 300\n' > /usr/local/etc/php/conf.d/uploads.ini

# Cap Apache workers so a traffic burst can't run the 512 MB instance out of memory
RUN printf '<IfModule mpm_prefork_module>\n\tStartServers 2\n\tMinSpareServers 2\n\tMaxSpareServers 4\n\tMaxRequestWorkers 6\n\tMaxConnectionsPerChild 500\n</IfModule>\n' > /etc/apache2/mods-available/mpm_prefork.conf

RUN usermod -s /bin/bash www-data
RUN chown www-data:www-data /var/www
USER www-data:www-data


