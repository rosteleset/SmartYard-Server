-- projects
CREATE TABLE tt_projects(project_id serial primary key, acronym character varying not null, project character varying not null);
CREATE UNIQUE INDEX tt_projects_acronym on tt_projects(acronym);
CREATE UNIQUE INDEX tt_projects_name on tt_projects(project);
-- issue types
CREATE TABLE tt_issue_types (type_id serial primary key, type character varying);
CREATE UNIQUE INDEX tt_issue_types_uniq on tt_issue_types(type);
-- issue statuses
CREATE TABLE tt_issue_statuses (status_id serial primary key, status character varying, final integer);
CREATE UNIQUE INDEX tt_issue_stauses_uniq on tt_issue_statuses(status);
-- issue resulutions
CREATE TABLE tt_issue_resolutions (resolution_id serial primary key, resolution character varying);
CREATE UNIQUE INDEX tt_issue_resolutions_uniq on tt_issue_resolutions(resolution);
-- issues
CREATE TABLE tt_issues (issue_id serial primary key,
                        project_id integer,                     -- project id
                        subject character varying not null,     -- subject
                        description character varying not null, -- description
                        author integer,                         -- uid
                        type_id integer,                        -- issue type
                        status_id integer,                      -- status
                        resolution_id integer,                  -- resolution
                        created timestamp,                      -- "YYYY-MM-DD HH:MM:SS.SSS"
                        updated timestamp,                      -- "YYYY-MM-DD HH:MM:SS.SSS"
                        closed timestamp,                       -- "YYYY-MM-DD HH:MM:SS.SSS"
                        external_id integer,                    -- link to external id
                        external_id_type character varying      -- external object description
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
CREATE TABLE tt_issue_plans (plan_id integer not null primary key, issue_id integer, action character varying, planned timestamp, uid integer, gid integer);
CREATE UNIQUE INDEX tt_issue_plans_uniq on tt_issue_plans(issue_id, action);
CREATE INDEX tt_issue_plans_issue_id on tt_issue_plans(issue_id);
CREATE INDEX tt_issue_plans_planned on tt_issue_plans(planned);
CREATE INDEX tt_issue_plans_uid on tt_issue_plans(uid);
CREATE INDEX tt_issue_plans_gid on tt_issue_plans(gid);
-- comments
CREATE TABLE tt_issue_comments (comment_id integer not null primary key,
                                issue_id integer,          -- issue
                                comment character varying, -- comment
                                created timestamp,         -- "YYYY-MM-DD HH:MM:SS.SSS"
                                updated timestamp,         -- "YYYY-MM-DD HH:MM:SS.SSS"
                                author integer             -- uid
);
CREATE INDEX tt_issue_comments_issue_id on tt_issue_comments(issue_id);
-- checklist
CREATE TABLE tt_issue_checklist (check_id integer not null primary key, issue_id integer, checkbox character varying, checked integer);
CREATE UNIQUE INDEX tt_issue_checklist_uniq on tt_issue_checklist(issue_id, checkbox);
CREATE INDEX tt_issue_checklist_issue_id on tt_issue_checklist(issue_id);
-- tags
CREATE TABLE tt_issue_tags (tag_id integer not null primary key, issue_id integer, tag character varying);
CREATE UNIQUE INDEX tt_issue_tags_uniq on tt_issue_tags (issue_id, tag);
CREATE INDEX tt_issue_tags_issue_id on tt_issue_tags(issue_id);
CREATE INDEX tt_issue_tags_tag on tt_issue_tags(tag);
-- custom fields names
CREATE TABLE tt_issue_custom_fields (custom_field_id integer not null primary key, name character varying not null, type character varying not null);
CREATE UNIQUE INDEX tt_issue_custom_fields_name on tt_issue_custom_fields(name);
-- custom fields values
CREATE TABLE tt_issue_custom_fields_values (custom_field_value_id integer not null primary key, issue_id integer, custom_field_id integer, value character varying);
CREATE INDEX tt_issue_custom_fields_values_issue_id on tt_issue_custom_fields_values(issue_id);
CREATE INDEX tt_issue_custom_fields_values_field_id on tt_issue_custom_fields_values(custom_field_id);
CREATE INDEX tt_issue_custom_fields_values_type_value on tt_issue_custom_fields_values(value);
-- custom fields values options
CREATE TABLE tt_issue_custom_fields_options (custom_field_value_id integer not null primary key, custom_field_id integer, option character varying);
CREATE UNIQUE INDEX tt_issue_custom_fields_options_uniq on tt_issue_custom_fields_options(custom_field_id, option);
-- projects roles types
CREATE TABLE tt_roles (role_id serial primary key, name character varying, level integer);
CREATE INDEX tt_roles_level on tt_roles(level);
INSERT INTO tt_roles (name, level) values (1000, 'viewer');
INSERT INTO tt_roles (name, level) values (2000, 'commenter');
INSERT INTO tt_roles (name, level) values (3000, 'reporter');
INSERT INTO tt_roles (name, level) values (4000, 'participant.junior');
INSERT INTO tt_roles (name, level) values (5000, 'participant.middle');
INSERT INTO tt_roles (name, level) values (6000, 'participant.senior');
INSERT INTO tt_roles (name, level) values (7000, 'manager');
INSERT INTO tt_roles (name, level) values (8000, 'admin');
-- project rights
CREATE TABLE tt_projects_roles (tt_project_role_id integer not null primary key autoincrement, project_id integer not null, role_id integer not null, uid integer, gid integer);
CREATE UNIQUE INDEX tt_projects_roles_uniq on tt_projects_roles (project_id, role_id);
CREATE INDEX tt_projects_roles_project_id on tt_projects_roles(project_id);
CREATE INDEX tt_projects_roles_role_id on tt_projects_roles(role_id);
CREATE INDEX tt_projects_roles_uid on tt_projects_roles(uid);
CREATE INDEX tt_projects_roles_gid on tt_projects_roles(gid);
