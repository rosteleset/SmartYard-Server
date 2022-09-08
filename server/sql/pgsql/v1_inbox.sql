-- inbox
CREATE TABLE inbox
(
    msg_id serial primary key,
    id character varying not null,
    date timestamp,
    msg character varying,
    action character varying,
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
    code character varying
);
