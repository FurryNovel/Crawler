<?php

namespace App\FetchRule;

use App\FetchRule\FetchRule;
use App\Utils\BilibiliSignature;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\CoroutineHandler;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @throws GuzzleException
 */
class BilibiliFetchRule extends FetchRule {
	#[Inject]
	protected BilibiliSignature $signature;
	
	const IP_LIST = [
		['607649792', '608174079'], //36.56.0.0-36.63.255.255
		['1038614528', '1039007743'], //61.232.0.0-61.237.255.255
		['1783627776', '1784676351'], //106.80.0.0-106.95.255.255
		['2035023872', '2035154943'], //121.76.0.0-121.77.255.255
		['2078801920', '2079064063'], //123.232.0.0-123.235.255.255
		['-1950089216', '-1948778497'], //139.196.0.0-139.215.255.255
		['-1425539072', '-1425014785'], //171.8.0.0-171.15.255.255
		['-1236271104', '-1235419137'], //182.80.0.0-182.92.255.255
		['-770113536', '-768606209'], //210.25.0.0-210.47.255.255
		['-569376768', '-564133889'], //222.16.0.0-222.95.255.255
	];
	
	
	static function getType(): string {
		return 'bilibili';
	}
	
	function getRequest(): Client {
		$handler = \GuzzleHttp\HandlerStack::create(new CoroutineHandler());
		$handler->push(function (callable $handler) {
			return function (RequestInterface $request, array $options) use ($handler) {
				$request->withUri($request->getUri()->withQuery($this->signature->sign($request->getUri()->getQuery())));
				$rand_key = mt_rand(0, 9);
				$ip = long2ip(mt_rand(self::IP_LIST[$rand_key][0], self::IP_LIST[$rand_key][1]));
				$request = $request
					->withAddedHeader('X-Forwarded-For', $ip)
					->withAddedHeader('Client-IP', $ip)
					->withAddedHeader('X-Real-IP', $ip)
					->withAddedHeader('X-Client-IP', $ip);
				return $handler($request, $options);
			};
		}, 'bilibili_signature');
		return new Client([
			'handler' => $handler,
			'base_uri' => 'https://api.bilibili.com/',
			'timeout' => 15,
		]);
	}
	
	function fetchNovelList(string $tag = 'furry', string $page = '1'): array {
		return [];
	}
	
	function fetchNovelDetail(string $novelId): NovelInfo {
		//https://api.bilibili.com/x/article/list/web/articles?id=605518
		$response = $this->getRequest()->get('/x/article/list/web/articles', [
			'query' => [
				'id' => $novelId,
			],
		]);
		$response = json_decode($response->getBody()->getContents(), true);
		$novel = $response['data']['list'];
		$tags = [];
		$tags[] = 'zh';
		return new NovelInfo(
			$novel['id'],
			$novel['name'],
			$response['author']['name'],
			$response['author']['mid'],
			$novel['image_url'] ?? '',
			$novel['summary'] ?? '',
			$tags,
			[
				'oneshot' => false,
			]
		);
	}
	
	function fetchChapterList(string $novelId, string $page = '1'): array {
		//https://api.bilibili.com/x/article/list/web/articles?id=605518
		$response = $this->getRequest()->get('/x/article/list/web/articles', [
			'query' => [
				'id' => $novelId,
			],
		]);
		$response = json_decode($response->getBody()->getContents(), true);
		$chapters = $response['data']['articles'];
		return array_map(function ($chapter) {
			return new ChapterInfo(
				$chapter['id'],
				$chapter['title'] ?? '',
				$chapter['image_urls'][0] ?? '',
				$chapter['words'] ?? 0,
				$chapter['words'] ?? 0,
				[],
				null
			);
		}, $chapters);
	}
	
	function fetchChapterContent(string $novelId, string $chapterId): ChapterInfo {
		//手动解析
		//https://www.bilibili.com/read/cv18272730/
		$response = $this->getRequest()->get("https://www.bilibili.com/read/cv$chapterId", [
			'query' => [
				'chapter_id' => $chapterId,
			],
		]);
		$response = $response->getBody()->getContents();
		//window.__INITIAL_STATE__=(\{[\S\s\r\n]+});
		
		$pattern = '/window.__INITIAL_STATE__=(\{[\S\s\r\n]+});/';
		preg_match($pattern, $response, $matches);
		$chapter = json_decode($matches[1], true);
		$chapter = $chapter['readInfo'];
		$chapter['content'] = preg_replace('/<br\s*\/?>/', "\n", $chapter['content']);
		$chapter['content'] = preg_replace('/<\/p><p>/', "\n", $chapter['content']);
		$chapter['content'] = preg_replace('/<p>/', '', $chapter['content']);
		$chapter['content'] = preg_replace('/<\/p>/', '', $chapter['content']);
		
		//cut-off
		$chapter['content'] = preg_replace('/<div\s+class="cut-off">[\s\S]+<\/div>/', '', $chapter['content']);
		
		$chapter['content'] = preg_replace('/<img\s+src="([^"]+)"\s*\/?>/', '![]($1)', $chapter['content']);
		
		//todo
		
		return new ChapterInfo(
			$chapter['id'],
			$chapter['title'] ?? '',
			$chapter['image_urls'][0] ?? '',
			$chapter['words'] ?? 0,
			$chapter['words'] ?? 0,
			[],
			$chapter['content']
		);
	}
	
	function fetchAuthorInfo(string $authorId): AuthorInfo {
		//https://api.bilibili.com/x/space/wbi/article?mid=123
		$response = $this->getRequest()->get('https://api.vc.bilibili.com/account/v1/user/cards', [
			'query' => [
				'uids' => $authorId,
			],
		]);
		$response = json_decode($response->getBody()->getContents(), true);
		$author = $response['data'][0] ?? null;
		if (!$author) {
			throw new \Exception('Author not found');
		}
		return new AuthorInfo(
			$author['mid'],
			$author['face'],
			$author['name'],
			$author['sign']
		);
	}
}