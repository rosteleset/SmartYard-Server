# Backend: Authorization (`server/backends/authorization/`)

Authorization backend controls whether a request is allowed and provides rights/methods introspection.

## Index

- [`authorization.php`](./authorization.md) — base backend class (`backends\authorization\authorization`)
- Variants:
  - [`allow`](./allow.md) — allow-all implementation (read-only capabilities)
  - [`internal`](./internal.md) — DB-based permissions implementation (read-write capabilities)

