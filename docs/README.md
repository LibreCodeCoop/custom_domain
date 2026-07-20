# Custom Domain documentation

## Setup

Install this app and enable the required apps from the Nextcloud root folder:

```bash
occ app:enable --force groupfolders
occ app:enable --force theming
```

## Customizations

Optional settings commonly used with this app:

```bash
occ config:system:set has_valid_subscription --value true --type boolean
occ app:enable --force registration
occ config:app:set registration show_fullname --value yes
occ config:app:set registration email_is_optional --value no
occ config:app:set registration disable_email_verification --value no
occ config:app:set registration enforce_fullname --value yes
occ config:app:set core shareapi_only_share_with_group_members --value yes
occ config:app:set files default_quota --value "50 MB"
occ config:app:set core shareapi_allow_share_dialog_user_enumeration --value yes
occ config:app:set password_policy minLength --value 8
occ config:system:set knowledgebaseenabled --value false --type boolean
occ config:system:set overwrite.cli.url --value "https://CustomDomain.coop"
mkdir -p data/appdata_`occ config:system:get instanceid`/custom_domain/skeleton
occ config:system:set skeletondirectory --value /data/appdata_`occ config:system:get instanceid`/custom_domain/skeleton
occ config:app:set theming name --value "Custom Domain"
occ config:app:set theming slogan --value "Made with ❤️"
occ config:app:set theming url --value "https://CustomDomain.coop"
occ config:app:set theming color --value "#0082c9"
occ config:app:set theming logoMime --value "image/png"
occ config:app:set theming backgroundMime --value "image/png"
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

## Ansible tests

Run the functional checks against a local Nextcloud instance:

```bash
ansible-playbook -i localhost, -c local tests/ansible/test_custom_domain.yml
```

The playbook verifies reachability, enables the app, creates test companies, checks JSON output, and cleans up the test domains.

## Runtime requirements

- `groupfolders`
- `theming`

The app checks these dependencies at runtime and refuses to run its company management commands if they are missing.
