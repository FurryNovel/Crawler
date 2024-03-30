<?php
declare(strict_types = 1);

namespace App\Controller;

use App\Controller\Abstract\BaseController;
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
class SitemapController extends BaseController {
	const SUPPORT_LANGUAGES = [
		[
			'code' => 'zh-CN',
			'route' => 'zh',
		],
		[
			'code' => 'en-US',
			'route' => 'en',
		],
		[
			'code' => 'ja-JP',
			'route' => 'ja',
		],
	];
	
	const ROUTE = [
		Novel::class => '/novel/%d',
	];
	
	const MAX_PAGE = 500;
	
	
	function getXmlRoot($root): SimpleXMLElement {
		return new SimpleXMLElement(
			'<?xml version="1.0" encoding="utf-8"?>' . "<{$root}></{$root}>");
	}
	
	function withXml($data, $root): string {
		return str_replace(
			[
				'xmlns:xmlns="xmlns" ',
				'xmlns:xhtml="xhtml" ',
			],
			'',
			Xml::toXml(
				$data,
				$root,
			)
		);
	}
	
	/** @noinspection PhpUndefinedMethodInspection */
	#[Cacheable(prefix: "sitemap", value: '_index', ttl: 86400)]
	function index($v = 3): string {
		$root = $this->getXmlRoot('sitemapindex');
		$root->addAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
		
		//page.xml
		$sitemap = $root->addChild('sitemap');
		$sitemap->addChild(
			'loc',
			'https://' . \Hyperf\Support\env('APP_DOMAIN') . \Hyperf\Support\env('API_ROOT') . '/sitemap/page.xml?v=' . $v
		);
		//model.xml
		foreach (self::ROUTE as $model => $route) {
			$name = strtolower(\Hyperf\Support\class_basename($model));
			$pages = ceil($model::count('id') / self::MAX_PAGE);
			for ($i = 1; $i <= $pages; $i++) {
				$sitemap = $root->addChild('sitemap');
				$sitemap->addChild(
					'loc',
					'https://' . \Hyperf\Support\env('APP_DOMAIN') . \Hyperf\Support\env('API_ROOT') . '/sitemap/' . $name . '-' . $i . '.xml?v=' . $v
				);
			}
		}
		return $this->withXml([], $root);
	}
	
	#[Cacheable(prefix: "sitemap", value: '_novel', ttl: 86400)]
	function novel(int $page = 1): string {
		$root = $this->getXmlRoot('urlset');
		$root->addAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
		$root->addAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1', 'xmlns');
		$root->addAttribute('xmlns:video', 'http://www.google.com/schemas/sitemap-video/1.1', 'xmlns');
		$root->addAttribute('xmlns:news', 'http://www.google.com/schemas/sitemap-news/0.9', 'xmlns');
		$root->addAttribute('xmlns:mobile', 'http://www.google.com/schemas/sitemap-mobile/1.0', 'xmlns');
		$root->addAttribute('xmlns:pagemap', 'http://www.google.com/schemas/sitemap-pagemap/1.0', 'xmlns');
		$root->addAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml', 'xmlns');
		
		
		$items = \Hyperf\Collection\Collection::make(Novel::paginate(self::MAX_PAGE, ['*'], 'page', $page)->items());
		$items->each(function (Novel $novel) use ($root) {
			$src = sprintf(self::ROUTE[Novel::class], $novel->id);
			$this->builder($root, $src, $novel->updated_at->toAtomString());
		});
		return $this->withXml([], $root);
	}
	
	#[Cacheable(prefix: "sitemap", value: '_page', ttl: 86400)]
	function page(): string {
		$pages = [
			'/' => [
				'changefreq' => 'daily',
				'priority' => '0.7',
			],
			'/about' => [
				'changefreq' => 'monthly',
				'priority' => '0.5',
			],
		];
		
		$root = $this->getXmlRoot('urlset');
		$root->addAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
		$root->addAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1', 'xmlns');
		$root->addAttribute('xmlns:video', 'http://www.google.com/schemas/sitemap-video/1.1', 'xmlns');
		$root->addAttribute('xmlns:news', 'http://www.google.com/schemas/sitemap-news/0.9', 'xmlns');
		$root->addAttribute('xmlns:mobile', 'http://www.google.com/schemas/sitemap-mobile/1.0', 'xmlns');
		$root->addAttribute('xmlns:pagemap', 'http://www.google.com/schemas/sitemap-pagemap/1.0', 'xmlns');
		$root->addAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml', 'xmlns');
		
		foreach ($pages as $page => $info) {
			$this->builder($root, $page, Carbon::today()->toAtomString());
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
	
	protected function builder(
		SimpleXMLElement $root,
		string           $src,
		string           $modified,
	): void {
		foreach (self::SUPPORT_LANGUAGES as $lang) {
			$targetSrc = 'https://' . \Hyperf\Support\env('APP_DOMAIN') . '/' . $lang['route'] . $src;
			$url = $root->addChild('url');
			$url->addChild('loc', $targetSrc);
			$url->addChild('lastmod', $modified);
			$url->addChild('changefreq', 'daily');
			$url->addChild('priority', '0.8');
			
			$link = $url->addChild('xhtml:link', null, 'xhtml');
			$link->addAttribute('rel', 'alternate');
			$link->addAttribute('hreflang', $lang['code']);
			$link->addAttribute('href', $targetSrc);
		}
	}
}
