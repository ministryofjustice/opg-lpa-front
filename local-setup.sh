

mkdir -p /app/vendor && \
chown -R app:app /app/vendor && \
gosu app php /tmp/composer.phar install --prefer-dist --optimize-autoloader --no-suggest --no-interaction --no-scripts && \
rm /tmp/composer.phar && \
rm -rf docker README* LICENSE* composer.*
