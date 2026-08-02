ALTER TABLE edge_agents
    ADD COLUMN IF NOT EXISTS revocation_requested_at timestamptz;

ALTER TABLE edge_agents
    ADD COLUMN IF NOT EXISTS revocation_reason text;

ALTER TABLE edge_agents
    DROP CONSTRAINT IF EXISTS edge_agents_pairing_status_check;

ALTER TABLE edge_agents
    ADD CONSTRAINT edge_agents_pairing_status_check
    CHECK (pairing_status IN ('active', 'revoking', 'revoked'));
