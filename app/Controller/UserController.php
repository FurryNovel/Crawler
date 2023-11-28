<?php
declare(strict_types = 1);

namespace App\Controller;

use App\Controller\Abstract\FS_Controller;
use App\Middleware\LoginMiddleware;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;

#[AutoController]
#[Middleware(LoginMiddleware::class)]
class UserController extends FS_Controller {
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
