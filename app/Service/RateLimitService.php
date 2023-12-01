<?php

namespace App\Service;

use App\Utils\Utils;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Di\Aop\ProceedingJoinPoint;

class RateLimitService {
	static function instance(): RateLimitService {
		static $instance;
		if (!$instance) {
			$instance = new RateLimitService();
		}
		return $instance;
	}
	
	
	function callback(float $seconds, ProceedingJoinPoint $proceedingJoinPoint): array {
		
		return [
			'code' => 429,
			'message' => '请求过于频繁，请稍后再试',
		];
	}
	
	#[Inject]
	protected UserService $userService;
	
	function getKey(ProceedingJoinPoint $proceedingJoinPoint): string {
		$user = $this->userService->getCurrent();
		return $proceedingJoinPoint->className . ':' . $proceedingJoinPoint->methodName . ':' . ($user->id ?? Utils::getVisitorIP());
	}
}
