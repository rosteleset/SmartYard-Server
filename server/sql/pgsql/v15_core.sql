ALTER TABLE core_users DROP COLUMN IF EXISTS push_token;
CREATE TABLE IF NOT EXISTS core_users_tokens(uid integer not null, token character varying);
CREATE INDEX IF NOT EXISTS core_users_tokens_uid on core_users_tokens(uid);
CREATE UNIQUE INDEX IF NOT EXISTS core_users_tokens_uniq on core_users_tokens(uid, token);
