ALTER TABLE core_users DROP COLUMN IF EXISTS push_token;
CREATE TABLE IF NOT EXISTS core_users_tokens(uid integer NOT NULL, token character varying);
CREATE INDEX IF NOT EXISTS core_users_tokens_uid ON core_users_tokens(uid);
CREATE UNIQUE INDEX IF NOT EXISTS core_users_tokens_uniq ON core_users_tokens(uid, token);

CREATE TABLE IF NOT EXISTS core_users_notifications(notification_id serial PRIMARY KEY, uid integer, created integer, sended integer, delivered integer, readed integer, caption character varying, body character varying, data character varying);
CREATE INDEX IF NOT EXISTS core_users_notifications_uid ON core_users_notifications(uid);
CREATE INDEX IF NOT EXISTS core_users_notifications_sended ON core_users_notifications(sended);
