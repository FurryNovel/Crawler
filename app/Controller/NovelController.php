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
	#[RequestMapping(path: '{key}[/{command}[/{other}]]', methods: 'get')]
	function command(int|string $key, ?string $command = null, ?string $other = null): array {
		if (is_numeric($key)) {
			return match ($command) {
				'chapter' => $this->chapters($key, $other),
				default => $this->success(Novel::findFromCache($key)),
			};
		} else if (is_string($key)) {
			return match ($key) {
				'recommend' => $this->byTag(),
				'tag' => $this->byTag($command),
				'search' => $this->bySearch($command),
				default => $this->error('参数错误'),
			};
		}
		return $this->error('参数错误');
	}
	
	function byTag(?string $tag = 'sfw'): array {
		return $this->success(Novel::where(function (Builder $query) use ($tag) {
			$query->where('status', Novel::STATUS_PUBLISH);
			$query->where('tags', 'like', '%' . $tag . '%');
		})->paginate());
	}
	
	function bySearch(string $keyword): array {
		return $this->success(Novel::where(function (Builder $query) use ($keyword) {
			$query->where('status', Novel::STATUS_PUBLISH);
			$query->where('name', 'like', '%' . $keyword . '%');
		})->paginate());
	}
	
	function chapters(string $novel_id, ?string $chapter_id = null): array {
		if ($chapter_id) {
			return $this->success(Chapter::findFromCache($chapter_id)->makeVisible('content'));
		}
		return $this->success(
			Chapter::where(function (Builder $query) use ($novel_id) {
				$query->where('novel_id', $novel_id);
				$query->where('status', Chapter::STATUS_PUBLISH);
			})->paginate(null, ['id', 'name', 'tags', 'text_count', 'word_count', 'created_at', 'updated_at'])
		);
	}
}
