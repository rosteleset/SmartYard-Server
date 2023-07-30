-- companies
CREATE TABLE companies
(
    company_id serial primary key,
    name character varying,
    uid character varying,
    contacts character varying,
    comment character varying
);
CREATE INDEX company_uid on companies(uid);

ALTER TABLE addresses_houses ADD COLUMN company integer;