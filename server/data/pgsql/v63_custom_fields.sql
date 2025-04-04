CREATE TABLE IF NOT EXISTS custom_fields
(
    custom_field_id SERIAL PRIMARY KEY,
    apply_to CHARACTER VARYING,
    catalog CHARACTER VARYING,
    type CHARACTER VARYING,
    field CHARACTER VARYING,
    field_display CHARACTER VARYING,
    field_description CHARACTER VARYING,
    regex CHARACTER VARYING,
    link CHARACTER VARYING,
    format CHARACTER VARYING,
    editor CHARACTER VARYING,
    indx INTEGER,
    search INTEGER,
    required INTEGER
);
CREATE UNIQUE INDEX IF NOT EXISTS custom_fields_name ON custom_fields(field);

CREATE TABLE IF NOT EXISTS custom_fields_options
(
    custom_field_option_id SERIAL PRIMARY KEY,
    custom_field_id INTEGER,
    option CHARACTER VARYING,
    display_order INTEGER,
    option_display CHARACTER VARYING
);
CREATE UNIQUE INDEX IF NOT EXISTS custom_fields_options_uniq ON custom_fields_options(custom_field_option_id, option);
