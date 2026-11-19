# Imagen oficial de PHP 8.3 con el servidor web Apache.
FROM php:8.3-apache

# Apache sirve SOLO la carpeta public/. src/, datos/ y tests/ quedan fuera de la web.
ENV APACHE_DOCUMENT_ROOT=/var/www/edunex/public \
    EDUNEX_DATOS=/var/www/edunex/datos
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf \
 && printf "expose_php=Off\ndisplay_errors=Off\nlog_errors=On\nupload_max_filesize=1M\npost_max_size=2M\nsession.cookie_httponly=1\nsession.use_strict_mode=1\n" > /usr/local/etc/php/conf.d/edunex.ini \
 && printf "ServerTokens Prod\nServerSignature Off\n" > /etc/apache2/conf-enabled/edunex.conf

WORKDIR /var/www/edunex
COPY src/ src/
COPY public/ public/
COPY tests/pruebas.php tests/pruebas.php
COPY data/ data/

# La carpeta de datos la puede escribir solo el usuario del servidor web.
RUN mkdir -p datos && chown -R www-data:www-data datos && chmod 770 datos

# Antes de terminar de construir la imagen, se corren los casos de prueba: si alguno falla, no se construye.
RUN EDUNEX_DATOS=/tmp/prueba php tests/pruebas.php && rm -rf /tmp/prueba

EXPOSE 80
