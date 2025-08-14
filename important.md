# 2025-08-14

Do

```bash
rm /opt/rbt/version
rm /opt/rbt/client/version.app
```

ONCE before updating to versions 0.0.11+ (to avoid git conflicts),
and
```
php /opt/rbt/server/cli.php --init-db
ln -sf /opt/rbt/version /opt/rbt/client/version.app
```
ONCE after updating

# 2025-04-11

You will need to update mongodb php library and driver

```bash
find /usr/lib/php/ | grep mongodb.so | xargs rm
pecl install -f mongodb
cd /opt/rbt/server
composer update
```

# 2025-03-20

The deprecated syslog service has been removed from the project. You should now use
the [event service](install/11.event.md).

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
