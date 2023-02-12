-- projects
CREATE TABLE tt_projects
(
    project_id serial primary key,
    acronym character varying not null,
    project character varying not null,
    max_file_size integer,
    search_subject integer default 1,
    search_description integer default 1,
    search_comments integer default 1
);
CREATE UNIQUE INDEX tt_projects_acronym on tt_projects(acronym);
CREATE UNIQUE INDEX tt_projects_name on tt_projects(project);

-- projects <-> workflows
CREATE TABLE tt_projects_workflows
(
    project_workflow_id serial primary key,
    project_id integer,
    workflow character varying
);
CREATE UNIQUE INDEX tt_projects_workflows_uniq on tt_projects_workflows (project_id, workflow);

-- projects <-> filters
CREATE TABLE tt_projects_filters
(
    project_filter_id serial primary key,
    project_id integer,
    filter character varying
);
CREATE UNIQUE INDEX tt_projects_filters_uniq on tt_projects_filters (project_id, filter);

-- issue statuses
CREATE TABLE tt_issue_statuses                                                                                          -- !!! managed by workflows !!!
(
    issue_status_id serial primary key,
    status character varying not null,                                                                                  -- internal (workflow)
    status_display character varying not null                                                                           -- human readable value
);
CREATE UNIQUE INDEX tt_issue_stauses_uniq on tt_issue_statuses(status);
INSERT INTO tt_issue_statuses (status, status_display) values ('opened', '');
INSERT INTO tt_issue_statuses (status, status_display) values ('closed', '');

-- issues resolutions
CREATE TABLE tt_issue_resolutions
(
    issue_resolution_id serial primary key,
    resolution character varying,
    alias character varying,
    protected integer default 0
);
CREATE UNIQUE INDEX tt_issue_resolutions_uniq1 on tt_issue_resolutions(resolution);
CREATE UNIQUE INDEX tt_issue_resolutions_uniq2 on tt_issue_resolutions(alias);
INSERT INTO tt_issue_resolutions (resolution, alias, protected) values ('fixed', 'fixed', 1);
INSERT INTO tt_issue_resolutions (resolution, alias, protected) values ('can''t fix', 'can''t fix', 1);
INSERT INTO tt_issue_resolutions (resolution, alias, protected) values ('duplicate', 'duplicate', 1);

-- projects <-> resolutions
CREATE TABLE tt_projects_resolutions
(
    project_resolution_id serial primary key,
    project_id integer,
    issue_resolution_id integer
);
CREATE UNIQUE INDEX tt_projects_resolutions_uniq on tt_projects_resolutions(project_id, issue_resolution_id);

-- custom fields
CREATE TABLE tt_issue_custom_fields
(
    issue_custom_field_id serial primary key,
    type character varying not null,
    workflow integer,                                                                                                   -- managed by workflow, only field_display can be edited, can't be removed by user
    field character varying not null,
    field_display character varying not null,
    field_description character varying,
    regex character varying,
    link character varying,
    format character varying,
    editor character varying,
    indx integer,
    search integer,
    required integer
);
CREATE UNIQUE INDEX tt_issue_custom_fields_name on tt_issue_custom_fields(field);

-- projects <-> custom fields
CREATE TABLE tt_projects_custom_fields
(
    project_custom_field_id serial primary key,
    project_id integer,
    issue_custom_field_id integer,
    workflow integer                                                                                                    -- managed by workflow, can't be removed by user
);
CREATE UNIQUE INDEX tt_projects_custom_fields_uniq on tt_projects_custom_fields (project_id, issue_custom_field_id);

-- custom fields values options
CREATE TABLE tt_issue_custom_fields_options
(
    issue_custom_field_option_id serial primary key,
    issue_custom_field_id integer,
    option character varying not null,
    option_display character varying,                                                                                   -- only for workflow's fields
    display_order integer
);
CREATE UNIQUE INDEX tt_issue_custom_fields_options_uniq on tt_issue_custom_fields_options(issue_custom_field_id, option);

-- projects roles types
CREATE TABLE tt_roles
(
    role_id serial primary key,
    name character varying,
    name_display character varying,
    level integer
);
CREATE INDEX tt_roles_level on tt_roles(level);
INSERT INTO tt_roles (level, name) values (-1, 'nobody');                                                               -- not a project member
INSERT INTO tt_roles (level, name) values (10, 'participant.junior');                                                   -- can view only
INSERT INTO tt_roles (level, name) values (20, 'participant.middle');                                                   -- can comment, can edit and delete own comments, can attach files and delete own files
INSERT INTO tt_roles (level, name) values (30, 'participant.senior');                                                   -- can create issues
INSERT INTO tt_roles (level, name) values (40, 'employee.junior');                                                      -- can change status (by workflow, without final)
INSERT INTO tt_roles (level, name) values (50, 'employee.middle');                                                      -- can change status (by workflow)
INSERT INTO tt_roles (level, name) values (60, 'employee.senior');                                                      -- can edit issues
INSERT INTO tt_roles (level, name) values (70, 'manager.junior');                                                       -- can edit all comments and delete comments, can delete files, can create tag
INSERT INTO tt_roles (level, name) values (80, 'manager.middle');                                                       -- can delete issues
INSERT INTO tt_roles (level, name) values (90, 'manager.senior');                                                       -- project owner

-- project rights
CREATE TABLE tt_projects_roles
(
    project_role_id serial primary key,
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
    tag_id serial primary key,
    project_id integer not null,
    tag character varying,
    foreground text,
    background text
);
CREATE UNIQUE INDEX tt_tags_uniq on tt_tags (project_id, tag);

-- crontabs
CREATE TABLE tt_crontabs
(
    crontab_id serial primary key,
    crontab character varying,
    project_id integer,
    filter character varying,
    uid integer,
    action character varying
);
CREATE UNIQUE INDEX tt_crontabs_uniq on tt_crontabs(project_id, filter, uid, action);
CREATE INDEX tt_crontabs_crontab on tt_crontabs(crontab);

-- viewers
CREATE TABLE tt_viewers
(
    field character varying not null,
    name character varying not null,
    code character varying
);
CREATE UNIQUE INDEX tt_viewers_uniq on tt_viewers (field, name);

-- projects viewers
CREATE TABLE tt_projects_viewers
(
    project_view_id serial primary key,
    project_id integer,
    field character varying,
    name character varying
);
CREATE UNIQUE INDEX tt_projects_viewers_uniq on tt_projects_viewers(project_id, field);