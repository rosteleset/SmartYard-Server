```
sudo zypper in -t pattern devel_basis
sudo zypper in php-cli php-redis redis php-mbstring php-curl php-pecl php-devel
```

```
sudo pecl install mongodb
```

download server libs

```
cd server/lib
git clone https://github.com/PHPMailer/PHPMailer
git clone https://github.com/ezyang/htmlpurifier
git clone -b 1.7.x https://github.com/erusev/parsedown
```

```
sudo pecl install mongodb
```

download client libs

```
cd client/lib
git clone https://github.com/ColorlibHQ/AdminLTE
git clone https://github.com/davidshimjs/qrcodejs
git clone https://github.com/loadingio/loading-bar
git clone https://github.com/ajaxorg/ace-builds/
```

edit client config
```
cp client/config/config.sample.json client/config/config.json
```

edit db, redis and email settings (email - optional)

```
cp server/config/config.sample.json server/config/config.json
vi server/config/config.json
```

generate redis.conf (if not exists)

```
sudo cp /etc/redis/default.conf.example /etc/redis/redis.conf
sudo chown redis:redis /etc/redis/redis.conf
```

if you want to use authorized connection to redis run
```
sudo sh -c "echo requirepass <your very secret passphrase> >>/etc/redis/redis.conf"
```

creating redis systemd unit (if needed)

```
sudo vi /etc/systemd/system/redis.service
```

```
[Unit]
Description=Redis In-Memory Data Store
After=network.target

[Service]
User=redis
Group=redis
ExecStart=/usr/sbin/redis-server /etc/redis/redis.conf
LimitNOFILE=10240
ExecStop=/usr/bin/redis-cli shutdown
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

starting redis server

```
sudo systemctl start redis
sudo systemctl enable redis
```

generating api and server documentation (optional)

```
apidoc -c apidoc.json -i server -o api
phpDocumentor.phar --sourcecode -t doc -s graphs.enabled=true
```

initializing internal db

```
php server/cli.php --init-db
php server/cli.php --admin-password=<your very secret admin password>
php server/cli.php --reindex
```

if you are using sqlite and standalone (not built-in) web-server (nginx)
set valid rights and ownership for web server to 
file server/db/internal.db and folder server/db 

optionally

```
php server/cli.php --check-mail=<your email address>
```

run local (built-in) server (not for production!)

```
php server/cli.php --run-demo-server
```

open in your browser

```
http://localhost:8000/client/index.html
```

use your server (in server field at login page)

```
http://localhost:8000/server/frontend.php
```
