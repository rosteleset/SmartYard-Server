-- issues
CREATE TABLE tt_issues
(
    issue_id integer not null primary key autoincrement,                                                                -- primary key
    project_id integer,                                                                                                 -- project_id
    workflow text,                                                                                                      -- workflow
    subject text not null,                                                                                              -- subject
    description text not null,                                                                                          -- description
    author integer,                                                                                                     -- uid
    issue_status_id integer,                                                                                            -- status
    issue_resolution_id integer,                                                                                        -- resolution
    created text not null,                                                                                              -- "YYYY-MM-DD HH:MM:SS.SSS"
    updated text                                                                                                        -- "YYYY-MM-DD HH:MM:SS.SSS"
);
CREATE INDEX tt_issues_project_id on tt_issues(project_id);
CREATE INDEX tt_issues_workflow on tt_issues(workflow);
CREATE INDEX tt_issues_subject on tt_issues(subject);
CREATE INDEX tt_issues_author on tt_issues(author);
CREATE INDEX tt_issues_status_id on tt_issues(issue_status_id);
CREATE INDEX tt_issues_resolution_id on tt_issues(issue_resolution_id);
CREATE INDEX tt_issues_created on tt_issues(created);
CREATE INDEX tt_issues_updated on tt_issues(updated);

-- assigned(s)
CREATE TABLE tt_issue_assigned
(
    issue_assigned_id integer not null primary key autoincrement,
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
    issue_watcher_id integer not null primary key autoincrement,
    issue_id integer,
    uid integer
);
CREATE UNIQUE INDEX tt_issue_watchers_uniq on tt_issue_watchers (issue_id, uid);
CREATE INDEX tt_issue_watchers_issue_id on tt_issue_watchers(issue_id);
CREATE INDEX tt_issue_watchers_uid on tt_issue_watchers(uid);

-- plans
CREATE TABLE tt_issue_plans
(
    issue_plan_id integer not null primary key autoincrement,
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
    issue_comment_id integer not null primary key autoincrement,
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
    issue_attachment_id integer not null primary key autoincrement,
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
    issue_checklist_id integer not null primary key,
    issue_id integer,
    checkbox text,
    checked integer
);
CREATE UNIQUE INDEX tt_issue_checklist_uniq on tt_issue_checklist(issue_id, checkbox);
CREATE INDEX tt_issue_checklist_issue_id on tt_issue_checklist(issue_id);

-- tags
CREATE TABLE tt_issue_tags
(
    issue_tag_id integer not null primary key autoincrement,
    issue_id integer,
    tag text
);
CREATE UNIQUE INDEX tt_issue_tags_uniq on tt_issue_tags (issue_id, tag);
CREATE INDEX tt_issue_tags_issue_id on tt_issue_tags(issue_id);
CREATE INDEX tt_issue_tags_tag on tt_issue_tags(tag);

-- custom fields values
CREATE TABLE tt_issue_custom_fields_values
(
    issue_custom_field_value_id integer not null primary key autoincrement,
    issue_id integer,
    issue_custom_field_id integer,
    value text
);
CREATE INDEX tt_issue_custom_fields_values_issue_id on tt_issue_custom_fields_values(issue_id);
CREATE INDEX tt_issue_custom_fields_values_field_id on tt_issue_custom_fields_values(issue_custom_field_id);
CREATE INDEX tt_issue_custom_fields_values_type_value on tt_issue_custom_fields_values(value);

-- subtasks
CREATE TABLE tt_subtasks
(
    subtask_id integer not null primary key autoincrement,
    issue_id integer,
    sub_issue_id integer
);
CREATE UNIQUE INDEX tt_subtasks_uniq on tt_subtasks(issue_id, sub_issue_id);
