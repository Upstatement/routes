<?php

/**
 * Routes makes it easy to add custom routing to your WordPress site. That's why we call it Routes. That is all.
 * 
 * The Routes class is responsible for defining the routing functionality of the plugin.
 * It uses the AltoRouter library to match the current request to the defined routes,
 * and to call the appropriate callback function when a route is matched.
 * It also provides a method for loading a template file and sending data to it, which can be used in the callback functions for the routes defined with the map() method.
 */
class Routes
{
    /**
     * The singleton instance of the Routes class.
     */
    private static ?self $instance = null;

    /**
     * The version of the library.
     */
    public static $version = '1.0.0'; // x-release-please-version
    /**
     * The AltoRouter instance used to match the current request to the defined routes.
     */
    protected ?AltoRouter $router = null;

    /**
     * The routes that were mapped with a name, keyed by that name.
     *
     * Kept on the instance rather than only inside AltoRouter so that url() can
     * still generate a path after match_current_request() has released the router.
     *
     * @var array<string, string>
     */
    private array $named_routes = [];

    /**
     * Private constructor to enforce the singleton pattern.
     *
     * Adds the match_current_request function to the init and wp_loaded hooks,
     * which will check if the current request matches any of the routes defined in this plugin,
     * and if so, will call the appropriate callback function.
     */
    private function __construct()
    {
        add_action('init', [$this, 'match_current_request']);
        add_action('wp_loaded', [$this, 'match_current_request']);
    }

    /**
     * Returns the singleton instance, creating it if it does not yet exist.
     */
    public static function get_instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Initializes the AltoRouter instance if it has not been created yet.
     * Called lazily by map() and add_match_types().
     */
    private function ensure_router(): void
    {
        if (null !== $this->router) {
            return;
        }
        $this->router = new AltoRouter();
        // Add a custom match type for named parameters that matches any character
        // except a slash. Unlike AltoRouter's default param type it allows dots, so
        // routes like /download/:version match version numbers (1.5.1); unlike an
        // explicit allow-list it keeps matching Unicode segments (e.g. /blog/café).
        $this->router->addMatchTypes(['slug' => '[^/]++']);
        $this->router->setBasePath($this->base_path());
    }

    /**
     * Returns the path WordPress is installed under, wrapped in slashes.
     * ex: '/' for a root install, '/blog/' for a subdirectory install.
     */
    private function base_path(): string
    {
        $site_url = get_bloginfo('url');
        $site_url_parts = explode('/', $site_url);
        $site_url_parts = array_slice($site_url_parts, 3);
        $base_path = implode('/', $site_url_parts);

        return $base_path ? '/' . trim($base_path, '/') . '/' : '/';
    }

    /**
     * Checks if the current request matches any of the routes defined in this plugin,
     * and if so, calls the appropriate callback function.
     *
     * @internal
     */
    public function match_current_request()
    {
        if (null == $this->router) {
            return;
        }

        $route = $this->router->match();
        $this->router = null;

        if ($route && isset($route['target'])) {
            // Always hand the callback its parameters, even when the matched route
            // has none or its optional parameters were absent from the request.
            // Dropping the argument makes callbacks declared as function ($params)
            // -- the form used throughout the README -- fatal on those requests.
            call_user_func($route['target'], $route['params'] ?? []);
        }
    }

    /**
     * Wrapper for AltoRouter's addMatchTypes function. See AltoRouter documentation for more details.
     *
     * @api
     *
     * @link https://dannyvankooten.github.io/AltoRouter/usage/mapping-routes.html
     *
     * @param array $match_types An array of custom match types to add to AltoRouter.
     *                           Keys are type names and values are regex patterns.
     *                           ex: Routes::add_match_types(['hex' => '[0-9A-Fa-f]+']);
     */
    public static function add_match_types($match_types)
    {
        $instance = self::get_instance();
        $instance->ensure_router();
        $instance->router->addMatchTypes($match_types);
    }

    /**
     * Maps a route to a callback function.
     *
     * @api
     *
     * @param string   $route    a string to match (ex: 'myfoo')
     * @param callable $callback A callback function to call when the route is matched.
     *                           This can be a string for a function name,
     *                           an array for a class method, or an anonymous function.
     * @param string   $name     an optional name for the route, which can be used to generate URLs with the url() method
     *
     * @example
     * ```php
     * Routes::map('myfoo', 'my_callback_function');
     * Routes::map('mybaq', array($my_class, 'method'));
     * Routes::map('myqux', function() {
     *     //stuff goes here
     * });
     * ```
     */
    public static function map($route, $callback, $name = '')
    {
        $instance = self::get_instance();
        $instance->ensure_router();
        $route = self::convert_route($route);
        $unslashed = untrailingslashit($route);
        // A route is mapped in both slash variants so it matches either way, but only
        // one of them may carry the name: AltoRouter rejects a name it has already
        // seen, so naming both would throw on every named route.
        $instance->router->map('GET|POST|PUT|DELETE|HEAD', trailingslashit($route), $callback);
        $instance->router->map('GET|POST|PUT|DELETE|HEAD', $unslashed, $callback, $name);

        if ($name) {
            $instance->named_routes[$name] = $unslashed;
        }
    }

    /**
     * Generates the path of a route that was mapped with a name.
     *
     * @api
     *
     * @param string $name   the name given as map()'s third argument
     * @param array  $params values for the route's named parameters, keyed by
     *                       parameter name (ex: ['userid' => 123])
     *
     * @return string A path relative to the site root, including the subdirectory
     *                WordPress is installed under, if any. Optional parameters that
     *                are not supplied are left out.
     *
     * @throws RuntimeException if no route was mapped under that name
     *
     * @example
     * ```php
     * Routes::map('my-users/:userid/edit', 'my_callback_function', 'user-edit');
     * $href = Routes::url('user-edit', ['userid' => 123]); // '/my-users/123/edit'
     * ```
     */
    public static function url($name, $params = [])
    {
        $instance = self::get_instance();

        if (!isset($instance->named_routes[$name])) {
            throw new RuntimeException("Route '{$name}' does not exist.");
        }

        // Generating from a dedicated AltoRouter instance keeps reverse routing
        // available after match_current_request() has released the matching one.
        $generator = new AltoRouter();
        $generator->setBasePath($instance->base_path());
        $generator->map('GET', $instance->named_routes[$name], null, $name);

        return $generator->generate($name, $params);
    }

    /**
     * Used internally to convert a route string with :param style parameters
     * to the format used by AltoRouter, which is [:param].
     * If the route string already contains [ and ] characters,
     * it is assumed to be in the correct format and is returned unchanged.
     *
     * Note: Default parameters are converted to [slug:param] to support dots,
     * underscores, and hyphens in parameter values (e.g., version numbers like 1.5.1).
     *
     * @internal
     *
     * @param string $route_string a route string with :param style parameters (ex: 'myfoo/:my_param')
     *
     * @return string A string in a format for AltoRouter
     *                ex: [slug:my_param] (supports dots in values)
     */
    public static function convert_route($route_string)
    {
        if (str_contains($route_string, '[')) {
            return $route_string;
        }
        // Convert :param to [slug:param] to support dots, underscores, and hyphens
        $route_string = preg_replace('/:(\w+)/', '[slug:$1]', $route_string);
        $route_string = str_replace('[[', '[', $route_string);
        $route_string = str_replace(']]', ']', $route_string);
        $route_string = str_replace('[/:', '[:', $route_string);
        $route_string = str_replace('//[', '/[', $route_string);
        if (str_starts_with($route_string, '/')) {
            $route_string = substr($route_string, 1);
        }

        return $route_string;
    }

    /**
     * Loads a template file and sends data to it. This is used in the callback functions for the routes defined with the map() method,
     * to load a specific template file when a route is matched, and to send data to that template file.
     *
     * @api
     * @param string                         $template        A php file to load (ex: 'single.php').
     * @param array|bool                     $tparams         An array of data to send to the php file. Inside the php file this data can be accessed via: `global $params;`.
     * @param WP_Query|callable|array|string $query           A WP_Query object, a callable that returns a WP_Query object, an array of query vars, or a query string. This will be used to set the main query for the request, which can be accessed with the global $wp_query variable in the template file. If a callable is passed, it will be called at the time of the 'parse_request' action, and should return a WP_Query object.
     * @param int                            $status_code     A code for the status (ex: 200).
     * @param int                            $priority        The priority used by the "template_include" filter.
     * @return bool
     */
    public static function load($template, $tparams = false, $query = false, $status_code = 200, $priority = 10)
    {
        $full_path = is_readable($template);
        if (!$full_path) {
            $template = locate_template($template);
        }
        if ($tparams) {
            global $params;
            $params = $tparams;
        }
        if ($status_code) {
            add_filter(
                'status_header',
                function ($status_header, $header, $text, $protocol) use ($status_code) {
                    $text = get_status_header_desc($status_code);

                    return "{$protocol} {$status_code} {$text}";
                },
                10,
                4
            );
            if (404 !== $status_code) {
                add_action(
                    'parse_query',
                    function ($query) {
                        if ($query->is_main_query()) {
                            $query->is_404 = false;
                        }
                    },
                    1
                );
                add_action(
                    'template_redirect',
                    function () {
                        global $wp_query;
                        $wp_query->is_404 = false;
                    },
                    1
                );
            }
        }

        if ($query) {
            add_action(
                'parse_request',
                function () use ($query) {
                    global $wp;
                    if (is_callable($query)) {
                        $query = call_user_func($query);
                    }

                    if (is_array($query)) {
                        $wp->query_vars = $query;
                    } elseif (!empty($query)) {
                        parse_str((string) $query, $wp->query_vars);
                    } else {
                        return true; // Could not interpret query. Let WP try.
                    }

                    return false;
                }
            );
        }
        if ($template) {
            add_filter(
                'template_include',
                fn($current_template) => $template,
                $priority
            );

            // Prevent WordPress's own canonical redirect logic from hijacking a matched
            // route. This most commonly happens when the requested slug also matches an
            // attachment: WordPress will otherwise redirect straight to the raw upload
            // file via `redirect_canonical()` before `template_include` ever runs.
            // @see https://github.com/Upstatement/routes/issues/13
            add_filter('redirect_canonical', '__return_false');

            return true;
        }

        return false;
    }
}
