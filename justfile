# SmartYard-Server recipes (experimental)
# https://just.systems
#
# First-time install:
#   git clone https://github.com/rosteleset/rbt /opt/rbt && cd /opt/rbt
#   export RBT_HOST=<host> RBT_ADMIN_PASSWORD=<password>
#   just all
#
# Day-to-day ops (existing install):
#   just update
#   just update-devel
#   just reindex
#   just exit-maintenance

set shell := ["bash", "-euo", "pipefail", "-c"]

app_dir := env("RBT_DIR", "/opt/rbt")
server_dir := app_dir + "/server"
client_dir := app_dir + "/client"
client_lib_dir := client_dir + "/lib"
client_config_src := client_dir + "/config/config.sample.json5"
client_config_dst := client_dir + "/config/config.json"
server_config_src := server_dir + "/config/config.sample.json5"
server_config_dst := server_dir + "/config/config.json"
repo_url := "https://github.com/rosteleset/rbt"
releases_api := "https://api.github.com/repos/rosteleset/SmartYard-Server/releases/latest"

# Show available recipes
default:
    @just --list

# Full first-time install (needs RBT_HOST and RBT_ADMIN_PASSWORD)
[group('install')]
all: check-env get-app restart-services get-server-libs get-client-libs init-client-conf init-server-conf strip-config init-server-db create-index

# Fail fast if required environment variables are missing
[group('install')]
check-env:
    #!/usr/bin/env bash
    set -euo pipefail
    missing=0
    if [[ -z "${RBT_HOST:-}" ]]; then
        echo "Error: RBT_HOST is not set (example: export RBT_HOST=yard.example.org)" >&2
        missing=1
    fi
    if [[ -z "${RBT_ADMIN_PASSWORD:-}" ]]; then
        echo "Error: RBT_ADMIN_PASSWORD is not set" >&2
        missing=1
    fi
    [[ "$missing" -eq 0 ]]

# Clone app, checkout the latest release tag and write version marker
[group('install')]
get-app:
    #!/usr/bin/env bash
    set -euo pipefail
    APP="{{app_dir}}"
    if [[ ! -d "$APP/.git" ]]; then
        git clone "{{repo_url}}" "$APP"
    fi
    cd "$APP"
    TAG="$(curl -s "{{releases_api}}" | jq -r '.tag_name')"
    if [[ -z "$TAG" || "$TAG" == "null" ]]; then
        echo "Error: failed to resolve latest release tag" >&2
        exit 1
    fi
    git -c advice.detachedHead=false checkout "$TAG"
    printf '%s\n' "$TAG" > version
    ln -sf "$APP/version" "$APP/client/version.app"
    echo "Checked out $TAG into $APP"

# Restart services needed before DB init
[group('install')]
restart-services:
    systemctl restart pgbouncer.service
    systemctl restart clickhouse-server.service
    systemctl reload nginx.service || service nginx force-reload

# Install PHP dependencies via Composer
[group('install')]
get-server-libs:
    cd "{{server_dir}}" && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction

# Clone client-side libraries and build Leaflet
[group('install')]
get-client-libs:
    #!/usr/bin/env bash
    set -euo pipefail
    mkdir -p "{{client_lib_dir}}"
    cd "{{client_lib_dir}}"
    [[ -d AdminLTE/.git ]] || git clone --branch v3.2.0 https://github.com/ColorlibHQ/AdminLTE
    [[ -d qrcodejs/.git ]] || git clone https://github.com/davidshimjs/qrcodejs
    [[ -d ace-builds/.git ]] || git clone https://github.com/ajaxorg/ace-builds/
    [[ -d Leaflet/.git ]] || git clone --branch v1.9.2 https://github.com/Leaflet/Leaflet
    cd Leaflet
    npm install
    npm run build

# Generate client config from sample (needs RBT_HOST)
[group('install')]
init-client-conf:
    #!/usr/bin/env bash
    set -euo pipefail
    if [[ -z "${RBT_HOST:-}" ]]; then
        echo "Error: RBT_HOST environment variable is not set" >&2
        echo "Example: export RBT_HOST=yard.example.org" >&2
        exit 1
    fi
    if [[ -f "{{client_config_dst}}" ]]; then
        echo "Client config already exists: {{client_config_dst}}"
        exit 0
    fi
    sed \
        -e "s|example\\.com|${RBT_HOST}|g" \
        -e "s|<!-- your asterisk server external ip here -->|${RBT_HOST}|g" \
        "{{client_config_src}}" > "{{client_config_dst}}"
    echo "Generated {{client_config_dst}} for host ${RBT_HOST}"

# Generate server config from sample (needs RBT_HOST)
[group('install')]
init-server-conf:
    #!/usr/bin/env bash
    set -euo pipefail
    if [[ -z "${RBT_HOST:-}" ]]; then
        echo "Error: RBT_HOST environment variable is not set" >&2
        echo "Example: export RBT_HOST=yard.example.org" >&2
        exit 1
    fi
    if [[ -f "{{server_config_dst}}" ]]; then
        echo "Server config already exists: {{server_config_dst}}"
        exit 0
    fi
    sed \
        -e "s|example\\.com|${RBT_HOST}|g" \
        -e "s|<!-- your asterisk server external ip (or domain name) here --!>|${RBT_HOST}|g" \
        -e "s|ntp:<-- your ntp server ip here -->|ntp:${RBT_HOST}|g" \
        -e '/"schema": "<!-- remove this line if you are not using schemas --!>",/d' \
        -e "s|syslog\\.udp:127\\.0\\.0\\.1:|syslog.udp:${RBT_HOST}:|g" \
        -e "s|http://127\\.0\\.0\\.1:45460|http://${RBT_HOST}:45460|g" \
        -e "s|http://127\\.0\\.0\\.1:46460|http://${RBT_HOST}:46460|g" \
        "{{server_config_src}}" > "{{server_config_dst}}"
    echo "Generated {{server_config_dst}} for host ${RBT_HOST}"

# Convert json5 configs to JSON
[group('install')]
strip-config:
    php "{{server_dir}}/cli.php" --strip-config

# Initialize databases, admin password, indexes and crontabs (needs RBT_ADMIN_PASSWORD)
[group('install')]
init-server-db:
    #!/usr/bin/env bash
    set -euo pipefail
    if [[ -z "${RBT_ADMIN_PASSWORD:-}" ]]; then
        echo "Error: RBT_ADMIN_PASSWORD environment variable is not set" >&2
        exit 1
    fi
    php "{{server_dir}}/cli.php" --init-db
    php "{{server_dir}}/cli.php" --init-clickhouse-db
    php "{{server_dir}}/cli.php" --admin-password="${RBT_ADMIN_PASSWORD}"
    php "{{server_dir}}/cli.php" --reindex
    php "{{server_dir}}/cli.php" --install-crontabs

# Create GridFS metadata index for TT templates
[group('install')]
create-index:
    php "{{server_dir}}/cli.php" files --create-index=metadata.type,filename

# Update to latest release (optional: --force --pre --version=TAG)
[group('ops')]
update *args:
    php "{{server_dir}}/cli.php" --update {{args}}

# Update to main tip
[group('ops')]
update-devel *args:
    php "{{server_dir}}/cli.php" --update --devel {{args}}

# Clear Redis cache and reindex API access
[group('ops')]
reindex:
    php "{{server_dir}}/cli.php" --reindex

# Exit maintenance mode
[group('ops')]
exit-maintenance:
    php "{{server_dir}}/cli.php" --exit-maintenance-mode
