<?php
declare(strict_types = 1);

namespace App\Controller;

use App\Controller\Abstract\FS_Controller;
use App\Middleware\LoginMiddleware;
use App\Model\Bookmark;
use App\Model\Novel;
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
#[Middleware(LoginMiddleware::class)]
class BookmarkController extends FS_Controller {
	
	function query(): array {
		$bookmarks = Bookmark::with(['novel', 'chapter'])->where([
			'user_id' => $this->userService->getCurrent()->id,
		])->paginate();
		return $this->success(
			$bookmarks,
			'查询成功'
		);
	}
	
	function mark(int $novel_id, int $chapter_id): array {
		/**
		 * @var Bookmark $bookmark
		 */
		$bookmark = Bookmark::where([
			'user_id' => $this->userService->getCurrent()->id,
			'novel_id' => $novel_id,
		])->first();
		if (!$bookmark) {
			$bookmark = Bookmark::create([
				'user_id' => $this->userService->getCurrent()->id,
				'novel_id' => $novel_id,
				'chapter_id' => $chapter_id,
			]);
		} else {
			$bookmark->chapter_id = $chapter_id;
			$bookmark->save();
		}
		return $this->success(
			$bookmark,
			'记录成功'
		);
	}
	
	function delete(int $novel_id): array {
		/**
		 * @var Bookmark $bookmark
		 */
		$bookmark = Bookmark::where([
			'user_id' => $this->userService->getCurrent()->id,
			'novel_id' => $novel_id,
		])->first();
		if (!$bookmark) {
			return $this->error('记录不存在');
		}
		$bookmark->delete();
		return $this->success(
			$bookmark,
			'删除成功'
		);
	}
}
