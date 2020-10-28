# MailChimp plugin for glFusion
This plugin allows site users to subscribe and unsubscribe from a specified
MailChimp mailing list via profile updates.

Uses the Mailchimp API version 3.0

## Features:
- New members can be automatically or optionally subscribed at registration
- Anyone can subscribe through a PHP block
- Cache update to sync local user accounts with MailChimp
- MailChimp update to subscribe all local users.
- Merge Fields can be obtained from other plugins, e.g. Membership.
  - Plugins should implement a `plugin_getMergeFields_<plugin_name>` function
    which accepts the user ID as the only argument. This should return an array
    containing the user ID and an array of name=value pairs for merge fields.
  - If appropriated, plugins can also define `plugin_getItemInfo_<plugin_name>()`
    to accept `merge_fields` as part of the request and return the same array as
    above. This is called from `plugin_itemSaved_mailchimp()` when a plugin's
    item is saved.
  - Works with version 0.2.0 or later of the Membership plugin.

## Included Modules
- Mailchimp API class by Drew McLellan <drew.mclellan@gmail.com>
  - https://apidocs.mailchimp.com/api/downloads/
