-- inbox
CREATE TABLE inbox
(
    msg_id serial primary key,
    house_subscriber_id integer,
    id character varying not null,                                                                                      -- phone number
    date integer,                                                                                                       -- send date, UNIX timestamp
    title character varying,                                                                                            -- title (subject)
    msg character varying not null,                                                                                     -- message
    action character varying,                                                                                           -- application action (money, new_address, ...)
    expire integer,                                                                                                     -- when need to delete (time()) unsended message
    push_message_id text,
    delivered integer,                                                                                                  -- delivered
    readed integer,                                                                                                     -- readed
    code character varying                                                                                              -- result code from google, smssending, etc...
);
CREATE INDEX inbox_readed on inbox(readed);
CREATE INDEX inbox_expire on inbox(expire);
CREATE INDEX inbox_date on inbox(date);
