CREATE TABLE custom_fields
(
    custom_field_id INTEGER PRIMARY KEY AUTOINCREMENT,
    apply_to TEXT,
    catalog TEXT,
    type TEXT,
    field TEXT,
    field_display TEXT,
    field_description TEXT,
    regex TEXT,
    link TEXT,
    format TEXT,
    editor TEXT,
    indx INTEGER,
    search INTEGER,
    required INTEGER
);
CREATE UNIQUE INDEX custom_fields_name on custom_fields(field);

CREATE TABLE custom_fields_options
(
    field_option_id INTEGER PRIMARY KEY AUTOINCREMENT,
    custom_field_id INTEGER,
    option TEXT,
    display_order INTEGER,
    option_display TEXT
);
CREATE UNIQUE INDEX custom_fields_options_uniq on custom_fields_options(custom_field_option_id, option);
