<?php

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\StreamWrapper;
use GuzzleHttp\Psr7\Utils;
use Hyperf\Di\Annotation\Inject;
use Imagick;
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
		//兼容旧缓存，统一使用i.pixiv.re计算key
		$url = str_replace('{domain}', 'i.pixiv.re', $url);
		
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
		return "https://{$domain}/{$path}";
	}
	
	function getUri($origin_url): string {
		$root = \Hyperf\Support\env('APP_DOMAIN');
		$root .= \Hyperf\Support\env('API_ROOT');
		$url = "https://{$root}/media/image";
		$origin_url = str_replace('i.pximg.net', '{domain}', $origin_url);
		return $url . '?' . http_build_query([
				'url' => $origin_url,
				'sign' => $this->sign($origin_url),
			]);
	}
	
	function getOriginUrl($url): string {
		$domain = [
			'i.pximg.net',
			//'i.pixiv.re',
		][0];
		return str_replace('{domain}', $domain, $url);
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
				'Referer' => 'https://www.pixiv.net/',
				'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) ' .
					'AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Safari/537.36',
			],
		]);
		$res = $client->get($this->getOriginUrl($url), [
			'verify' => false,
		]);
		try {
			$image = $res->getBody()->getContents();
			$imagick = new Imagick();
			$imagick->readImageBlob($image);
			$imagick->setImageFormat('png');
			$imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
			$imagick->setImageCompressionQuality(70);
			$imagick->stripImage();
			if ($imagick->getImageHeight() > 225 and $imagick->getImageWidth() > 160) {
				$imagick->resizeImage(160, 225, Imagick::FILTER_LANCZOS, 1);
			}
			$this->getSystem()->write($path, $imagick->getImageBlob());
			return true;
		} catch (\Throwable $exception) {
			return false;
		}
	}
}
