DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'link_lp_flat' AND column_name = 'valid_from'
    ) THEN
        ALTER TABLE link_lp_flat ADD COLUMN valid_from timestamp with time zone;
        COMMENT ON COLUMN link_lp_flat.valid_from IS 'Date and time when the license plate number link to the flat becomes valid';
        CREATE INDEX IF NOT EXISTS link_lp_flat_valid_from_index ON link_lp_flat (valid_from);
    END IF;
END;
$$;
