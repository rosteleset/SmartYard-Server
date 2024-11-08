CREATE TABLE core_users_notifications_queue
(
    notification_id SERIAL PRIMARY KEY,
    uid INTEGER,
    subject CHARACTER VARYING,
    message CHARACTER VARYING
);
