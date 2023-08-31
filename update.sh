#/bin/bash

cd /opt/rbt

git pull
supervisorctl restart all

cd /opt/rbt/server

composer install --no-dev
composer dump-autoload

php cli.php --clear-config
