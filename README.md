=== Plugin Name ===
* Contributors: Genyus
* Tags: contact form 7, cf7, Agile CRM
* Requires at least: 4.3
* Tested up to: 4.9.1
* Stable tag: 1.1
* License: Apache 2.0
* License URI: http://www.apache.org/licenses/LICENSE-2.0

Contact form 7 Agile CRM integration.

== Description ==

This plugin adds integration for Agile CRM to contact form 7. With this plugin it is possible to submit a contact to an external Agile CRM.

Per contact form you could enable Agile CRM integration. The submission of the contact form is then submitted to the Agile CRM REST api. That is why you should also enter an Agile CRM entity and an Agile CRM action. The data in the form should then match the data for the API. E.g. if you push a first_name to the api your field should be called first_name.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the Contact->Integration screen to configure the plugin
1. Enable Agile CRM on per form basis.


== Screenshots ==

1. This screenshot shows the settings screen
2. This screenshot shows the screen for enabling and setting up Agile CRM integration at a contact form.

== Changelog ==

= 1.1 =
* chore: Integrates WPS_Extend_Plugin. Replaces AGPL license with Apache.

= 1.0.1 =
* fix: Applies hard-coded tag correctly

= 1.0 =
* Initial commit
