<?php

use function Mantle\Testing\manager;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$rootDir = realpath(__DIR__ . '/..');

// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv
putenv("WP_CORE_DIR=$rootDir/tmp/wordpress");
putenv("CACHEDIR=$rootDir/tmp/test-cache");

// Pre-download SQLite from develop branch for PHP 8.5 compatibility.
// Mantle expects sqlite-database-integration-main.zip with -main folder inside.
cache_sqlite_develop(getenv('CACHEDIR'));

function cache_sqlite_develop(string $cacheDir): void
{
	$sqliteZip = $cacheDir . '/sqlite-database-integration-main.zip';

	if (is_file($sqliteZip)) {
		return;
	}

	if (!is_dir($cacheDir)) {
		mkdir($cacheDir, 0755, true);
	}

	// Download develop branch, extract, rename folder, re-zip with correct name
	$url = 'https://github.com/WordPress/sqlite-database-integration/archive/refs/heads/develop.zip';
	shell_exec('curl -sL ' . escapeshellarg($url) . ' -o ' . escapeshellarg("$cacheDir/tmp.zip"));
	shell_exec('unzip -q ' . escapeshellarg("$cacheDir/tmp.zip") . ' -d ' . escapeshellarg($cacheDir));
	rename("$cacheDir/sqlite-database-integration-develop", "$cacheDir/sqlite-database-integration-main");
	shell_exec('cd ' . escapeshellarg($cacheDir) . ' && zip -rq sqlite-database-integration-main.zip sqlite-database-integration-main');
	shell_exec('rm -rf ' . escapeshellarg("$cacheDir/tmp.zip") . ' ' . escapeshellarg("$cacheDir/sqlite-database-integration-main"));
}

/**
 * Parse TIMBER_TEST_PLUGINS environment variable.
 *
 * @return array List of plugins to activate (e.g., ['acf', 'coauthors-plus', 'wpml'])
 */
function timber_get_test_plugins(): array
{
	$plugins = getenv('TIMBER_TEST_PLUGINS');
	if (empty($plugins)) {
		return [];
	}
	return array_filter(array_map(trim(...), explode(',', $plugins)));
}

/**
 * Check if a specific plugin should be activated for this test run.
 */
function timber_test_has_plugin(string $plugin): bool
{
	return in_array($plugin, timber_get_test_plugins(), true);
}

/**
 * Map plugin shortnames to their main file path (relative to wp-content/plugins/).
 */
function timber_get_plugin_map(): array
{
	return [
		'acf' => 'advanced-custom-fields/acf.php',
		'coauthors-plus' => 'co-authors-plus/co-authors-plus.php',
	];
}




// Load test helper classes (global namespace)
foreach (glob(__DIR__ . '/Support/*.php') as $file) {

	require_once $file;
}


$manager = manager();

// Enable multisite based on WP_MULTISITE env var
$isMultisite = filter_var(getenv('WP_MULTISITE'), FILTER_VALIDATE_BOOLEAN);
$manager->with_multisite($isMultisite);

$manager
	->with_sqlite()
	->loaded(function () {})
	->install();
