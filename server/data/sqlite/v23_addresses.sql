ALTER TABLE addresses_houses ADD COLUMN company_id INTEGER DEFAULT 0;

CREATE INDEX addresses_houses_company_id ON addresses_houses (company_id);