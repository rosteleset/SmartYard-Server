CREATE INDEX IF NOT EXISTS custom_fields_apply_to ON custom_fields(apply_to);
CREATE INDEX IF NOT EXISTS common_custom_fileds_options_custom_field_id ON common_custom_fileds_options(custom_field_id);
CREATE INDEX IF NOT EXISTS custom_fileds_values_apply_to ON custom_fileds_values(apply_to);
CREATE INDEX IF NOT EXISTS custom_fileds_values_value ON custom_fileds_values(value);
