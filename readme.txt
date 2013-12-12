=== Plugin Name ===
Contributors: Sovit Tamrakar
Tags: Post, Posts, Featured Post, Featured, Featured Custom Posts
Requires at least: 2.9.2
Tested up to: 3.5
Stable tag: 2.0.1
License: GPLv2 or later

Featured Post Plugin for Wordpress.

== Description ==
Plugin for featured wordpress posts. This is a cool plugin that makes it easier to mark posts as featured posts (not using specific categories) and simple markup to show theme from your theme file.

Add `<?php query_posts($query_string."&featured=yes"); ?>` before the post loop starts and manage the featured posts from the post edit list.

Now added widget for listing featured post in sidebar widgets with custom number of post.

*Supports Custom Post Type*

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to post list are then choose your featured post.
4. Put the code `<?php query_posts($query_string."&featured=yes"); ?>` before the post loop starts where you want to showt he featured posts.
5. Put a widget on sidebar for listing featured post in unordered list
6. NOTE: ****Please just after the loop you have called the custom query <?php query_posts(*."&featured=yes"); ?> don't forget to add wp_reset_query(); after the loop, else you will have all other normal post loops showing the featured posts.

== Upgrade Notice ==

= 2.0.1 =
Fixed Some Minor Bugs
= 2.0 =
Did major update to work with Worpdress Version 3.5
= 1.4.1 =
Fixed some other minor bug with widget (Just for some more features updates)
= 1.4.0 =
Fixed Some Minor bugs with featured post management (I really didn't want to do it)
= 1.3.1 =
Fixed some bug with widget and backend post section (I wanted to work it out)
= 1.3 =
Added multi-instance widget(I am not sure if this was needed)
= 1.2.1.0 =
Added Widget Sidebar for featured post (Just a dumb update)
= 1.0 =
Frist stable version 1.0 (Just forget this version)

== Frequently Asked Questions ==
= Why was this plugin created? =
This plugin was created to for the featured post management. Its easy and any minor wordpress developer or user can use and integrate into his wordpress theme with ease.

= When I use this plugin to show featured post even when i am not adding "featured=yes" before the loop? =
This is not a common problem, but this occurs when you are have called query_posts(***."featured=yes"); before the normal template loop like in category.php or archive.php or even index.php. So, its a good practice to add a wp_reset_query(); after each custom query you make adding "featured=yes". So why not add the wp_reset_query(); after the loop of featured posts.

= What is this plugin exactly? =
As mentioned earlier, this is just a simple featured posts management plugin that adds the post meta data as featured=yes for the particular post marked as featured.




== Screenshots ==

1. This is how it looks like in post edit list page of wp-admin where you can mark or un-mark posts as featured.
2. Screenshot of widget control section to add Featured Post widget.
3. This is how you pass featured post variable to post loop to list featured post.

== Changelog ==
1.4.0
I would hate all old versions of this plugin. i hate them!! :(