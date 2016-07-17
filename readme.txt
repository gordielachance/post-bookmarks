=== Custom Post Links ===
Contributors:grosbouff,daggerhart
Donate link:http://bit.ly/gbreant
Tags: links,quick links,related links
Requires at least: 3.5
Tested up to: 4.5.3
Stable tag: trunk
License: GPLv2 or later

Add Fields to post types for appending/prepending arbitrary links to the post output.

== Description ==

Add Fields to post types for appending/prepending arbitrary links to the post output.


= Contributors =

The first version of this plugin has been forked from [Custom Post Links](https://github.com/daggerhart/custom-post-links) by Jonathan Daggerhart.
Other contributors [are listed here](https://github.com/gordielachance/custom-post-links/contributors)

= Notes =

For feature request and bug reports, [please use the forums](http://wordpress.org/support/plugin/custom-post-links#postform).

If you are a plugin developer, [we would like to hear from you](https://github.com/gordielachance/custom-post-links). Any contribution would be very welcome.

== Installation ==

1. Upload the plugin to your blog and Activate it.

== Frequently Asked Questions ==

= How can I style a link based on its domain, using CSS ? =

Use the *data-cp-link-domain* attribute, for example : 

li.cp-links[data-cp-link-domain='wikipedia.org'] {
    background-image: url('https://wikipedia.org/static/favicon/wikipedia.ico');
}

= How can I change the way links are displayed ? =

Use the filter *cp_links_output_single_link* (located in the **cp_links_output_single_link** function), for example : 

`<?php

function custom_output_single_link($output,$link){
    return $output;
}

add_filter('cp_links_output_single_link','custom_output_single_link',10,2);

?>`

== Screenshots ==


== Changelog ==

= 0.1.1 =
* set the link domain as class in the link output
* display entries in metabox using class CP_Links_List_Table (extends WP_List_Table)
* store / read entries from the Link Manager plugin (Worpress core) instead of metas
* wrapped in a class, better code structure
* use fontAwesome css, deleted drag_handle.png
* new function allowed_post_types() to get dynamically the post types allowed for the plugin
* various other improvements

= 0.1 =
* Forked from [Custom Post Links](https://github.com/daggerhart/custom-post-links) by Jonathan Daggerhart.

== Upgrade Notice ==

== Localization ==