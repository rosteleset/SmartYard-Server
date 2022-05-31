CREATE TABLE tt_projects(project_id integer not null primary key autoincrement, acronym text not null, name text not null);
CREATE UNIQUE INDEX tt_projects_acronym on tt_projects(acronym);
CREATE UNIQUE INDEX tt_projects_name on tt_projects(name);
CREATE TABLE tt_issues(issue_id integer not null primary key autoincrement,
    project_id integer,             -- project id
    subject text not null,          -- subject
    description text not null,      -- description
    author integer,                 -- uid
    status_id integer,              -- status
    resolution_id integer,          -- resolution
    created text not null,          -- "YYYY-MM-DD HH:MM:SS.SSS"
    updated text,                   -- "YYYY-MM-DD HH:MM:SS.SSS"
    closed text                     -- "YYYY-MM-DD HH:MM:SS.SSS"
);
CREATE INDEX tt_issues_subject on tt_issues(subject);
CREATE INDEX tt_issues_author on tt_issues(author);
CREATE INDEX tt_issues_status_id on tt_issues(status_id);
CREATE INDEX tt_issues_resolution_id on tt_issues(resolution_id);
CREATE INDEX tt_issues_created on tt_issues(created);
CREATE INDEX tt_issues_updated on tt_issues(updated);
CREATE INDEX tt_issues_closed on tt_issues(closed);
CREATE TABLE tt_issue_assigned (tt_issue_assigned_id integer not null primary key, issue_id integer, uid integer, gid integer);
CREATE UNIQUE INDEX tt_issue_assigned_uniq on tt_issue_assigned (issue_id, uid, gid);
CREATE INDEX tt_issue_assigned_issue_id on tt_issue_assigned(issue_id);
CREATE INDEX tt_issue_assigned_uid on tt_issue_assigned(uid);
CREATE INDEX tt_issue_assigned_gid on tt_issue_assigned(gid);
CREATE TABLE tt_issue_watchers (tt_issue_watchers_id integer not null primary key, issue_id integer, uid integer);
CREATE UNIQUE INDEX tt_issue_watchers_uniq on tt_issue_watchers (issue_id, uid);
CREATE INDEX tt_issue_watchers_issue_id on tt_issue_watchers(issue_id);
CREATE INDEX tt_issue_watchers_uid on tt_issue_watchers(uid);
CREATE TABLE tt_issue_plans(tt_issue_plans_id integer not null primary key, issue_id integer, action text, planned text, uid integer, gid integer);
CREATE UNIQUE INDEX tt_issue_plans_uniq on tt_issue_plans(issue_id, action);
CREATE INDEX tt_issue_plans_issue_id on tt_issue_plans(issue_id);
CREATE INDEX tt_issue_plans_planned on tt_issue_plans(planned);
CREATE INDEX tt_issue_plans_uid on tt_issue_plans(uid);
CREATE INDEX tt_issue_plans_gid on tt_issue_plans(gid);