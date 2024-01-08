<?php

namespace App\Utils;

use Hyperf\Context\ApplicationContext;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Logger\LoggerFactory;

class Utils {
	const DEFAULT_LANGUAGE = 'zh_CN';
	
	static public function getVisitorIP() {
		if (ApplicationContext::hasContainer()) {
			$container = ApplicationContext::getContainer();
			if ($container->has(RequestInterface::class)) {
				$request = ApplicationContext::getContainer()->get(RequestInterface::class);
				$res = $request->getServerParams();
				if (isset($res['http_client_ip'])) {
					return $res['http_client_ip'];
				} elseif (isset($res['http_x_real_ip'])) {
					return $res['http_x_real_ip'];
				} elseif (isset($res['http_x_forwarded_for'])) {
					$arr = explode(',', $res['http_x_forwarded_for']);
					return $arr[0];
				} else {
					return $res['remote_addr'];
				}
			} else {
				return '';
			}
		} else {
			return '';
		}
	}
	
	static public function getVisitorLanguage(): string {
		if (ApplicationContext::hasContainer()) {
			$container = ApplicationContext::getContainer();
			if ($container->has(RequestInterface::class)) {
				$request = $container->get(RequestInterface::class);
				$res = $request->getHeader('accept-language');
				if (isset($res[0])) {
					$arr = explode(',', $res[0]);
					if (isset($arr[0])) {
						$arr[0] = str_replace('-', '_', $arr[0]);
					}
					return $arr[0] ?? self::DEFAULT_LANGUAGE;
				} else {
					return self::DEFAULT_LANGUAGE;
				}
			} else {
				return self::DEFAULT_LANGUAGE;
			}
		} else {
			return self::DEFAULT_LANGUAGE;
		}
	}
	
	static public function filterLike(?string $str): ?string {
		if ($str)
			return str_replace(['%', '\\'], '', $str);
		return null;
	}
	
	static public function err(mixed $error): void {
		$logger = ApplicationContext::getContainer()->get(LoggerFactory::class)->get('app');
		if ($error instanceof \Throwable) {
			$logger->error($error->getMessage(), [
				'file' => $error->getFile(),
				'line' => $error->getLine(),
				'trace' => $error->getTraceAsString()
			]);
		} else {
			$logger->error(json_encode($error));
		}
	}
}