<?php
declare(strict_types = 1);

namespace App\Controller;

use App\Controller\Abstract\BaseController;
use App\DataSet\DataSet;
use App\DataSet\LanguageService;
use App\FetchRule\FetchRule;
use App\FetchRule\PixivFetchRule;
use App\Middleware\AdminMiddleware;
use App\Model\Author;
use App\Model\Chapter;
use App\Model\Novel;
use App\Model\User;
use App\Service\FetchQueueService;
use App\Task\FetchLatestNovelTask;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Collection;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;
use function Hyperf\Support\env as env;

#[AutoController]
class LibraryController extends BaseController {
	#[Inject]
	protected DataSet $dataSet;
	#[Inject]
	protected LanguageService $language;
	#[Inject]
	protected FetchQueueService $fetchQueueService;
	
	function fetch_novel(string $type, string $rule_novel_id): array {
		$rule = FetchRule::getRule($type);
		if (!$rule) {
			return $this->error('规则不存在');
		}
		$rule_novel_id = trim($rule_novel_id);
		
		$novelInfo = $rule->fetchNovelDetail($rule_novel_id);
		if (!$novelInfo) {
			return $this->error('小说不存在');
		}
		$novel = Novel::fromFetchRule($rule, $novelInfo);
		return $this->success($novel,
			'请求成功，请耐心等候系统处理'
		);
	}
	
	
	function push($url): array {
		$token = $this->request->header('Authorization', 'Bearer ');
		$token = str_replace('Bearer ', '', $token);
		if (!$token or strcmp($token, env('THIRD_TOKEN')) !== 0) {
			return $this->error('验证失败');
		}
		$url = trim($url);
		if (strpos($url, 'pixiv.net')) {
			$mode = null;
			$novelId = null;
			if (preg_match('/pixiv\.net\/novel\/series\/(\d+)/', $url, $matches)) {
				$mode = 'series';
				$novelId = intval($matches[1] ?? 0);
			} elseif (preg_match('/pixiv\.net\/novel\/show\.php\?id=(\d+)/', $url, $matches)) {
				$mode = 'novel';
				$novelId = intval($matches[1] ?? 0);
			}
			if (!$this->checkInQueue($novelId)) {
				/**
				 * @var PixivFetchRule $rule
				 */
				$rule = FetchRule::getRule('pixiv');
				if ($mode === 'novel') {
					$chapterInfo = $rule->fetchChapterContent((string)$novelId, (string)$novelId);
					if (!$chapterInfo) {
						return $this->error('小说不存在');
					}
					if (!empty($chapterInfo->ext_data['novelId'])) {
						$novelId = $chapterInfo->ext_data['novelId'];
						$mode = 'series';
						if ($this->checkInQueue($novelId)) {
							return $this->error('已经在队列中');
						}
					} else {
						$novelInfo = $rule->convertOneshotToNovel($chapterInfo);
						if ($novelInfo) {
							$novel = Novel::fromFetchRule($rule, $novelInfo);
							return $this->success($novel,
								'请求成功，请耐心等候系统处理'
							);
						}
					}
				}
				if ($mode === 'series') {
					$novelInfo = $rule->fetchNovelDetail((string)$novelId);
					if (!$novelInfo) {
						return $this->error('小说不存在');
					}
					$novel = Novel::fromFetchRule($rule, $novelInfo);
					return $this->success($novel,
						'请求成功，请耐心等候系统处理'
					);
				}
			} else {
				return $this->error('已经在队列中');
			}
		}
		return $this->error('暂不支持');
	}
	
	protected function checkInQueue($novelId): bool {
		if (empty($novelId)) {
			return true;
		}
		$cache = \FriendsOfHyperf\Helpers\cache();
		$key = 'third_bot_novel_' . md5((string)$novelId);
		if (!$cache->has($key)) {
			$cache->set($key, 1, 60 * 60 * 24);
			return false;
		}
		return true;
	}
	
	
	function queue(): array {
		return $this->success($this->fetchQueueService->info());
	}
}
