<?php
declare(strict_types = 1);

namespace App\Controller;

use App\Controller\Abstract\BaseController;
use App\DataSet\DataSet;
use App\FetchRule\FetchRule;
use App\Model\Chapter;
use App\Model\Novel;
use App\Model\Tag;
use App\Model\User;
use App\Service\MediaService;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Database\Model\Collection;
use Hyperf\Database\Query\Builder;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Qbhy\HyperfAuth\AuthManager;

#[AutoController]
class MediaController extends BaseController {
	#[Inject]
	protected MediaService $media;
	
	#[Cacheable(prefix: __CLASS__, ttl: 604800)]
	function checkCache(string $url): array {
		$is_success = $this->media->save($url);
		return [
			'result' => $is_success,
		];
	}
	
	
	function image(string $url, string $sign) {
		if (!$this->media->checkSign($sign, $url)) {
			return $this->response->json($this->error('签名错误', 403));
		}
		$is_success = $this->checkCache($url);
		if ($is_success) {
			$path = $this->media->getDriverUri($url);
			return $this->response
				->redirect($path, 302)
				->withHeader('Cache-Control', 'public, max-age=604800')
				->withHeader('Expires', gmdate('D, d M Y H:i:s T', strtotime('+7d')));
		}
		return $this->response->json($this->error('签名错误', 404))->withStatus(404);
	}
}
