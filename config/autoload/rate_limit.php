<?php

declare(strict_types = 1);

/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

use App\Service\RateLimitService;
use Hyperf\Di\Aop\ProceedingJoinPoint;

return [
	'create' => 1,
	'consume' => 1,
	'capacity' => 1,
	'limitCallback' => function (float $seconds, ProceedingJoinPoint $proceedingJoinPoint) {
		return RateLimitService::instance()
			->callback($seconds, $proceedingJoinPoint);
	},
	'key' => function (ProceedingJoinPoint $proceedingJoinPoint) {
		return RateLimitService::instance()
			->getKey($proceedingJoinPoint);
	},
	'waitTimeout' => 1,
	'storage' => [
		'options' => [
			'pool' => 'default',
		],
	],
];
