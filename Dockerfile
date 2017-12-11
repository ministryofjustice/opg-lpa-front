FROM registry.service.opg.digital/opg-php-fpm-1604

# adds nodejs pkg repository
RUN  curl --silent --location https://deb.nodesource.com/setup_8.x | bash -

# installs nodejs & ruby
RUN  apt-add-repository ppa:brightbox/ruby-ng && \
        apt-get update && \
        apt-get install -y \
        dos2unix \
        nodejs ruby2.4 ruby2.4-dev && \
        apt-get clean && apt-get autoremove && \
        rm -rf /var/lib/cache/* /var/lib/log/* /tmp/* /var/tmp/*

RUN  gem install --no-ri --no-rdoc sass -v 3.4.25

RUN mkdir -p /usr/local/{share/man,bin,lib/node,include/node} /usr/etc && \
    chown -R app /usr/local/{share/man,bin,lib/node,include/node} /usr/etc 

# Install npm dependencies
WORKDIR /app
USER app
ENV  HOME /app
COPY bower.json /app/
COPY package.json /app/
# USER root
RUN  npm -g set progress=false
RUN  npm install

# Install bower dependencies
USER root
RUN npm install -g bower

USER app
RUN bower install

USER root
# Install grunt command line
RUN  npm install -g grunt-cli

RUN groupadd webservice && \
    groupadd supervisor

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
