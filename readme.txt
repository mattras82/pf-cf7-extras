=== PublicFunction CF7 Extras Plugin ===
Tested up to: 5.7
Requires at least: 4.8
License: GPL-3.0+
License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 
WordPress plugin for adding additional functionality to the Contact Form 7 plugin. This includes extra validation, dynamic asset enqueueing, custom data sets for form fields, and more.

== Description ==

WordPress plugin for adding additional functionality to the Contact Form 7 plugin. This includes extra validation, dynamic asset enqueueing, custom data sets for form fields, and more.

This plugin uses session variables to track a user's HTTP Referrer and visited path, which are then added as values to every Contact Form 7 submission.

PPC Keyword tracking can be configured in the settings menu in the WordPress Admin Dashboard.

Shortcodes are added for easy display of the additional values in CF7 email templates, and this plugin hooks into the [CFDB plugin](https://github.com/mattras82/contact-form-7-to-database-extension) to add the values to the database.

== Changelog ==

= 1.0.5 =

Released on 28 Jul 2022

 - Warning Fix: Fixing PHP 8.0 warning in SingletonTrait.php
 - Fix: CF7 plugin changed how form properties are constructed, so our PF Settings page has been updated accordingly
 - Enhancement: Adding ability to change the PF Settings title
 - Bug Fix: Fixing display attribute bug in Settings.php

= 1.0.4 =

Released on 29 Apr 2021

 - Bug Fix: Fixing PHP warning in Validation.php
 - Update: Updating file upload logic to account for new file array setup in CF7
 - Update: Adding `user_ppc` special mail tag

= 1.0.3 =

Released on 11 Jan 2021

 - New Feature: Adding custom form settings tab
 - New Feature: Adding Grouped Select & Multi File input types
 - Security: Adding support for file uploads outside the site root
 - Enhancement: Adding readme.txt file

= 1.0.2 =
- Bug fixes for `session_start()` not running & PHP errors

= 1.0.1 =
- Adding Includes fix, README clarification, removing unneeded Geolocation functionality

= 1.0.0 =
- Initializing plugin.