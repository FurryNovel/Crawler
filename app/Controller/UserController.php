<?php
declare(strict_types = 1);

namespace App\Controller;

use App\Controller\Abstract\Controller;
use App\Controller\Abstract\LoginController;
use App\Controller\Abstract\PublicController;
use App\FetchRule\FetchRule;
use App\Model\Novel;
use App\Model\User;
use App\Service\UserService;
use Hyperf\Database\Query\Builder;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Engine\Contract\Http\V2\RequestInterface;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Qbhy\HyperfAuth\AuthManager;
use Qbhy\HyperfAuth\AuthMiddleware;

#[AutoController]
class UserController extends LoginController {
	#[Inject]
	protected UserService $userService;
	
	function info(): array {
		$user = $this->userService->getCurrent();
		return $this->success(
			$user,
			'登录成功'
		);
	}
	
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
