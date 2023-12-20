<?php
declare(strict_types = 1);

namespace App\Controller;

use App\Controller\Abstract\FS_Controller;
use App\Model\Chapter;
use App\Model\Novel;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Query\JoinClause;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;

#[Controller]
class NovelController extends FS_Controller {
	protected function baseQuery(): Builder {
		$query = Novel::where('novel.status', Novel::STATUS_PUBLISH);
		
		$tag = $this->request->input('tag');
		$keyword = $this->request->input('keyword');
		$user_id = intval($this->request->input('user_id'));
		$order_by = $this->request->input('order_by', 'latest');
		$order = $this->request->input('order', 'desc');
		
		switch ($order) {
			case 'desc':
			case 'asc':
				break;
			
			default:
				$order = 'desc';
				break;
		}
		
		if ($tag) {
			$query->where('novel.tags', 'like', '%' . $tag . '%');
		}
		if ($keyword) {
			$query->where('novel.name', 'like', '%' . $keyword . '%');
		}
		if ($user_id) {
			$query->where('novel.author_id', $user_id);
		}
		
		switch ($order_by) {
			case 'latest':
				$query->orderBy('updated_at', $order);
				break;
			case 'popular':
				$query->orderBy('view_count', $order);
				break;
			case 'newest':
				$query->orderBy('created_at', $order);
				break;
			case 'random':
				$query->inRandomOrder();
				break;
		}
		return $query;
	}
	
	#[RequestMapping(path: '', methods: 'get')]
	function index(): array {
		return $this->success($this->baseQuery()->paginate(), '获取成功');
	}
	
	#[RequestMapping(path: '{novel_id:\d+}', methods: 'get')]
	function novel(string $novel_id): array {
		$novel = Novel::findFromCache($novel_id);
		if (!$novel or $novel->status !== Novel::STATUS_PUBLISH) {
			return $this->error('小说未公开');
		}
		$novel->load(['latestChapters']);
		return $this->success($novel);
	}
	
	#[RequestMapping(path: '{novel_id:\d+}/chapter[/{chapter_id:\d+}]', methods: 'get')]
	function chapters(string $novel_id, ?string $chapter_id = null, ?string $current = null): array {
		if ($chapter_id) {
			return $this->success(Chapter::findFromCache($chapter_id)->makeVisible('content'));
		}
		if ($current) {
			$count = Chapter::where(function (Builder $query) use ($current, $novel_id) {
				$query->where('novel_id', $novel_id);
				$query->where('id', '<', $current);
				$query->where('status', Chapter::STATUS_PUBLISH);
			})->count('id');
			$page = (int)ceil($count / 15);
			return $this->success(
				Chapter::where(function (Builder $query) use ($current, $novel_id) {
					$query->where('novel_id', $novel_id);
					$query->where('status', Chapter::STATUS_PUBLISH);
				})->paginate(
					null,
					['id', 'name', 'tags', 'text_count', 'word_count', 'created_at', 'updated_at'],
					'page',
					$page
				)
			);
		} else {
			return $this->success(
				Chapter::where(function (Builder $query) use ($novel_id) {
					$query->where('novel_id', $novel_id);
					$query->where('status', Chapter::STATUS_PUBLISH);
				})->paginate(null, ['id', 'name', 'tags', 'text_count', 'word_count', 'created_at', 'updated_at'])
			);
		}
	}
	
}
