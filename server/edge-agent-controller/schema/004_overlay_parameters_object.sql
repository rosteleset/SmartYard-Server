UPDATE edge_overlay_pools
SET parameters = '{}'::jsonb
WHERE parameters = '[]'::jsonb;

DO $migration$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'edge_overlay_pools_parameters_object'
          AND conrelid = 'edge_overlay_pools'::regclass
    ) THEN
        ALTER TABLE edge_overlay_pools
            ADD CONSTRAINT edge_overlay_pools_parameters_object
            CHECK (jsonb_typeof(parameters) = 'object');
    END IF;
END
$migration$;
