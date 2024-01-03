<?php

return [
	'default' => [
		'driver' => Hyperf\AsyncQueue\Driver\RedisDriver::class,
		'redis' => [
			'pool' => 'default'
		],
		'channel' => 'queue',
		'timeout' => 2,
		'retry_seconds' => 5,
		'handle_timeout' => 300,
		'processes' => 3,
		'concurrent' => [
			'limit' => 1,
		],
	],
];
