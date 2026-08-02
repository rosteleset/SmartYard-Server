ALTER TABLE edge_agents
    ADD COLUMN IF NOT EXISTS overlay_public_key text;

ALTER TABLE edge_overlay_pools
    ADD COLUMN IF NOT EXISTS gateway_tunnel_address inet;

ALTER TABLE edge_overlay_pools
    ADD COLUMN IF NOT EXISTS overlay_type text NOT NULL DEFAULT 'wireguard';

ALTER TABLE edge_overlay_pools
    ADD COLUMN IF NOT EXISTS persistent_keepalive_sec integer NOT NULL DEFAULT 25;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'edge_overlay_pools_overlay_type_check'
    ) THEN
        ALTER TABLE edge_overlay_pools
            ADD CONSTRAINT edge_overlay_pools_overlay_type_check
            CHECK (overlay_type IN ('amneziawg', 'wireguard'));
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'edge_overlay_pools_keepalive_check'
    ) THEN
        ALTER TABLE edge_overlay_pools
            ADD CONSTRAINT edge_overlay_pools_keepalive_check
            CHECK (persistent_keepalive_sec BETWEEN 0 AND 3600);
    END IF;
END
$$;

CREATE UNIQUE INDEX IF NOT EXISTS edge_overlay_pools_gateway_interface_uidx
    ON edge_overlay_pools (gateway_interface);

CREATE UNIQUE INDEX IF NOT EXISTS edge_overlay_pools_gateway_endpoint_uidx
    ON edge_overlay_pools (gateway_endpoint);
