<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\StringableForToStringRector;
use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;
use Rector\PHPUnit\Set\PHPUnitSetList;

return RectorConfig::configure()
	->withPaths(
		[
			__DIR__ . '/Routes.php',
		]
	)
	->withPhpSets(
		php82: true,
	)
	->withSets(
		[
			PHPUnitSetList::PHPUNIT_100,
			PHPUnitSetList::PHPUNIT_110,
		]
	)
	->withSkip(
		[
			ArrayToFirstClassCallableRector::class,
			StringableForToStringRector::class,
		]
	);
