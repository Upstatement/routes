<?php

/**
 * Plugin Name:                 Routes
 * Plugin URI:                  http://www.upstatement.com
 * Description:                 Routes makes it easy to add custom routing to your WordPress site. That's why we call it Routes. That is all.
 * Author:                      Jared Novack + Upstatement
 * Author URI:                  http://www.upstatement.com
 * Text Domain:                 routes
 * Version:                     0.9.2
 */

/**
 * The Routes class is responsible for defining the routing functionality of the plugin.
 * It uses the AltoRouter library to match the current request to the defined routes,
 * and to call the appropriate callback function when a route is matched.
 * It also provides a method for loading a template file and sending data to it, which can be used in the callback functions for the routes defined with the map() method.
 *
 * @package Routes
 */
class Routes
{
    /**
     * The AltoRouter instance used to match the current request to the defined routes.
     *
     * @var AltoRouter
     */
    protected $router;

    /**
     * Constructor.
     *
     * Adds the match_current_request function to the init and wp_loaded hooks,
     * which will check if the current request matches any of the routes defined in this plugin,
     * and if so, will call the appropriate callback function.
     */
    public function __construct()
    {
        add_action('init', [$this, 'match_current_request']);
        add_action('wp_loaded', [$this, 'match_current_request']);
    }

    /**
     * Checks if the current request matches any of the routes defined in this plugin,
     * and if so, calls the appropriate callback function.
     *
     * @internal
     */
    public static function match_current_request()
    {
        global $upstatement_routes;
        if (isset($upstatement_routes->router)) {
            $route = $upstatement_routes->router->match();

            unset($upstatement_routes->router);

            if ($route && isset($route['target'])) {
                if (isset($route['params'])) {
                    call_user_func($route['target'], $route['params']);
                } else {
                    call_user_func($route['target']);
                }
            }
        }
    }

    /**
     * Maps a route to a callback function.
     *
     * @api
     * @param string   $route         A string to match (ex: 'myfoo').
     * @param callable $callback    A callback function to call when the route is matched.
     *                                    This can be a string for a function name,
     *                              an array for a class method, or an anonymous function.
     * @param string   $name        An optional name for the route, which can be used to generate URLs with the url() method.
     * @return void
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
        global $upstatement_routes;
        if (!isset($upstatement_routes->router)) {
            $upstatement_routes->router = new AltoRouter();
            $site_url = get_bloginfo('url');
            $site_url_parts = explode('/', $site_url);
            $site_url_parts = array_slice($site_url_parts, 3);
            $base_path = implode('/', $site_url_parts);
            if (!$base_path || str_starts_with($route, $base_path)) {
                $base_path = '/';
            } else {
                $base_path = '/' . $base_path . '/';
            }
            // Clean any double slashes that have resulted
            $base_path = str_replace('//', '/', $base_path);
            $upstatement_routes->router->setBasePath($base_path);
        }
        $route = self::convert_route($route);
        $upstatement_routes->router->map('GET|POST|PUT|DELETE|HEAD', trailingslashit($route), $callback, $name);
        $upstatement_routes->router->map('GET|POST|PUT|DELETE|HEAD', untrailingslashit($route), $callback, $name);
    }

    /**
     *
     * Used internally to convert a route string with :param style parameters
     * to the format used by AltoRouter, which is [:param].
     * If the route string already contains [ and ] characters,
     * it is assumed to be in the correct format and is returned unchanged.
     *
     * @internal
     * @param string $route_string A route string with :param style parameters (ex: 'myfoo/:my_param').
     * @return string                   A string in a format for AltoRouter
     *                                  ex: [:my_param]
     */
    public static function convert_route($route_string)
    {
        if (strpos($route_string, '[') > -1) {
            return $route_string;
        }
        $route_string = preg_replace('/(:)\w+/', '/[$0]', $route_string);
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
     * @param string     $template           A php file to load (ex: 'single.php').
     * @param array|bool $tparams       An array of data to send to the php file. Inside the php file this data can be accessed via: `global $params;`.

     * @param WP_Query   $query           Use a WP_Query object in the template file instead of the default query.
     * @param int        $status_code     A code for the status (ex: 200).
     * @param int        $priority        The priority used by the "template_include" filter.
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
                    $header_string = "$protocol $status_code $text";
                    return $header_string;
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
                fn() => $template,
                $priority
            );
            return true;
        }
        return false;
    }
}

global $upstatement_routes;
$upstatement_routes = new Routes();

if (
    file_exists($composer_autoload = __DIR__ . '/vendor/autoload.php')
    || file_exists($composer_autoload = WP_CONTENT_DIR . '/vendor/autoload.php')
) {
    require_once $composer_autoload;
}
