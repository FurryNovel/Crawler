<?php
/** @noinspection ALL */

namespace App\FetchRule;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\Guzzle\CoroutineHandler;
use Psr\Http\Message\RequestInterface;

/**
 */
abstract class FetchRule {
	const RULES = [
		'pixiv' => PixivFetchRule::class,
		'bilibili' => BilibiliFetchRule::class,
	];
	
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
	
	static function getRule(string $type): ?self {
		if (!isset(self::RULES[$type])) {
			return null;
		}
		return new (self::RULES[$type])();
	}
	
	
	function getRequestHandler() {
		$handler = \GuzzleHttp\HandlerStack::create(new CoroutineHandler());
		$handler->push(function (callable $handler) {
			return function (RequestInterface $request, array $options) use ($handler) {
				$rand_key = mt_rand(0, count(self::IP_LIST) - 1);
				$ip = long2ip(mt_rand(self::IP_LIST[$rand_key][0], self::IP_LIST[$rand_key][1]));
				$request = $request
					->withAddedHeader('X-Forwarded-For', $ip)
					->withAddedHeader('Client-IP', $ip)
					->withAddedHeader('X-Real-IP', $ip)
					->withAddedHeader('X-Client-IP', $ip);
				return $handler($request, $options);
			};
		}, 'fake_ip');
		return $handler;
	}
	
	
	abstract static function getType(): string;
	
	/**
	 * 获取BaseClient
	 * @return Client
	 */
	abstract function getRequest(): Client;
	
	/**
	 * 获取小说列表
	 * @return NovelInfo[]
	 * @throws GuzzleException
	 */
	abstract function fetchNovelList(string $tag = 'furry', string $page = '1'): array;
	
	/**
	 * 获取小说详情
	 * @param string $novelId
	 * @return ?NovelInfo
	 * @throws GuzzleException
	 */
	abstract function fetchNovelDetail(string $novelId): ?NovelInfo;
	
	/**
	 * 获取章节列表
	 * @param string $novelId
	 * @return ChapterInfo[]
	 * @throws GuzzleException
	 */
	abstract function fetchChapterList(string $novelId, string $page = '1'): array;
	
	/**
	 * 获取章节内容
	 * @param string $novelId
	 * @param string $chapterId
	 * @return ?ChapterInfo
	 */
	abstract function fetchChapterContent(string $novelId, string $chapterId): ?ChapterInfo;
	
	/**
	 * 获取作者信息
	 * @param string $authorId
	 * @return ?AuthorInfo
	 * @throws GuzzleException
	 */
	abstract function fetchAuthorInfo(string $authorId): ?AuthorInfo;
	
	/**
	 * 获取作者小说列表
	 * @param string $authorId
	 * @param string $page
	 * @return NovelInfo[]
	 */
	abstract function fetchAuthorNovelList(string $authorId, string $page = '1'): array;
	
	/**
	 * 获取作者列表
	 * @param string $name
	 * @return AuthorInfo[]
	 */
	abstract function fetchAuthorList(string $name): array;
	
	function processContent(string $content): string {
		return $content;
	}
}


class NovelInfo {
	public string $id;
	public string $name;
	public string $author;
	public string $author_id;
	public string $cover;
	public string $desc;
	public array $tags;
	
	public array $options;
	
	
	public function __construct(string $id, string $name, string $author, string $author_id, string $cover, string $desc, array $tags, array $options) {
		$this->id = $id;
		$this->name = $name;
		$this->author = $author;
		$this->author_id = $author_id;
		$this->cover = $cover;
		$this->desc = $desc;
		$this->tags = $tags;
		$this->options = $options;
	}
	
	function isOneshot(): bool {
		return $this->options['oneshot'] ?? false;
	}
}


class ChapterInfo {
	public string $id;
	public string $name;
	public string $cover;
	public int $text_count;
	public int $word_count;
	public ?string $content;
	
	public array $tags;
	
	public function __construct(string $id, string $name, string $cover, int $text_count, int $word_count, array $tags, ?string $content) {
		$this->id = $id;
		$this->name = $name;
		$this->cover = $cover;
		$this->text_count = $text_count;
		$this->word_count = $word_count;
		$this->tags = $tags;
		$this->content = $content;
	}
}

class AuthorInfo {
	public string $id;
	public string $avatar;
	public string $name;
	public string $desc;
	
	public function __construct(string $id, string $avatar, string $name, string $desc) {
		$this->id = $id;
		$this->avatar = $avatar;
		$this->name = $name;
		$this->desc = $desc;
	}
}