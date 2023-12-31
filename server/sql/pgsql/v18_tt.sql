CREATE TABLE tt_prints (
    tt_print_id SERIAL PRIMARY KEY,
    form_name CHARACTER VARYING NOT NULL,
    extension CHARACTER VARYING NOT NULL,
    description CHARACTER VARYING NOT NULL
);

CREATE UNIQUE INDEX tt_prings_uniq ON tt_prints (form_name, extension);