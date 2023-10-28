CREATE TABLE core_users_tokens(uid integer not null, token character varying);
CREATE INDEX core_users_tokens_uid on core_users_tokens(uid);
CREATE UNIQUE INDEX core_users_tokens_uniq on core_users_tokens(uid, token);
