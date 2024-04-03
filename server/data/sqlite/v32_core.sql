ALTER TABLE core_users ADD COLUMN secret text;
ALTER TABLE core_users ADD COLUMN two_fa integer default 0;
