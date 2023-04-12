# 10up WP Scrubber

# WordPress Scrubber Plugin

This plugin provides a command-line interface for scrubbing sensitive user and comment data from a WordPress installation.

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
