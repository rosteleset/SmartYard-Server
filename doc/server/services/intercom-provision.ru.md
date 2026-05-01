# `server/services/intercom_provision` — черновик SIP provision

**Статус:** черновик / прототип.

Сервер выдачи конфигурации для клиентского SIP-домофона (в описании — Akuvox indoor): при старте оборудование запрашивает конфиг по URL вида:

`http://rbt-example.com:9992/provision/{{SERIAL_NUMBER}}.cfg`

## TODO (из наследованных заметок)

- Реализовать методы API в SmartYard-Server:

  `http://${API_ADDRESS}/internal/intercom/${serial}`

- Добавить таблицу регистрации SIP-домофонов у клиента, например `house_flats_intercoms`:

  `id`, `flat_id`, `intercom_id AS serial`, …

Код: [`server/services/intercom_provision/`](../../../server/services/intercom_provision/).
