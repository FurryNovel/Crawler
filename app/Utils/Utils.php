<?php

namespace App\Utils;

use Hyperf\Context\ApplicationContext;
use Hyperf\HttpServer\Contract\RequestInterface;

class Utils {
	static public function getVisitorIP() {
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
	}
	
	static public function getVisitorLanguage(): string {
		$request = ApplicationContext::getContainer()->get(RequestInterface::class);
		$lang = $request->getHeaderLine('x-language');
		if ($lang) {
			return str_replace('-', '_', $lang);
		} else {
			return 'zh_CN';
		}
	}
}