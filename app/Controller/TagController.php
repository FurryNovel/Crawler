<?php
declare(strict_types = 1);

namespace App\Controller;

use App\Controller\Abstract\BaseController;
use App\DataSet\DataSet;
use App\Model\Chapter;
use App\Model\Novel;
use App\Model\Tag;
use App\Utils\Utils;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Query\JoinClause;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;

#[Controller]
class TagController extends BaseController {
	#[Inject]
	protected DataSet $dataSet;
	
	protected function baseQuery(): Builder {
		return Tag::where('count', '>', 0)->orderBy('count', 'desc');
	}
	
	#[RequestMapping(path: '', methods: 'get')]
	function index(): array {
		return $this->success($this->tags($this->modelLanguage), '获取成功');
	}
	
	
	#[Cacheable(prefix: __CLASS__, value: '_#{lang}', ttl: 3600)]
	private function tags($lang): array {
		return $this->baseQuery()->select()->get()->each(function (Tag $tag) use ($lang) {
			$tag->name = $this->dataSet->convertTo($lang, null, [$tag->name])[0] ?? $tag->name;
		})->toArray();
	}
}
