<?php
/*
Plugin Name: Routes
Plugin URI: http://routes.upstatement.com
Description: The WordPress Timber Library allows you to write themes using the power Twig templates
Author: Jared Novack + Upstatement
Version: 0.1
Author URI: http://upstatement.com/

Usage:

Routes::map('/my-location', function(){
	//do stuff
	Routes::load('single.php', $data);
});
*/

class Routes {

	protected $router;

	function __construct(){
		add_action('init', array($this, 'init'));
	}

	function init() {
        global $upstatement_routes;
        if (isset($upstatement_routes->router)) {
            $route = $upstatement_routes->router->matchCurrentRequest();
            if ($route) {
                $callback = $route->getTarget();
                $params = $route->getParameters();
                $callback($params);
            }
        }
    }

    /**
     * @param string $route
     * @param callable $callback
     */
    public static function map($route, $callback, $args = array()) {
        global $upstatement_routes;
        if (!isset($upstatement_routes->router)) {
            if (class_exists('PHPRouter\Router')){
                $upstatement_routes->router = new PHPRouter\Router();
                $site_url = get_bloginfo('url');
                $site_url_parts = explode('/', $site_url);
                $site_url_parts = array_slice($site_url_parts, 3);
                $base_path = implode('/', $site_url_parts);
                if (!$base_path || strpos($route, $base_path) === 0) {
                    $base_path = '/';
                } else {
                    $base_path = '/' . $base_path . '/';
                }
                $upstatement_routes->router->setBasePath($base_path);
            }
        }
        if (class_exists('PHPRouter\Router')){
            $upstatement_routes->router->map($route, $callback, $args);
        }
    }

    /**
     * @param array $template
     * @param mixed $query
     * @param int $status_code
     * @param bool $tparams
     * @return bool
     */
    public static function load($template, $query = false, $status_code = 200, $tparams = false) {
        $fullPath = is_readable($template);
        if (!$fullPath) {
            $template = locate_template($template);
        }
        if ($tparams){
            global $params;
            $params = $tparams;
        }
        if ($status_code) {
            add_filter('status_header', function($status_header, $header, $text, $protocol) use ($status_code) {
                $text = get_status_header_desc($status_code);
                $header_string = "$protocol $status_code $text";
                return $header_string;
            }, 10, 4 );
            if (404 != $status_code) {
                add_action('parse_query', function($query) {
                    if ($query->is_main_query()){
                        $query->is_404 = false;
                    }
                },1);
                add_action('template_redirect', function(){
                    global $wp_query;
                    $wp_query->is_404 = false;
                },1);
            }
        }

        if ($query) {
            add_action('do_parse_request', function() use ($query) {
                global $wp;
                if ( is_callable($query) )
                    $query = call_user_func($query);

                if ( is_array($query) )
                    $wp->query_vars = $query;
                elseif ( !empty($query) )
                    parse_str($query, $wp->query_vars);
                else
                    return true; // Could not interpret query. Let WP try.

                return false;
            });
        }
        if ($template) {
        	add_filter('template_include', function($t) use ($template) {
        		return $template;
        	});
            return true;
        }
        return false;
    }
}

global $upstatement_routes;
$upstatement_routes = new Routes();

if (    file_exists($composer_autoload = __DIR__ . '/vendor/autoload.php')
        || file_exists($composer_autoload = WP_CONTENT_DIR.'/vendor/autoload.php')){
  require_once($composer_autoload);
}

