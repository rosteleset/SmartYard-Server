#/bin/bash

cd /opt/rbt

git pull
supervisorctl restart all

php server/cli.php --clear-config