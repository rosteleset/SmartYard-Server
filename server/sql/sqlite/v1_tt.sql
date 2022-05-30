CREATE TABLE tt_projects(project_id integer not null primary key autoincrement, acronym text not null, name text not null);
CREATE UNIQUE INDEX tt_projects_acronym on tt_projects(acronym);
CREATE UNIQUE INDEX tt_projects_name on tt_projects(name);
CREATE TABLE tt_issues(issue_id integer not null primary key autoincrement);