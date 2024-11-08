CREATE TABLE core_users_notifications_queue
(
    notification_id INTEGER PRIMARY KEY AUTOINCREMENT,
    uid INTEGER,
    subject TEXT,
    message TEXT
);
