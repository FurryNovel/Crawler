<?php
declare(strict_types = 1);

namespace App\Controller;

use App\Controller\Abstract\FS_Controller;
use App\Controller\Abstract\PublicController;
use App\FetchRule\FetchRule;
use App\Model\Novel;
use App\Model\User;
use Hyperf\Database\Query\Builder;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Qbhy\HyperfAuth\AuthManager;

#[AutoController]
class IndexController extends FS_Controller {
	function ping(): array {
		return $this->success(
			[
				'time' => time(),
			],
			'pong'
		);
	}
	
	function login($username, $password): array {
		$user = User::login($username, $password);
		if (!$user) {
			return $this->error('用户信息不正确');
		}
		$user->token = $this->auth->login($user);
		return $this->success(
			$user,
			'登录成功'
		);
	}
}
