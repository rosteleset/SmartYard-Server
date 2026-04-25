## Documentation rules (`doc/`)

- **Bilingual docs**: English is the default (no suffix). Russian translation uses `.ru` in the filename.
  - Example: `doc/server/utils/PDOExt.md` and `doc/server/utils/PDOExt.ru.md`.

- **Mirror the code structure**: documentation paths under `doc/` should reflect the source paths.
  - If a file lives at `server/utils/PDOExt.php`, its doc should live at `doc/server/utils/PDOExt.md` (and `doc/server/utils/PDOExt.ru.md` if translated).

