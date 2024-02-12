<?php
declare(strict_types = 1);

namespace App\Controller;

use App\Controller\Abstract\FS_Controller;
use App\DataSet\DataSet;
use App\Model\Chapter;
use App\Model\Novel;
use App\Utils\Utils;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Query\JoinClause;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Psr\SimpleCache\CacheInterface;
use function FriendsOfHyperf\Helpers\di;

#[Controller]
class NovelController extends FS_Controller {
	#[Inject]
	protected DataSet $dataSet;
	
	protected function baseQuery(): Builder {
		$query = Novel::where('novel.status', Novel::STATUS_PUBLISH);
		
		$ids = $this->request->input('ids');
		$tags = array_values(array_filter($this->request->input('tags', []), 'App\Utils\Utils::filterLike'));
		if (empty($tags)) {
			$tags = Utils::filterLike($this->request->input('tag'));
			if (!empty($tags) and is_string($tags)) {
				$tags = [$tags];
			}
		}
		$hate_tags = array_values(array_filter($this->request->input('hate_tags', []), 'App\Utils\Utils::filterLike'));
		$keyword = Utils::filterLike($this->request->input('keyword'));
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
		
		if (!empty($tags)) {
			if (count($tags) == 1) {
				$query->where(function (Builder $query) use ($tags) {
					$tag = $this->dataSet->convertToPattern(null, $tags)[0] ?? '';
					$query->where('novel.tags', 'like', '%' . $tag . '%');
					$query->whereIn('novel.id', function (\Hyperf\Database\Query\Builder $query) use ($tag) {
						$query->select('novel_id')
							->from('chapter')
							->where('chapter.status', Chapter::STATUS_PUBLISH)
							->where('chapter.tags', 'like', '%' . $tag . '%');
					}, 'OR');
				});
			} else {
				$tags = array_values(
					array_slice(
						$this->dataSet->convertToPattern(null, $tags),
						0,
						10
					)
				);
				if (!empty($tags)) {
					foreach ($tags as $tag) {
						$query->where('novel.tags', 'like', '%' . $tag . '%');
					}
				}
			}
		}
		if (!empty($keyword)) {
			$query->where(function (Builder $query) use ($keyword) {
				$query->where('novel.name', 'like', '%' . $keyword . '%', 'OR');
				$query->where('novel.desc', 'like', '%' . $keyword . '%', 'OR');
				$query->whereIn('novel.author_id', function (\Hyperf\Database\Query\Builder $query) use ($keyword) {
					$query->select('id')
						->from('user')
						->where('user.nickname', 'like', '%' . $keyword . '%');
				}, 'OR');
			});
		}
		if ($user_id) {
			$query->where('novel.author_id', $user_id);
		}
		if ($ids) {
			$query->whereIn('novel.id', array_map('intval', $ids));
		}
		if (!empty($hate_tags)) {
			$hate_tags = array_values(
				array_slice(
					$this->dataSet->convertToPattern(null, $hate_tags),
					0,
					10
				)
			);
			if (!empty($hate_tags)) {
				foreach ($hate_tags as $tag) {
					$query->where('novel.tags', 'not like', '%' . $tag . '%');
				}
			}
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
	function index(bool $with_chapters = false, int $limit = 15): array {
		$query = $this->baseQuery();
		if ($limit > 30 || $limit < 1) {
			$limit = 15;
		}
		$data = $query->paginate($limit);
		if ($with_chapters) {
			$data->getCollection()->each(function (Novel $novel) {
				$novel->load(['latestChapters']);
			});
		}
		return $this->success($data, '获取成功');
	}
	
	#[RequestMapping(path: '{novel_id:\d+}', methods: 'get')]
	function novel(string $novel_id): array {
		$novel = Novel::findFromCache($novel_id);
		if (!$novel or $novel->status !== Novel::STATUS_PUBLISH) {
			return $this->error('小说未公开');
		}
		$novel->load(['latestChapters']);
		//tags不触发getter
		$novel->tags = $novel->tags ?? [];
		
		//增加访问计数
		$redis = di(\Hyperf\Redis\Redis::class);
		if (!$redis->hExists('novel:view_count', $novel_id)) {
			$redis->hSet('novel:view_count', $novel_id, $novel->view_count);
		}
		$redis->hIncrBy('novel:view_count', $novel_id, 1);
		
		//
		return $this->success($novel);
	}
	
	#[Cacheable(prefix: __CLASS__, ttl: 300)]
	#[RequestMapping(path: '{novel_id:\d+}/chapter[/{chapter_id:\d+}]', methods: 'get')]
	function chapters(string $novel_id, ?string $chapter_id = null): array {
		if ($chapter_id) {
			return $this->success(Chapter::findFromCache($chapter_id)->makeVisible('content'));
		}
		return $this->success(
			Chapter::where(function (Builder $query) use ($novel_id) {
				$query->where('novel_id', $novel_id);
				$query->where('status', Chapter::STATUS_PUBLISH);
			})->get(['id', 'name', 'tags', 'text_count', 'word_count', 'created_at', 'updated_at'])
		);
	}
	
}
