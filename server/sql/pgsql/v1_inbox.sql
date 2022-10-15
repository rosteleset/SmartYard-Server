-- inbox
CREATE TABLE inbox
(
    msg_id serial primary key,
    id character varying not null,                                                                                      -- phone number
    date timestamp,                                                                                                     -- send date
    msg character varying,                                                                                              -- message
    action character varying,                                                                                           -- application action (money, new_address, ...)
    expire integer,                                                                                                     -- when need to move to archive or delete (time())
    readed integer,                                                                                                     -- readed
    code character varying                                                                                              -- send code from google, smssending, etc...
);
