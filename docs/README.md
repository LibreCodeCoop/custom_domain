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

Supported files are `logo`, `favicon`, and `background` with `.png`, `.jpg`, or `.svg` extensions. The fallback theme is in `themes/default/core/img/` and is bootstrapped during install and post-migration.

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

The app checks these dependencies at runtime and refuses to run its company management commands if they are missing.
