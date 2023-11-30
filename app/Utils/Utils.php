<?php

namespace App\Utils;

use Hyperf\Context\ApplicationContext;
use Hyperf\HttpServer\Contract\RequestInterface;

class Utils {
	static public function ip() {
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
}