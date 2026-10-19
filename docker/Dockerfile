# Imagen web: PHP 8.3 + Apache con el código de Moodle 4.5.15 incluido.
# Tener el código dentro de la imagen (y no en una carpeta de Windows montada) acelera mucho la instalación y la web.
FROM moodlehq/moodle-php-apache:8.3

ARG MOODLE_TAG=v4.5.15
RUN curl -fsSL "https://github.com/moodle/moodle/archive/refs/tags/${MOODLE_TAG}.tar.gz" \
      | tar xz --strip-components=1 -C /var/www/html

COPY config.php /var/www/html/config.php
