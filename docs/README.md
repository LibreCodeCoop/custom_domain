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
The app checks these dependencies at runtime and refuses to run its company management commands if they are missing.
