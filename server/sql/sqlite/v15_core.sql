CREATE TABLE core_users_tokens(uid integer not null, token character varying);
CREATE INDEX core_users_tokens_uid on core_users_tokens(uid);
CREATE UNIQUE INDEX core_users_tokens_uniq on core_users_tokens(uid, token);

CREATE TABLE core_users_notifications(notification_id integer AUTOINCREMENT PRIMARY KEY, uid integer, created integer, sended integer, delivered integer, readed integer, caption text, body text, data text);
CREATE INDEX core_users_notifications_uid ON core_users_notifications(uid);
CREATE INDEX core_users_notifications_sended ON core_users_notifications(sended);
