=== Routes ===
Contributors: jarednova
Tags: admin, configuration
Requires at least: 4.0
Stable tag: 0.3.1
Tested up to: 4.5
PHP version: 5.3.0 or greater
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Simple ways to add HTTP routes to your WordPress Theme

== Description ==
Simple ways to make admin customizations for WordPress. You know all that brain space you saved for rewrite regex? Now you can simply...

* On [GitHub](https://github.com/upstatement/routes)

### Usage
<code><pre>
/* functions.php */
Routes::map('myfoo/bar', 'my_callback_function');
Routes::map('my-events/:event', function($params) {
    $event_slug = $params['event'];
    $event = new ECP_Event($event_slug);
    Routes::load('single.php', array('event' => $event));
});
</code></pre>


== Installation ==

1. Activate the plugin through the 'Plugins' menu in WordPress
2. Add custom routes in a theme file like functions.php

== Support ==

Please use the [GitHub repo](https://github.com/upstatement/routes/issues?state=open) to file bugs or questions.
