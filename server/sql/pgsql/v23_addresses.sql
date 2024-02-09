ALTER TABLE addresses_houses ADD IF NOT EXISTS company_id INTEGER;

CREATE IF NOT EXISTS INDEX addresses_houses_company_id ON addresses_houses (company_id);