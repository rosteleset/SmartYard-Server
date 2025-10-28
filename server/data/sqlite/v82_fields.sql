ALTER TABLE tt_projects_custom_fields ADD COLUMN childrens INTEGER DEFAULT -1;
ALTER TABLE tt_projects_custom_fields ADD COLUMN links INTEGER DEFAULT -1;

CREATE TABLE tt_projects_fields_settings
(
    tt_projects_field TEXT PRIMARY KEY,
    childrens  DEFAULT -1,
    links INTEGER DEFAULT -1
);
