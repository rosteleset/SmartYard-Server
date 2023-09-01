#/bin/bash

cd /opt/rbt

git pull

cd /opt/rbt/server

composer install --no-dev --optimize-autoloader

php cli.php --clear-config
php cli.php --reindex

supervisorctl restart all