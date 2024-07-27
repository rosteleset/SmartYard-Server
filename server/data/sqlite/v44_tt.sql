CREATE TABLE tt_projects_custom_fields_nojournal
(
    project_custom_field_id INTEGER PRIMARY KEY AUTOINCREMENT,
    project_id INTEGER,
    issue_custom_field_id INTEGER
);
CREATE UNIQUE INDEX tt_projects_custom_fields_nojournal_uniq ON tt_projects_custom_fields (project_id, issue_custom_field_id);
