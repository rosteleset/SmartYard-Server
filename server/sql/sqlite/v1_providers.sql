-- providers
CREATE TABLE providers
(
    provider_id integer primary key autoincrement,
    id text not null,
    name text,
    base_url text,
    logo text,
    token_common text,                                                                                                  -- for push and outgoing calls
    token_sms text,
    hidden integer
);
CREATE UNIQUE INDEX providers_id on providers (id);
CREATE UNIQUE INDEX providers_name on providers (name);
