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

---

!!! DEVEL MODE ONLY !!! NOT FOR PRODUCTION !!!

if you are using sqlite and standalone (not built-in) web-server (nginx)
set valid rights and ownership for web server to 
file server/db/internal.db and folder server/db 

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
