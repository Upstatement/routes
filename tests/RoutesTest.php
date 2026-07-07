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

	public function testRouteWithDecimalParameter()
	{
		// Test for issue #45: routes with decimal numbers (e.g., version numbers like 1.5.1)
		global $matches;
		$matches = [];
		Routes::map(
			'download/:version',
			function ($params) {
				global $matches;
				$matches = [];
				if ('1.5.1' === $params['version']) {
					$matches[] = true;
				}
			}
		);
		$this->get(home_url('/download/1.5.1'));
		$this->matchRoutes();
		$this->assertCount(1, $matches);
	}

	public function testRouteWithUnicodeParameter()
	{
		// A named parameter must still match Unicode segments (accented characters,
		// etc.) — the slug match type excludes only slashes, so it does not regress
		// non-ASCII URLs like /blog/café. Raised in review of #52.
		global $matches;
		$matches = [];
		Routes::map(
			'blog/:slug',
			function ($params) {
				global $matches;
				$matches = [];
				if ('café' === $params['slug']) {
					$matches[] = true;
				}
			}
		);
		$this->get(home_url('/blog/café'));
		$this->matchRoutes();
		$this->assertCount(1, $matches);
	}

	public function testRouteSurvivesAttachmentRedirect()
	{
		// Regression test for issue #13: WordPress redirects any request that resolves
		// to an attachment straight to the raw upload file, since attachment pages are
		// disabled by default (`wp_attachment_pages_enabled`). If the requested slug
		// also matches a mapped route, that redirect must not hijack the route.
		$attachment_id = $this->factory->attachment->with_image()->create(
			[
				'post_name' => 'books',
				'post_title' => 'Books',
			]
		);

		global $matches;
		$matches = [];

		Routes::map(
			'books',
			function () use ($attachment_id) {
				global $matches;
				$matches = [];
				$matches[] = true;

				// Force the main query to resolve as the attachment, mirroring what
				// WordPress's own request parsing can produce for a colliding slug.
				Routes::load(
					__DIR__ . '/Supports/single.php',
					false,
					[
						'attachment' => 'books',
						'attachment_id' => $attachment_id,
					]
				);
			}
		);

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['REQUEST_URI'] = '/books/';
		$this->matchRoutes();

		$response = $this->get(home_url('/books/'));

		$response->assertOk();
		$this->assertEquals(1, count($matches));
	}

	public function testUnmatchedRouteDoesNotAffectCanonicalRedirects()
	{
		// Sanity check for the fix above: Routes must only cancel WordPress's canonical
		// redirect when a route has actually matched and rendered a template. Regular
		// WordPress routing -- like redirecting a legacy ?p=123 link to its canonical
		// permalink -- must keep working untouched when no route matches at all.
		$post_id = $this->factory->post->create(['post_title' => 'Hello World']);

		// No Routes::map() call in this test, so match_current_request() is a no-op.
		$this->matchRoutes();

		$response = $this->get(home_url('/?p=' . $post_id));

		$response->assertRedirect(get_permalink($post_id));
	}

	public function testMatchedRouteWithoutLoadDoesNotAffectCanonicalRedirects()
	{
		// A route can match and run its callback without ever calling Routes::load()
		// (e.g. it only performs a side effect). Since no template was set, the fix
		// must not touch WordPress's canonical redirect handling for that request.
		$post_id = $this->factory->post->create(['post_title' => 'Hello World']);

		global $matches;
		$matches = [];

		Routes::map(
			'no-op',
			function () {
				global $matches;
				$matches = [];
				$matches[] = true;
			}
		);

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['REQUEST_URI'] = '/no-op/';
		$this->matchRoutes();

		$this->assertEquals(1, count($matches));

		$response = $this->get(home_url('/?p=' . $post_id));

		$response->assertRedirect(get_permalink($post_id));
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
