ALTER TABLE addresses_houses ADD IF NOT EXISTS company_id INTEGER DEFAULT 0;

CREATE INDEX IF NOT EXISTS addresses_houses_company_id ON addresses_houses (company_id);