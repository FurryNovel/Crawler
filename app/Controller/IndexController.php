<?php

declare(strict_types = 1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Controller;

use App\Controller\Abstract\Controller;
use App\FetchRule\FetchRule;
use Hyperf\HttpServer\Annotation\AutoController;

#[AutoController]
class IndexController extends Controller {
	public function index(): array {
		
		return [
			'text' => FetchRule::getRule('pixiv')
				->fetchChapterContent('', '20065569'),
		];
	}
	
	public function test(): array {
		return [
			'text' => 'connection',
		];
	}
}
