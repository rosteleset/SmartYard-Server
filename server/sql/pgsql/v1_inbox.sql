-- inbox
CREATE TABLE inbox
(
    msg_id serial primary key,
    id character varying not null,                                                                                      -- phone number
    date timestamp,                                                                                                     -- send date
    msg character varying,                                                                                              -- message
    action character varying,                                                                                           -- application action (money, new_address, ...)
    expire integer,                                                                                                     -- when need to delete (time()) unsended message
    readed integer,                                                                                                     -- readed
    code character varying                                                                                              -- result code from google, smssending, etc...
);
