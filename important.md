# 2026-05-15

The integration for IS Sokol Plus (rev.5) has been updated. Existing intercom and camera models are now marked
as legacy and renamed to `IS SOKOL PLUS LEGACY (rev.5)` and `IS SOKOL LEGACY` respectively.

Please update your devices to firmware version
[2.5.0.14.13](https://doc.is74.ru/books/umnyi-domofon-sokol-plius/page/cto-novogo-firmware)
and switch the intercom to `IS SOKOL PLUS (rev.5)` and the camera to `IS SOKOL`.

> [!WARNING]
> Matrix and coordinate-matrix switch (CMS) configuration has been significantly changed in the new integration version.
> To avoid issues, it is recommended to check that the existing matrix and CMS settings were migrated correctly
> after changing the intercom model and running autoconfiguration.

⚠️ The old integration version is no longer maintained and will be removed in future releases.

# 2026-04-21

Update crontab entries to add the weekly schedule:

```bash
php /opt/rbt/server/cli.php --update-crontabs
```

You should see a message like:

```text
7 crontabs lines removed and 8 crontabs lines added
```

Then add the recommended `autocompact` value for the `files` backend in `/opt/rbt/server/config/config.json`:

```json
{
    "backends": {
        "files": {
            "backend": "mongo",
            "autocompact": "weekly"
        }
    }
}
```

This enables automatic weekly disk space reclamation in MongoDB after file deletions and helps reduce long-term GridFS fragmentation.

# 2026-01-05

Since version 0.0.19e, backend tt type "mongo" renamed to "internal" (need modify server/config/config.json)

# 2025-11-08

Since version 0.0.18c, rbt/install/systemd/mongodb.service renamed to mongod.service (check your /etc/systemd/system folder)

# 2025-10-31

Since version 0.0.17, backend dvr_export renamed to dvrExports (need modify server/config/config.json)

Since version 0.0.17, backend issue_adapter renamed to issueAdapter (need modify server/config/config.json)

# 2025-09-03

Since version 0.0.15, server configs "max_allowed_tokens" and "token_idle_ttl" moved from "redis" section to "backends->authentication" section

# 2025-08-14

Do

```bash
rm /opt/rbt/version
rm /opt/rbt/client/version.app
```

ONCE **before** updating to versions 0.0.11+ (to avoid git conflicts),
and
```
php /opt/rbt/server/cli.php --init-db
ln -sf /opt/rbt/version /opt/rbt/client/version.app
```
ONCE **after** updating

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
