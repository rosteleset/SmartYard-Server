download from 

```
https://www.mongodb.com/try/download/community
```

or direct download from

```
wget https://fastdl.mongodb.org/linux/mongodb-linux-x86_64-ubuntu2004-6.0.3.tgz
```

unpack to /opt/mongodb, create group, user and directories

```
groupadd mongodb
useradd -g mongodb -s /bin/true mongodb

mkdir /var/lib/mongodb
chown mongodb.mongodb /var/lib/mongodb

mkdir /var/run/mongodb
chown mongodb.mongodb /var/run/mongodb
```

/etc/systemd/system/mongodb.service

```
[Unit]
Description=MongoDB NoSQL database.
After=network.target

[Service]
Type=simple
Environment=HOME=/var/lib/mongodb
WorkingDirectory=/var/lib/mongodb
User=mongodb
Group=mongodb
ExecStart=/opt/mongodb/bin/mongod --dbpath /var/lib/mongodb --directoryperdb --pidfilepath /var/run/mongodb/mongodb.pid
LimitCORE=infinity
Restart=always
RestartSec=4
StandardOutput=null
PrivateTmp=true

[Install]
WantedBy=multi-user.target
```
