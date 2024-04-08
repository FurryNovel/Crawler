<?php
declare(strict_types = 1);

namespace App\Controller;

use App\Controller\Abstract\BaseController;
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
class NovelController extends BaseController {
	
	#[Inject]
	protected DataSet $dataSet;
	
	protected function baseQuery(): Builder {
		$query = Novel::where(function (Builder $query) {
			$query->whereIn('novel.status', [
				Novel::STATUS_PUBLISH
			]);
		});
		
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
		
		$tags_mode = $this->request->input('tags_mode', 'novel');
		
		
		switch ($order) {
			case 'desc':
			case 'asc':
				break;
			default:
				$order = 'desc';
				break;
		}
		
		if (!empty($tags)) {
			$tags = array_values(
				array_slice(
					$this->dataSet->convertToPattern(null, $tags),
					0,
					10
				)
			);
			if (!empty($tags)) {
				foreach ($tags as $tag) {
					$query->where(function (Builder $query) use ($tag, $tags_mode) {
						$query->where('novel.tags', 'like', '%' . $tag . '%');
						if ($tags_mode !== 'novel') {
							$query->whereIn('novel.id', function (\Hyperf\Database\Query\Builder $query) use ($tag) {
								$query->select('novel_id')
									->from('chapter')
									->where('chapter.status', Chapter::STATUS_PUBLISH)
									->where('chapter.tags', 'like', '%' . $tag . '%');
							}, 'OR');
						}
					});
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
		$items = $data->getCollection()
			->map(function (Novel $novel) {
				return $novel->withLanguage($this->modelLanguage);
			});
		if ($with_chapters) {
			$items = $items->map(function (Novel $novel) {
				return $novel->load(['latestChapters']);
			});
		}
		$data = $data->setCollection($items);
		return $this->success($data, '获取成功');
	}
	
	#[RequestMapping(path: '{novel_id:\d+}', methods: 'get')]
	function novel(string $novel_id): array {
		$novel = Novel::findFromCache($novel_id);
		if (!$novel or !in_array($novel->status, [Novel::STATUS_PUBLISH, Novel::STATUS_SUSPEND])) {
			return $this->error('小说未公开');
		}
		$novel->load(['latestChapters']);
		return $this->success($novel->withLanguage($this->modelLanguage));
	}
	
	
	#[RequestMapping(path: '{novel_id:\d+}/action/{action_name}')]
	function action(string $novel_id, string $action_name): array {
		$novel = Novel::findFromCache($novel_id);
		if (!$novel or !in_array($novel->status, [Novel::STATUS_PUBLISH, Novel::STATUS_SUSPEND])) {
			return $this->error('小说未公开');
		}
		switch ($action_name) {
			default:
				if (!in_array($action_name . '_count', $novel->getLazy())) {
					return $this->error('不支持的操作');
				}
				$novel->delayInc($action_name . '_count', 1);
				break;
		}
		return $this->success();
	}
	
	
	#[Cacheable(prefix: __CLASS__, ttl: 300)]
	#[RequestMapping(path: '{novel_id:\d+}/chapter[/{chapter_id:\d+}]', methods: 'get')]
	function chapters(string $novel_id, ?string $chapter_id = null): array {
		if ($chapter_id) {
			return $this->success(Chapter::findFromCache($chapter_id)
				->withLanguage($this->modelLanguage)
				->makeVisible('content'));
		}
		return $this->success(
			Chapter::where(function (Builder $query) use ($novel_id) {
				$query->where('novel_id', $novel_id);
				$query->where('status', Chapter::STATUS_PUBLISH);
			})
				->get(['id', 'name', 'tags', 'text_count', 'word_count', 'created_at', 'updated_at'])
				->map(function (Chapter $chapter) {
					return $chapter->withLanguage($this->modelLanguage);
				})
		);
	}
	
}
