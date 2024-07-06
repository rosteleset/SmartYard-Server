CREATE TABLE core_inbox
(
    msg_id INTEGER PRIMARY KEY AUTOINCREMENT,
    msg_date INTEGER,
    msg_from TEXT,
    msg_to TEXT,
    msg_subject TEXT,
    msg_type TEXT,
    msg_body TEXT,
    msg_readed INTEGER
);
CREATE INDEX core_msg_to ON core_inbox(msg_to);