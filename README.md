# WordPress Scrubber

> This WordPress plugin provides a command-line interface for scrubbing sensitive user and comment data from a WordPress installation.

[![Support Level](https://img.shields.io/badge/support-beta-blueviolet.svg)](#support-level) [![GPLv2 License](https://img.shields.io/github/license/10up/wp-scrubber.svg)](https://github.com/10up/wp-scrubber/blob/develop/LICENSE.md)

## Installation

1. Clone or download the plugin files into your WordPress plugins directory.
   1. Note: If on WordPress VIP, the plugin should be added to the `/client-mu-plugins` directory.
2. Activate the plugin through the WordPress admin interface or via WP-CLI.
3. Set the `WP_ENVIRONMENT_TYPE` to `local` or `staging`.

## Usage

The plugin provides a WP-CLI command called `wp scrub all` that will scrub all user and comment data from the WordPress database. This command can only be run on non-production environments, unless overridden with `wp_scrubber_allow_on_production`. You can also run `wp scrub users` or `wp scrub comments` to only scrub users or comments.

To use the command, open up your terminal and navigate to your WordPress installation. Then run the following commands:

```
wp scrub all
wp cache flush
```

When creating an export for local development, best practice is to export a scrubbed database from a lower environment:
 * Copy production into a lower environment (staging, develop, preprod, etc...)
 * Scrub the data on the lower environment with the `wp scrub all` command.
 * Export the scrubbed database.

Note: On WordPress VIP, scrubbing commands will occur automatically when copying from production to a lower environment.

## Scrubbed Data

### Users

 * All exisiting passwords are replaced with a random password.
 * Email, user_login and display_name are replaced with dummy values.

To only scrub users run the following command:

```
wp scrub users
```

### Comments

 * Comment and Comment Meta tables are completely emptied.
 To only scrub comments run the following command:

```
wp scrub comments
```

On a multisite, to scrub comments across all the sites you can run the following commands:

```
wp site list --field=url | xargs -n1 -I % wp --url=% scrub comments
wp site list --field=url | xargs -n1 -I % wp --url=% cache flush
wp cache flush --network
```

## CLI Arguments

 * `--allowed-domains` - Comma separated list of email domains. Any WordPress user with this email domain will be ignored by the scrubbing scripts. `10up.com` and `get10up.com` are ignored by default.
   * ex: `wp scrub all --allowed-domains=example.com,example.net`
 * `--allowed-emails` - Comma separated list of email addresses. Any WordPress user with this email will be ignored by the scrubbing scripts.
   * ex: `wp scrub all --allowed-emails=user1@example.com,user2@example.com`
 * `--ignore-size-limit` - Ignore the database size limit.
   * ex: `wp scrub all --ignore-size-limit

## Database Size Limit
WP Scrubber includes a database size limit of 2GB. This limit exists as a failsafe to prevent the scrubbing action from taking effect on large sites, unless the developer chosen to ignore the warning.

The core plugin is very quick and scales based on the number of users which need to be scrubbed. However, because there are additional hooks and filter which projects are encouraged to extend, cvustom queries which loop through post or postmeta can slow down the scrub operation considerably.

You can adjust the default size limit with `wp_scrubber_db_size_limit`.

## Extensibility

WP Scrubber includes several filters and actions which allows developers to hook into the scrubbing process and add their own rules.

### How can I add my own scrubbing rules?

Additional scrubbing commands can be added before or after the default commands with `wp_scrubber_before_scrub` and `wp_scrubber_after_scrub`.

### How do I allow this on production environments?

WP Scrubber uses `wp_get_environment_type()` to ensure it is not accidentally run on a production environment. By default, unless `WP_ENVIRONMENT_TYPE` is defined, WordPress will assume all environments are production environments.

It is encouraged that `WP_ENVIRONMENT_TYPE` be updated to `local` and `staging`, however if you do indeed need to run on production you can add a filter to `wp_scrubber_allow_on_production`, changing the value to `true`.

### How can I change which users are scrubbed?

In addition to CLI arguments, three filters are available to developers

1. `wp_scrubber_allowed_email_domains` - Allows for ignoring users based on their email domain. By default, `10up.com` and `get10up.com` are ignored.
2. `wp_scrubber_allowed_emails` - Allows for ignoring specific users based on their full email address. Helpful if you want to save certain users from an organization, but not all of them.
3. `wp_scrubber_should_scrub_user` - If you conditions are more complex, you can use this filter to check each user individually.

## PII

WP Scrubber scrubs PII based on where WordPress core stores data (users, comments, standard user/comment meta keys). It does not have knowledge of PII stored by third party plugins. Even when using this tool you should audit your database for third party PII.

## Definition of Beta

10up considers this tool production ready for 10up projects. Publicly we define it as beta because we are wary of people relying on this tool solely when third party software can store PII in unknown locations.

## Support Level

**Beta:** This project is quite new and we're not sure what our ongoing support level for this will be. Bug reports, feature requests, questions, and pull requests are welcome. If you like this project please let us know, but be cautious using this in a Production environment!

## Like what you see?

<p align="center">
<a href="http://10up.com/contact/"><img src="https://10up.com/uploads/2016/10/10up-Github-Banner.png" width="850"></a>
</p>
