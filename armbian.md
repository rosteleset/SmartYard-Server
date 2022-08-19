apt-get update && apt-get -y install -f && apt-get -y full-upgrade && apt-get -y autoremove && apt-get -y autoclean && apt-get -y clean
apt-get install redis
apt-get install nginx php-fpm php-redis php-mbstring php-curl php-pdo-sqlite php-pdo-pgsql php-pdo php-pear php-dev
pecl install mongodb
apt-get install python-dev-is-python3 libssl-dev
apt-get install liblzma-dev libcurl4-openssl-dev

wget https://www.openssl.org/source/openssl-1.1.1o.tar.gz
cd openssl-1.1.1o
./config
make
make install

wget https://fastdl.mongodb.org/src/mongodb-src-r6.0.0.tar.gz
cd mongodb-src-r6.0.0
python3 -m pip install -r etc/pip/compile-requirements.txt
python3 buildscripts/scons.py DESTDIR=/opt/mongo install-mongod --disable-warnings-as-errors

apt-get install lua5.4 libedit-dev libxml2-dev xmlstarlet liblua5.4-dev libcurl4-openssl-dev libxslt1-dev libssl-dev libsrtp2-dev lua-cjson luarocks patch uuid-dev libldap2-dev libsqlite3-dev

luarocks-5.4 install luasec
luarocks-5.4 install inspect
luarocks-5.4 install luasocket

wget http://downloads.asterisk.org/pub/telephony/asterisk/asterisk-18-current.tar.gz -O - | gzip -dc | tar -xvf -
cd asterisk-18-...
./configure --with-jansson-bundled
