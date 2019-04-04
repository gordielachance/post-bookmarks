=== Post Bookmarks ===
Contributors:grosbouff
Donate link:http://bit.ly/gbreant
Tags: links,quick links,related links,custom links, post links
Requires at least: 3.5
Tested up to: 5.1.1
Stable tag: trunk
License: GPLv2 or later

Manage links attached to a post through a metabox, and setup how they are displayed in your posts...  With a link favicon.

== Description ==

Manage links attached to a post through a metabox, and setup how they are displayed in your posts...  With a link favicon.

* Nice GUI
* Options page
* Default link title and image based on its url (ajaxed)
* Sort links by names or a custom order
* Powered by the WP core bookmark functions
* actions and filters hooks for developpers

= Contributors =

Contributors [are listed here](https://github.com/gordielachance/post-bookmarks/contributors)

= Notes =

For feature request and bug reports, [please use the forums](http://wordpress.org/support/plugin/post-bookmarks#postform).

If you are a plugin developer, [we would like to hear from you](https://github.com/gordielachance/post-bookmarks). Any contribution would be very welcome.

== Installation ==

1. Upload the plugin to your blog and Activate it.

== Frequently Asked Questions ==

= How can I get only the links attached to a post ? =
Use the function **Post_Bookmarks::get_post_links($post_id,$args)**.
If you want to display those links use the function **Post_Bookmarks::get_link_list($post_id,$args)**.

The two parameters are optionals.
$args should be an array of parameters - the same you would set when using the native [get_bookmarks()](https://codex.wordpress.org/Function_Reference/get_bookmarks) function.  Example : 

`<?php
$args = array('category'=>12);
Post_Bookmarks::get_link_list(null,$args);
?>`

= How can I get only the links NOT attached to a post ? =
Use the the native function **get_bookmarks()** with the 'post_bkmarks_exclude' parameter.  Example : 

`<?php
get_bookmarks( array('post_bkmarks_exclude'=>true) );
?>`

= How can I style a link based on its domain, using CSS ? =

Use the *data-cp-link-domain* attribute, for example : 

`li.post-bkmarks[data-cp-link-domain='wikipedia.org'] .post-bkmarks-favicon {
    background-image: url('https://wikipedia.org/static/favicon/wikipedia.ico');
}`

= How can I change the way links are displayed ? =

Use the filter *post_bkmarks_single_link_html* (located in the function **Post_Bookmarks::get_link_html()**), for example : 

`<?php

function custom_output_single_link($output,$link){
    return $output;
}

add_filter('post_bkmarks_single_link_html','custom_output_single_link',10,2);

?>`

== Screenshots ==

1. Post Bookmarks metabox in the backend editor
2. Links displayed under a post
3. Settings page

== Changelog ==

= 2.1.7 =
* cleaned and updated doc.

= 2.1.6 =
* Fixed ajax actions
* links IDs order is now saved (only) with ajax each time links are reordered, and is a standalone PHP action

= 2.1.4 =
* Fixed ajax actions
* Updated Font Awesome
* debug_log() function
* better way of saving links / handling bulk actions

= 2.1.3 =
* some code improvements + renamed some functions & filters

= 2.1.2 =
* improved filter_bookmarks_for_post: removed the 'include' arg which was incompatible with 'category', 'category_name', and 'exclude' bookmarks parameters.
* improved Post_Bookmarks::get_tab_links.

= 2.1.1 =
* improved row actions
* colorize the checkbox if the URL of the link is attached to the post

= 2.1.0 =
* remove 'hide_from_bookmarks' option (see FAQ)
* fix protocol bug
* fix url field not hiding when quick editing a link
* bulk actions

= 2.0.9 =
* Renamed plugin from 'Custom Post Links' to 'Post Bookmarks'
* Upgrade routine for 'Custom Post Links' plugin
* Improved class Post_Bookmarks_List_Table

= 2.0.8 =
* 'Quick Edit' mode
* Link categories column
* Better way of saving existing / new links
* improved javascript (& includes URI.js)
* 'hide_from_bookmarks' option
* Filters on the 'get_bookmarks' hook to include / exclude our links

= 2.0.7 =
* cleaner code for blank row link + links tabe actions

= 2.0.6 =
* try to guess the link name from remote page title or domain (ajaxed)
* URL as first column
* better way to load JS

= 2.0.5 =
* Better output styling
* Favicons : Option to automatically load URL pictures from Google API
* New filter ‘post_bkmarks_get_for_post_pre’
* New function ::get_blank_link()

= 2.0.4 =
* minor

= 2.0.3 =
* ignore targets (eg. ’_blank’) if a link is a local link
* backend : use ‘_blank’ target for URLs in the links table
* implemented links targets

= 2.0.2 =
* Importer and admin notice for links from the original [Custom Post Links](https://github.com/daggerhart/post-bookmarks) plugin (metas '_post_bkmarks').
* new function Post_Bookmarks::save_link()
* Improved function 'post_bkmarks_get_existing_link_id'
* new function ‘post_bkmarks_get_metas’

= 2.0.1 =
* Do not use the same post meta key than than the original [Custom Post Links](https://github.com/daggerhart/post-bookmarks) plugin.

= 2.0 =
* custom sorting
* options page
* set the link domain as class in the link output
* display entries in metabox using class Post_Bookmarks_List_Table (extends WP_List_Table)
* store / read entries from the Link Manager plugin (Worpress core) instead of metas
* wrapped in a class, better code structure
* use fontAwesome css, deleted drag_handle.png
* various other improvements

= 1.0 =
* Forked from [Custom Post Links](https://github.com/daggerhart/post-bookmarks) by Jonathan Daggerhart.

== Upgrade Notice ==

== Localization ==