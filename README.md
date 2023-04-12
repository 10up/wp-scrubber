# 10up WP Scrubber

> This plugin provides a command-line interface for scrubbing sensitive user and comment data from a WordPress installation.

[![Support Level](https://img.shields.io/badge/support-beta-blueviolet.svg)](#support-level) [![GPLv2 License](https://img.shields.io/github/license/10up/wp-scrubber.svg)](https://github.com/10up/wp-scrubber/blob/develop/LICENSE.md)

## Installation

1. Clone or download the plugin files into your WordPress plugins directory.
2. Activate the plugin through the WordPress admin interface or via WP-CLI.
3. Set the `WP_ENVIRONMENT_TYPE` to `local` or `staging`.

## Usage

The plugin provides a WP-CLI command called `wp scrub all` that will scrub all user and comment data from the WordPress database. This command can only be run on non-production environments, unless overridden with `wp_scrubber_allow_on_production`.

To use the command, open up your terminal and navigate to your WordPress installation. Then run the following command:

```
wp scrub all
```

## Scrubbed Data

### Users

 * All passwords are replaced with `password`.
 * Emails are replace with dummy values.
 * `display_name` is replaced with `user_login` values.

### Comments

 * Comment and Comment Meta tables are completely emptied.

## Support Level

**Beta:** This project is quite new and we're not sure what our ongoing support level for this will be. Bug reports, feature requests, questions, and pull requests are welcome. If you like this project please let us know, but be cautious using this in a Production environment!

## Like what you see?

<a href="http://10up.com/contact/"><img src="https://10up.com/uploads/2016/10/10up-Github-Banner.png" width="850" alt="Work with us at 10up"></a>
