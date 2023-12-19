<?php
declare(strict_types = 1);

namespace App\Controller;

use App\Controller\Abstract\FS_Controller;
use App\Model\Chapter;
use App\Model\Novel;
use Hyperf\Database\Model\Builder;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;

#[Controller]
class NovelController extends FS_Controller {
	#[RequestMapping(path: 'tag/{tag:\S+}', methods: 'get')]
	function byTag(?string $tag = 'sfw'): array {
		return $this->success(Novel::where(function (Builder $query) use ($tag) {
			$query->where('status', Novel::STATUS_PUBLISH);
			$query->where('tags', 'like', '%' . $tag . '%');
		})->paginate());
	}
	
	#[RequestMapping(path: 'latest', methods: 'get')]
	function latest(): array {
		return $this->success(
			Novel::where('status', Novel::STATUS_PUBLISH)
				->orderBy('created_at', 'desc')
				->paginate()
		);
	}
	
	#[RequestMapping(path: 'search/{keyword}', methods: 'get')]
	function bySearch(string $keyword): array {
		return $this->success(Novel::where(function (Builder $query) use ($keyword) {
			$query->where('status', Novel::STATUS_PUBLISH);
			$query->where('name', 'like', '%' . $keyword . '%');
		})->paginate());
	}
	
	#[RequestMapping(path: 'user/{user_id}', methods: 'get')]
	function byUser(string $user_id): array {
		return $this->success(Novel::where(function (Builder $query) use ($user_id) {
			$query->where('status', Novel::STATUS_PUBLISH);
			$query->where('author_id', $user_id);
		})->paginate());
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
