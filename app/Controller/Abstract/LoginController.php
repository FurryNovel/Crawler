<?php

namespace App\Controller\Abstract;

use App\Middleware\AdminMiddleware;
use App\Service\UserService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Middleware;
use Qbhy\HyperfAuth\AuthManager;
use Qbhy\HyperfAuth\AuthMiddleware;

#[Middleware(AuthMiddleware::class)]
abstract class LoginController extends Controller {
	
}