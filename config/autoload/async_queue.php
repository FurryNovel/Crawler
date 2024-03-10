<?php

return [
	'default' => [
		'driver' => App\Driver\RedisDriver::class,
		'redis' => [
			'pool' => 'default'
		],
		'channel' => 'queue',
		'timeout' => 2,
		'retry_seconds' => 5,
		'handle_timeout' => 660,
		'processes' => 2,
		'concurrent' => [
			'limit' => 2,
		],
	],
];
