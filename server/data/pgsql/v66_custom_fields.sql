ALTER TABLE custom_fields ADD IF NOT EXISTS magic_icon CHARACTER VARYING;
ALTER TABLE custom_fields ADD IF NOT EXISTS magic_function CHARACTER VARYING;

CREATE TABLE IF NOT EXISTS custom_fileds_values
(
    custom_fileds_value_id SERIAL PRIMARY KEY,
    apply_to INTEGER,
    value CHARACTER VARYING
);
