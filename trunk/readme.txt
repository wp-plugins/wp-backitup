=== Plugin Name ===
Contributors: jcpeden
Donate link: http://www.wpbackitup.com
Tags: backup, restore, clone, database, wp-content, files
Requires at least: 3.4
Tested up to: 3.4
Stable tag: 3.4
License: Personal/Premium (Check your WPBackItUp membership)

WP BackItUp allows you to backup your database and wp-content folder. This allows you to quickly clone, backup and restore any of your Wordpress sites.

== Description ==

WP BackItUp uses nothing but PHP to allow you to backup and restore your Wordpress database, plugins, themes and uploads directories. You can create a 
backup of any site and, using WP BackItUp, quickly import your files, settings and content into a new site. 

== Installation ==

Installation of the plugin is straightforward:

1. Upload the directory `wp-backitup` to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Through the Wordpress dashboard, browse to Tools > WP BackItUp.

== Frequently Asked Questions ==

= Will this work with subdomains? =
Yes, but please test it first.

= Will the plugin work on shared hosting? =
Yes

= Are there any hosts this plugin won’t work with? =
None that I am aware of.

= Will WP BackItUp work on Windows hosting? =
WP BackItUp was developed in a Windows environment so it should work with your Windows hosting.

= Can I install this on several of my computers? =
Yes

= Does WP BackItUp need to be removed when selling a cloned blog if I have a personal version license? =
Yes, the personal license entitles you to use on any sites that you own.

= Why do I need a developer’s license instead of the personal license? =
You can sell a site with personal license but you must remove WP BackItUp first. The developer’s license allows you to keep WP BackItUp installed for your clients/buyers, adding value to your sale.

= Are you going to be making progress bars both for backing up and restoring with this plugin? =
This may feature in the next version.

= Can this plugin back to Amazon S3? =
Not at this time.

= Can this plugin backup to the web host? =
Not at this time.

= Is there an auto back up schedule feature? =
Not at this time.

= Will the plugin work with Wordpres version x.x? =
The plugin works on the latest release of WordPress and is updated to function with all new releases.

= Can this backup one version of WordPress to a different version? =
No. It is absolutely critical that your WordPress versions are exactly the same.

= Will WP BackItUp work on WordPress Multisite? =
It should work with individual sites but is untested.

= Does this plugin clone sites? =
Yes, it can be used very easily to clone sites.

= Does the plugin copy the database details as well? =
A wordpress installation to restore to, must already have a database to function. Once you copy the information to the receiving DB, WP BackItUp will overwrite anything with the backed-up settings.

= I want to backup site A with WordPress version 2.8 and restore the backup to site B with WordPress 3.0 but the latest version is 3.2. How can I do this? =
If you are worried about the function of plugins you are using, disable them, run WP BackItUp, import your new site to the latest version of WordPress and renable each plugin to check their function. For security reasons you should always keep your site and plugins up-to-date.

= Can I make a basic WordPress site, with all my desired plugins and settings, make a few pages, setup permalinks, remove all the default junk and load in a basic themplate? =
Yes. WP BackItUp can be used to create a good ‘starting point’ for any and all sites you work on.

= Does WP BackItUp need to be installed? =
Yes. You must install the WP BackItUp plugin on the site you wish to backup and the site you wish to restore to. Its just a simple plugin.

= Does WP BackItUp backup plugins settings or just the plugins themselves? =
WP BackItUp will (optionally) backup posts, pages, users, plugins and settings, themes and settings and site settings.

= Do you have any ideas about how large a blog is “too big” for WP BackItUp to handle? =
I’ve tested up to 1.2 gigs without any issues.

= Do I need to deactivate my plugins before I use the tool? =
The plugin might not work if you are running any security plugins designed to lock down your files or keep things out. Try disabling them before backing up if you are running into problems.

= Do I need to have WordPress installed at the receiving site before running WP BackItUp? =
WP BackItUp does not install WordPress. You need to have a WordPress installation set up and the plugin installed in order to restore your backup. However, you can create backups with just a single installation of WordPress.

= Aren’t there similar products like this already? =
Yes, plenty but they aren’t as easy to use. The plugin requires no additional setup after activation and no access to FTP or cPanel.

= Do you do regular updates to this product to match with WP version updates? =
Yes.

== Changelog ==

= 1.0.5 =
* Modified backup to use AJAX and restore to use AJAX-like functionality. Added read-write for options so they are saved to DB on exit.

= 1.0.4 =
* Reduced the size of the plugin by re-using code. Added support for multiple table prefixes and media library import.

= 1.0.3 =
* Removed redundant code, allowed plugin to work with multiple table prefixes and user IDs other than 1.

= 1.0.2 =
* Fixed backup/restore function of database, plugins and themes dir. Removed PHP error notices if options are not set on admin page

= 1.0.1 =
* Increased PHP timeout to 900 seconds (5 minutes).

= 1.0 =
* Plugin released.

== Upgrade Notice ==

= 1.0.5 =
* Critical upgrade: More stable, increased flexibility and power.

= 1.0.4 =
* Critical upgrade: Many people have had trouble prior to this release. Flexibility has been increased and the plugin is now more stable and lightweight.

= 1.0.3 = 
* Recommended upgrade: The plugin is more flexible and offers more helpful status and error messages.

= 1.0.2 =
* Critical upgrade: Plugin does not work on most systems without this upgrade

= 1.0.1 =
* This version increases PHP timeout if possible (upgrade if you are having timeout errors).

= 1.0 =
Initial version of the plugin.