ALTER TABLE custom_fields ADD IF NOT EXISTS magic_icon CHARACTER VARYING;
ALTER TABLE custom_fields ADD IF NOT EXISTS magic_function CHARACTER VARYING;

CREATE TABLE IF NOT EXISTS custom_fields_values
(
    custom_fields_value_id SERIAL PRIMARY KEY,
    apply_to INTEGER,
    value CHARACTER VARYING
);
