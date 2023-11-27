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

namespace App\Exception\Handler;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use function Hyperf\Support\env;

class AppExceptionHandler extends ExceptionHandler {
	public function __construct(protected StdoutLoggerInterface $logger) {}
	
	public function handle(Throwable $throwable, ResponseInterface $response) {
		$this->logger->error(sprintf('%s[%s] in %s', $throwable->getMessage(), $throwable->getLine(), $throwable->getFile()));
		$this->logger->error($throwable->getTraceAsString());
		
		$data = [
			'code' => $throwable->getCode(),
			'message' => $throwable->getMessage(),
		];
		
		if (env('APP_ENV') == 'dev'){
			$data['file'] = $throwable->getFile();
			$data['line'] = $throwable->getLine();
			$data['traces'] = $throwable->getTrace();
		}
		
		return $response
			->withHeader('Server', 'Hyperf')
			->withHeader('Content-Type', 'application/json; charset=utf-8')
			->withStatus(500)
			->withBody(new SwooleStream(json_encode($data, JSON_UNESCAPED_UNICODE)));
	}
	
	public function isValid(Throwable $throwable): bool {
		return true;
	}
}
