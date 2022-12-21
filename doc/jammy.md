packages

```
apt-get update && apt-get -y install -f && apt-get -y full-upgrade && apt-get -y autoremove && apt-get -y autoclean && apt-get -y clean

apt-get install redis nginx php-fpm php-redis php-mbstring php-curl php-pdo-sqlite php-pdo-pgsql php-pdo php-pear php-dev libxt6 libxmu6 python-dev-is-python3 libssl-dev liblzma-dev libcurl4-openssl-dev lua5.4 libedit-dev libxml2-dev xmlstarlet liblua5.4-dev libcurl4-openssl-dev libxslt1-dev libssl-dev libsrtp2-dev lua-cjson luarocks patch uuid-dev libldap2-dev libsqlite3-dev git

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

asterisk

```
wget http://downloads.asterisk.org/pub/telephony/asterisk/asterisk-18-current.tar.gz -O - | gzip -dc | tar -xvf -

cd asterisk-18-...

./configure --with-jansson-bundled

make

make install
```

/etc/systemd/system/asterisk.service

```
[Unit]
Description=Asterisk PBX and telephony daemon.
After=network.target

[Service]
Type=simple
Environment=HOME=/var/lib/asterisk
WorkingDirectory=/var/lib/asterisk
User=asterisk
Group=asterisk
ExecStart=/usr/sbin/asterisk -mqf -C /etc/asterisk/asterisk.conf
ExecReload=/usr/sbin/asterisk -rx 'core reload'
LimitCORE=infinity
Restart=always
RestartSec=4
StandardOutput=null
PrivateTmp=true

[Install]
WantedBy=multi-user.target
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

        location /frontend {
                rewrite ^.*$ /frontend.php last;
        }

        location = /frontend.php {
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