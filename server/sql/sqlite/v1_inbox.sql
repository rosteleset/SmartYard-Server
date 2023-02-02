-- inbox
CREATE TABLE inbox
(
    msg_id integer primary key autoincrement,
    house_subscriber_id integer,
    id test not null,                                                                                                   -- phone number
    date integer,                                                                                                       -- send date, UNIX timestamp
    title text,                                                                                                         -- title (subject)
    msg text not null,                                                                                                  -- message
    action text,                                                                                                        -- application action (money, new_address, ...)
    expire integer,                                                                                                     -- when need to delete (time()) unsended message
    push_message_id text,
    delivered integer,                                                                                                  -- delivered
    readed integer,                                                                                                     -- readed
    code character varying                                                                                              -- result code from google, smssending, etc...
);
CREATE INDEX inbox_readed on inbox(readed);
CREATE INDEX inbox_expire on inbox(expire);
CREATE INDEX inbox_date on inbox(date);
