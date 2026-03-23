# Routes

Simple routing for WordPress. Designed for usage with [Timber](https://github.com/timber/timber)

[![PHP unit tests](https://github.com/Upstatement/routes/actions/workflows/php-unit-tests.yml/badge.svg?branch=2.x)](https://github.com/Upstatement/routes/actions/workflows/php-unit-tests.yml?query=branch:2.x)
[![Latest Stable Version](https://img.shields.io/packagist/v/Upstatement/routes.svg?style=flat-square)](https://packagist.org/packages/Upstatement/routes)

## Installation

Install via [Composer](https://getcomposer.org/):

```bash
composer require upstatement/routes
```

Then make sure Composer's autoloader is included in your project. In a standard WordPress setup this is typically done in `functions.php` or your plugin's main file:

```php
require_once __DIR__ . '/vendor/autoload.php';
```

If you're using a WordPress-specific Composer setup (e.g. [Bedrock](https://roots.io/bedrock/)), the autoloader is usually already loaded for you.

## Upgrading to 1.x

Version 1.0 introduces several breaking changes. If you're upgrading from 0.x, read through the following sections to make sure your code is compatible.

### PHP 8.2+ required

Routes now requires PHP 8.2 or higher.

### Singleton — no direct instantiation

The class is now a singleton. You can no longer instantiate it directly:

```php
// Before (0.x) — no longer works
$routes = new Routes();

// After (1.x)
$instance = Routes::get_instance();
```

In practice most applications never instantiated `Routes` directly, so this is unlikely to affect you.

### No more global `$upstatement_routes`

The global variable `$upstatement_routes` used internally in 0.x has been removed. If your code accessed called `$upstatement_routes->match_current_request()` directly, update it:

```php
// Before (0.x)
global $upstatement_routes;
$upstatement_routes->match_current_request();

// After (1.x)
Routes::get_instance()->match_current_request();
```

### New `add_match_types()` method

Custom AltoRouter match types can now be registered via `Routes::add_match_types()` and may be called before or after `Routes::map()`:

```php
Routes::add_match_types(['hex' => '[0-9A-Fa-f]+']);
Routes::map('color/[hex:color]', function($params) {
    // $params['color'] is guaranteed to be a hex string
});
```

### Class is no longer auto-instantiated on include

In 0.x, including `Routes.php` immediately instantiated the class and registered WordPress hooks. In 1.x, the singleton is created lazily on the first call to `Routes::map()` or `Routes::add_match_types()`. No action is required as long as you call `Routes::map()` before `wp_loaded` fires, which is the standard usage pattern.

### Basic Usage

```php
/* functions.php */
Routes::map('myfoo/bar', 'my_callback_function');
Routes::map('my-events/:event', function($params) {
    $event_slug = $params['event'];
    $event = new ECP_Event($event_slug);
    $query = new WPQuery(); //if you want to send a custom query to the page's main loop
    Routes::load('single.php', array('event' => $event), $query, 200);
});
```

Using routes makes it easy for you to implement custom pagination — and anything else you might imagine in your wildest dreams of URLs and parameters. OMG so easy!

## Some examples

In your functions.php file, this can be called anywhere (don't hook it to init or another action or it might be called too late)

```php
<?php
Routes::map('blog/:name', function($params){
    $query = 'posts_per_page=3&post_type='.$params['name'];
    Routes::load('archive.php', null, $query, 200);
});

Routes::map('blog/:name/page/:pg', function($params){
    $query = 'posts_per_page=3&post_type='.$params['name'].'&paged='.$params['pg'];
    $params = array('thing' => 'foo', 'bar' => 'I dont even know');
    Routes::load('archive.php', $params, $query);
});
```

## map

`Routes::map($pattern, $callback)`

### Usage

A `functions.php` where I want to display custom paginated content:

```php
<?php
Routes::map('info/:name/page/:pg', function($params){
	//make a custom query based on incoming path and run it...
	$query = 'posts_per_page=3&post_type='.$params['name'].'&paged='.intval($params['pg']);

	//load up a template which will use that query
	Routes::load('archive.php', null, $query);
});
```

### Arguments

`$pattern` (required)
Set a pattern for Routes to match on, by default everything is handled as a string. Any segment that begins with a `:` is handled as a variable, for example:

**To paginate:**

```
page/:pagenum
```

**To edit a user:**

```
my-users/:userid/edit
```

`$callback`
A function that should fire when the pattern matches the request. Callback takes one argument which is an array of the parameters passed in the URL.

So in this example: `'info/:name/page/:pg'`, $params would have data for:

- `$data['name']`
- `$data['pg']`

... which you can use in the callback function as a part of your query

---

## load

`Routes::load($php_file, $args, $query = null, $status_code = 200)`

### Arguments

`$php_file` (required)
A PHP file to load, in my experience this is usually your archive.php or a generic listing page (but don't worry it can be anything!)

`$template_params`
Any data you want to send to the resulting view. Example:

```php
<?php
/* functions.php */

Routes::map('info/:name/page/:pg', function($params){
    //make a custom query based on incoming path and run it...
    $query = 'posts_per_page=3&post_type='.$params['name'].'&paged='.intval($params['pg']);

    //load up a template which will use that query
    $params['my_title'] = 'This is my custom title';
    Routes::load('archive.php', $params, $query, 200);
});
```

```php
<?php
/* archive.php */

global $params;
$context['wp_title'] = $params['my_title']; // "This is my custom title"
/* the rest as normal... */
Timber::render('archive.twig', $context);
```

`$query`
The query you want to use, it can accept a string or array just like `Timber::get_posts` -- use the standard WP_Query syntax (or a WP_Query object too)

`$status_code`
Send an optional status code. Defaults to 200 for 'Success/OK'

## add_match_types

This method makes it possible to add custom match types in Routes.

```php
<?php
/* functions.php */

Routes::add_match_types([
	'oldID' => '@[0-9]++',
]);

Routes::map(
	'[oldID:id]/[:slug]',
	function ($params) {
		$old_id = $params['id'];
		$slug = $params['slug'];

		/* the rest as normal... */
		Timber::render('single.php', $context);
	}
);
```
