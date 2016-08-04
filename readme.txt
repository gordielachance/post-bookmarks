=== Custom Post Links ===
Contributors:grosbouff
Donate link:http://bit.ly/gbreant
Tags: links,quick links,related links,custom links, post links
Requires at least: 3.5
Tested up to: 4.5.3
Stable tag: trunk
License: GPLv2 or later

Adds a new metabox to the editor, allowing you to attach a set of related links to any post.


== Description ==

Adds a new metabox to the editor, allowing you to attach a set of related links to any post.

* Nice GUI
* Options page
* Each link has a data attribute whose the value is the link domain url, making it very simple to style (for example, all links from Wikipedia could have a wikipedia icon — see the FAQ section)
* Links are handled by the bookmark functions from the WP core
* actions and filters hooks allows you to tweak the plugin for your own needs
* links can be ordered by name or by a custom order

= Contributors =

The first version of this plugin has been forked from [Custom Post Links](https://github.com/daggerhart/custom-post-links) by Jonathan Daggerhart.  The code has been deeply rewritten since.
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

= 2.0 =
* custom sorting
* options page
* set the link domain as class in the link output
* display entries in metabox using class CP_Links_List_Table (extends WP_List_Table)
* store / read entries from the Link Manager plugin (Worpress core) instead of metas
* wrapped in a class, better code structure
* use fontAwesome css, deleted drag_handle.png
* various other improvements

= 1.0 =
* Forked from [Custom Post Links](https://github.com/daggerhart/custom-post-links) by Jonathan Daggerhart.

== Upgrade Notice ==

== Localization ==