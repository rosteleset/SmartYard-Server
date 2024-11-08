CREATE TABLE core_users_notifications
(
    notification_id INTEGER PRIMARY KEY AUTOINCREMENT,
    uid INTEGER,
    subject TEXT,
    message TEXT
);
