-- Create project Mobile
insert into
  tt_projects (acronym, project, max_file_size, assigned, search_subject, search_description,
               search_comments)
values ('APP', 'Mobile', 1048576, 0, 1, 1, 1)
on conflict do nothing;

-- Create custom fields
insert into
  tt_issue_custom_fields (catalog, type, field, field_display, field_description, regex, link, format, editor, indx,
                          search, required)
values ('Заявка из приложения', 'text', 'phone', 'Телефон', '', '', '', '', 'text-ro', 0, 0, 0)
on conflict do nothing;

insert into
  tt_issue_custom_fields (catalog, type, field, field_display, field_description, regex, link, format, editor, indx,
                          search, required)
values ('Заявка из приложения', 'geo', 'geo', 'Геокоординаты', '', '', '', '', 'text', 0, 0, 0)
on conflict do nothing;

insert into
  tt_issue_custom_fields (catalog, type, field, field_display, field_description, regex, link, format, editor, indx,
                          search, required)
values ('Заявка из приложения', 'text', 'camera_id', 'Идентификатор камеры', '', '', '', '', 'text-ro', 0, 0, 0)
on conflict do nothing;

insert into
  tt_issue_custom_fields (catalog, type, field, field_display, field_description, regex, link, format, editor, indx,
                          search, required)
values ('Заявка из приложения', 'select', 'qr_delivery', 'Доставка QR-кода', '', '', '', 'editable', 'text', 0, 0, 0)
on conflict do nothing;

insert into
  tt_issue_custom_fields (catalog, type, field, field_display, field_description, regex, link, format, editor, indx,
                          search, required)
values ('Заявка из приложения', 'text', 'address', 'Адрес', '', '', '', '', 'text-ro', 0, 0, 0)
on conflict do nothing;

-- Create custom field options
insert into
  tt_issue_custom_fields_options (issue_custom_field_id, option, option_display, display_order)
select issue_custom_field_id, 'Курьер', 'Курьер', 1
from
  tt_issue_custom_fields
where
  field = 'qr_delivery'
on conflict do nothing;

insert into
  tt_issue_custom_fields_options (issue_custom_field_id, option, option_display, display_order)
select issue_custom_field_id, 'Самовывоз', 'Самовывоз', 2
from
  tt_issue_custom_fields
where
  field = 'qr_delivery'
on conflict do nothing;

-- Assign custom fields to project Mobile
insert into tt_projects_custom_fields(project_id, issue_custom_field_id)
select
  p.project_id,
  cf.issue_custom_field_id
from
  tt_projects p,
  tt_issue_custom_fields cf
where
  p.project = 'Mobile'
  and cf.catalog = 'Заявка из приложения'
on conflict do nothing;

-- Project Mobile workflow
insert into
  tt_projects_workflows (project_id, workflow)
select
  p.project_id,
  'Mobile'
from
  tt_projects p
where
  p.project = 'Mobile'
on conflict do nothing;

-- Core groups
insert into core_groups (acronym, name) values ('call-center', 'call-center') on conflict do nothing;
insert into core_groups (acronym, name) values ('office', 'office') on conflict do nothing;
insert into core_groups (acronym, name) values ('cctv-managers', 'cctv-managers') on conflict do nothing;

-- Project Mobile resolutions
insert into tt_issue_resolutions (resolution) VALUES ('fixed') on conflict do nothing;
insert into tt_issue_resolutions (resolution) VALUES ('can''t fix') on conflict do nothing;
insert into tt_issue_resolutions (resolution) VALUES ('duplicate') on conflict do nothing;

select * from tt_projects_resolutions;
insert into
  tt_projects_resolutions (project_id, issue_resolution_id)
select
  p.project_id, r.issue_resolution_id
from
  tt_projects p,
  tt_issue_resolutions r
where
  p.project = 'Mobile'
  and r.resolution = 'fixed'
on conflict do nothing;

select * from tt_projects_resolutions;
insert into
  tt_projects_resolutions (project_id, issue_resolution_id)
select
  p.project_id, r.issue_resolution_id
from
  tt_projects p,
  tt_issue_resolutions r
where
  p.project = 'Mobile'
  and r.resolution = 'can''t fix'
on conflict do nothing;

select * from tt_projects_resolutions;
insert into
  tt_projects_resolutions (project_id, issue_resolution_id)
select
  p.project_id, r.issue_resolution_id
from
  tt_projects p,
  tt_issue_resolutions r
where
  p.project = 'Mobile'
  and r.resolution = 'duplicate'
on conflict do nothing;

--- Statuses
insert into tt_issue_statuses (status, final) values ('opened', 0) on conflict do nothing;
insert into tt_issue_statuses (status, final) values ('closed', 0) on conflict do nothing;
