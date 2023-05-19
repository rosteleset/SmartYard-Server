-- projects
CREATE TABLE tt_projects
(
    project_id integer primary key autoincrement,
    acronym text not null,
    project text not null,
    owner text,
    max_file_size integer default 16777216,
    search_subject integer default 1,
    search_description integer default 1,
    search_comments integer default 1
);
CREATE UNIQUE INDEX tt_projects_acronym on tt_projects(acronym);
CREATE UNIQUE INDEX tt_projects_name on tt_projects(project);

-- projects <-> workflows
CREATE TABLE tt_projects_workflows
(
    project_workflow_id integer primary key autoincrement,
    project_id integer,
    workflow text
);
CREATE UNIQUE INDEX tt_projects_workflows_uniq on tt_projects_workflows (project_id, workflow);

-- projects <-> filters
CREATE TABLE tt_projects_filters
(
    project_filter_id integer primary key autoincrement,
    project_id integer,
    personal integer,
    filter text
);
CREATE UNIQUE INDEX tt_projects_filters_uniq on tt_projects_filters (project_id, filter, personal);

-- issue statuses
CREATE TABLE tt_issue_statuses                                                                                          -- !!! managed by workflows !!!
(
    issue_status_id integer primary key autoincrement,
    status text not null
);
CREATE UNIQUE INDEX tt_issue_stauses_uniq on tt_issue_statuses(status);

-- issues resolutions
CREATE TABLE tt_issue_resolutions
(
    issue_resolution_id integer primary key autoincrement,
    resolution text
);
CREATE UNIQUE INDEX tt_issue_resolutions_uniq on tt_issue_resolutions(resolution);

-- projects <-> resolutions
CREATE TABLE tt_projects_resolutions
(
    project_resolution_id integer primary key autoincrement,
    project_id integer,
    issue_resolution_id integer
);
CREATE UNIQUE INDEX tt_projects_resolutions_uniq on tt_projects_resolutions(project_id, issue_resolution_id);

-- custom fields
CREATE TABLE tt_issue_custom_fields
(
    issue_custom_field_id integer primary key autoincrement,
    catalog text,
    type text not null,
    field text not null,
    field_display text not null,
    field_description text,
    regex text,
    link text,
    format text,
    editor text,
    indx integer,
    search integer,
    required integer
);
CREATE UNIQUE INDEX tt_issue_custom_fields_name on tt_issue_custom_fields(field);

-- projects <-> custom fields
CREATE TABLE tt_projects_custom_fields
(
    project_custom_field_id integer primary key autoincrement,
    project_id integer,
    issue_custom_field_id integer
);
CREATE UNIQUE INDEX tt_projects_custom_fields_uniq on tt_projects_custom_fields (project_id, issue_custom_field_id);

-- custom fields values options
CREATE TABLE tt_issue_custom_fields_options
(
    issue_custom_field_option_id integer primary key autoincrement,
    issue_custom_field_id integer,
    option text not null,
    display_order integer
);
CREATE UNIQUE INDEX tt_issue_custom_fields_options_uniq on tt_issue_custom_fields_options(issue_custom_field_id, option);

-- projects roles types
CREATE TABLE tt_roles
(
    role_id integer primary key autoincrement,
    name text,
    name_display text,
    level integer
);
CREATE INDEX tt_roles_level on tt_roles(level);
INSERT INTO tt_roles (level, name) values (-1, 'nobody');                                                               -- not a project member
INSERT INTO tt_roles (level, name) values (10, 'participant.junior');                                                   -- can view only
INSERT INTO tt_roles (level, name) values (20, 'participant.middle');                                                   -- can comment, can edit and delete own comments, can attach files and delete own files
INSERT INTO tt_roles (level, name) values (30, 'participant.senior');                                                   -- can create issues, can add to watchers
INSERT INTO tt_roles (level, name) values (40, 'employee.junior');                                                      -- can edit issues (by workflow)
INSERT INTO tt_roles (level, name) values (50, 'employee.middle');                                                      -- can assign issue to itself
INSERT INTO tt_roles (level, name) values (60, 'employee.senior');                                                      -- unused
INSERT INTO tt_roles (level, name) values (70, 'manager.junior');                                                       -- can edit all comments and delete comments, can delete files, can create tags
INSERT INTO tt_roles (level, name) values (80, 'manager.middle');                                                       -- can delete issues
INSERT INTO tt_roles (level, name) values (90, 'manager.senior');                                                       -- unused

-- project rights
CREATE TABLE tt_projects_roles
(
    project_role_id integer primary key autoincrement,
    project_id integer not null,
    role_id integer not null,
    uid integer default 0,
    gid integer default 0
);
CREATE UNIQUE INDEX tt_projects_roles_uniq on tt_projects_roles (project_id, role_id, uid, gid);
CREATE INDEX tt_projects_roles_project_id on tt_projects_roles(project_id);
CREATE INDEX tt_projects_roles_role_id on tt_projects_roles(role_id);
CREATE INDEX tt_projects_roles_uid on tt_projects_roles(uid);
CREATE INDEX tt_projects_roles_gid on tt_projects_roles(gid);

-- tags
CREATE TABLE tt_tags
(
    tag_id integer primary key autoincrement,
    project_id integer not null,
    tag text,
    foreground text,
    background text
);
CREATE UNIQUE INDEX tt_tags_uniq on tt_tags (project_id, tag);

-- crontabs
CREATE TABLE tt_crontabs
(
    crontab_id integer primary key autoincrement,
    crontab text,
    project_id integer,
    filter text,
    uid integer,
    action text
);
CREATE UNIQUE INDEX tt_crontabs_uniq on tt_crontabs(project_id, filter, uid, action);
CREATE INDEX tt_crontabs_crontab on tt_crontabs(crontab);

-- projects viewers
CREATE TABLE tt_projects_viewers
(
    project_view_id integer primary key autoincrement,
    project_id integer,
    field text,
    name text
);
CREATE UNIQUE INDEX tt_projects_viewers_uniq on tt_projects_viewers(project_id, field);