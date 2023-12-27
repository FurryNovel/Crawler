<?php
declare(strict_types = 1);

namespace App\Controller;

use App\Controller\Abstract\FS_Controller;
use App\DataSet\DataSet;
use App\FetchRule\FetchRule;
use App\Model\Chapter;
use App\Model\Novel;
use App\Model\Tag;
use App\Model\User;
use App\Service\MediaService;
use Hyperf\Database\Model\Collection;
use Hyperf\Database\Query\Builder;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Qbhy\HyperfAuth\AuthManager;

#[AutoController]
class MediaController extends FS_Controller {
	
	#[Inject(lazy: true)]
	protected MediaService $media;
	
	function image($url, $sign) {
		if (!$this->media->checkSign($sign, $url)) {
			return $this->response->json($this->error('签名错误', 403));
		}
		$is_success = $this->media->save($url);
		if ($is_success) {
			$path = $this->media->getDriverUri($url);
			return $this->response->redirect($path, 302)->withAddedHeader('Cache-Control', 'max-age=31536000');
		}
		return $this->response->json($this->error('签名错误', 404))->withStatus(404);
	}
}
