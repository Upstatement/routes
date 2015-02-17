# Routes
Simple name. Simple routing.

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
