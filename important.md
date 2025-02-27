# 2025-02-07

Use global composer as dependency manager

Please install dependencies:

```bash
cd /opt/rbt/server
composer install
```

Sometimes updating php mongodb required:

```bash
find /usr/lib/php/ | grep mongodb.so | xargs rm
pecl install -f mongodb
```

Restart php-fpm:

```bash
systemctl restart php<PHP_VERSION>-fpm
```

# 2024-12-26

New mechanism for setting up motion detection zones. **New detection zones need to be configured on all cameras after
updating!**

# 2024-10-12

YAML configs support for both (client and server) is now deprecated and will be removed soon

# 2024-08-30

asterisk config's tree moved from /opt/rbt/install/asterisk to /opt/rbt/asterisk
