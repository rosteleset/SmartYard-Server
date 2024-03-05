-- providers
CREATE TABLE providers
(
    provider_id serial primary key,
    id character varying not null,
    name character varying,
    base_url character varying,
    logo character varying,
    token_common character varying,                                                                                     -- for push and outgoing calls
    token_sms character varying,
    hidden integer
);
CREATE UNIQUE INDEX providers_id on providers (id);
CREATE UNIQUE INDEX providers_name on providers (name);
