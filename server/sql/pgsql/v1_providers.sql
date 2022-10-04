-- providers
CREATE TABLE providers
(
    provider_id serial not null primary key,
    id character varying not null,
    name character varying,
    baseUrl character varying,
    logo character varying,
    token character varying,
    allow_sms integer,
    allow_flash_call integer,
    allow_outgoing_call integer
);
