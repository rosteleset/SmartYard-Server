CREATE TABLE IF NOT EXISTS core_users_notifications_queue
(
    notification_id SERIAL PRIMARY KEY,
    login CHARACTER VARYING,
    uid INTEGER,
    subject CHARACTER VARYING,
    message CHARACTER VARYING
);
