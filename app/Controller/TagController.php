<?php
declare(strict_types = 1);

namespace App\Controller;

use App\Controller\Abstract\FS_Controller;
use App\Model\Chapter;
use App\Model\Novel;
use App\Model\Tag;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Query\JoinClause;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;

#[Controller]
class TagController extends FS_Controller {
	protected function baseQuery(): Builder {
		return Tag::where('count', '>', 0)->orderBy('count', 'desc');
	}
	
	#[Cacheable(prefix: __CLASS__, ttl: 3600)]
	#[RequestMapping(path: '', methods: 'get')]
	function index(): array {
		return $this->success($this->baseQuery()->select()->get(), '获取成功');
	}
}
