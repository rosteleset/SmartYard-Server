packages

```
apt-get update && apt-get -y install -f && apt-get -y full-upgrade && apt-get -y autoremove && apt-get -y autoclean && apt-get -y clean

apt-get install -y redis nginx php-fpm php-redis php-mbstring php-curl php-pdo-sqlite php-pdo-pgsql php-pdo php-pear php-dev libxt6 libxmu6 python-dev-is-python3 libssl-dev liblzma-dev libcurl4-openssl-dev lua5.4 libedit-dev libxml2-dev xmlstarlet liblua5.4-dev libcurl4-openssl-dev libxslt1-dev libssl-dev libsrtp2-dev lua-cjson luarocks patch uuid-dev libldap2-dev libsqlite3-dev git ntp cron rsyslog logrotate socat

pecl channel-update pecl.php.net
pecl install mongodb
echo "extension=mongodb.so" >/etc/php/8.1/mods-available/mongodb.ini
ln -sf /etc/php/8.1/mods-available/mongodb.ini /etc/php/8.1/cli/conf.d/30-mongodb.ini
ln -sf /etc/php/8.1/mods-available/mongodb.ini /etc/php/8.1/fpm/conf.d/30-mongodb.ini

apt-get purge lua-sec lua-socket

luarocks-5.4 install luasec

luarocks-5.4 install inspect

luarocks-5.4 install luasocket

luarocks-5.4 install lua-cjson 2.1.0-1
```