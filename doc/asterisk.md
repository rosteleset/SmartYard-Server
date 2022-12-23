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
