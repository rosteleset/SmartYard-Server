```
cd /opt
git clone https://github.com/rosteleset/rbt
```

download server libs

```
cd /opt/rbt/server/lib
git clone https://github.com/PHPMailer/PHPMailer
git clone https://github.com/ezyang/htmlpurifier
git clone -b 1.7.x https://github.com/erusev/parsedown
```

download client libs

```
cd /opt/rbt/client/lib
git clone https://github.com/ColorlibHQ/AdminLTE
git clone https://github.com/davidshimjs/qrcodejs
git clone https://github.com/loadingio/loading-bar
git clone https://github.com/ajaxorg/ace-builds/
```

edit client config
```
cp /opt/rbt/client/config/config.sample.json /opt/rbt/client/config/config.json
```

edit db, redis and email settings

```
cp /opt/rbt/server/config/config.sample.json /opt/rbt/server/config/config.json
```

initialize db

```
php /opt/rbt/server/cli.php --init-db
php /opt/rbt/server/cli.php --admin-password=<your very secret admin password>
php /opt/rbt/server/cli.php --reindex
```
