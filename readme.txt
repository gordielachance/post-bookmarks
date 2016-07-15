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

= How can I style a link based on its domain ? =

Use the *data-cp-link-domain* attribute, for example : 

li.cp-links[data-cp-link-domain='wordpress.org'] {
  background-image: url('http://placehold.it/16x16');
}
`


== Screenshots ==


== Changelog ==

= 0.1.1 =

* wrapped in a class
* use fontAwesome css, deleted drag_handle.png
* better plugin structure
* new function allowed_post_types() to get dynamically the post types allowed for the plugin

= 0.1 =
* Forked from [Custom Post Links](https://github.com/daggerhart/custom-post-links) by Jonathan Daggerhart.

== Upgrade Notice ==

== Localization ==