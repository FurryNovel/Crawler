<?php

namespace App\Controller\Abstract;

use App\Middleware\AdminMiddleware;
use App\Model\User;
use Hyperf\HttpServer\Annotation\Middleware;

#[Middleware(AdminMiddleware::class)]
abstract class AdminController extends LoginController {

}