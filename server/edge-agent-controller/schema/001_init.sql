CREATE TABLE IF NOT EXISTS edge_agents (
    agent_id text PRIMARY KEY,
    display_name text NOT NULL,
    public_key text NOT NULL,
    key_id text NOT NULL UNIQUE,
    pairing_status text NOT NULL DEFAULT 'active'
        CHECK (pairing_status IN ('active', 'revoking', 'revoked')),
    paired_at timestamptz NOT NULL,
    revocation_requested_at timestamptz,
    revocation_reason text,
    revoked_at timestamptz,
    last_seen_at timestamptz,
    last_sequence bigint NOT NULL DEFAULT 0 CHECK (last_sequence >= 0),
    agent_version text,
    capabilities jsonb NOT NULL DEFAULT '{}'::jsonb,
    overlay_public_key text,
    management_state jsonb NOT NULL DEFAULT '{}'::jsonb,
    managed_authorized boolean NOT NULL DEFAULT false,
    managed_scopes jsonb NOT NULL DEFAULT '[]'::jsonb,
    management_revision bigint NOT NULL DEFAULT 0 CHECK (management_revision >= 0),
    management_challenge text,
    management_proof text,
    management_requested_scopes jsonb NOT NULL DEFAULT '[]'::jsonb,
    management_expires_at timestamptz,
    desired_generation bigint NOT NULL DEFAULT 0 CHECK (desired_generation >= 0),
    observed_generation bigint NOT NULL DEFAULT 0 CHECK (observed_generation >= 0),
    applied_generation bigint NOT NULL DEFAULT 0 CHECK (applied_generation >= 0),
    desired_state jsonb NOT NULL DEFAULT '{"overlay":{},"mappings":[]}'::jsonb,
    actual_state jsonb NOT NULL DEFAULT '{}'::jsonb,
    health jsonb NOT NULL DEFAULT '{}'::jsonb,
    condition_summary jsonb NOT NULL DEFAULT '[]'::jsonb,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS edge_agents_last_seen_idx
    ON edge_agents (last_seen_at DESC);

CREATE TABLE IF NOT EXISTS edge_agent_pairing_invitations (
    pairing_id text PRIMARY KEY,
    code_hash text NOT NULL UNIQUE,
    controller_id text NOT NULL,
    status text NOT NULL DEFAULT 'created'
        CHECK (status IN ('created', 'pending', 'used', 'expired', 'revoked')),
    expires_at timestamptz NOT NULL,
    created_by integer,
    metadata jsonb NOT NULL DEFAULT '{}'::jsonb,
    pending_agent_id text,
    pending_agent_name text,
    pending_public_key text,
    pending_key_id text,
    pending_agent_version text,
    pending_capabilities jsonb,
    agent_challenge text,
    controller_challenge text,
    pending_last_sequence bigint NOT NULL DEFAULT 0 CHECK (pending_last_sequence >= 0),
    pending_at timestamptz,
    used_at timestamptz,
    created_at timestamptz NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS edge_agent_pairing_status_expiry_idx
    ON edge_agent_pairing_invitations (status, expires_at);

CREATE TABLE IF NOT EXISTS edge_agent_request_replay (
    signer_id text NOT NULL,
    request_id text NOT NULL,
    expires_at timestamptz NOT NULL,
    created_at timestamptz NOT NULL DEFAULT now(),
    PRIMARY KEY (signer_id, request_id)
);

CREATE INDEX IF NOT EXISTS edge_agent_request_replay_expiry_idx
    ON edge_agent_request_replay (expires_at);

CREATE TABLE IF NOT EXISTS edge_overlay_pools (
    pool_id text PRIMARY KEY,
    title text NOT NULL,
    tunnel_pool cidr NOT NULL,
    overlay_pool cidr NOT NULL,
    agent_prefix_length smallint NOT NULL CHECK (agent_prefix_length BETWEEN 1 AND 32),
    gateway_endpoint text NOT NULL UNIQUE,
    gateway_public_key text NOT NULL,
    gateway_tunnel_address inet NOT NULL,
    gateway_interface text NOT NULL DEFAULT 'awg-rbt' UNIQUE,
    overlay_type text NOT NULL DEFAULT 'wireguard'
        CHECK (overlay_type IN ('amneziawg', 'wireguard')),
    persistent_keepalive_sec integer NOT NULL DEFAULT 25
        CHECK (persistent_keepalive_sec BETWEEN 0 AND 3600),
    allowed_source_prefixes jsonb NOT NULL DEFAULT '[]'::jsonb,
    parameters jsonb NOT NULL DEFAULT '{}'::jsonb
        CONSTRAINT edge_overlay_pools_parameters_object
        CHECK (jsonb_typeof(parameters) = 'object'),
    enabled boolean NOT NULL DEFAULT true,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS edge_overlay_leases (
    lease_id text PRIMARY KEY,
    agent_id text NOT NULL UNIQUE REFERENCES edge_agents(agent_id) ON DELETE CASCADE,
    pool_id text NOT NULL REFERENCES edge_overlay_pools(pool_id),
    tunnel_ip inet NOT NULL UNIQUE,
    overlay_prefix cidr NOT NULL UNIQUE,
    agent_public_key text,
    enabled boolean NOT NULL DEFAULT true,
    desired_generation bigint NOT NULL DEFAULT 0 CHECK (desired_generation >= 0),
    gateway_state text NOT NULL DEFAULT 'pending',
    gateway_error text,
    gateway_applied_at timestamptz,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS edge_overlay_mappings (
    mapping_id text PRIMARY KEY,
    agent_id text NOT NULL REFERENCES edge_agents(agent_id) ON DELETE CASCADE,
    local_ip inet NOT NULL,
    overlay_ip inet NOT NULL UNIQUE,
    enabled boolean NOT NULL DEFAULT true,
    comment text NOT NULL DEFAULT '',
    desired_generation bigint NOT NULL CHECK (desired_generation >= 0),
    current_state text NOT NULL DEFAULT 'pending',
    current_error text,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now(),
    UNIQUE (agent_id, local_ip),
    CHECK (family(local_ip) = 4),
    CHECK (family(overlay_ip) = 4)
);

CREATE INDEX IF NOT EXISTS edge_overlay_mappings_agent_idx
    ON edge_overlay_mappings (agent_id, enabled);

CREATE TABLE IF NOT EXISTS edge_agent_actions (
    action_id text PRIMARY KEY,
    agent_id text NOT NULL REFERENCES edge_agents(agent_id) ON DELETE CASCADE,
    action_type text NOT NULL
        CHECK (action_type IN ('diagnostics', 'lan_scan', 'inventory_refresh', 'awg_key_rotation')),
    payload jsonb NOT NULL DEFAULT '{}'::jsonb,
    idempotency_key text NOT NULL,
    state text NOT NULL DEFAULT 'queued'
        CHECK (state IN ('queued', 'running', 'completed', 'failed', 'expired')),
    result jsonb,
    error text,
    expires_at timestamptz NOT NULL,
    started_at timestamptz,
    completed_at timestamptz,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now(),
    UNIQUE (agent_id, idempotency_key)
);

CREATE INDEX IF NOT EXISTS edge_agent_actions_delivery_idx
    ON edge_agent_actions (agent_id, state, expires_at);
