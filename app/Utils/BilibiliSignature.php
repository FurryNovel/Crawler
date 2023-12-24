<?php

namespace App\Utils;

use GuzzleHttp\Client;

class BilibiliSignature {
	const mixinKeyEncTab = [
		46, 47, 18, 2, 53, 8, 23, 32, 15, 50, 10, 31, 58, 3, 45, 35, 27, 43, 5, 49,
		33, 9, 42, 19, 29, 28, 14, 39, 12, 38, 41, 13, 37, 48, 7, 16, 24, 55, 40,
		61, 26, 17, 0, 1, 60, 51, 30, 4, 22, 25, 54, 21, 56, 59, 6, 63, 57, 62, 11,
		36, 20, 34, 44, 52
	];
	
	function getMixinKey($orig, $mixinKeyEncTab): string {
		$mixinKey = '';
		foreach ($mixinKeyEncTab as $n) {
			$mixinKey .= $orig[$n];
		}
		return substr($mixinKey, 0, 32);
	}
	
	function encWbi($params, $img_key, $sub_key): string {
		$mixin_key = $this->getMixinKey($img_key . $sub_key, self::mixinKeyEncTab);
		$curr_time = round(microtime(true));
		$params['wts'] = $curr_time;
		ksort($params);
		
		$query = '';
		foreach ($params as $key => $value) {
			$value = preg_replace('/[!\'()*]/', '', $value);
			$query .= urlencode($key) . '=' . urlencode($value) . '&';
		}
		$query = rtrim($query, '&');
		$wbi_sign = md5($query . $mixin_key);
		return $query . '&w_rid=' . $wbi_sign;
	}
	
	function getWbiKeys(): array {
		$url = 'https://api.bilibili.com/x/web-interface/nav';
		$client = new Client(); // 创建一个Guzzle的客户端对象
		$response = $client->request('GET', $url, [
			'headers' => [
				'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) ' .
					'AppleWebKit/537.36 (KHTML, like Gecko) ' .
					'Chrome/91.0.4472.124 Safari/537.36 Edg/91.0.864.67',
			],
		]);
		// 使用Guzzle发送GET请求，并设置Cookie头
		$result = $response->getBody()->getContents();
		// 获取响应的内容
		$data = json_decode($result, true);
		return [
			'img_key' => substr(
				$data['data']['wbi_img']['img_url'],
				strrpos($data['data']['wbi_img']['img_url'], '/') + 1,
				strrpos($data['data']['wbi_img']['img_url'], '.') - strrpos($data['data']['wbi_img']['img_url'], '/') - 1
			),
			'sub_key' => substr(
				$data['data']['wbi_img']['sub_url'],
				strrpos($data['data']['wbi_img']['sub_url'], '/') + 1,
				strrpos($data['data']['wbi_img']['sub_url'], '.') - strrpos($data['data']['wbi_img']['sub_url'], '/') - 1
			)
		];
	}
	
	function sign($params): string {
		$keys = $this->getWbiKeys();
		return $this->encWbi($params, $keys['img_key'], $keys['sub_key']);
	}
}