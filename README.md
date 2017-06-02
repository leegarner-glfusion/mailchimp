# MailChimp plugin for glFusion
This plugin allows site users to subscribe and unsubscribe from a specified
MailChimp mailing list via profile updates.

Uses the Mailchimp API version 3.0

## Features:
- New members can be automatically or optionally subscribed at registration
- Anyone can subscribe through a PHP block
- Cache update to sync local user accounts with MailChimp
- MailChimp update to subscribe all local users. Segment is updated from
the Membership plugin if it is installed.

## Included Modules
- Mailchimp API class by Drew McLellan <drew.mclellan@gmail.com>
- https://apidocs.mailchimp.com/api/downloads/
