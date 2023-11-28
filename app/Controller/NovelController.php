<?php
declare(strict_types = 1);

namespace App\Controller;

use App\Controller\Abstract\PublicController;
use App\FetchRule\FetchRule;
use App\Model\Novel;
use Hyperf\Database\Model\Builder;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;

#[Controller]
class NovelController extends PublicController {
	#[RequestMapping(path: '{key}/[{params}]', methods: 'get')]
	function command(int|string $key, ?string $params = null): array {
		if (is_numeric($key)) {
			return $this->success(Novel::findFromCache($key));
		} else if (is_string($key)) {
			return match ($key) {
				'recommend' => $this->tags(),
				'tag' => $this->tags($params),
				'search' => $this->search($params),
				default => $this->error('参数错误'),
			};
		}
		return $this->error('参数错误');
	}
	
	function tags(?string $tag = 'sfw'): array {
		return $this->success(Novel::where(function (Builder $query) use ($tag) {
			$query->where('tags', 'like', '%' . $tag . '%');
		})->paginate());
	}
	
	function search(string $keyword): array {
		return $this->success(Novel::where(function (Builder $query) use ($keyword) {
			$query->where('name', 'like', '%' . $keyword . '%');
		})->paginate());
	}
}
