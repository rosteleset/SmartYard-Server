CREATE TABLE tt_prints (
    tt_print_id INTEGER PRIMARY KEY AUTOINCREMENT,
    form_name TEXT NOT NULL,
    extension TEXT NOT NULL,
    description TEXT NOT NULL
);

CREATE UNIQUE INDEX tt_prints_uniq ON tt_prints (form_name);