-- projects
CREATE TABLE tt_projects(project_id integer not null primary key autoincrement, acronym text not null, project text not null);
CREATE UNIQUE INDEX tt_projects_acronym on tt_projects(acronym);
CREATE UNIQUE INDEX tt_projects_name on tt_projects(project);
-- issue types
CREATE TABLE tt_issue_types (type_id integer not null primary key autoincrement, type text);
CREATE UNIQUE INDEX tt_issue_types_uniq on tt_issue_types(type);
-- issue statuses
CREATE TABLE tt_issue_statuses (status_id integer not null primary key autoincrement, status text, final integer);
CREATE UNIQUE INDEX tt_issue_stauses_uniq on tt_issue_statuses(status);
-- issue resolutions
CREATE TABLE tt_issue_resolutions (resolution_id integer not null primary key autoincrement, resolution text);
CREATE UNIQUE INDEX tt_issue_resolutions_uniq on tt_issue_resolutions(resolution);
-- issues
CREATE TABLE tt_issues (issue_id integer not null primary key autoincrement,
    project_id integer,             -- project id
    subject text not null,          -- subject
    description text not null,      -- description
    author integer,                 -- uid
    type_id integer,                -- issue type
    status_id integer,              -- status
    resolution_id integer,          -- resolution
    created text not null,          -- "YYYY-MM-DD HH:MM:SS.SSS"
    updated text,                   -- "YYYY-MM-DD HH:MM:SS.SSS"
    closed text,                    -- "YYYY-MM-DD HH:MM:SS.SSS"
    external_id integer,            -- link to external id
    external_id_type text           -- external object description
);
CREATE INDEX tt_issues_subject on tt_issues(subject);
CREATE INDEX tt_issues_author on tt_issues(author);
CREATE INDEX tt_issues_type_id on tt_issues(type_id);
CREATE INDEX tt_issues_status_id on tt_issues(status_id);
CREATE INDEX tt_issues_resolution_id on tt_issues(resolution_id);
CREATE INDEX tt_issues_created on tt_issues(created);
CREATE INDEX tt_issues_updated on tt_issues(updated);
CREATE INDEX tt_issues_closed on tt_issues(closed);
-- assigned(s)
CREATE TABLE tt_issue_assigned (assigned_id integer not null primary key, issue_id integer, uid integer, gid integer);
CREATE UNIQUE INDEX tt_issue_assigned_uniq on tt_issue_assigned (issue_id, uid, gid);
CREATE INDEX tt_issue_assigned_issue_id on tt_issue_assigned(issue_id);
CREATE INDEX tt_issue_assigned_uid on tt_issue_assigned(uid);
CREATE INDEX tt_issue_assigned_gid on tt_issue_assigned(gid);
-- watchers
CREATE TABLE tt_issue_watchers (watcher_id integer not null primary key, issue_id integer, uid integer);
CREATE UNIQUE INDEX tt_issue_watchers_uniq on tt_issue_watchers (issue_id, uid);
CREATE INDEX tt_issue_watchers_issue_id on tt_issue_watchers(issue_id);
CREATE INDEX tt_issue_watchers_uid on tt_issue_watchers(uid);
-- plans
CREATE TABLE tt_issue_plans (plan_id integer not null primary key, issue_id integer, action text, planned text, uid integer, gid integer);
CREATE UNIQUE INDEX tt_issue_plans_uniq on tt_issue_plans(issue_id, action);
CREATE INDEX tt_issue_plans_issue_id on tt_issue_plans(issue_id);
CREATE INDEX tt_issue_plans_planned on tt_issue_plans(planned);
CREATE INDEX tt_issue_plans_uid on tt_issue_plans(uid);
CREATE INDEX tt_issue_plans_gid on tt_issue_plans(gid);
-- comments
CREATE TABLE tt_issue_comments (comment_id integer not null primary key,
    issue_id integer,               -- issue
    comment text,                   -- comment
    created text,                   -- "YYYY-MM-DD HH:MM:SS.SSS"
    updated text,                   -- "YYYY-MM-DD HH:MM:SS.SSS"
    author integer                  -- uid
);
CREATE INDEX tt_issue_comments_issue_id on tt_issue_comments(issue_id);
-- checklist
CREATE TABLE tt_issue_checklist (check_id integer not null primary key, issue_id integer, checkbox text, checked integer);
CREATE UNIQUE INDEX tt_issue_checklist_uniq on tt_issue_checklist(issue_id, checkbox);
CREATE INDEX tt_issue_checklist_issue_id on tt_issue_checklist(issue_id);
-- tags
CREATE TABLE tt_issue_tags (tag_id integer not null primary key, issue_id integer, tag text);
CREATE UNIQUE INDEX tt_issue_tags_uniq on tt_issue_tags (issue_id, tag);
CREATE INDEX tt_issue_tags_issue_id on tt_issue_tags(issue_id);
CREATE INDEX tt_issue_tags_tag on tt_issue_tags(tag);
-- custom fields names
CREATE TABLE tt_issue_custom_fields (custom_field_id integer not null primary key, name text not null, type text not null);
CREATE UNIQUE INDEX tt_issue_custom_fields_name on tt_issue_custom_fields(name);
-- custom fields values
CREATE TABLE tt_issue_custom_fields_values (custom_field_value_id integer not null primary key, issue_id integer, custom_field_id integer, value text);
CREATE INDEX tt_issue_custom_fields_values_issue_id on tt_issue_custom_fields_values(issue_id);
CREATE INDEX tt_issue_custom_fields_values_field_id on tt_issue_custom_fields_values(custom_field_id);
CREATE INDEX tt_issue_custom_fields_values_type_value on tt_issue_custom_fields_values(value);
-- custom fields values options
CREATE TABLE tt_issue_custom_fields_options (custom_field_value_id integer not null primary key, custom_field_id integer, option text);
CREATE UNIQUE INDEX tt_issue_custom_fields_options_uniq on tt_issue_custom_fields_options(custom_field_id, option);
-- projects roles types
CREATE TABLE tt_roles (role_id integer not null primary key autoincrement, name text, level integer);
CREATE INDEX tt_roles_level on tt_roles(level);
INSERT INTO tt_roles (name, level) values (1000, 'viewer');                 -- can view only
INSERT INTO tt_roles (name, level) values (2000, 'commenter');              -- can comment and edit own comments
INSERT INTO tt_roles (name, level) values (3000, 'reporter');               -- can create issue
INSERT INTO tt_roles (name, level) values (4000, 'participant.junior');     -- can change status (withot final)
INSERT INTO tt_roles (name, level) values (5000, 'participant.middle');     -- can change status
INSERT INTO tt_roles (name, level) values (6000, 'participant.senior');     -- can edit issues
INSERT INTO tt_roles (name, level) values (7000, 'manager');                -- can edit all comments and delete comments
INSERT INTO tt_roles (name, level) values (8000, 'admin');                  -- can delete issues
-- project rights
CREATE TABLE tt_projects_roles (tt_project_role_id integer not null primary key autoincrement, project_id integer not null, role_id integer not null, uid integer, gid integer);
CREATE UNIQUE INDEX tt_projects_roles_uniq on tt_projects_roles (project_id, role_id);
CREATE INDEX tt_projects_roles_project_id on tt_projects_roles(project_id);
CREATE INDEX tt_projects_roles_role_id on tt_projects_roles(role_id);
CREATE INDEX tt_projects_roles_uid on tt_projects_roles(uid);
CREATE INDEX tt_projects_roles_gid on tt_projects_roles(gid);
