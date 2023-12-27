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

return [
	'default' => 's3',
	'storage' => [
		'local' => [
			'driver' => \Hyperf\Filesystem\Adapter\LocalAdapterFactory::class,
			'root' => __DIR__ . '/../../runtime',
		],
		'ftp' => [
			'driver' => \Hyperf\Filesystem\Adapter\FtpAdapterFactory::class,
			'host' => 'ftp.example.com',
			'username' => 'username',
			'password' => 'password',
			// 'port' => 21,
			// 'root' => '/path/to/root',
			// 'passive' => true,
			// 'ssl' => true,
			// 'timeout' => 30,
			// 'ignorePassiveAddress' => false,
			// 'timestampsOnUnixListingsEnabled' => true,
		],
		'memory' => [
			'driver' => \Hyperf\Filesystem\Adapter\MemoryAdapterFactory::class,
		],
		's3' => [
			'driver' => \Hyperf\Filesystem\Adapter\S3AdapterFactory::class,
			'credentials' => [
				'key' => Hyperf\Support\env('S3_KEY'),
				'secret' => Hyperf\Support\env('S3_SECRET'),
			],
			'region' => Hyperf\Support\env('S3_REGION'),
			'version' => 'latest',
			'bucket_endpoint' => false,
			'use_path_style_endpoint' => false,
			'endpoint' => Hyperf\Support\env('S3_ENDPOINT'),
			'bucket_name' => Hyperf\Support\env('S3_BUCKET'),
			'scheme' => 'http'
		],
		'minio' => [
			'driver' => \Hyperf\Filesystem\Adapter\S3AdapterFactory::class,
			'credentials' => [
				'key' => Hyperf\Support\env('S3_KEY'),
				'secret' => Hyperf\Support\env('S3_SECRET'),
			],
			'region' => Hyperf\Support\env('S3_REGION'),
			'version' => 'latest',
			'bucket_endpoint' => false,
			'use_path_style_endpoint' => true,
			'endpoint' => Hyperf\Support\env('S3_ENDPOINT'),
			'bucket_name' => Hyperf\Support\env('S3_BUCKET'),
		],
		'oss' => [
			'driver' => \Hyperf\Filesystem\Adapter\AliyunOssAdapterFactory::class,
			'accessId' => Hyperf\Support\env('OSS_ACCESS_ID'),
			'accessSecret' => Hyperf\Support\env('OSS_ACCESS_SECRET'),
			'bucket' => Hyperf\Support\env('OSS_BUCKET'),
			'endpoint' => Hyperf\Support\env('OSS_ENDPOINT'),
			// 'timeout' => 3600,
			// 'connectTimeout' => 10,
			// 'isCName' => false,
			// 'token' => null,
			// 'proxy' => null,
		],
		'qiniu' => [
			'driver' => \Hyperf\Filesystem\Adapter\QiniuAdapterFactory::class,
			'accessKey' => Hyperf\Support\env('QINIU_ACCESS_KEY'),
			'secretKey' => Hyperf\Support\env('QINIU_SECRET_KEY'),
			'bucket' => Hyperf\Support\env('QINIU_BUCKET'),
			'domain' => Hyperf\Support\env('QINIU_DOMAIN'),
		],
		'cos' => [
			'driver' => \Hyperf\Filesystem\Adapter\CosAdapterFactory::class,
			'region' => Hyperf\Support\env('COS_REGION'),
			'app_id' => Hyperf\Support\env('COS_APPID'),
			'secret_id' => Hyperf\Support\env('COS_SECRET_ID'),
			'secret_key' => Hyperf\Support\env('COS_SECRET_KEY'),
			// 可选，如果 bucket 为私有访问请打开此项
			// 'signed_url' => false,
			'bucket' => Hyperf\Support\env('COS_BUCKET'),
			'read_from_cdn' => false,
			// 'timeout' => 60,
			// 'connect_timeout' => 60,
			// 'cdn' => '',
			// 'scheme' => 'https',
		],
	],
];
