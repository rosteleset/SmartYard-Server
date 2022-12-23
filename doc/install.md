download server libs

```
cd server/lib
git clone https://github.com/PHPMailer/PHPMailer
git clone https://github.com/ezyang/htmlpurifier
git clone -b 1.7.x https://github.com/erusev/parsedown
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
```

initialize db

```
php server/cli.php --init-db
php server/cli.php --admin-password=<your very secret admin password>
php server/cli.php --reindex
```
