-- Keep the entire migration atomic, including legacy-column removal.
DO $$
DECLARE
    legacy_column text;
BEGIN
    CREATE TABLE IF NOT EXISTS addresses_houses_companies (
        address_house_id integer NOT NULL REFERENCES addresses_houses(address_house_id) ON DELETE CASCADE,
        company_id integer NOT NULL REFERENCES companies(company_id) ON DELETE CASCADE,
        PRIMARY KEY (address_house_id, company_id)
    );

    CREATE INDEX IF NOT EXISTS addresses_houses_companies_company_id
        ON addresses_houses_companies(company_id);

    -- v5 used company; v23 introduced company_id. Preserve both if present.
    FOREACH legacy_column IN ARRAY ARRAY['company_id', 'company'] LOOP
        IF EXISTS (
            SELECT 1 FROM pg_attribute
            WHERE attrelid = 'addresses_houses'::regclass
              AND attname = legacy_column AND NOT attisdropped
        ) THEN
            -- A dangling company reference fails the migration rather than
            -- silently discarding a customer's assignment.
            EXECUTE format(
                'INSERT INTO addresses_houses_companies (address_house_id, company_id)
                 SELECT address_house_id, %I FROM addresses_houses WHERE %I > 0
                 ON CONFLICT DO NOTHING', legacy_column, legacy_column
            );
            EXECUTE format('ALTER TABLE addresses_houses DROP COLUMN %I', legacy_column);
        END IF;
    END LOOP;
END;
$$;
