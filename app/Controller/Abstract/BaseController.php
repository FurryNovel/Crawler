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

namespace App\Controller\Abstract;

use App\Service\UserService;
use App\Utils\Utils;
use Hyperf\Contract\TranslatorInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Paginator\LengthAwarePaginator;
use Hyperf\Paginator\Paginator;
use Psr\Container\ContainerInterface;
use Qbhy\HyperfAuth\AuthManager;

abstract class BaseController {
	#[Inject]
	protected ContainerInterface $container;
	#[Inject]
	protected RequestInterface $request;
	#[Inject]
	protected ResponseInterface $response;
	#[Inject]
	protected AuthManager $auth;
	#[Inject]
	protected UserService $userService;
	#[Inject]
	protected TranslatorInterface $translator;
	
	protected string $modelLanguage = Utils::DEFAULT_LANGUAGE;
	
	protected function trans($key): array|string {
		$this->translator->setLocale(Utils::getVisitorLanguage());
		$trans = $this->translator->trans($key);
		if (str_starts_with($trans, 'global.')) {
			return substr($trans, 7);
		}
		return $trans;
	}
	
	protected function success($data = null, $message = '操作成功', $code = 200): array {
		if ($data instanceof LengthAwarePaginator) {
			return [
				'code' => $code,
				'message' => $message,
				'page' => $data->currentPage(),
				'pageSize' => $data->perPage(),
				'total' => $data->total(),
				'count' => $data->count(),
				'data' => $data->items()
			];
		}
		return [
			'code' => $code,
			'message' => $this->trans('global.' . $message),
			'data' => $data
		];
	}
	
	protected function error($message = '发生错误', $code = 500): array {
		return [
			'code' => $code,
			'message' => $this->trans('global.' . $message),
		];
	}
}
