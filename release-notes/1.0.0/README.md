# 🚀 SmartYard-Server 1.0.0

[English](README.md) | [Русский](README.ru.md)

This release significantly expands hardware support and monitoring across Akuvox, BasIP, IS Sokol Plus, Rubetek and
Ufanet devices. It also adds bulk RFID management, configurable CCTV and entrance layouts, new mobile API capabilities,
Sesame DVR support, and faster snapshots and door opening.

## ✨ Features

- Support for Akuvox S532, BasIP AA-07BD, AA-12FB and AA-14FB intercoms.
- Support for Ufanet Secret Mini M2, Secret Solo and Secret Mole devices.
- Support for Rubetek RV-3437. The integration is currently marked as beta.
- RODOS relay support was expanded across the full device lineup, including models with up to 16 channels.
- Zabbix monitoring for Ufanet Secret Mini, Secret Solo, Akuvox R20A and Akuvox S532.
- New IS Sokol Plus implementations for current firmware; previous implementations remain available as legacy models.
- Added Sesame DVR media server type.
- Bulk RFID key adding through the web interface and API.
- Server-configurable entrance display modes in the mobile app.
- CCTV tree group display modes and manual camera ordering in `allTree`.
- Mobile inbox API method for sending messages by subscriber mobile number.
- House-specific service lists in mobile `getServices`, sourced from custom fields.
- Mobile event tracking and notifications for openings from the app, access codes and license plate recognition.
- Mobile FRS response now includes `faceId` when marking a face as recognized.
- Subscriber-defined face groups and optional face clustering with FALPRS 1.1.0 or later.
- Configurable Stories support in the mobile API.
- Added API methods for importing addresses and synchronizing subscriber contracts, blocking state, phone numbers and
  custom fields from external billing systems.
- Camera form extension hooks and custom submodule localization loading.
- Camera motion and recognition zone editors can now refresh the current snapshot.
- Weekly automatic MongoDB GridFS disk space reclamation.

## 🛠️ Improvements

- The LPRS mode setting is now shown only when an LPRS server is selected.
- Camera stream fields now accept RTSP, HTTP and HTTPS URLs.
- Zabbix device naming was made consistent across intercom templates shared by multiple models.

## ⚡ Performance

- Snapshots and door opening are faster because hardware instances no longer perform redundant availability and system
  information requests before each operation.

## 🐛 Bug Fixes

- Fixed connection and call controls for Asterisk calls from the web interface.
- Clearing `floor` and `openCode` values through `modifyFlat()`.
- RFID normalization for Akuvox E12 and R20A when codes start with zero.
- Output counts for Sokol and Sokol Plus intercoms.
- Autoconfiguration for Brovotech, iFLOW and Omny cameras.
- SIP registration monitoring for IS Sokol Plus (rev.5) and Rubetek intercoms.
- Zabbix 6.x template export compatibility and Zabbix 7.x ICMP trigger matching.
- Address timezone resolution when a house has no settlement assigned but its street does.
- User dropdown menu rendering.

## 🔌 Compatibility

- IS Sokol Plus (rev.5) integration now targets firmware `2.5.0.15.30`.
- Rubetek RV-3434 integration now targets firmware `2026.05` while retaining compatibility with supported older
  firmware versions starting from `2025.04`.

## ⚠️ Upgrade Notes

### Zabbix templates

Monitoring templates have changed significantly. After updating SmartYard, if you use Zabbix, update the `monitoring`
backend configuration according to the [documentation](../../install/97.zabbix.md#configuration-smartyard-server), then
re-import the templates:

```bash
php /opt/rbt/server/cli.php --init-monitoring-config
```

### MongoDB GridFS maintenance

Install the weekly cron entry:

```bash
php /opt/rbt/server/cli.php --update-crontabs
```

To enable automatic GridFS disk space reclamation, configure the files backend:

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

If you use TT, create the recommended GridFS metadata index for efficient loading of workflows, filters, viewers and
print templates:

```bash
php /opt/rbt/server/cli.php files --create-index=metadata.type,filename
```

### IS Sokol Plus (rev.5)

[Firmware `2.5.0.15.30`](https://doc.is74.ru/books/umnyi-domofon-sokol-plius/page/cto-novogo-firmware) is recommended.
Firmware `2.5.0.14.13` remains compatible, but updating is strongly recommended. Existing intercom and camera models
are now marked as legacy and renamed to `IS SOKOL PLUS LEGACY (rev.5)` and `IS SOKOL LEGACY`.

After switching to the current models and running autoconfiguration, verify that matrix and CMS settings were migrated
correctly. The legacy integration is no longer maintained and is scheduled for removal in a future major release.

### Rubetek RV-3434

[Firmware `2026.05`](https://support.rubetek.com/ru/access-control-system/firmwares/) is recommended. Firmware versions
from `2025.04` up to `2026.05` remain supported as legacy versions. Earlier firmware is no longer supported;
update such devices to `2025.04` before updating SmartYard, and then update the device to firmware `2026.05`.

Rubetek intercom autoconfiguration now uses UDP instead of TCP for SIP transport.

### Configuration compatibility

Configuration keys `2faName` and `2faTitle` were renamed to `two_fa_name` and `two_fa_title`.
