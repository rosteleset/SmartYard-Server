CREATE INDEX IF NOT EXISTS custom_fields_apply_to ON custom_fields(apply_to);
CREATE INDEX IF NOT EXISTS custom_fields_options_custom_field_id ON custom_fields_options(custom_field_id);

DROP TABLE IF EXISTS common_custom_fileds_options;
DROP TABLE IF EXISTS common_custom_fields_options;
DROP TABLE IF EXISTS custom_fileds_values;
DROP TABLE IF EXISTS custom_fields_values;

CREATE TABLE IF NOT EXISTS custom_fields_values
(
    custom_fields_value_id SERIAL PRIMARY KEY,
    apply_to CHARACTER VARYING,
    id INTEGER,
    field CHARACTER VARYING,
    value CHARACTER VARYING
);

CREATE INDEX IF NOT EXISTS custom_fields_values_apply_to ON custom_fields_values(apply_to);
CREATE INDEX IF NOT EXISTS custom_fields_values_id ON custom_fields_values(id);
CREATE INDEX IF NOT EXISTS custom_fields_values_field ON custom_fields_values(field);
CREATE INDEX IF NOT EXISTS custom_fields_values_value ON custom_fields_values(value);
CREATE UNIQUE INDEX IF NOT EXISTS custom_fields_values_uniq ON custom_fields_values(apply_to, id, field);
