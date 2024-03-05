ALTER TABLE core_users ADD IF NOT EXISTS primary_group integer;
ALTER TABLE core_groups ADD IF NOT EXISTS admin integer;