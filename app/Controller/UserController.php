<?php
declare(strict_types = 1);

namespace App\Controller;

use App\Controller\Abstract\FS_Controller;
use App\Middleware\LoginMiddleware;
use App\Model\User;
use App\Service\RateLimitService;
use Hyperf\Context\ApplicationContext;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\RateLimit\Annotation\RateLimit;

#[AutoController]
#[RateLimit]
class UserController extends FS_Controller {
	
	function login($username, $password): array {
		try {
			$user = User::login($username, $password);
			if (!$user) {
				return $this->error('用户信息不正确');
			}
		} catch (\Exception $exception) {
			return $this->error($exception->getMessage());
		}
		$user->token = $this->auth->login($user);
		return $this->success(
			$user,
			'登录成功'
		);
	}
	
	#[Middleware(LoginMiddleware::class)]
	function info(): array {
		$user = $this->userService->getCurrent();
		return $this->success(
			$user,
			'登录成功'
		);
	}
	
	#[Middleware(LoginMiddleware::class)]
	function change_password(string $old_password, string $new_password): array {
		$user = $this->userService->getCurrent();
		if (!$user) {
			return $this->error('用户信息不正确');
		}
		if (!$user->checkPassword($old_password)) {
			return $this->error('旧密码不正确');
		}
		$user->changePassword($new_password);
		$user->save();
		return $this->success(
			$user,
			'修改成功'
		);
	}
	
	#[Middleware(LoginMiddleware::class)]
	function update(string $nickname, string $desc): array {
		$user = $this->userService->getCurrent();
		if (!$user) {
			return $this->error('用户信息不正确');
		}
		$user->nickname = $nickname;
		$user->desc = $desc;
		$user->save();
		return $this->success(
			$user,
			'修改成功'
		);
	}
	
}
