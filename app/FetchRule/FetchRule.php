<?php
/** @noinspection ALL */

namespace App\FetchRule;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 */
abstract class FetchRule {
	const RULES = [
		'pixiv' => PixivFetchRule::class,
	];
	
	static function getRule(string $type): ?self {
		if (!isset(self::RULES[$type])) {
			return null;
		}
		return new (self::RULES[$type])();
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
	abstract function fetchNovelList(string $page = '1'): array;
	
	/**
	 * 获取小说详情
	 * @param string $novelId
	 * @return NovelInfo
	 * @throws GuzzleException
	 */
	abstract function fetchNovelDetail(string $novelId): NovelInfo;
	
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
	 * @return string
	 */
	abstract function fetchChapterContent(string $novelId, string $chapterId): ChapterInfo;
	
	/**
	 * 获取作者信息
	 * @param string $authorId
	 * @return AuthorInfo
	 * @throws GuzzleException
	 */
	abstract function fetchAuthorInfo(string $authorId): AuthorInfo;
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
		return $this->options['isOneshot'] ?? false;
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