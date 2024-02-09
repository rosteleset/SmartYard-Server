ALTER TABLE addresses_houses ADD COLUMN company_id INTEGER;

CREATE INDEX addresses_houses_company_id ON addresses_houses (company_id);