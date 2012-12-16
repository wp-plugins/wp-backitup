=== Plugin Name ===
Contributors: jcpeden
Donate link: http://www.wpbackitup.com
Tags: backup, restore, clone, database, wp-content, files
Requires at least: 3.4
Tested up to: 3.5
Stable tag: 1.0.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WP BackItUp allows you to backup your database and wp-content folder. This allows you to quickly clone, backup and restore any of your Wordpress sites.

== Description ==

WP BackItUp uses nothing but PHP to allow you to backup and restore your Wordpress database, plugins, themes and uploads directories. You can create a 
backup of any site and, using WP BackItUp, quickly import your files, settings and content into a new site. 

== Installation ==

Installation of the plugin is straightforward:

1. Upload the directory `wp-backitup` to the `/wp-content/plugins/` directory.
1. Activate the plugin through the `Plugins` menu in WordPress.
1. Through the Wordpress dashboard, browse to Tools > WP BackItUp.

== Frequently Asked Questions ==


= Will the plugin work on shared hosting/sub domains/webhost xxx? =
Yes

= Will WP Backitup work on Windows hosting? =
Yes

= Are you going to be making progress bars both for backing up and restoring with this plugin? =
Not at this time.

= Can this plugin back to Amazon S3? =
Not at this time.

= Is there an auto back up schedule feature? =
Not at this time.

= Will the plugin work with Wordpres version x.x? =
The plugin works on the latest release of WordPress and is updated to function with all new releases.

= Can this backup one version of WordPress to a different version? =
No. It is absolutely critical that your WordPress versions are exactly the same.

= Will WP Backitup work on WordPress Multisite? =
It is untested with Wordpress multisite and probably will not work.

= Does the plugin copy the database details as well? =
Yes, a database dump is created with each backup.

= Can I make a basic WordPress site, with all my desired plugins and settings, make a few pages, setup permalinks, remove all the default junk and load in a basic themplate? =
Yes. WP Backitup can be used to create a good starting point for any and all sites you work on.

= Does WP Backitup need to be installed? =
Yes. You must install the WP Backitup plugin on the site you wish to backup and the site you wish to restore to. Its just a simple plugin.

= Does WP Backitup backup plugins settings or just the plugins themselves? =
WP Backitup creates a database dump and a backup of all your themes, plugins and uploads.

= Do you have any ideas about how large a blog is too big for WP Backitup to handle? =
I`ve tested up to 5 themes, 20 plugins and 100 posts/pages without any issues.

= Do you do regularly update this product to match with WP version updates? =
Yes.

== Screenshots ==
1. Once activated, the plugin loads a new menu into Tools > WP Backitup.
2. Simply click 'Export' to generate a backup of your site. The plugin will update you on its progress.
3. When the backup has been created, click the download link to access a zipped backup of your site.

== Changelog ==

= 1.0.6 =
* Initial free version of the plugin distributed on Wordpress. This version can only backup.

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

= 1.0.6 =
* Non-critical upgrade.

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