<?php

namespace App\Service;

use App\Model\User;
use Hyperf\Di\Annotation\Inject;
use Qbhy\HyperfAuth\AuthManager;

class UserService {
	#[Inject]
	protected AuthManager $authService;
	
	function getCurrent(): ?User {
		if (!$this->authService->guard()->check()) {
			return null;
		}
		$id = $this->authService->guard()->user()->getId();
		return User::findFromCache($id);
	}
}