-- companies
CREATE TABLE companies
(
    company_id integer primary key autoincrement,
    name text,
    uid text,
    contacts text,
    comment text
);
CREATE INDEX company_uid on companies(uid);

ALTER TABLE addresses_houses ADD COLUMN company integer;