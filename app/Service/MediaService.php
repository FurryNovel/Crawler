<?php

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\StreamWrapper;
use GuzzleHttp\Psr7\Utils;
use Hyperf\Di\Annotation\Inject;
use League\Flysystem\Filesystem;

class MediaService {
	protected ?Filesystem $system = null;
	#[Inject]
	protected \Hyperf\Filesystem\FilesystemFactory $factory;
	
	function getSystem(): Filesystem {
		if (!$this->system) {
			$this->system = $this->factory->get('s3');
		}
		return $this->system;
	}
	
	function getKey($url): string {
		$ext = pathinfo($url, PATHINFO_EXTENSION);
		$key = md5($url);
		if ($ext) {
			$key .= '.' . $ext;
		}
		return $key;
	}
	
	function getDriverUri($url): string {
		$path = $this->getKey($url);
		$domain = \Hyperf\Support\env('IMAGE_DOMAIN');
		return $domain . '/' . $path;
	}
	
	function getUri($origin_url): string {
		$url = '/media/image';
		return $url . '?' . http_build_query([
				'url' => $origin_url,
				'sign' => $this->sign($origin_url),
			]);
	}
	
	
	function sign($url): string {
		$path = $this->getKey($url);
		$salt = \Hyperf\Support\env('IMAGE_SALT');
		return md5($path . $salt);
	}
	
	function checkSign($sign, $url): bool {
		return $sign == $this->sign($url);
	}
	
	
	function save($url): bool {
		$path = $this->getKey($url);
		if ($this->getSystem()->has($path)) {
			return true;
		}
		$client = new Client([
			'headers' => [
				'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) ' .
					'AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Safari/537.36',
			],
		]);
		$res = $client->get($url, [
			'stream' => true,
			'verify' => false,
		]);
		$stream = $res->getBody();
		try {
			$this->getSystem()->writeStream($path, StreamWrapper::getResource($stream));
			return true;
		} catch (\Throwable $exception) {
			//@todo
			//throw $exception->getPrevious();
			return false;
		}
	}
}