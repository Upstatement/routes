<?php
/*
Plugin Name: Routes
Plugin URI: http://routes.upstatement.com
Description: The WordPress Timber Library allows you to write themes using the power Twig templates
Author: Jared Novack + Upstatement
Version: 0.3.1
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
		add_action('init', array($this, 'match_current_request') );
		add_action('wp_loaded', array($this, 'match_current_request') );
	}

	static function match_current_request() {
		global $upstatement_routes;
		if (isset($upstatement_routes->router)) {
			$route = $upstatement_routes->router->match();
			
			unset($upstatement_routes->router);
			
			if ($route && isset($route['target'])) {
				if ( isset($route['params']) ) {
					call_user_func($route['target'], $route['params']);
				} else {
					call_user_func($route['target']);
				}
			}
		}
	}

	/**
	 * @param string $route         A string to match (ex: 'myfoo')
	 * @param callable $callback    A function to run, examples:
	 *                              Routes::map('myfoo', 'my_callback_function');
	 *                              Routes::map('mybaq', array($my_class, 'method'));
	 *                              Routes::map('myqux', function() {
	 *                                  //stuff goes here
	 *                              });
	 */
	public static function map($route, $callback, $args = array()) {
		global $upstatement_routes;
		if (!isset($upstatement_routes->router)) {
			$upstatement_routes->router = new AltoRouter();
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
		$route = self::convert_route($route);
		$upstatement_routes->router->map('GET|POST|PUT|DELETE', trailingslashit($route), $callback, $args);
		$upstatement_routes->router->map('GET|POST|PUT|DELETE', untrailingslashit($route), $callback, $args);
	}

	/**
	 * @return string 					A string in a format for AltoRouter
	 *                       			ex: [:my_param]
	 */
	public static function convert_route($route_string) {
		if (strpos($route_string, '[') > -1) {
			return $route_string;
		}
		$route_string = preg_replace('/(:)\w+/', '/[$0]', $route_string);
		$route_string = str_replace('[[', '[', $route_string);
		$route_string = str_replace(']]', ']', $route_string);
		$route_string = str_replace('[/:', '[:', $route_string);
		$route_string = str_replace('//[', '/[', $route_string);
		if ( strpos($route_string, '/') === 0 ) {
			$route_string = substr($route_string, 1);
		}
		return $route_string;
	}

	/**
	 * @param array $template           A php file to load (ex: 'single.php')
	 * @param array|bool $tparams       An array of data to send to the php file. Inside the php file
	 *                                  this data can be accessed via:
	 *                                  global $params;
	 * @param int $status_code          A code for the status (ex: 200)
	 * @param WP_Query $query           Use a WP_Query object in the template file instead of
	 *                                  the default query
	 * @param int $priority		    The priority used by the "template_include" filter
	 * @return bool
	 */
	public static function load($template, $tparams = false, $query = false, $status_code = 200, $priority = 10) {
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
			}, $priority);
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

