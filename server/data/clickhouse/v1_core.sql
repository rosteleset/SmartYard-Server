CREATE TABLE IF NOT EXISTS core_vars
(
    `var_name`  String,
    `var_value` String
) ENGINE = ReplacingMergeTree ORDER BY var_name;
