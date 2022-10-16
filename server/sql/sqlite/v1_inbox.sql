-- inbox
CREATE TABLE inbox
(
    msg_id integer not null primary key autoincrement,
    house_subscriber_id integer,
    id test not null,                                                                                                   -- phone number
    date test,                                                                                                          -- send date
    title text,                                                                                                         -- title (subject)
    msg text not null,                                                                                                  -- message
    action text,                                                                                                        -- application action (money, new_address, ...)
    expire integer,                                                                                                     -- when need to delete (time()) unsended message
    readed integer,                                                                                                     -- readed
    code character varying                                                                                              -- result code from google, smssending, etc...
);
