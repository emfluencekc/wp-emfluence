=== Plugin Name ===
Tags: email, email marketing, emailmarketing, emfluence, api, marketing automation, widget, email widget, email signup, mailing list, newsletter, form, automation
Requires at least: 3.0.1
Tested up to: 4.7.4
Stable tag: 2.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easily add forms to your website for contacts to add or update their details in your emfluence Marketing Platform account.


== Description ==

If you're a user of the emfluence Marketing Platform, this plugin is for you!

Easily add any number of widgetized forms to your website. Each widget can have different input fields and add contacts
to different contact groups.

If you're not a user of our marketing platform, <a href="https://www.emfluence.com/about-us/contact-us">contact us now</a> to get started with the best digital marketing platform around.

Note that you'll likely want to tailor the forms to your theme using some CSS. You can also template the success message
by copying the theme/success.php file from this plugin to your theme, if you wish (or just type out a success message
in widget settings).

Want to change how this plugin works, or add to it? Fork it on GitHub!
https://github.com/emfluencekc/wp-emfluence


== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wp-emfluence` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the Settings->emfluence Marketing Platform screen to enter an API access token. (If you don't have an access token, contact our support team.)
4. Add widget forms via the Appearance->widgets screen. Each widget has settings for display, groups and contact fields.


== Screenshots ==

1. Widget edit screen. Enter your widget title and description, customize your Submit Button text,
and add your own success message (which will appear after the information is submitted).
2. Widget edit screen. Choose what groups your new contacts should be added to. The group names
can be found in your Platform Account.
3. Widget edit screen. Choose from the standard set of fields (First Name, Last Name, City, State,
etc.) to include on your form. You can also choose any custom fields to add to your form. Enter
the custom field number (1 – 250) that you would like to add data to. Then customize the Required
Message. This displays the error message if the data isn’t entered. Add a label and choose the
input type. NOTE: Be sure your input type matches your data type for your custom field. For example,
if your Custom01 is set to a date field inside the emfluence Marketing Platform, then be sure to
choose date as your type for your field on your widget too.


== Changelog ==

= 2.2.1 =

* Fail more gracefully in the admin area if API token is not available.

= 2.2 =
* Add honeypot to cut down on spam submissions.
* Add filters and actions to widget form display:
* new filter 'emfl_widget_validate'
* new action 'emfl_widget_top_of_form'
* new action 'emfl_widget_before_submit'

= 2.1 =
* Admins can choose to have submissions also sent to a notification email address.

= 2.0 =
* Contacts are added to any groups selected by the admin. Private groups can be selected.
* Support for more contact fields, including all custom variables.
* Support for field types.
* Revamp of widget settings UI.

= 1.0 =
* Contacts can add themselves to public groups that they select.
