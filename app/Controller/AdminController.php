<?php
declare(strict_types = 1);

namespace App\Controller;

use App\Controller\Abstract\FS_Controller;
use App\DataSet\DataSet;
use App\DataSet\LanguageService;
use App\FetchRule\FetchRule;
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

#[AutoController]
class AdminController extends FS_Controller {
	
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
	
	
	function queue(): array {
		return $this->success($this->fetchQueueService->info());
	}
}
