<?php

namespace App\FetchRule;

use App\FetchRule\FetchRule;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\Guzzle\CoroutineHandler;

/**
 * @throws GuzzleException
 */
class PixivFetchRule extends FetchRule {
	static function getType(): string {
		return 'pixiv';
	}
	
	function getRequest(): Client {
		return new Client([
			'handler' => \GuzzleHttp\HandlerStack::create(new CoroutineHandler()),
			'base_uri' => 'https://www.pixiv.net/',
			'timeout' => 15,
		]);
	}
	
	function fetchNovelList(string $page = '1'): array {
		//https://www.pixiv.net/ajax/search/novels/%E3%82%B1%E3%83%A2%E3%83%9B%E3%83%A2?word=%E3%82%B1%E3%83%A2%E3%83%9B%E3%83%A2&order=date_d&mode=all&p=1&s_mode=s_tag_full&work_lang=zh-cn&gs=1&lang=zh&version=42055d64ddbad5c0a69639e157b82e921bf63b31
		$response = $this->getRequest()
			->post('/ajax/search/novels/%E3%82%B1%E3%83%A2%E3%83%9B%E3%83%A2', [
				'params' => [
					'word' => '%E3%82%B1%E3%83%A2%E3%83%9B%E3%83%A2',
					'order' => 'date_d',
					'mode' => 'all',
					's_mode' => 's_tag_full',
					'work_lang' => 'zh-cn',
					'gs' => 1,
					'lang' => 'zh',
					'version' => '42055d64ddbad5c0a69639e157b82e921bf63b31',
					'p' => $page,
				],
			]);
		$response = json_decode($response->getBody()->getContents(), true);
		$data = $response['body']['novel']['data'];
		return array_map(function ($novel) {
			$tags = $novel['tags'];
			if ($novel['aiType']) {
				$tags[] = 'AI生成';
			}
			if ($novel['language']) {
				$tags[] = $novel['language'];
			}
			return new NovelInfo(
				$novel['id'],
				$novel['title'],
				$novel['userName'],
				$novel['userId'],
				$novel['cover']['urls']['original'],
				$novel['caption'],
				$tags
			);
		}, $data);
	}
	
	function fetchNovelDetail(string $novelId): NovelInfo {
		//https://www.pixiv.net/ajax/novel/series/10579180?lang=zh&version=42055d64ddbad5c0a69639e157b82e921bf63b31
		$response = $this->getRequest()->get('/ajax/novel/series/' . $novelId, [
			'query' => [
				'lang' => 'zh',
				'version' => '42055d64ddbad5c0a69639e157b82e921bf63b31',
			],
		]);
		$response = json_decode($response->getBody()->getContents(), true);
		$novel = $response['body'];
		$tags = $novel['tags'] ?? [];
		if ($novel['aiType']) {
			$tags[] = 'AI生成';
		}
		if ($novel['language']) {
			$tags[] = $novel['language'];
		}
		return new NovelInfo(
			$novel['id'],
			$novel['title'],
			$novel['userName'],
			$novel['userId'],
			$novel['cover']['urls']['original'],
			$novel['caption'],
			$tags
		);
	}
	
	function fetchChapterList(string $novelId, string $page = '1'): array {
		//https://www.pixiv.net/ajax/novel/series_content/10579180?limit=30&last_order=0&order_by=asc&lang=zh&version=42055d64ddbad5c0a69639e157b82e921bf63b31
		$response = $this->getRequest()->get('/ajax/novel/series_content/' . $novelId, [
			'query' => [
				'limit' => 30,
				'last_order' => 30 * (intval($page) - 1),
				'order_by' => 'asc',
				'lang' => 'zh',
				'version' => '42055d64ddbad5c0a69639e157b82e921bf63b31'
			],
		]);
		$response = json_decode($response->getBody()->getContents(), true);
		return array_map(function ($chapter) {
			return new ChapterInfo(
				$chapter['id'],
				$chapter['title'] ?? '',
				$chapter['url'] ?? '',
				$chapter['textCount'] ?? 0,
				$chapter['wordCount'] ?? 0,
				[],
				null
			);
		}, $response['body']['page']['seriesContents']);
	}
	
	function fetchChapterContent(string $novelId, string $chapterId): ChapterInfo {
		//https://www.pixiv.net/ajax/novel/20065569?lang=zh&version=42055d64ddbad5c0a69639e157b82e921bf63b31
		$response = $this->getRequest()->get('/ajax/novel/' . $chapterId, [
			'query' => [
				'lang' => 'zh',
				'version' => '42055d64ddbad5c0a69639e157b82e921bf63b31',
			],
		]);
		$response = json_decode($response->getBody()->getContents(), true);
		$chapter = $response['body'];
		return new ChapterInfo(
			$chapterId,
			$chapter['title'] ?? '',
			$chapter['coverUrl'] ?? '',
			$chapter['textCount'] ?? 0,
			$chapter['wordCount'] ?? 0,
			[],
			$chapter['content']
		);
	}
	
	function fetchAuthorInfo(string $authorId): AuthorInfo {
		//https://www.pixiv.net/ajax/user/3337300?full=1&lang=zh&version=42055d64ddbad5c0a69639e157b82e921bf63b31
		$response = $this->getRequest()->get('/ajax/user/' . $authorId, [
			'query' => [
				'full' => 1,
				'lang' => 'zh',
				'version' => '42055d64ddbad5c0a69639e157b82e921bf63b31',
			],
		]);
		$response = json_decode($response->getBody()->getContents(), true);
		$response = $response['body'];
		return new AuthorInfo(
			$response['userId'],
			$response['imageBig'],
			$response['name'],
			$response['comment']
		);
	}
}