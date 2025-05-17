ALTER TABLE tt_issue_custom_fields ADD IF NOT EXISTS readonly INTEGER DEFAULT 0;
UPDATE tt_issue_custom_fields SET readonly = 1 WHERE field = 'text-ro';
UPDATE tt_issue_custom_fields SET field = 'text' where field = 'text-ro';
