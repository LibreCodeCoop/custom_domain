[![Start contributing](https://img.shields.io/github/issues/LibreCodeCoop/custom_domain/good%20first%20issue?color=7057ff&label=Contribute)](https://github.com/LibreCodeCoop/custom_domain/issues?q=is%3Aissue+is%3Aopen+sort%3Aupdated-desc+label%3A%22good+first+issue%22)

# Custom Domain

Use custom domains at Nextcloud instance

## Setup

* Install this app
* Configuration
* go to root folder of your nextcloud instance and run the follow commands:
```bash
# Group folders
occ app:enable --force groupfolders

## Customizations

Not mandatory, but maybe is important
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
## use custom domain insteadof localhost when use occ commands
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
* Inside the folder `appdata_<instanceId>/custom_domain/theming` you will need go create a folder with the domain of company
* Inside the folder of company, create the file `background` and `logo` without extension.
  > Logo need to be PNG and background need to be PNG  to follow the defined at `theming` app at `logoMime` and `backgroundMime` setting
* Refresh the cache of app data folder to update the metadata of new images:
  ```bash
  occ files:scan-app-data
  ```

## Contributing

[here](.github/CONTRIBUTING.md)
