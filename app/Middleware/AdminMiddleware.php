<?php

namespace App\Middleware;

use App\Model\User;
use App\Service\UserService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AdminMiddleware implements MiddlewareInterface {
	protected ContainerInterface $container;
	protected RequestInterface $request;
	protected HttpResponse $response;
	
	#[Inject]
	protected UserService $userService;
	
	public function __construct(ContainerInterface $container, HttpResponse $response, RequestInterface $request) {
		$this->container = $container;
		$this->response = $response;
		$this->request = $request;
	}
	
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		$user = $this->userService->current_user();
		if (!$user) {
			return $this->response->json([
				'code' => 401,
				'message' => '请先登录'
			]);
		}
		$user = $this->userService->current_user();
		if ($user->type !== User::TYPE_ADMIN) {
			return $this->response->json([
				'code' => 403,
				'message' => '权限不足'
			]);
		}
		return $handler->handle($request);
	}
}