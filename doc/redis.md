generate redis.conf (if not exists)

```
sudo cp /etc/redis/default.conf.example /etc/redis/redis.conf
sudo chown redis:redis /etc/redis/redis.conf
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
