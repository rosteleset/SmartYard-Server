ALTER TABLE tt_projects_custom_fields ADD IF NOT EXISTS childrens INTEGER DEFAULT -1;
ALTER TABLE tt_projects_custom_fields ADD IF NOT EXISTS links INTEGER DEFAULT -1;

CREATE TABLE tt_projects_fields_settings
(
    tt_projects_field CHARACTER VARYING PRIMARY KEY,
    childrens INTEGER DEFAULT -1,
    links INTEGER DEFAULT -1
);
