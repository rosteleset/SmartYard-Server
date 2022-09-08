-- inbox
CREATE TABLE inbox
(
    msg_id integer not null primary key autoincrement,
    id text not null,
    date text,
    msg text,
    action text,
    bulk integer,
    push_only integer,
    push integer,
    cascad integer,
    delivered integer,
    readed integer,
    sms integer,
    force_sms integer,
    ext_id text,
    archive integer,
    code text
);
