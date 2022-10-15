-- inbox
CREATE TABLE inbox
(
    msg_id integer not null primary key autoincrement ,
    id test not null,                                                                                                   -- phone number
    date test,                                                                                                          -- send date
    msg text,                                                                                                           -- message
    action text,                                                                                                        -- application action (money, new_address, ...)
    expire integer,                                                                                                     -- when need to move to archive or delete (time())
    readed integer,                                                                                                     -- readed
    code character varying                                                                                              -- send code from google, smssending, etc...
);
