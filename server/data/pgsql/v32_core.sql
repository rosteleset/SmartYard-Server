ALTER TABLE core_users ADD IF NOT EXISTS secret character varying;
ALTER TABLE core_users ADD IF NOT EXISTS two_fa integer default 0;
