CREATE TABLE core_inbox
(
    msg_id serial primary key,
    msg_date INTEGER,
    msg_from CHARACTER VARYING,
    msg_to CHARACTER VARYING,
    msg_subject CHARACTER VARYING,
    msg_type CHARACTER VARYING,
    msg_body CHARACTER VARYING,
    msg_readed INTEGER
);
CREATE INDEX core_msg_to ON core_inbox(msg_to);