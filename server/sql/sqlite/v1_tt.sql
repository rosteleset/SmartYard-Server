-- projects
CREATE TABLE tt_projects
(
    project_id integer not null primary key autoincrement,
    acronym text not null,
    project text not null
);
CREATE UNIQUE INDEX tt_projects_acronym on tt_projects(acronym);
CREATE UNIQUE INDEX tt_projects_name on tt_projects(project);

-- workflows
CREATE TABLE tt_workflows_aliases
(
    workflow text primary key,
    alias text
);

-- projects <-> workflows
CREATE TABLE tt_projects_workflows
(
    project_id integer,
    workflow text
);
CREATE UNIQUE INDEX tt_projects_workflows_uniq on tt_projects_workflows (project_id, workflow);

-- issue statuses
CREATE TABLE tt_issue_statuses                                                                                          -- !!! managed by workflows !!!
(
    status_id integer not null primary key autoincrement,
    status text not null,                                                                                               -- internal (workflow)
    status_display text not null                                                                                        -- human readable value
);
CREATE UNIQUE INDEX tt_issue_stauses_uniq on tt_issue_statuses(status);
INSERT INTO tt_issue_statuses (status, status_display) values ('opened', '');
INSERT INTO tt_issue_statuses (status, status_display) values ('closed', '');

-- issues resolutions
CREATE TABLE tt_issue_resolutions
(
    resolution_id integer not null primary key autoincrement,
    resolution text
);
CREATE UNIQUE INDEX tt_issue_resolutions_uniq on tt_issue_resolutions(resolution);
INSERT INTO tt_issue_resolutions (resolution) values ('fixed');
INSERT INTO tt_issue_resolutions (resolution) values ('can''t fix');
INSERT INTO tt_issue_resolutions (resolution) values ('duplicate');

-- projects <-> resolutions
CREATE TABLE tt_projects_resolutions
(
    project_id integer,
    resolution_id integer
);
CREATE UNIQUE INDEX tt_projects_resolutions_uniq on tt_projects_resolutions(project_id, resolution_id);

-- issues
CREATE TABLE tt_issues
(
    issue_id integer not null primary key autoincrement,                                                                -- primary key
    project_id integer,                                                                                                 -- project_id
    workflow text,                                                                                                      -- workflow
    subject text not null,                                                                                              -- subject
    description text not null,                                                                                          -- description
    author integer,                                                                                                     -- uid
    status_id integer,                                                                                                  -- status
    resolution_id integer,                                                                                              -- resolution
    created text not null,                                                                                              -- "YYYY-MM-DD HH:MM:SS.SSS"
    updated text                                                                                                        -- "YYYY-MM-DD HH:MM:SS.SSS"
);
CREATE INDEX tt_issues_project_id on tt_issues(project_id);
CREATE INDEX tt_issues_workflow on tt_issues(workflow);
CREATE INDEX tt_issues_subject on tt_issues(subject);
CREATE INDEX tt_issues_author on tt_issues(author);
CREATE INDEX tt_issues_status_id on tt_issues(status_id);
CREATE INDEX tt_issues_resolution_id on tt_issues(resolution_id);
CREATE INDEX tt_issues_created on tt_issues(created);
CREATE INDEX tt_issues_updated on tt_issues(updated);
CREATE INDEX tt_issues_closed on tt_issues(closed);
CREATE INDEX tt_issues_external on tt_issues(external_id, external_id_type);

-- assigned(s)
CREATE TABLE tt_issue_assigned
(
    assigned_id integer not null primary key autoincrement,
    issue_id integer,
    uid integer,
    gid integer
);
CREATE UNIQUE INDEX tt_issue_assigned_uniq on tt_issue_assigned(issue_id, uid, gid);
CREATE INDEX tt_issue_assigned_issue_id on tt_issue_assigned(issue_id);
CREATE INDEX tt_issue_assigned_uid on tt_issue_assigned(uid);
CREATE INDEX tt_issue_assigned_gid on tt_issue_assigned(gid);

-- watchers
CREATE TABLE tt_issue_watchers
(
    watcher_id integer not null primary key autoincrement,
    issue_id integer,
    uid integer
);
CREATE UNIQUE INDEX tt_issue_watchers_uniq on tt_issue_watchers (issue_id, uid);
CREATE INDEX tt_issue_watchers_issue_id on tt_issue_watchers(issue_id);
CREATE INDEX tt_issue_watchers_uid on tt_issue_watchers(uid);

-- plans
CREATE TABLE tt_issue_plans
(
    plan_id integer not null primary key autoincrement,
    issue_id integer,
    action text,
    planned text,                                                                                                       -- "YYYY-MM-DD HH:MM:SS.SSS"
    uid integer,
    gid integer
);
CREATE UNIQUE INDEX tt_issue_plans_uniq on tt_issue_plans(issue_id, action);
CREATE INDEX tt_issue_plans_issue_id on tt_issue_plans(issue_id);
CREATE INDEX tt_issue_plans_planned on tt_issue_plans(planned);
CREATE INDEX tt_issue_plans_uid on tt_issue_plans(uid);
CREATE INDEX tt_issue_plans_gid on tt_issue_plans(gid);

-- comments
CREATE TABLE tt_issue_comments
(
    comment_id integer not null primary key autoincrement,
    issue_id integer,                                                                                                   -- issue
    comment text,                                                                                                       -- comment
    role_id integer,                                                                                                    -- permission level
    created text,                                                                                                       -- "YYYY-MM-DD HH:MM:SS.SSS"
    updated text,                                                                                                       -- "YYYY-MM-DD HH:MM:SS.SSS"
    author integer                                                                                                      -- uid
);
CREATE INDEX tt_issue_comments_issue_id on tt_issue_comments(issue_id);

-- attachments
CREATE TABLE tt_issue_attachments
(
    attachment_id integer not null primary key autoincrement,
    issue_id integer,                                                                                                   -- issue
    uuid text,                                                                                                          -- file uuid for attachments backend
    role_id integer,                                                                                                    -- permission level
    created text,                                                                                                       -- "YYYY-MM-DD HH:MM:SS.SSS"
    author integer                                                                                                      -- uid
);
CREATE INDEX tt_issue_attachments_issue_id on tt_issue_attachments(issue_id);

-- checklist
CREATE TABLE tt_issue_checklist
(
    check_id integer not null primary key,
    issue_id integer,
    checkbox text,
    checked integer
);
CREATE UNIQUE INDEX tt_issue_checklist_uniq on tt_issue_checklist(issue_id, checkbox);
CREATE INDEX tt_issue_checklist_issue_id on tt_issue_checklist(issue_id);

-- tags
CREATE TABLE tt_issue_tags
(
    tag_id integer not null primary key autoincrement,
    issue_id integer,
    tag text
);
CREATE UNIQUE INDEX tt_issue_tags_uniq on tt_issue_tags (issue_id, tag);
CREATE INDEX tt_issue_tags_issue_id on tt_issue_tags(issue_id);
CREATE INDEX tt_issue_tags_tag on tt_issue_tags(tag);

-- custom fields
CREATE TABLE tt_issue_custom_fields
(
    custom_field_id integer not null primary key autoincrement,
    type text not null,
    workflow integer,                                                                                                   -- managed by workflow, only field_display can be edited
    field text not null,
    field_display text not null,
    field_description text,
    regex text,
    link text
);
CREATE UNIQUE INDEX tt_issue_custom_fields_name on tt_issue_custom_fields(field);

-- projects <-> custom fields
CREATE TABLE tt_projects_custom_fields
(
    custom_fields_usage_id integer not null primary key autoincrement,
    project_id integer,
    custom_field_id integer
);
CREATE UNIQUE INDEX tt_projects_custom_fields_uniq on tt_projects_custom_fields (project_id, custom_field_id);

-- custom fields values options
CREATE TABLE tt_issue_custom_fields_options
(
    custom_field_option_id integer not null primary key autoincrement,
    custom_field_id integer,
    value text,
    option text not null,
    option_display text
);
CREATE UNIQUE INDEX tt_issue_custom_fields_options_uniq on tt_issue_custom_fields_options(custom_field_id, option);

-- custom fields values
CREATE TABLE tt_issue_custom_fields_values
(
    custom_field_value_id integer not null primary key autoincrement,
    issue_id integer,
    custom_field_id integer,
    value text
);
CREATE INDEX tt_issue_custom_fields_values_issue_id on tt_issue_custom_fields_values(issue_id);
CREATE INDEX tt_issue_custom_fields_values_field_id on tt_issue_custom_fields_values(custom_field_id);
CREATE INDEX tt_issue_custom_fields_values_type_value on tt_issue_custom_fields_values(value);

-- projects roles types
CREATE TABLE tt_roles
(
    role_id integer not null primary key autoincrement,
    name text,
    level integer
);
CREATE INDEX tt_roles_level on tt_roles(level);
INSERT INTO tt_roles (level, name) values (1000, 'participant.junior');                                                 -- can view only
INSERT INTO tt_roles (level, name) values (2000, 'participant.middle');                                                 -- can comment, can edit and delete own comments, can attach files and delete own files
INSERT INTO tt_roles (level, name) values (3000, 'participant.senior');                                                 -- can create issues
INSERT INTO tt_roles (level, name) values (4000, 'employee.junior');                                                    -- can change status (by workflow, without final)
INSERT INTO tt_roles (level, name) values (5000, 'employee.middle');                                                    -- can change status (by workflow)
INSERT INTO tt_roles (level, name) values (6000, 'employee.senior');                                                    -- can edit issues
INSERT INTO tt_roles (level, name) values (7000, 'manager.junior');                                                     -- can edit all comments and delete comments, can delete files, can create tag
INSERT INTO tt_roles (level, name) values (8000, 'manager.middle');                                                     -- can delete issues
INSERT INTO tt_roles (level, name) values (9000, 'manager.senior');                                                     --

-- project rights
CREATE TABLE tt_projects_roles
(
    project_role_id integer not null primary key autoincrement,
    project_id integer not null,
    role_id integer not null,
    uid integer,
    gid integer
);
CREATE UNIQUE INDEX tt_projects_roles_uniq on tt_projects_roles (project_id, role_id, uid, gid);
CREATE INDEX tt_projects_roles_project_id on tt_projects_roles(project_id);
CREATE INDEX tt_projects_roles_role_id on tt_projects_roles(role_id);
CREATE INDEX tt_projects_roles_uid on tt_projects_roles(uid);
CREATE INDEX tt_projects_roles_gid on tt_projects_roles(gid);

-- subtasks
CREATE TABLE tt_subtasks
(
    subtask_id integer not null primary key autoincrement,
    issue_id integer,
    sub_issue_id integer
);
CREATE UNIQUE INDEX tt_subtasks_uniq on tt_subtasks(issue_id, sub_issue_id);
