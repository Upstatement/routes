<?php

use Mantle\Testkit\Integration_Test_Case;

/**
 * @internal
 *
 * @coversNothing
 */
class RoutesTest extends Integration_Test_Case
{
	public function testThemeRoute()
	{
		$template = Routes::load(__DIR__ . '/Supports/single.php');
		$this->assertTrue($template);
	}

	public function testThemeRouteDoesntExist()
	{
		$template = Routes::load('singlefoo.php');
		$this->assertFalse($template);
	}

	public function testFullPathRoute()
	{
		$hello = WP_CONTENT_DIR . '/plugins/hello.php';
		$template = Routes::load($hello);
		$this->assertTrue($template);
	}

	public function testFullPathRouteDoesntExist()
	{
		$hello = WP_CONTENT_DIR . '/plugins/hello-foo.php';
		$template = Routes::load($hello);
		$this->assertFalse($template);
	}

	public function testRouterClass()
	{
		$this->assertTrue(class_exists('AltoRouter'));
	}

	public function testAppliedRoute()
	{
		$_SERVER['REQUEST_METHOD'] = 'GET';
		global $matches;
		$matches = [];
		$phpunit = $this;
		Routes::map(
			'foo',
			function () use ($phpunit) {
				global $matches;
				$matches = [];
				$phpunit->assertTrue(true);
				$matches[] = true;
			}
		);
		$this->get(home_url('foo'));
		$this->matchRoutes();
		$this->assertEquals(1, count($matches));
	}

	public function testRouteWithVariable()
	{
		$post_name = 'ziggy';
		$post = $this->factory->post->create(
			[
				'post_title' => 'Ziggy',
				'post_name' => $post_name,
			]
		);
		global $matches;
		$matches = [];
		$phpunit = $this;
		Routes::map(
			'mything/:slug',
			function ($params) {
				global $matches;
				$matches = [];
				if ('ziggy' == $params['slug']) {
					$matches[] = true;
				}
			}
		);
		$this->get(home_url('/mything/' . $post_name));
		$this->matchRoutes();
		$this->assertEquals(1, count($matches));
	}

	public function testRouteWithAltoVariable()
	{
		$post_name = 'ziggy';
		$post = $this->factory->post->create(
			[
				'post_title' => 'Ziggy',
				'post_name' => $post_name,
			]
		);
		global $matches;
		$matches = [];
		$phpunit = $this;
		Routes::map(
			'mything/[*:slug]',
			function ($params) {
				global $matches;
				$matches = [];
				if ('ziggy' == $params['slug']) {
					$matches[] = true;
				}
			}
		);
		$this->get(home_url('/mything/' . $post_name));
		$this->matchRoutes();
		$this->assertEquals(1, count($matches));
	}

	public function testRouteWithMultiArguments()
	{
		$phpunit = $this;
		Routes::map(
			'artist/[:artist]/song/[:song]',
			function ($params) {
				global $matches;
				$matches = [];
				if ('smashing-pumpkins' == $params['artist']) {
					$matches[] = true;
				}
				if ('mayonaise' == $params['song']) {
					$matches[] = true;
				}
			}
		);
		$this->get(home_url('/artist/smashing-pumpkins/song/mayonaise'));
		$this->matchRoutes();
		global $matches;
		$this->assertEquals(2, count($matches));
	}

	public function testRouteWithMultiArgumentsOldStyle()
	{
		$phpunit = $this;
		global $matches;
		Routes::map(
			'studio/:studio/movie/:movie',
			function ($params) {
				global $matches;
				$matches = [];
				if ('universal' == $params['studio']) {
					$matches[] = true;
				}
				if ('brazil' == $params['movie']) {
					$matches[] = true;
				}
			}
		);
		$this->get(home_url('/studio/universal/movie/brazil/'));
		$this->matchRoutes();
		$this->assertEquals(2, count($matches));
	}

	public function testRouteAgainstPostName()
	{
		$post_name = 'jared';
		$post = $this->factory->post->create(
			[
				'post_title' => 'Jared',
				'post_name' => $post_name,
			]
		);
		global $matches;
		$matches = [];
		$phpunit = $this;
		Routes::map(
			'randomthing/' . $post_name,
			function () use ($phpunit) {
				global $matches;
				$matches = [];
				$phpunit->assertTrue(true);
				$matches[] = true;
			}
		);
		$this->get(home_url('/randomthing/' . $post_name));
		$this->matchRoutes();
		$this->assertEquals(1, count($matches));
	}

	public function testVerySimpleRoute()
	{
		$_SERVER['REQUEST_METHOD'] = 'GET';
		global $matches;
		$matches = [];
		$phpunit = $this;
		Routes::map(
			'crackers',
			function () {
				global $matches;
				$matches = [];
				$matches[] = true;
			}
		);
		$this->get(home_url('crackers'));
		$this->matchRoutes();
		$this->assertEquals(1, count($matches));
	}

	public function testVerySimpleRouteTrailingSlash()
	{
		$_SERVER['REQUEST_METHOD'] = 'GET';
		global $matches;
		$matches = [];
		$phpunit = $this;
		Routes::map(
			'bip/',
			function () {
				global $matches;
				$matches = [];
				$matches[] = true;
			}
		);
		$this->get(home_url('bip'));
		$this->matchRoutes();
		$this->assertEquals(1, count($matches));
	}

	public function testVerySimpleRouteTrailingSlashInRequest()
	{
		$_SERVER['REQUEST_METHOD'] = 'GET';
		global $matches;
		$matches = [];
		$phpunit = $this;
		Routes::map(
			'bopp',
			function () {
				global $matches;
				$matches = [];
				$matches[] = true;
			}
		);
		$this->get(home_url('bopp/'));
		$this->matchRoutes();
		$this->assertEquals(1, count($matches));
	}

	public function testVerySimpleRouteTrailingSlashInRequestAndMapping()
	{
		$_SERVER['REQUEST_METHOD'] = 'GET';
		global $matches;
		$matches = [];
		$phpunit = $this;
		Routes::map(
			'zappers',
			function () {
				global $matches;
				$matches = [];
				$matches[] = true;
			}
		);
		$this->get(home_url('zappers/'));
		$this->matchRoutes();
		$this->assertEquals(1, count($matches));
	}

	function testVerySimpleRoutePrecedingSlash()
	{
		$_SERVER['REQUEST_METHOD'] = 'GET';
		global $matches;
		$matches = [];
		$phpunit = $this;
		Routes::map(
			'/gobbles',
			function () {
				global $matches;
				$matches = [];
				$matches[] = true;
			}
		);
		$this->get(home_url('gobbles'));
		$this->matchRoutes();
		$this->assertEquals(1, count($matches));
	}

	public function testFailedRoute()
	{
		$_SERVER['REQUEST_METHOD'] = 'GET';
		global $matches;
		$matches = [];
		$phpunit = $this;
		Routes::map(
			'foo',
			function () use ($phpunit) {
				$matches = [];
				$phpunit->assertTrue(false);
				$matches[] = true;
			}
		);
		$this->get(home_url('bar'));
		$this->matchRoutes();
		$this->assertEquals(0, count($matches));
	}

	public function testRouteWithClassCallback()
	{
		Routes::map('classroute', ['RoutesTest', '_testCallback']);
		$this->get(home_url('classroute'));
		$this->matchRoutes();
		global $matches_class_test;
		$this->assertEquals(1, count($matches_class_test));
	}

	public function testAddMatchTypes()
	{
		$_SERVER['REQUEST_METHOD'] = 'GET';
		global $matches;
		$matches = [];
		Routes::add_match_types(['hex' => '[0-9A-Fa-f]+']);
		Routes::map(
			'color/[hex:color]',
			function ($params) {
				global $matches;
				$matches = [];
				if ('ff5733' === $params['color']) {
					$matches[] = true;
				}
			}
		);
		$this->get(home_url('/color/ff5733'));
		$this->matchRoutes();
		$this->assertEquals(1, count($matches));
	}

	public function testAddMatchTypesBeforeMap()
	{
		$_SERVER['REQUEST_METHOD'] = 'GET';
		global $matches;
		$matches = [];
		// Calling add_match_types before map() should still work
		Routes::add_match_types(['word' => '\w+']);
		Routes::map(
			'tag/[word:name]',
			function ($params) {
				global $matches;
				$matches = [];
				if ('hello' === $params['name']) {
					$matches[] = true;
				}
			}
		);
		$this->get(home_url('/tag/hello'));
		$this->matchRoutes();
		$this->assertEquals(1, count($matches));
	}

	public function matchRoutes()
	{
		Routes::get_instance()->match_current_request();
	}

	public static function _testCallback()
	{
		global $matches_class_test;
		$matches_class_test = [];
		$matches_class_test[] = true;
	}
}
