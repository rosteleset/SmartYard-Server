-- providers
CREATE TABLE providers
(
    provider_id integer not null primary key autoincrement,
    id text not null,
    name text,
    base_url text,
    logo text,
    token text,
    allow_sms integer,
    allow_flash_call integer,
    allow_outgoing_call integer
);
