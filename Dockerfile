FROM registry.service.opg.digital/opg-php-fpm-1604

WORKDIR /app
ENV  HOME /app
ENV NPM_CONFIG_PREFIX /usr/local

# adds nodejs pkg repository
RUN  curl --silent --location https://deb.nodesource.com/setup_8.x | bash -

ARG APT_PACKAGES="dos2unix nodejs ruby2.4 ruby2.4-dev"

# installs nodejs & ruby
RUN apt-add-repository ppa:brightbox/ruby-ng && \
	apt-get update && \
	apt-get install -y ${APT_PACKAGES} && \
	apt-get clean && apt-get autoremove && \
	rm -rf /var/lib/cache/* /var/lib/log/* /tmp/* /var/tmp/* && \
	gem install --no-ri --no-rdoc sass -v 3.4.25

# To install bower globally
run echo "app ALL=(ALL) NOPASSWD: ALL" >> /etc/sudoers

# Install npm dependencies
USER app
ADD . /app
RUN sudo chown -R app:app . && \
    rm -rf /app/assets && \
    npm set progress=false && \
	npm install && \
	sudo npm install -g bower && \
	bower install && \
	sudo npm install -g grunt-cli

USER root
RUN groupadd webservice && \
    groupadd supervisor

# Add application logging config(s)
ADD docker/beaver.d /etc/beaver.d

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
