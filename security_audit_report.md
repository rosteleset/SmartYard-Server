# Security Audit Report

## Summary

This report details the findings of a security audit conducted on the project. The audit involved static analysis of server-side PHP code, client-side JavaScript, Node.js services, Lua scripts, Asterisk configurations, and Docker configurations. Key tools used included PHPStan, NVD for dependency checking, `luacheck`, and `hadolint`. Several critical vulnerabilities were identified, including hardcoded secrets, outdated dependencies with known exploits (e.g., SSTI in EJS, SSRF in Axios), and insecure configurations for critical services like Asterisk AMI, Redis, and backend data sources. Automated ESLint scans for JavaScript and Node.js code could not be performed due to environmental limitations.

## Methodology

The audit followed these steps:
1.  **Server-Side PHP Analysis:**
    *   Reviewed `server/composer.json` for dependencies and checked them against the NVD.
    *   Performed static code analysis on the `server/` directory using PHPStan.
2.  **Credentials Management Review:**
    *   Manually inspected configuration files (e.g., `server/config/config.sample.json5`, `asterisk/manager.conf`) for hardcoded secrets and placeholder usage.
3.  **Client-Side JavaScript Analysis:**
    *   Manually identified JavaScript libraries in `client/js/`.
    *   Checked identified library versions against the NVD.
    *   Attempted to set up ESLint for static analysis but faced environmental constraints preventing its execution.
4.  **Node.js Services Analysis:**
    *   Identified Node.js services in `server/services/` by looking for `package.json` files.
    *   Checked dependencies listed in `package.json` files against the NVD.
    *   Attempted to set up ESLint for static analysis but faced environmental constraints.
5.  **Lua Script Analysis:**
    *   Performed static code analysis on Lua scripts in `asterisk/lua/` and `doc/drafts/` using `luacheck`.
6.  **Asterisk Configuration Review:**
    *   Manually reviewed key Asterisk configuration files in `asterisk/` (e.g., `manager.conf`, `extensions.lua`, `sorcery.conf`, `modules.conf`).
7.  **Docker Configuration Review:**
    *   Analyzed `server/services/sys_exporter/docker/node/Dockerfile` using `hadolint`.
    *   Reviewed `server/services/sys_exporter/docker-compose.yml`.

Limitations:
*   Could not perform dynamic analysis or penetration testing.
*   ESLint scans for client-side JavaScript and Node.js services were not performed due to environmental constraints.
*   The exact versions of some client-side JavaScript libraries could not be determined.
*   The actual version of the MongoDB server in use is unknown.

## Findings and Recommendations

Organize by area:

### 1. Server-Side PHP

*   **Dependency Analysis (`server/composer.json`):**
    *   Dependencies listed: `php: >=7.0`, `ext-json: *`, `ext-mbstring: *`, `ext-sockets: *`, `ext-pcre: *`, `ext-curl: *`, `ext-openssl: *`, `ext-tokenizer: *`, `ext-xml: *`, `ext-ctype: *`, `ext-mongodb: ^1.17`, `mongodb/mongodb: 2.0`, `elasticsearch/elasticsearch: v7.17.0`, `guzzlehttp/guzzle: ^7.0`, `monolog/monolog: ^2.0`, `phpmailer/phpmailer: ^6.1`, `firebase/php-jwt: ^6.10`, `setasign/fpdi: ^2.6`, `tecnickcom/tcpdf: ^6.7`, `google/cloud-storage: ^1.35`, `google/cloud-translate: ^1.17`, `google/cloud-speech: ^1.17`, `google/cloud-text-to-speech: ^1.9`, `php-amqplib/php-amqplib: ^3.0`, `aws/aws-sdk-php: ^3.299`, `microsoft/azure-storage-blob: ^1.5`, `alibabacloud/sdk: dev-master`, `alibabacloud/tea-oss-sdk: dev-master`, `yandex-cloud/core: dev-master`, `yandex-cloud/s3: dev-master`, `yandex-cloud/speechkit: dev-master`, `yandex-cloud/vision: dev-master`, `yandex-cloud/translate: dev-master`, `phpstan/phpstan: ^1.10`.
    *   Note: Ambiguity in `mongodb/mongodb:2.0`. This refers to a specific tag/branch of the `mongodb/mongo-php-driver-legacy` repository, which is a driver, not the MongoDB server version. The actual MongoDB server version used by the application is unknown and could pose a risk if outdated.
    *   Other dependencies: No critical vulnerabilities found for the specified versions using NVD lookups.
*   **Static Code Analysis (PHPStan - `server/`):**
    *   Result: 1953 errors (level 5).
    *   Critical Issues: Numerous instances of method calls on potentially null/mixed objects, undefined variables, undefined classes (e.g., `backend`, `backend_config`), issues with abstract method implementations (especially in `server/backends/tt/mongo/mongo.php` regarding `save_transobj` and `get_calls_obj_count`), and a missing `PHPGangsta_GoogleAuthenticator` class referenced in `server/api/two_factor_auth.php`.
    *   Recommendation: Prioritize fixing critical PHPStan errors, starting with those causing fatal errors or incorrect behavior. Investigate and integrate the missing `PHPGangsta_GoogleAuthenticator` library or replace it with a suitable alternative. Address the abstract method implementation issues.

### 2. Credentials Management

*   **Hardcoded Secrets:**
    *   Default PostgreSQL password (`rbt`) found in `server/config/config.sample.json5`.
    *   Default ClickHouse password (`qqq`) found in `server/config/config.sample.json5`.
    *   Hardcoded Asterisk Management Interface (AMI) secret (`sEcrEt`) in `asterisk/manager.conf`.
    *   Recommendation: Remove all hardcoded secrets from configuration files, including samples. Utilize environment variables, Docker secrets, or a dedicated secrets management solution (e.g., HashiCorp Vault). Default passwords, even in sample files, should be changed immediately if there's any chance they could be used as a base for production deployments.
*   **Placeholders:** Numerous placeholders (e.g., `__RABBITMQ_LOGIN__`, `__PG_HOST__`) in `server/config/config.sample.json5` are good practice for sample configurations, guiding users to replace them with actual values.

### 3. Client-Side JavaScript (`client/js/`)

*   **Dependency Analysis (Manual Version Check & NVD):**
    *   `json5.min.js` (JSON5 library): Version unknown. If prior to 2.2.2, potentially vulnerable to Prototype Pollution (CVE-2022-46175, High). Recommend verifying version and updating if necessary.
    *   `mqtt.min.js` (MQTT.js library): Version unknown. If version is 2.x and < 2.15.0, potentially vulnerable to Denial of Service (CVE-2017-10910). Recommend verifying version and updating.
    *   `phpjs.js`: Contains an MD5 implementation. Review its usage, especially if used for password hashing (MD5 is cryptographically broken for this purpose).
    *   `pwgen.js`: An old password generator script. Review the algorithm's strength and its usage context, especially if generating sensitive credentials. Modern, robust libraries are preferred.
    *   Several libraries with unknown versions: `idbkvstore.min.js`, `leaflet.markercluster.min.js`.
    *   Recommendation: Adopt a package manager (like npm or yarn) for client-side dependencies to manage versions effectively and facilitate vulnerability scanning. Update or replace libraries with known vulnerabilities.
    *   Other checked libraries (version identified or assumed from file content/context):
        *   `clipboard.js 2.0.11`: No NVD entries for this version.
        *   `Cropper.js 1.6.2`: No NVD entries for this version.
        *   `JsSIP 3.9.1`: No NVD entries for this version.
        *   `Favico.js 0.3.10`: No NVD entries for this version.
        *   `jsTree 3.3.17`: No NVD entries for this version.
        *   `Leaflet.AwesomeMarkers 1.0` (version inferred from common practice, actual not specified): No NVD entries.
        *   `linkifyjs 5.5.5` (actually linkify-string, version from package if it were used): No NVD entries.
        *   `DOMPurify 3.0.0` (version from a comment, likely accurate): No NVD entries.
*   **Static Code Analysis (ESLint):**
    *   Limitation: Automated ESLint scan could not be performed due to environmental constraints during the audit.
    *   Recommendation: Project owners should run ESLint with appropriate plugins (e.g., `eslint-plugin-security`) locally on all JavaScript code in `client/js/` and `client/modules/` to identify potential security issues.

### 4. Node.js Services (`server/services/`)

*   **Dependency Analysis (from `package.json` files and NVD):**
    *   **`sys_exporter` (`server/services/sys_exporter/package.json`):**
        *   `axios: ^1.7.2`: Vulnerable to CVE-2024-39338 (SSRF, High). Potentially CVE-2024-57965 (details pending). Recommend update to latest.
        *   Other dependencies: `express: ^4.19.2`, `figlet: ^1.7.0`, `gradient-string: ^2.0.2`, `prom-client: ^15.1.2`. No specific CVEs for these versions.
    *   **`intercom_provision` (`server/services/intercom_provision/package.json`):**
        *   `ejs: ^3.1.9`: Vulnerable to CVE-2022-29078 (Server-Side Template Injection - SSTI, Critical) and CVE-2024-33883 (Prototype Pollution, Moderate). Recommend update to >=3.1.10.
        *   `express: ^4.18.2`: Vulnerable to CVE-2024-29041 (Open Redirect, Medium). Recommend update to >=4.19.2.
        *   Other dependencies: `body-parser: ^1.20.2`, `digest-fetch: ^3.4.1`, `express-basic-auth: ^1.2.1`, `xml2js: ^0.6.2`. No specific CVEs for these versions.
    *   **`push` (`server/services/push/package.json`):**
        *   `body-parser: ^1.20.2`: Vulnerable to CVE-2024-45590 (Denial of Service via large bodies, High). Recommend update to >=1.20.3.
        *   Other dependencies: `express: ^4.18.3`, `firebase-admin: ^12.1.0`, `mqtt: ^5.5.0`, `redis: ^4.6.13`. No specific CVEs for these versions.
    *   **`mqtt` (`server/services/mqtt/package.json`):**
        *   `express: ^4.18.2`: Vulnerable to CVE-2024-29041 (Open Redirect, Medium). Recommend update to >=4.19.2.
        *   Other dependencies: `asterisk-manager: ^0.2.0`, `body-parser: ^1.20.2`, `fs: 0.0.1-security` (wrapper, not actual fs module), `mqtt: ^5.3.5`, `redis: ^4.6.13`. No specific CVEs for these versions.
    *   **`syslog` (`server/services/syslog/package.json`):**
        *   Dependencies: `concurrently: ^8.2.2`, `nodemon: ^3.1.0`, `syslog-server: ^1.1.1`. No specific CVEs for these versions.
    *   **`turn` (`server/services/turn/package.json`):**
        *   Dependencies: `node-libcurl: ^4.0.0`. No specific CVEs for this version.
*   **Static Code Analysis (ESLint):**
    *   Limitation: Automated ESLint scan could not be performed due to environmental constraints.
    *   Recommendation: Project owners should run ESLint with security-focused plugins locally on each Node.js service to identify potential coding vulnerabilities.

### 5. Lua Scripts (`asterisk/lua/`, `doc/drafts/`)

*   **Static Code Analysis (`luacheck`):**
    *   `luacheck` reported various warnings across multiple Lua files:
        *   Unused variables (e.g., `res` in `asterisk/lua/http_handler.lua`).
        *   Accessing undefined variables (e.g., `HTTP_CONFIG` in `asterisk/lua/http_handler.lua`, `agi` in `asterisk/lua/libtts.lua`).
        *   Use of non-standard global variables (e.g., `s`, `AGI` in `asterisk/lua/extensions.lua`).
        *   Lines longer than the limit.
        *   Unused loop variables.
    *   Recommendation: Review the full `luacheck` output. Prioritize fixing access to undefined variables as these can lead to runtime errors and unpredictable behavior. Clean up unused variables and address other warnings to improve code quality and maintainability.

### 6. Asterisk Configuration (`asterisk/`)

*   **AMI (`manager.conf`):**
    *   Enabled and bound to `127.0.0.1` (good for limiting local access).
    *   Secret is hardcoded (`sEcrEt`): Critical security risk. This secret should be managed externally (e.g., environment variable, vault) and be strong.
    *   The `asterisk` user has full `read/write` permissions: Ensure these extensive permissions are strictly necessary for its operations. Apply the principle of least privilege.
*   **Dialplan (`extensions.conf` is empty, `extensions.lua` is used):**
    *   The dialplan logic is primarily in `asterisk/lua/extensions.lua` and `asterisk/lua/libfunc.lua`.
    *   Highly reliant on an external `dm_server` and Redis for its operations.
    *   Communication with `dm_server` is configured via HTTP (e.g., `http://127.0.0.1:8088/api/v1/call/route`) as seen in `asterisk/lua/config.sample.lua`. This is a Critical risk as sensitive call routing information could be intercepted or modified. Must be upgraded to HTTPS with proper certificate validation.
    *   Redis connection appears to be unauthenticated (no password in `asterisk/lua/config.sample.lua`). This is a Critical risk, allowing unauthorized access to potentially sensitive call data or system control. Must be secured with a strong password and potentially network ACLs.
    *   MD5 is used for generating temporary TURN server credentials in `asterisk/lua/libturn.lua`. MD5 is considered cryptographically weak and should be replaced with a stronger algorithm (e.g., SHA-256) for any security-sensitive token generation.
    *   Recommendation: Secure all external communications: use HTTPS for `dm_server` with robust authentication and authorization. Secure Redis with a strong password and ACLs. Review the entire Lua dialplan for potential input sanitization issues from external sources (AMI, HTTP requests, call data) and ensure secure handling of feature codes and call parameters. Replace MD5 with a stronger hashing algorithm.
*   **PJSIP Configuration (`sorcery.conf`, `extconfig.sample.conf`):**
    *   PJSIP configuration (AORs, auth, endpoints) is fetched dynamically via `res_curl` from HTTP endpoints (e.g., `http://127.0.0.1/asterisk/pjsip/aor`, `http://127.0.0.1/asterisk/pjsip/auth`, `http://127.0.0.1/asterisk/pjsip/endpoint`) as specified in `asterisk/sorcery.conf` and implied by `asterisk/extconfig.sample.conf`.
    *   Critical: The use of HTTP for fetching PJSIP configurations is a major security risk. There is no visible authentication mechanism for these endpoints. The security of the web application serving these PJSIP configurations is paramount and currently insufficient.
    *   Recommendation: Secure these PJSIP configuration endpoints immediately. Use HTTPS with strong authentication and authorization. Review the security of the underlying database or system that provides this configuration data to the web application.
*   **Module Loading (`modules.conf`):** Good practice of disabling many unused modules (`noload => ...`) is observed, reducing the potential attack surface.
*   **Other:** `acl.conf` and `features.conf` are largely empty or default; their main logic might be handled elsewhere (e.g., within Lua scripts or the external `dm_server`).

### 7. Docker Configuration (`server/services/sys_exporter/`)

*   **Dockerfile (`docker/node/Dockerfile`):**
    *   `hadolint` reported no issues with the Dockerfile syntax or common best practices it checks.
    *   Manual Review: The container runs as the `root` user by default. This is a security risk.
    *   Recommendation: Add a non-root user in the Dockerfile and use the `USER` instruction to run the application as this less privileged user.
*   **`docker-compose.yml`:**
    *   Mounts an `.env` file for configuration: The security of the `.env` file on the host system is critical.
    *   Exposes `${APP_PORT}` to the host: Review if this port needs to be exposed externally or only to linked services. Apply appropriate firewall rules on the host.
    *   No resource limits (CPU, memory) are defined for the service. This could lead to resource exhaustion on the host if the container misbehaves.
    *   Recommendation: Harden permissions of the `.env` file on the host. For production environments, consider using Docker secrets for managing sensitive configuration data. Define resource limits (memory, CPU) for the container to prevent abuse. Ensure the container, as configured by the Dockerfile, runs as a non-root user.

## Overall Recommendations

*   **Prioritize Critical Fixes:** Address the critical vulnerabilities urgently. This includes:
    *   Server-Side Template Injection (SSTI) in EJS (`intercom_provision`).
    *   Server-Side Request Forgery (SSRF) in Axios (`sys_exporter`).
    *   Hardcoded secrets (AMI, sample configs).
    *   Insecure communication for Asterisk PJSIP configuration (HTTP, no auth).
    *   Insecure communication with `dm_server` (HTTP).
    *   Unauthenticated Redis access from Asterisk.
*   **Implement Robust Secrets Management:** Remove all hardcoded credentials. Adopt a secure method for managing secrets such as environment variables, Docker secrets, or a dedicated secrets management tool (e.g., HashiCorp Vault).
*   **Update Vulnerable Dependencies:** Systematically update all identified vulnerable dependencies in PHP, Node.js, and client-side JavaScript to patched versions. Establish a process for regular dependency review and updates.
*   **Conduct Local Static Analysis:** Project owners must perform thorough static analysis using ESLint (with `eslint-plugin-security`) for all client-side JavaScript (`client/js/`, `client/modules/`) and Node.js (`server/services/`) codebases.
*   **Secure External Integrations:** Perform a thorough security review of the external `dm_server` application, focusing on authentication, authorization, input validation, and secure communication (HTTPS).
*   **Strengthen Asterisk Security:**
    *   Secure AMI by using strong, externally managed secrets and applying the principle of least privilege.
    *   Replace MD5 usage in Lua scripts with stronger hashing algorithms.
    *   Ensure all external data sources (Redis, PJSIP config API) are authenticated and use encrypted transport.
*   **Adopt Docker Security Best Practices:**
    *   Ensure containers run as non-root users.
    *   Define resource limits for containers.
    *   Securely manage `.env` files or use Docker secrets.
*   **Address PHPStan Findings:** Systematically work through the PHPStan errors to improve code quality and reduce potential bugs and security flaws.
*   **Review Lua `luacheck` Warnings:** Address warnings from `luacheck`, particularly those related to undefined variables, to prevent runtime errors.

```
