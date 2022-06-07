download server libs

```
cd server/lib
git clone https://github.com/PHPMailer/PHPMailer 
git clone https://github.com/ezyang/htmlpurifier
```

download client libs

```
cd client/lib
git clone https://github.com/ColorlibHQ/AdminLTE
git clone https://github.com/davidshimjs/qrcodejs
git clone https://github.com/lekoala/bootstrap5-tags
git clone https://github.com/loadingio/loading-bar
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
http://localhost:8000/server/api.php
```