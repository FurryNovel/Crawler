<?php
declare(strict_types = 1);

namespace App\Controller\Ja;

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
class NovelController extends \App\Controller\NovelController {
	protected string $modelLanguage = 'ja_JP';
}
