# 🚀 SmartYard-Server 1.3.0

[English](README.md) | [Русский](README.ru.md)

> [!WARNING]
> Critical PHP vulnerabilities CVE-2026-109618 and CVE-2026-109619 allow remote code execution,
> including deployments using PHP-FPM. Install PHP security updates urgently.
>
> For PHP installed from the official Ubuntu repositories, packages with backported fixes are available:
>
> - Ubuntu 22.04 LTS, PHP 8.1: `8.1.2-1ubuntu2.28` or later.
> - Ubuntu 24.04 LTS, PHP 8.3: `8.3.6-0ubuntu0.24.04.13` or later.
>
> **Updating SmartYard alone does not fix these vulnerabilities.**
> [Official PHP security advisory](https://www.php.net/archive/2026.php#2026-10-21-5) and
> [Ubuntu security notice USN-8948-1](https://ubuntu.com/security/notices/USN-8948-1).

This release introduces a virtual intercom for browser calls via a link or QR code and expands bulk text messaging
to the mobile app with address-based recipient selection. It also adds beta support for the Rubetek RACS-1101
controller and Dahua cameras, license plate validity start dates and configurable SIP over TLS for mobile calls.
Call pushes are now sent independently to each device, and device autoconfiguration no longer delays other
minutely cron tasks.

## ✨ Features

- Added a virtual intercom: visitors can call subscribers from a browser via a link or QR code.
  It supports video calls, door opening by the subscriber and opening with the flat's personal code. The panel
  is linked to an existing entrance; its title, apartment list visibility and call permissions are managed
  in the web UI.
- Added beta support for the Rubetek RACS-1101 access controller; device log processing will be added in future
  releases.
- Added beta support for Dahua cameras, including a dedicated motion event service for FRS/LPRS.
- Expanded bulk text messaging to the mobile app: recipients can be selected by house, street, city or settlement,
  area, region or all addresses. Messages are saved in the app's inbox and accompanied by push notifications.
  The recipient count is shown before submission. Delivery is queued and continues after the page is closed.
  Added `GET/POST /api/inbox/broadcast` for integrations.
- The mobile `getSettingsList` response now includes the available service catalog, the flat's active services
  and contract owner status. The catalog and default services can be configured for the installation
  and individual houses.
- Added configurable SIP over TLS for mobile calls.
- Added license plate validity start dates and times in the web UI and mobile API.
- Added Belarusian and Armenian license plate format validation to the mobile API.
- Mobile options now return supported license plate formats (`supportedPlateNumberFormats`) and the configured
  LPRS API version (`lprsVersion`). Added the standalone `POST /mobile/lprs/listSupportedPlateNumberFormats` method.
- Added the `watcherId` tracking identifier to the `listNumbersV2` response.

## 🛠️ Improvements

- FALPRS and media-server snapshots in the web event journal now have different border colors and source tooltips.
- Added the number of flats with SIP intercoms to statistics. Removed the "Other" mobile devices counter.
- Increased scrollbar width in the web UI.

## ⚡ Performance

- Device autoconfiguration no longer blocks minutely cron. The maximum number of concurrent jobs and the maximum
  duration of each job can be changed in the server configuration.
- Initial incoming-call push delivery is faster: requests for different mobile devices now run in parallel.
  A slow push service response for one device no longer delays sending to the others.
- Optimized house and entrance event journal queries for different ClickHouse JSON formats.
  Increased the ClickHouse query timeout from 5 to 30 seconds.

## 🐛 Bug Fixes

- Fixed short RFID codes uploaded to Akuvox-compatible intercoms by padding them to at least four bytes.
- Fixed the permissions page failing to load when a user's rights reference methods hidden from the editor.
- Fixed mobile face clustering availability checks and FRS server selection in mixed FRS/LPRS configurations.
- Prevented unintended repeat calls to the mobile app within a single call from the panel.
- Fixed house and entrance event journal errors across different ClickHouse JSON formats.
- Fixed ClickHouse SELECT queries using the default database instead of the configured database.
- Fixed MongoDB file indexes being created with duplicated `metadata.metadata.*` prefixes.
- Fixed statistics excluding mobile devices whose subscribers have no flat link.
- Fixed cameras from nested groups not appearing on the mobile map. A group in "Map" mode is a terminal view:
  it does not provide navigation into child groups. The map now shows all cameras accessible to the subscriber
  in that group and all its descendants, regardless of their display mode.
- Fixed camera group lists failing to load when empty groups are present.
- License plate opening now checks the validity period when processing the event, without waiting for periodic
  expired-link cleanup.
- Fixed push service requests hanging on APNs, Huawei and RuStore transport errors.
- Standardized license plate validity dates in `listNumbersV2` to ISO 8601 UTC.
- Fixed premature loading indicator dismissal when opening the web event journal.
- Fixed failed ClickHouse inserts being reported as successful.

## 🔒 Security

- Prevented command injection in FFmpeg when extracting DVR event snapshots.
  Snapshot URLs are now restricted to HTTP and HTTPS.

## 🔌 Compatibility

- Promoted Rubetek RV-3437 integration from beta to stable for firmware `2026.09.210465341`:
  the device firmware now supports snapshot retrieval.
- Extended custom `frs` and `households` backend contracts and changed the argument order of `addFlatPlateNumber()`.
  See Upgrade Notes for details.
- PostgreSQL migrations v99 and v100 register virtual intercom fields and add license plate validity start dates.
  They run during the standard update without a separate command; external integrations that access the database
  directly must be checked for compatibility with these changes.

## ⚠️ Upgrade Notes

### Asterisk

After upgrading, reload the Lua dialplan to apply parallel initial push delivery and prevent repeat calls:

```bash
asterisk -rx "module reload pbx_lua"
```

### Push service

Restart active push service instances to apply the transport-error fixes:

```bash
systemctl try-restart 'push@*.service'
```

If the installation uses the older `rbt-push.service` unit, run this instead:

```bash
systemctl try-restart rbt-push.service
```

### MongoDB file indexes

After upgrading, repair previously created indexes:

```bash
php /opt/rbt/server/scripts/fixes/repairMongoFileIndexes.php
```

The script creates and verifies correct indexes, then removes the incorrect ones. Files and other indexes are
untouched; repeated runs are safe. Index creation may temporarily increase database load.

### Short Akuvox RFID codes

Previously uploaded short RFID codes may not be rewritten by regular autoconfiguration because their normalized values
already match the SmartYard database. If affected keys do not work, remove the corresponding old entries through
the intercom's web interface, then run autoconfiguration. Do not delete these keys from the SmartYard database.

### Mobile service catalog

The `mobile.default_services` setting in `/opt/rbt/server/config/config.json` defines the installation's service
catalog. Without it, houses without an explicit `services` custom-field value receive the entire built-in catalog.
To display only the services you provide, configure the catalog using the
[sample configuration](../../server/config/config.sample.json5).

`byDefault` controls whether a service is enabled automatically for flats, and `canChange` allows plan changes.
Both flags use the strings `"t"` or `"f"`. The house's `services` custom field selects services from this catalog;
the flat's field of the same name defines its active services.

### Autoconfiguration queue

Optional `backends.queue` settings override the limits:

```json
{
    "backends": {
        "queue": {
            "backend": "internal",
            "max_concurrent_tasks": 25,
            "task_timeout": 3600
        }
    }
}
```

`max_concurrent_tasks` limits concurrent camera and intercom autoconfiguration jobs in total.
`task_timeout` is the maximum duration of each job in seconds. Existing configurations use the defaults shown above.

### Virtual intercom

The feature requires HTTPS and working browser SIP settings in `/opt/rbt/client/config/config.json`:
`asterisk.ws`, `asterisk.sipDomain` and, if needed, `asterisk.ice`. Enable the Nginx configuration and reload
the Asterisk door-opening handler:

```bash
mkdir -p /etc/nginx/rbt.d
ln -s /opt/rbt/install/nginx/virtual-intercom.conf /etc/nginx/rbt.d/virtual-intercom.conf
nginx -t && systemctl reload nginx
asterisk -rx "module reload features"
```

Then configure the virtual intercom in the house's entrance list. The entrance must be linked to an enabled physical
intercom and its relay output. To allow calls only to selected flats, disable "Allow calls to all entrance subscribers"
in the virtual intercom settings and enable "Allow calls from virtual intercoms" in the settings of those flats.

### SIP over TLS

Enable TLS in the relevant `backends.sip.servers[]` entry using `sip_mobile_transport: "tls"` and `sip_tls_port`.
Existing TCP remains the default. Before enabling TLS, configure the SIP server's TLS transport and a trusted
certificate; the mobile client must also support TLS. For iOS with TURN, verify TURN over TLS support.
See the [setup guide](../../doc/server/backends/sip/README.md).

### Dahua motion events

Assign an FRS/LPRS server to the Dahua camera, add the `hw.dahua` section from
`/opt/rbt/server/services/event/config.sample.json` to the event service configuration and start `event_dahua.service`
following the [event service installation guide](../../install/11.event.md).

### Custom FRS backends

Backends that directly extend the abstract `frs` backend must implement:

```php
getAdditionalFeaturesFrs(): array
getSupportedPlateNumberFormats(): array
```

Backends that inherit these methods from `frs/internal` require no changes.

### Custom households backends

Backends that directly extend the abstract `households` backend must implement these new methods:

```php
getHouseServices($houseId): ?array
getFlatServices($flatId): ?array
getAddressBroadcastRecipientCount($by, $query)
queueAddressBroadcast($by, $query, $title, $msg, $action = "inbox")
updatePlateValidFrom($number, $validFrom = null, $countryCode = 'ru')
updateFlatPlateValidFrom($flatId, $number, $validFrom = null, $countryCode = 'ru')
```

The license plate adding method now takes `validFrom` before `validTo`:

```php
addFlatPlateNumber($flatId, $number, $validFrom = null, $validTo = null, $countryCode = 'ru')
```

Backends based on `households/internal` inherit the new methods automatically; changes are only required when
overriding `addFlatPlateNumber()` or calling it with the old argument order.
