CREATE TABLE core_users_notifications
(
    notification_id SERIAL PRIMARY KEY,
    uid INTEGER
    subject CHARACTER VARYING,
    message CHARACTER VARYING
);
