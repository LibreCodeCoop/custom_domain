# Custom Domain documentation

## Setup

Install this app and enable the required apps from the Nextcloud root folder:

```bash
occ app:enable --force groupfolders
occ app:enable --force theming
```

## Theming

Theme files are read from:

```text
appdata_<instanceId>/custom_domain/themes/<company-code>/core/img/
```

The company directory is created lazily. After running
`custom-domain:company:add`, access the company domain at least once for its
`themes/<company-code>/` directory to be created under app data.

Supported files are `logo`, `favicon`, and `background` with `.png`, `.jpg`, or `.svg` extensions. The fallback theme is in `themes/default/core/img/` and is bootstrapped during install and post-migration.

Administrators can configure these images from `Settings → Administration → Custom domain themes`. Select an image for each company and upload it; choosing `Use default` removes the company-specific image. The page creates the company theme directory when it is first opened.

Refresh app data after changing files:

```bash
occ files:scan-app-data
```

## CLI

```bash
occ custom-domain:company:add <code> [--name <name>] [--domain <domain>] [--force]
occ custom-domain:company:disable <code>
occ custom-domain:company:list
```

The `add` command creates or reuses a group and adds the domain to `trusted_domains`. `list` shows companies inferred from `trusted_domains`, and `disable` removes matching trusted domains.

## Runtime requirements

- `groupfolders`
- `theming`

## Deployment notes

After deploying the app, regenerate its Composer autoloader. The app uses an
authoritative classmap, so new controllers and settings classes are not
available until this step is completed:

```bash
cd /var/www/html/custom_apps/custom_domain
composer dump-autoload --no-dev --optimize
```

If the data directory was upgraded to Nextcloud 34, keep the deployment image
on the same major version, for example `NEXTCLOUD_VERSION=34-fpm`. Rebuilding
with an older image can trigger an unsupported downgrade and prevent Nextcloud
from starting.

The app checks these dependencies at runtime and refuses to run its company management commands if they are missing.
