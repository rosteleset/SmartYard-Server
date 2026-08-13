# 🚀 SmartYard-Server 1.1.0

[English](README.md) | [Русский](README.ru.md)

This release includes OMNY VDP-10L support, enhanced bulk RFID import capabilities, and camera group visibility
controls for individual flats. It also introduces new mobile API extension hooks, the ability to specify whether a
newly added subscriber is a flat owner, and explicit storage configuration for event snapshots and exported video
clips.

## ✨ Features

- Added OMNY VDP-10L intercom support.
- Bulk RFID import now supports DEC/HEX conversion, byte-order controls and result preview.
- Added bulk RFID key assignment to flats from the house view.
- Added the ability to specify which flats can see each camera group.
- When adding a subscriber through the web interface or API, you can now specify whether the subscriber is a flat owner.
- Added extension hooks for customizing mobile API behavior and responses without replacing the standard handlers.

## 🛠️ Improvements

- Expanded diagnostic message filtering for Akuvox-compatible intercoms in the event service.
- Added explicit `gridfs` or `tmpfs` storage selection for event snapshots and DVR exports.

## ⚡ Performance

- Reduced redundant database writes during mobile authorization by throttling device metadata updates.

## 🐛 Bug Fixes

- Fixed temporary file cleanup when DVR exports or event snapshot storage fails.
- Autoconfiguration now enables outgoing call cancellation with hardware buttons on Akuvox-compatible intercoms.
- Limited short apartment number handling in Asterisk to the legacy Sokol models for which it was originally intended
  (`IS SOKOL (rev.2)` and `IS SOKOL PLUS LEGACY (rev.5)`).

## 🔌 Compatibility

- MongoDB GridFS is now the default storage for new event snapshots and DVR exports.
- The `tmpfs.ttl_max` option was removed. File expiration is now controlled by `metadata.expire`.
- Obsolete SQLite support was removed. PostgreSQL is now the only supported primary relational database.
- Signatures of several abstract `households` backend methods used by custom backends have changed.

## 📚 Documentation

- Updated [syslog setup](../../install/11.event.md#akuvox-omny) for Akuvox-compatible intercoms and OMNY VDP-10L,
  including UDP port 514 redirection.
- The [MongoDB guide](../../install/06.mongo.md#fix-for-linux-kernel-619-incompatibility) now documents a workaround
  for running MongoDB 8.0+ on Linux kernel version 6.19
  ([Issue description](https://jira.mongodb.org/browse/SERVER-121912)).

## ⚠️ Upgrade Notes

### Custom households backends

The `households::addSubscriber()` method now accepts an optional seventh `$owner` argument:

```php
addSubscriber($mobile, $name, $patronymic, $last, $flatId, $message, $owner = false)
```

Custom `households` backends that override this method must update its signature. Backends that inherit the
implementation from `households/internal` require no changes.

The `households` path tree contract has been extended to support camera visibility by flat:

```php
addRootPathNode($tree, $text, $icon, $type = "list", $visibleForFlats = null)
addPathNode($parentId, $text, $icon, $type = "list", $visibleForFlats = null)
modifyPathNode($nodeId, $text, $icon, $type = null, $visibleForFlats = false)
getPathVisibleForFlats($nodeId)
```

Backends that directly extend the abstract `households` backend must update the three method signatures and implement
`getPathVisibleForFlats()` or they will fail to load. Backends based on `households/internal` inherit the new
implementation and require no changes.

### File storage

New event snapshots and DVR exports are now stored in MongoDB GridFS regardless of whether the `tmpfs` backend is
configured. To keep storing new files in `tmpfs` as before, add these options to `config.json` **before updating
SmartYard**. Otherwise, files created after the update and before the configuration change will be stored in GridFS:

```json5
{
    "backends": {
        "plog": {
            "camshot_storage": "tmpfs"
        },
        "dvrExports": {
            "storage": "tmpfs"
        }
    }
}
```

Existing files are not migrated automatically. Do not remove the `backends.tmpfs` section from the configuration.
Otherwise, the content of legacy files becomes unavailable. The `tmpfs.ttl_max` option is no longer used. Expiration
is controlled by each file's `metadata.expire` value.

For event snapshots, `metadata.expire` is calculated from `backends.plog.ttl_camshot_days` in days. DVR exports use
`backends.dvrExports.dvr_files_ttl` in seconds. The default is `259200` seconds (3 days). Files without
`metadata.expire` are not deleted automatically.
