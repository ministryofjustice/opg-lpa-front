FROM registry.service.opg.digital/opguk/php-fpm

RUN groupadd webservice && \
    groupadd supervisor

RUN apt-get update && apt-get install -y \
    php5-curl php-pear php5-dev

RUN php5enmod mcrypt

RUN echo "expose_php = Off" > /etc/php5/mods-available/do_not_expose_php.ini && \
    php5enmod do_not_expose_php

RUN echo "short_open_tag = On" > /etc/php5/mods-available/allow_php_short_tags.ini && \
    php5enmod allow_php_short_tags

# Add application logging config(s)
ADD docker/beaver.d /etc/beaver.d

ADD . /app
RUN mkdir -p /srv/opg-lpa-front2/application && \
    mkdir /srv/opg-lpa-front2/application/releases && \
    chown -R app:app /srv/opg-lpa-front2/application && \
    chmod -R 755 /srv/opg-lpa-front2/application && \
    ln -s /app /srv/opg-lpa-front2/application/current

ADD docker/confd /etc/confd
ADD docker/my_init/* /etc/my_init.d/
ADD docker/certificates/* /usr/local/share/ca-certificates/

ADD docker/bin/update-ca-certificates /usr/sbin/update-ca-certificates
RUN chmod 755 /usr/sbin/update-ca-certificates; sync; /usr/sbin/update-ca-certificates --verbose

ENV OPG_SERVICE front
