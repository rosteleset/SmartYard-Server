packages

```
apt-get update && apt-get -y install -f && apt-get -y full-upgrade && apt-get -y autoremove && apt-get -y autoclean && apt-get -y clean

apt-get install redis nginx php-fpm php-redis php-mbstring php-curl php-pdo-sqlite php-pdo-pgsql php-pdo php-pear php-dev

pecl install mongodb

apt-get install python-dev-is-python3 libssl-dev

apt-get install liblzma-dev libcurl4-openssl-dev

apt-get install lua5.4 libedit-dev libxml2-dev xmlstarlet liblua5.4-dev libcurl4-openssl-dev libxslt1-dev libssl-dev libsrtp2-dev lua-cjson luarocks patch uuid-dev libldap2-dev libsqlite3-dev

luarocks-5.4 install luasec

luarocks-5.4 install inspect

luarocks-5.4 install luasocket

luarocks-5.4 install lua-cjson 2.1.0-1
```

asterisk

```
wget http://downloads.asterisk.org/pub/telephony/asterisk/asterisk-18-current.tar.gz -O - | gzip -dc | tar -xvf -

cd asterisk-18-...

./configure --with-jansson-bundled

make

make install
```

nginx

```
server {
        listen 80 default_server;
        listen [::]:80 default_server;

        server_name rbt.mmikel.ru;

        location / {
                root /opt/rbt/client;
                try_files $uri $uri/ =404;
        }

        location /api {
                rewrite ^.*$ /api.php last;
        }

        location = /api.php {
                root /opt/rbt/server;
                include snippets/fastcgi-php.conf;
                fastcgi_pass unix:/var/run/php/php-fpm.sock;
        }

        location /asterisk {
                rewrite ^.*$ /asterisk.php last;
        }

        location = /asterisk.php {
                allow 127.0.0.1;
                deny all;
                root /opt/rbt/server;
                include snippets/fastcgi-php.conf;
                fastcgi_pass unix:/var/run/php/php-fpm.sock;
        }

        location /internal {
                rewrite ^.*$ /internal.php last;
        }

        location = /internal.php {
                allow 127.0.0.1;
                deny all;
                root /opt/rbt/server;
                include snippets/fastcgi-php.conf;
                fastcgi_pass unix:/var/run/php/php-fpm.sock;
        }

        location /mobile {
                rewrite ^.*$ /mobile.php last;
        }

        location = /mobile.php {
                root /opt/rbt/server;
                include snippets/fastcgi-php.conf;
                fastcgi_pass unix:/var/run/php/php-fpm.sock;
        }

        location /wss {
                proxy_pass http://127.0.0.1:8088/ws;
                proxy_http_version 1.1;
                proxy_set_header Upgrade $http_upgrade;
                proxy_set_header Connection "upgrade";
                proxy_set_header Host $host;
                proxy_set_header X-Real-IP $remote_addr;
                proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
                proxy_read_timeout 43200000;
        }
}
```
