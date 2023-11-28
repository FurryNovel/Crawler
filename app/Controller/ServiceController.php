<?php
declare(strict_types = 1);

namespace App\Controller;

use App\Controller\Abstract\AdminController;
use App\Controller\Abstract\Controller;
use App\Controller\Abstract\PublicController;
use App\FetchRule\FetchRule;
use App\Model\Novel;
use Hyperf\Database\Query\Builder;
use Hyperf\HttpServer\Annotation\AutoController;

#[AutoController]
class ServiceController extends AdminController {
	function restart(): array {}
}
