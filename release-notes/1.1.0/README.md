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

Previously, the presence of the `backends.tmpfs` section implicitly routed all files with an expiration time to
`tmpfs`, including event snapshots and DVR exports. In the default configuration for new installations, the content
of these files was stored in `/tmp/tmpfs`. In addition to deletion based on `metadata.expire`, the daily cleanup of the
`tmpfs` backend deleted physical files older than three days according to `tmpfs.ttl_max`. Their content could
therefore become unavailable before the configured retention period for snapshots and DVR exports had elapsed.

Storage for new files is now selected explicitly and independently for event snapshots and DVR exports:

- `backends.plog.camshot_storage` selects storage for event snapshots
- `backends.dvrExports.storage` selects storage for DVR exports

The scenarios below use the `gridfs` and `tmpfs` values for these options. If an option is omitted, `gridfs` is used.

> [!WARNING]
> The SmartYard `tmpfs` backend is not the Linux filesystem of the same name. It writes files to a regular directory
> specified by `backends.tmpfs.path` and neither creates nor mounts a `tmpfs` filesystem.

Before upgrading, choose the applicable scenario and make it explicit in `config.json`.

#### The `backends.tmpfs` section is not present in the server configuration

Behavior does not change. New files continue to be stored in MongoDB GridFS. However, explicitly specifying the
storage is recommended so the configuration does not depend on the default:

```json5
{
    "backends": {
        "plog": {
            "camshot_storage": "gridfs"
        },
        "dvrExports": {
            "storage": "gridfs"
        }
    }
}
```

#### The `backends.tmpfs` section is present in the server configuration, but new files must be stored in GridFS

To store new files in MongoDB GridFS, set both options to `gridfs`, as shown above. The existing `backends.tmpfs`
section can remain in the configuration: by itself, it no longer affects storage selection for new files. The content
of legacy files remains in the directory used by the `tmpfs` file backend, and the metadata in MongoDB allows
SmartYard to determine the previous storage location. If the section must be removed, first wait until regular cleanup
has deleted all legacy files. Without this section, their content becomes unavailable and expired records cannot be
fully deleted. Existing files are not migrated to GridFS automatically.

#### New files must be stored in the `tmpfs` file backend

Keep or add the `backends.tmpfs` section and explicitly route the required file types to it. This setting affects only
new files. Existing files are not migrated automatically: files in GridFS remain in GridFS, while files in the `tmpfs`
file backend remain in their existing directory. They stay available until they are deleted according to their
`metadata.expire` values.

```json5
{
    "backends": {
        "plog": {
            "camshot_storage": "tmpfs"
        },
        "dvrExports": {
            "storage": "tmpfs"
        },
        "tmpfs": {
            "backend": "internal",
            "path": "/path/to/storage",
            "path_rights": "777",
            "file_rights": "777"
        }
    }
}
```

> [!WARNING]
> `/path/to/storage` in the example is a placeholder. If the `backends.tmpfs` section already exists, do not change
> `backends.tmpfs.path` while files remain in the previous directory. To change the path, first move all content from
> the previous directory to the new one while preserving the subdirectory structure.

Event snapshots and DVR exports can use different storage backends. For example, snapshots can remain in `tmpfs`
while DVR exports are routed to `gridfs`.

The `tmpfs.ttl_max` option is no longer used and is ignored if it remains in the configuration. Automatic deletion is
controlled by each file's `metadata.expire` value.

For event snapshots, `metadata.expire` is calculated from `backends.plog.ttl_camshot_days` in days. DVR exports use
`backends.dvrExports.dvr_files_ttl` in seconds. The default is `259200` seconds (3 days). Files without
`metadata.expire` are not deleted automatically.
