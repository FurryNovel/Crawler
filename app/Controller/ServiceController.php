<?php
declare(strict_types = 1);

namespace App\Controller;

use App\Controller\Abstract\FS_Controller;
use App\FetchRule\FetchRule;
use App\Middleware\AdminMiddleware;
use App\Model\Novel;
use Hyperf\Database\Query\Builder;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Middleware;

#[AutoController]
#[Middleware(AdminMiddleware::class)]
class ServiceController extends FS_Controller {
	function restart(): array {}
}
