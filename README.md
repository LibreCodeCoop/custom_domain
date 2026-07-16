[![Start contributing](https://img.shields.io/github/issues/LibreCodeCoop/custom_domain/good%20first%20issue?color=7057ff&label=Contribute)](https://github.com/LibreCodeCoop/custom_domain/issues?q=is%3Aissue+is%3Aopen+sort%3Aupdated-desc+label%3A%22good+first+issue%22)

# Custom Domain

Use custom domains on a Nextcloud instance by mapping a company code or subdomain to:

- a Nextcloud group
- a trusted domain entry
- a company-specific theme asset set

## Setup

* Install this app
* Enable the required apps from the Nextcloud root folder:

```bash
# Group folders
occ app:enable --force groupfolders

# Theming
occ app:enable --force theming
```

## Customizations

Optional, but commonly used with this app:

```bash
# Hide development notice
occ config:system:set has_valid_subscription --value true --type boolean

# registration
occ app:enable --force registration
occ config:app:set registration show_fullname --value yes
occ config:app:set registration email_is_optional --value no
occ config:app:set registration disable_email_verification --value no
occ config:app:set registration enforce_fullname --value yes
occ config:app:set core shareapi_only_share_with_group_members --value yes

occ config:app:set files default_quota --value "50 MB"

occ config:app:set core shareapi_allow_share_dialog_user_enumeration --value yes

# System settings
## Set the min length of password
occ config:app:set password_policy minLength --value 8
## Disable Nextcloud knowledge database (help)
occ config:system:set knowledgebaseenabled --value false --type boolean
## Use custom domain instead of localhost when using occ commands
occ config:system:set overwrite.cli.url --value "https://CustomDomain.coop"

# Skeleton directory
# First, go to root folder of Nextcloud
mkdir -p data/appdata_`occ config:system:get instanceid`/custom_domain/skeleton
occ config:system:set skeletondirectory --value /data/appdata_`occ config:system:get instanceid`/custom_domain/skeleton

# Theme
occ config:app:set theming name --value "Custom Domain"
occ config:app:set theming slogan --value "Made with ❤️"
occ config:app:set theming url --value "https://CustomDomain.coop"
occ config:app:set theming color --value "#0082c9"
## This is mandatory if you want to use custom logo and background by domain
occ config:app:set theming logoMime --value "image/png"
occ config:app:set theming backgroundMime --value "image/png"
```

## Theming

The app looks up theme files inside the app data folder, using this layout:

```text
appdata_<instanceId>/custom_domain/themes/<company-code>/core/img/
```

Supported files:

- `logo.png`, `logo.jpg`, `logo.svg`
- `favicon.png`, `favicon.jpg`, `favicon.svg`
- `background.png`, `background.jpg`, `background.svg`

There is also a fallback theme in:

```text
appdata_<instanceId>/custom_domain/themes/default/core/img/
```

The app bootstraps that default theme from the files in `themes/default/` during install and post-migration.

To refresh app data after changing files:

```bash
occ files:scan-app-data
```

## CLI

Available commands:

```bash
occ custom-domain:company:add <code> [--name <name>] [--domain <domain>] [--force]
occ custom-domain:company:disable <code>
occ custom-domain:company:list
```

Behavior:

- `add` creates or reuses a Nextcloud group named after the company code.
- `add` also adds the resulting domain to `trusted_domains`.
- `list` shows companies inferred from `trusted_domains`.
- `disable` removes the matching trusted domains for that company code.

## Runtime requirements

- `groupfolders`
- `theming`

The app checks those dependencies at runtime and refuses to run its company management commands if they are missing.

## Contributing

[here](.github/CONTRIBUTING.md)
