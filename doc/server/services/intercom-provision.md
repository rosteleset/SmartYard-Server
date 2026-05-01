# `server/services/intercom_provision` — SIP provision draft

**Status:** draft / prototype.

Provision server for client SIP intercoms (described for Akuvox indoor units): on boot the device pulls config from:

`http://rbt-example.com:9992/provision/{{SERIAL_NUMBER}}.cfg`

## TODO (from legacy notes)

- Implement API methods in SmartYard-Server:

  `http://${API_ADDRESS}/internal/intercom/${serial}`

- Add a registration table for client SIP intercoms, e.g. `house_flats_intercoms`:

  `id`, `flat_id`, `intercom_id AS serial`, …

Source: [`server/services/intercom_provision/`](../../../server/services/intercom_provision/).
