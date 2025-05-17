ALTER TABLE tt_issue_custom_fields ADD IF NOT EXISTS readonly INTEGER DEFAULT 0;
UPDATE tt_issue_custom_fields SET readonly = 1 WHERE editor = 'text-ro';
UPDATE tt_issue_custom_fields SET editor = 'text' where editor = 'text-ro';
