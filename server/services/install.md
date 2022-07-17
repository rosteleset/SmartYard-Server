mkdir nodejs
wget -O - https://nodejs.org/dist/v16.16.0/node-v16.16.0-linux-x64.tar.xz | xz -d | tar -xf - -C nodejs --strip-components=1

./nodejs/bin/npm i asterisk-manager
