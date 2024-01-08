<?php
declare(strict_types = 1);

namespace App\Controller;

use App\Controller\Abstract\FS_Controller;
use App\DataSet\DataSet;
use App\FetchRule\FetchRule;
use App\Model\Chapter;
use App\Model\Novel;
use App\Model\Tag;
use App\Model\User;
use App\Service\MediaService;
use Carbon\Carbon;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Codec\Xml;
use Hyperf\Database\Model\Collection;
use Hyperf\Database\Query\Builder;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Qbhy\HyperfAuth\AuthManager;
use SimpleXMLElement;

#[Controller]
class SitemapController extends FS_Controller {
	const ROUTE = [
		Novel::class => '/pages/novel/info?id=%d',
	];
	
	const MAX_PAGE = 500;
	
	
	function getXmlRoot($root): SimpleXMLElement {
		return new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?>' . "<{$root}></{$root}>");
	}
	
	function withXml($data, $root): string {
		return Xml::toXml(
			$data,
			$root,
		);
	}
	
	/** @noinspection PhpUndefinedMethodInspection */
	#[Cacheable(prefix: "sitemap", value: '_index', ttl: 86400)]
	function index(): string {
		$root = $this->getXmlRoot('sitemapindex');
		$root->addAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
		
		//page.xml
		$sitemap = $root->addChild('sitemap');
		$sitemap->addChild(
			'loc',
			'https://' . \Hyperf\Support\env('APP_DOMAIN') . \Hyperf\Support\env('API_ROOT') . '/sitemap/page.xml'
		);
		//model.xml
		foreach (self::ROUTE as $model => $route) {
			$name = strtolower(\Hyperf\Support\class_basename($model));
			$pages = ceil($model::count('id') / self::MAX_PAGE);
			for ($i = 1; $i <= $pages; $i++) {
				$sitemap = $root->addChild('sitemap');
				$sitemap->addChild(
					'loc',
					'https://' . \Hyperf\Support\env('APP_DOMAIN') . \Hyperf\Support\env('API_ROOT') . '/sitemap/' . $name . '-' . $i . '.xml'
				);
			}
		}
		return $this->withXml([], $root);
	}
	
	#[Cacheable(prefix: "sitemap", value: '_novel', ttl: 86400)]
	function novel(int $page = 1): string {
		$root = $this->getXmlRoot('urlset');
		$root->addAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
		$root->addAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');
		$root->addAttribute('xmlns:video', 'http://www.google.com/schemas/sitemap-video/1.1');
		$root->addAttribute('xmlns:news', 'http://www.google.com/schemas/sitemap-news/0.9');
		$root->addAttribute('xmlns:mobile', 'http://www.google.com/schemas/sitemap-mobile/1.0');
		$root->addAttribute('xmlns:pagemap', 'http://www.google.com/schemas/sitemap-pagemap/1.0');
		$root->addAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');
		
		
		$items = \Hyperf\Collection\Collection::make(Novel::paginate(self::MAX_PAGE, ['*'], 'page', $page)->items());
		$items->each(function (Novel $novel) use ($root) {
			$src = 'https://' . \Hyperf\Support\env('APP_DOMAIN') . sprintf(self::ROUTE[Novel::class], $novel->id);
			
			$url = $root->addChild('url');
			$url->addChild('loc', $src);
			$url->addChild('lastmod', $novel->updated_at->toAtomString());
			$url->addChild('changefreq', 'daily');
			$url->addChild('priority', '0.8');
			
			$link = $url->addChild('xhtml:link');
			$link->addAttribute('rel', 'alternate');
			$link->addAttribute('hreflang', 'zh-CN');
			$link->addAttribute('href', $src);
		});
		return $this->withXml([], $root);
	}
	
	#[Cacheable(prefix: "sitemap", value: '_page', ttl: 86400)]
	function page(): string {
		$pages = [
			'/pages/index/index' => [
				'changefreq' => 'daily',
				'priority' => '0.7',
			],
			'/pages/index/about' => [
				'changefreq' => 'monthly',
				'priority' => '0.5',
			],
		];
		
		$root = $this->getXmlRoot('urlset');
		$root->addAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
		$root->addAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');
		$root->addAttribute('xmlns:video', 'http://www.google.com/schemas/sitemap-video/1.1');
		$root->addAttribute('xmlns:news', 'http://www.google.com/schemas/sitemap-news/0.9');
		$root->addAttribute('xmlns:mobile', 'http://www.google.com/schemas/sitemap-mobile/1.0');
		$root->addAttribute('xmlns:pagemap', 'http://www.google.com/schemas/sitemap-pagemap/1.0');
		$root->addAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');
		
		foreach ($pages as $page => $info) {
			$src = 'https://' . \Hyperf\Support\env('APP_DOMAIN') . $page;
			$url = $root->addChild('url');
			$url->addChild('loc', $src);
			$url->addChild('lastmod', Carbon::today()->toAtomString());
			$url->addChild('changefreq', $info['changefreq']);
			$url->addChild('priority', $info['priority']);
			
			$link = $url->addChild('xhtml:link');
			$link->addAttribute('rel', 'alternate');
			$link->addAttribute('hreflang', 'zh-CN');
			$link->addAttribute('href', $src);
		}
		return $this->withXml([], $root);
	}
	
	#[RequestMapping(path: 'novel-{page:\d+}.xml', methods: 'get')]
	function novelWrapper(int $page = 1): \Psr\Http\Message\MessageInterface|\Psr\Http\Message\ResponseInterface {
		return $this->response
			->withAddedHeader('Content-Type', 'application/xml')
			->withBody(
				new SwooleStream($this->novel($page))
			);
	}
	
	#[RequestMapping(path: 'page.xml', methods: 'get')]
	function pageWrapper(): \Psr\Http\Message\MessageInterface|\Psr\Http\Message\ResponseInterface {
		return $this->response
			->withAddedHeader('Content-Type', 'application/xml')
			->withBody(
				new SwooleStream($this->page())
			);
	}
	
	#[RequestMapping(path: 'index.xml', methods: 'get')]
	function indexWrapper(): \Psr\Http\Message\MessageInterface|\Psr\Http\Message\ResponseInterface {
		return $this->response
			->withAddedHeader('Content-Type', 'application/xml')
			->withBody(
				new SwooleStream($this->index())
			);
	}
	
	
	#[RequestMapping(path: 'robots.txt', methods: 'get')]
	function robots(): string {
		$src = 'https://' . \Hyperf\Support\env('APP_DOMAIN') . \Hyperf\Support\env('API_ROOT') . '/sitemap/index.xml';
		return 'sitemap: ' . $src;
	}
}
