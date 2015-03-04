# Routes
Simple routing for WordPress.

[![Build Status](https://travis-ci.org/Upstatement/routes.svg?branch=master)](https://travis-ci.org/Upstatement/routes)

### Usage
```php
/* functions.php */
Routes::map('myfoo/bar', 'my_callback_function');
Routes::map('my-events/:event', function($params) {
    $event_slug = $params['event'];
    $event = new ECP_Event($event_slug);
    Routes::load('single.php', array('event' => $event));
});
```
