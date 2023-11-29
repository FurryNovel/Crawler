<?php
declare(strict_types = 1);

namespace App\Controller;

use App\Controller\Abstract\FS_Controller;
use App\FetchRule\FetchRule;
use App\Middleware\AdminMiddleware;
use App\Model\Author;
use App\Model\Novel;
use App\Model\User;
use App\Service\FetchQueueService;
use Hyperf\Database\Model\Builder;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;

#[AutoController]
#[Middleware(AdminMiddleware::class)]
class AdminController extends FS_Controller {
	#[Inject]
	protected FetchQueueService $fetchQueueService;
	
	function fetch_novel(string $type, string $rule_novel_id): array {
		$rule = FetchRule::getRule($type);
		if (!$rule) {
			return $this->error('规则不存在');
		}
		$rule_novel_id = trim($rule_novel_id);
		
		$novelInfo = $rule->fetchNovelDetail($rule_novel_id);
		$novelInfo->author = $novelInfo->author ?? '佚名';
		
		
		$author = Author::where(function (Builder $query) use ($novelInfo) {
			$query->where('name', $novelInfo->author);
		})->first();
		if (!$author) {
			$authorInfo = $rule->fetchAuthorInfo($novelInfo->author_id);
			$author = Author::register(
				User::TYPE_AUTHOR,
				$authorInfo->name,
				base64_encode('kk_novel_' . $authorInfo->name),
				[]
			);
		}
		/**
		 * @var Novel $novel
		 */
		$novel = Novel::where(function (Builder $query) use ($rule_novel_id, $novelInfo) {
			$query->where('source_id', $rule_novel_id);
		})->first();
		
		if (!$novel) {
			$novel = new Novel([
				'author_id' => $author->id,
				'name' => $novelInfo->name,
				'cover' => $novelInfo->cover,
				'desc' => $novelInfo->desc,
				'tags' => $novelInfo->tags,
				'view_count' => 0,
				'furry_weight' => 0,
				'source' => $type,
				'source_id' => $rule_novel_id,
				'status' => Novel::STATUS_PUBLISH,
				'ext_data' => [],
			]);
			$novel->save();
		}
		$this->fetchQueueService->push([
			'novel_id' => $novel->id
		]);
		return $this->success($novel,
			'请求成功，请耐心等候系统处理'
		);
	}
}
