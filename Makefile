# Makefile
# Still experimental

# Variables
APP_DIR = /opt/rbt
SERVER_LIB_DIR = /opt/rbt/server/lib
CLIENT_LIB_DIR = /opt/rbt/client/lib
MONGO_DIR = /opt/rbt/server/mzfc/mongodb
CLIENT_CONFIG_SRC = /opt/rbt/client/config/config.sample.json
CLIENT_CONFIG_DST = /opt/rbt/client/config/config.json
SERVER_CONFIG_SRC = /opt/rbt/server/config/config.sample.json
SERVER_CONFIG_DST = /opt/rbt/server/config/config.json

# Default task
.DEFAULT_GOAL := help

# Targets
help: ## Show this help message
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-16s\033[0m %s\n", $$1, $$2}'

all: ## Execute all setup tasks
	$(MAKE) get-server-libs get-client-libs init-client-conf init-server-conf init-server-db

get-server-libs: ## Clone server-side libraries
	cd $(SERVER_LIB_DIR) && \
	git clone https://github.com/PHPMailer/PHPMailer && \
	git clone https://github.com/ezyang/htmlpurifier && \
	git clone -b 1.7.x https://github.com/erusev/parsedown && \
	git clone https://github.com/PHPGangsta/GoogleAuthenticator &&\
	cd $(MONGO_DIR) && \
	composer require mongodb/mongodb --no-interaction

get-client-libs: ## Clone client-side libraries and build Leaflet
	cd $(CLIENT_LIB_DIR) && \
	git clone --branch v3.2.0 https://github.com/ColorlibHQ/AdminLTE && \
	git clone https://github.com/davidshimjs/qrcodejs && \
	git clone https://github.com/loadingio/loading-bar && \
	git clone https://github.com/ajaxorg/ace-builds/ && \
	git clone https://github.com/Leaflet/Leaflet && \
	cd Leaflet && npm install && npm run build

init-client-conf: ## Copy client configuration example
	cp $(CLIENT_CONFIG_SRC) $(CLIENT_CONFIG_DST)

init-server-conf: ## Copy server configuration example
	cp $(SERVER_CONFIG_SRC) $(SERVER_CONFIG_DST)

init-server-db: ## Initialize configuration and database, set ENV "export RBT_ADMIN_PASSWORD=<your very secret admin password>"
	@if [ -z "$$RBT_ADMIN_PASSWORD" ]; then \
		echo "Error: RBT_ADMIN_PASSWORD environment variable is not set"; \
		exit 1; \
	fi; \
	php /opt/rbt/server/cli.php --init-db && \
	php /opt/rbt/server/cli.php --admin-password=$$RBT_ADMIN_PASSWORD && \
	php /opt/rbt/server/cli.php --reindex && \
	php /opt/rbt/server/cli.php --init-clickhouse-db && \
	php /opt/rbt/server/cli.php --install-crontabs
