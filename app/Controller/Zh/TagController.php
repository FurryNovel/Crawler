<?php
declare(strict_types = 1);

namespace App\Controller\Zh;

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
class TagController extends \App\Controller\TagController {
	protected string $modelLanguage = 'zh_CN';
}
