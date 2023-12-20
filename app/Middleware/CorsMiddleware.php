<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Hyperf\Context\Context;

class CorsMiddleware implements MiddlewareInterface {
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		$response = Context::get(ResponseInterface::class);
		$response = $response->withHeader('Access-Control-Allow-Origin', '*')
			->withHeader('Access-Control-Allow-Credentials', 'true')
			->withHeader('Access-Control-Allow-Headers', 'DNT,Keep-Alive,User-Agent,Cache-Control,Content-Type,Authorization')
			->withHeader('Access-Control-Max-Age', 3600);
		
		Context::set(ResponseInterface::class, $response);
		
		if ($request->getMethod() == 'OPTIONS') {
			return $response;
		}
		
		return $handler->handle($request);
	}
}