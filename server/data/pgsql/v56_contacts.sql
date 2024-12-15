CREATE TABLE IF NOT EXISTS contacts_fields
(
 issue_custom_field_id | integer           |           | not null | nextval('tt_issue_custom_fields_issue_custom_field_id_seq'::regclass)
 catalog               | character varying |           |          |
 type                  | character varying |           | not null |
 field                 | character varying |           | not null |
 field_display         | character varying |           | not null |
 field_description     | character varying |           |          |
 regex                 | character varying |           |          |
 link                  | character varying |           |          |
 format                | character varying |           |          |
 editor                | character varying |           |          |
 indx                  | integer           |           |          |
 search                | integer           |           |          |
 required              | integer           |           |          |

Indexes:
    "tt_issue_custom_fields_pkey" PRIMARY KEY, btree (issue_custom_field_id)
    "tt_issue_custom_fields_name" UNIQUE, btree (field)
);

CREATE TABLE IF NOT EXISTS contacts_fileds_options
(
 issue_custom_field_option_id | integer           |           | not null | nextval('tt_issue_custom_fields_options_issue_custom_field_option_id_seq'::regclass)
 issue_custom_field_id        | integer           |           |          |
 option                       | character varying |           | not null |
 display_order                | integer           |           |          |
 option_display               | character varying |           |          |

Indexes:
    "tt_issue_custom_fields_options_pkey" PRIMARY KEY, btree (issue_custom_field_option_id)
    "tt_issue_custom_fields_options_uniq" UNIQUE, btree (issue_custom_field_id, option)
);

CREATE TABLE IF NOT EXISTS contacts_viewers
(
 project_view_id | integer           |           | not null | nextval('tt_projects_viewers_project_view_id_seq'::regclass)
 project_id      | integer           |           |          |
 field           | character varying |           |          |
 name            | character varying |           |          |

Indexes:
    "tt_projects_viewers_pkey" PRIMARY KEY, btree (project_view_id)
    "tt_projects_viewers_uniq" UNIQUE, btree (project_id, field)
);

CREATE TABLE IF NOT EXISTS contacts_tags
(
 tag_id     | integer           |           | not null | nextval('tt_tags_tag_id_seq'::regclass)
 project_id | integer           |           | not null |
 tag        | character varying |           |          |
 foreground | character varying |           |          |
 background | character varying |           |          |

Indexes:
    "tt_tags_pkey" PRIMARY KEY, btree (tag_id)
    "tt_tags_uniq" UNIQUE, btree (project_id, tag)
);