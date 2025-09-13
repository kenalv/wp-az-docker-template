# WP Azure Docker Template

Production-ready Dockerized WordPress template for Azure App Service. This repository demonstrates a simple, secure, and reproducible pattern:

- Use the official WordPress Docker image as the base.
- Manage themes and plugins in the repository (copied into the image at build time).
- Offload media to Azure Storage or mount an Azure File Share for persistence.
- Use Azure Database for MySQL Flexible Server with SSL and Azure Cache for Redis (TLS).
- Build images in GitHub Actions and push to GitHub Container Registry (GHCR). Deploy from GHCR to Azure App Service.

This README documents prerequisites, environment variables, secrets, build & deploy flow, operational notes, and troubleshooting tips.

**Important:** Never commit secrets into the repository. Use GitHub Secrets, Azure App Service Configuration or Azure Key Vault.

**Table of Contents**

- Prerequisites
- Repository layout
- Environment variables (App Service)
- Secrets and GitHub Actions
- Deployment (build → GHCR → Azure App Service)
- Plugins, themes & media storage guidance
- Health checks and monitoring
- Troubleshooting

Prerequisites
- Azure subscription with permissions to create resources.
- Azure resources required:
  - Azure Database for MySQL Flexible Server (supported version for WordPress, enable SSL enforcement).
  - Azure Cache for Redis (Premium/Standard supporting TLS; Azure requires TLS on port 6380).
  - Azure Storage Account (Blob for uploads offload, optional File Share for mounting `wp-content/uploads`).
  - Azure App Service (Linux container) — App Service Plan minimum B1 recommended for production workloads.
- GitHub account and repository access.
- GitHub Packages/Container Registry (GHCR) access for private/public images.

Repository Layout

- `Dockerfile` — builds the image from `wordpress:6.8.2-php8.3-apache`, installs `redis` PECL, copies `config/*` and `src/` into the image.
- `config/` — application configuration files (custom `php.ini`, `apache.conf`, `wp-config.php` template, health/status pages, CA certificate path expectation).
- `scripts/` — optional init scripts (current image uses official WordPress startup; init scripts removed for simplicity).
- `src/` — repository-managed `wp-content` assets; put `plugins/` and `themes/` here to include them in the image.

Environment variables (set in Azure App Service → Configuration)

Set these Application Settings (do not store secrets in code):

- Database
  - `WORDPRESS_DB_HOST` : `<your-mysql-host>:<port>` (e.g., `mydb.mysql.database.azure.com:3306`)
  - `WORDPRESS_DB_NAME` : `<database name>`
  - `WORDPRESS_DB_USER` : `<db user>` (use the user@servername form if required)
  - `WORDPRESS_DB_PASSWORD` : `<db password>`
  - `MYSQL_SSL_CA_PATH` : optional path to CA cert inside the container (if you add CA cert to image)

- WordPress salts
  - `AUTH_KEY`, `SECURE_AUTH_KEY`, `LOGGED_IN_KEY`, `NONCE_KEY`, `AUTH_SALT`, `SECURE_AUTH_SALT`, `LOGGED_IN_SALT`, `NONCE_SALT`
  - You can store those as App Service settings or use an automated secret provider.

- Redis (Azure Cache for Redis)
  - Option A (single URL): `REDIS_URL` = `rediss://:password@myredis.redis.cache.windows.net:6380`
  - Option B (separate): `AZURE_REDIS_HOST`, `AZURE_REDIS_PORT` (usually `6380`), `AZURE_REDIS_PASSWORD`
  - `WP_REDIS_PREFIX` : optional prefix for keys (defaults to repository name)

- Azure Storage (optional for media offload)
  - `AZURE_STORAGE_ACCOUNT` : storage account name
  - `AZURE_STORAGE_CONTAINER` : container name (e.g., `wp-uploads`)
  - `AZURE_STORAGE_KEY` : storage account key (consider using Key Vault or Managed Identity)
  - `AZURE_STORAGE_CONNECTION_STRING` : alternative to `AZURE_STORAGE_KEY`

- App behavior
  - `WP_DEBUG` : `0` or `1`
  - `WP_ENV` : `production` (recommended for prod configuration)

Secrets and GitHub Actions

- GitHub Secrets used by CI (store in repository Settings → Secrets):
  - `GHCR_TOKEN` : Personal access token or PAT with `write:packages` scope (used to push image to GHCR)
  - `AZURE_WEBAPP_PUBLISH_PROFILE` : App Service publish profile XML (used by `azure/webapps-deploy` action)
  - `AZURE_CREDENTIALS` (optional) : JSON used by `azure/login` if you prefer `az cli` steps

CI/CD: Build and Deploy

Typical flow (GitHub Actions):

1. On push to `main` (or release tag), GitHub Actions builds Docker image.
2. Action logs in to GHCR using `GHCR_TOKEN` and pushes image as `ghcr.io/<owner>/<image>:<tag>`.
3. Action deploys to Azure App Service using `AZURE_WEBAPP_PUBLISH_PROFILE` or `azure/webapps-deploy` action.

Example environment variables used in the workflow (these should be set in `env` or repository secrets):

- `CONTAINER_REGISTRY`: `ghcr.io`
- `IMAGE_NAME`: `ghcr.io/<owner>/<repository>`
- `AZURE_WEBAPP_NAME`: your App Service name

Notes about the image build
- Keep the image build simple: do not download third-party plugins during the image build. Instead:
  - Place required plugins/themes under `src/plugins/` and `src/themes/` in the repository and the Dockerfile will COPY them into the image (so plugis must be in repository).
  - For plugin activation, use a mu-plugin or a CI step that runs WP-CLI to activate plugins on the target environment.

Plugins, Themes & Media Storage Guidance

- Plugins & Themes
  - Recommended: treat plugins/themes as code. Add them to `src/plugins/` or `src/themes/` and commit to the repository.
  - Avoid downloading zips during Docker builds — network or remote site failures make builds brittle.
  - If you need runtime installs from the WP Admin, ensure you have a persistent `uploads` and plugin/theme storage (mount or offload).

- Media storage (uploads)
  - Option 1 (recommended): Offload media to Azure Blob Storage using a plugin (e.g., `windows-azure-storage` or a maintained alternative). Store storage credentials as App Service settings or use Managed Identity.
  - Option 2: Mount an Azure File Share to `/var/www/html/wp-content/uploads` on the App Service container so uploads persist across container restarts. Be aware of performance and locking limitations.

Troubleshooting

- Missing WordPress files at startup
  - Ensure you use the your image . The image places core files in `/var/www/html`.

- Database connection errors
  - Verify `WORDPRESS_DB_HOST`, `WORDPRESS_DB_USER`, `WORDPRESS_DB_PASSWORD`, and `WORDPRESS_DB_NAME` are correct.
  - Azure MySQL server enforces SSL (!! AZURE ENFORCES SSL !!), so Dockerfile already installs the CA certificate into the image and set `MYSQL_SSL_CA_PATH` to point at it. The `wp-config.php` in `config/` contains logic to use an SSL CA if present.


- Redis problems
  - Azure Redis requires TLS. Provide `AZURE_REDIS_HOST`, `AZURE_REDIS_PORT=6380` and `AZURE_REDIS_PASSWORD`.
  - The included `wp-config.php` will detect TLS and set `WP_REDIS_SCHEME` accordingly.

Operational Checklist (before first push)

1. Create Azure resources: MySQL Flexible Server, Redis Cache, Storage Account, App Service (Plan >= B1).
2. Populate `env.azure.example.json` with variable names and values (without secrets) and copy into App Service Configuration with `Advanced edit`.
3. Add secrets to GitHub (GHCR_TOKEN, AZURE_WEBAPP_PUBLISH_PROFILE).
4. Add required plugins/themes into `src/plugins/` and `src/themes/` and push them to repo.
5. Push to GitHub main/master; verify the GitHub Action builds the image and pushes to GHCR.
6. Confirm App Service pulls the image and containers start. Visit the site and complete WordPress install if required.

Appendix: Example `env.azure.example.json`

{
  "WORDPRESS_DB_HOST": "mydb.mysql.database.azure.com:3306",
  "WORDPRESS_DB_NAME": "wordpress",
  "WORDPRESS_DB_USER": "wpadmin@mydb",
  "WORDPRESS_DB_PASSWORD": "<REDACTED>",
  "REDIS_URL": "rediss://:password@myredis.redis.cache.windows.net:6380",
  "AZURE_STORAGE_ACCOUNT": "mystorageaccount",
  "AZURE_STORAGE_CONTAINER": "wp-uploads",
  "WP_DEBUG": "0",
  "WP_ENV": "production"
}

Contributing

- Add or update plugins/themes under `wp-content/` and submit a PR. Keep changes focused and test locally using Docker Desktop before pushing.

Contact

If you want, I can:

- Add a GitHub Action workflow file for building and publishing the image.
- Create a short PowerShell snippet to set App Service settings from `env.azure.example.json`.
- Add instructions to auto-activate plugins using a mu-plugin or WP-CLI in CI.

Would you like me to also create the GitHub Actions workflow and an `env.azure.example.json` file in this repo?
