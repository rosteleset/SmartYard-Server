CREATE TABLE core_inbox
(
    msg_id integer primary key autoincrement,
    msg_date INTEGER,
    msg_from TEXT,
    msg_to TEXT,
    msg_subject TEXT,
    msg_type TEXT,
    msg_body TEXT,
    msg_readed INTEGER
);
CREATE INDEX core_msg_to ON core_inbox(msg_to);