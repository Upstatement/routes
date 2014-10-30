<?php
/*
Plugin Name: Routes
Plugin URI: http://timber.upstatement.com
Description: The WordPress Timber Library allows you to write themes using the power Twig templates
Author: Jared Novack + Upstatement
Version: 0.1
Author URI: http://upstatement.com/
*/

//Routes::map('/products/sales', 'page#sales');
//Routes::map('')

/*
$router = new AltoRouter();

// map homepage
$router->map( 'GET', '/', function() {
    require __DIR__ . '/views/home.php';
});

// map users details page
$router->map( 'GET|POST', '/users/[i:id]/', function( $id ) {
  $user = .....
  require __DIR__ . '/views/user/details.php';
});
*/
class Routes {

	function __construct() {
		require_once 'vendor/altorouter/altorouter/AltoRouter.php';
		global $routes_alto_router;
		$routes_alto_router = new AltoRouter();
		add_action( 'init', array( $this, 'init' ) );
	}

	function init() {
		global $routes_alto_router;
		if ( isset( $routes_alto_router ) ) {
			$match = $routes_alto_router->match();
		}
		if ( isset( $match ) && $match ) {
			$callback = $match['target'];
			$params = $match['params'];
			call_user_func_array( $callback, $params );
		}
	}

	public static function map( $route, $callback ) {
		global $routes_alto_router;
		if ( isset( $routes_alto_router ) ) {
			$routes_alto_router->map( 'GET', $route, $callback );
		}
	}

	/**
	 *
	 *
	 * @param array   $template
	 * @param mixed   $query
	 * @param int     $status_code
	 * @param bool    $tparams
	 * @return bool
	 */
	public static function load_view( $template, $query = false, $status_code = 200, $tparams = false ) {
		$fullPath = is_readable( $template );
		if ( !$fullPath ) {
			$template = locate_template( $template );
		}
		if ( $tparams ) {
			global $params;
			$params = $tparams;
		}
		if ( $status_code ) {
			add_filter( 'status_header', function( $status_header, $header, $text, $protocol ) use ( $status_code ) {
					$text = get_status_header_desc( $status_code );
					$header_string = "$protocol $status_code $text";
					return $header_string;
				}, 10, 4 );
			if ( 404 != $status_code ) {
				add_action( 'parse_query', function( $query ) {
						if ( $query->is_main_query() ) {
							$query->is_404 = false;
						}
					}, 1 );
				add_action( 'template_redirect', function() {
						global $wp_query;
						$wp_query->is_404 = false;
					}, 1 );
			}
		}

		if ( $query ) {
			add_action( 'do_parse_request', function() use ( $query ) {
					global $wp;
					if ( is_callable( $query ) ) {
						$query = call_user_func( $query );
					}
					if ( is_array( $query ) ) {
						$wp->query_vars = $query;
					} elseif ( !empty( $query ) ) {
						parse_str( $query, $wp->query_vars );
					} else {
						return true; // Could not interpret query. Let WP try.
					}
					return false;
				} );
		}
		if ( $template ) {
			add_filter( 'template_include', function( $t ) use ( $template ) {
					return $template;
				} );
			return true;
		}
		return false;
	}

}

new Routes();
