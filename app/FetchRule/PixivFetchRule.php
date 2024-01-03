<?php

namespace App\FetchRule;

use App\FetchRule\FetchRule;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\Guzzle\CoroutineHandler;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @throws GuzzleException
 */
class PixivFetchRule extends FetchRule {
	static function getType(): string {
		return 'pixiv';
	}
	
	function getRequest(): Client {
		return new Client([
			'handler' => $this->getRequestHandler(),
			'base_uri' => 'https://www.pixiv.net/',
			'timeout' => 15,
		]);
	}
	
	function fetchNovelList(string $tag = 'furry', string $page = '1'): array {
		//https://www.pixiv.net/ajax/search/novels/%E3%82%B1%E3%83%A2%E3%83%9B%E3%83%A2?word=%E3%82%B1%E3%83%A2%E3%83%9B%E3%83%A2&order=date_d&mode=all&p=1&s_mode=s_tag_full&gs=1&lang=zh&version=42055d64ddbad5c0a69639e157b82e921bf63b31
		return $this->getRequest()
			->getAsync('/ajax/search/novels/' . $tag, [
				'query' => [
					'word' => $tag,
					'order' => 'date_d',
					'mode' => 'all',
					's_mode' => 's_tag_full',
					'gs' => 1,
					'lang' => 'zh',
					'version' => '42055d64ddbad5c0a69639e157b82e921bf63b31',
					'p' => $page,
				],
			])->then(function (ResponseInterface $response) {
				$response = json_decode($response->getBody()->getContents(), true);
				$data = $response['body']['novel']['data'];
				return array_map(function ($novel) {
					$tags = $novel['tags'];
					if (isset($novel['aiType']) and $novel['aiType'] == 2) {
						$tags[] = 'AI Generated';
					}
					if (isset($novel['language'])) {
						$tags[] = $novel['language'];
					}
					if (isset($novel['xRestrict'])) {
						switch ($novel['xRestrict']) {
							case 0:
								$tags[] = 'SFW';
								break;
							case 1:
								$tags[] = 'NSFW';
								$tags[] = 'R-18';
								break;
							case 2:
								$tags[] = 'NSFW';
								$tags[] = 'R-18G';
								break;
						}
					}
					$oneshot = $novel['isOneshot'] ?? false;
					if (!$oneshot) {
						return new NovelInfo(
							$novel['id'],
							$novel['title'],
							$novel['userName'],
							$novel['userId'],
							$novel['cover']['urls']['original'] ?? '',
							$novel['caption'] ?? '',
							$tags,
							[
								'oneshot' => false,
							]
						);
					} else {
						return new NovelInfo(
							$novel['novelId'],
							$novel['title'],
							$novel['userName'],
							$novel['userId'],
							$novel['cover']['urls']['original'] ?? '',
							$novel['caption'] ?? '',
							$tags,
							[
								'oneshotId' => $novel['id'],
								'oneshot' => true,
							]
						);
					}
				}, $data);
			})->otherwise(function (\Throwable $e) {
				return [];
			})->wait();
	}
	
	function fetchNovelDetail(string $novelId): ?NovelInfo {
		//https://www.pixiv.net/ajax/novel/series/10579180?lang=zh&version=42055d64ddbad5c0a69639e157b82e921bf63b31
		return $this->getRequest()->getAsync('/ajax/novel/series/' . $novelId, [
			'query' => [
				'lang' => 'zh',
				'version' => '42055d64ddbad5c0a69639e157b82e921bf63b31',
			],
		])->then(function (ResponseInterface $response) {
			$response = json_decode($response->getBody()->getContents(), true);
			$novel = $response['body'];
			$tags = $novel['tags'] ?? [];
			if ($novel['aiType'] == 2) {
				$tags[] = 'AI Generated';
			}
			if ($novel['language']) {
				$tags[] = $novel['language'];
			}
			if (isset($novel['xRestrict'])) {
				switch ($novel['xRestrict']) {
					case 0:
						$tags[] = 'SFW';
						break;
					case 1:
						$tags[] = 'NSFW';
						$tags[] = 'R-18';
						break;
					case 2:
						$tags[] = 'NSFW';
						$tags[] = 'R-18G';
						break;
				}
			}
			$oneshot = $novel['isOneshot'] ?? false;
			if (!$oneshot) {
				return new NovelInfo(
					$novel['id'],
					$novel['title'],
					$novel['userName'],
					$novel['userId'],
					$novel['cover']['urls']['original'] ?? '',
					$novel['caption'] ?? '',
					$tags,
					[
						'oneshot' => false,
					]
				);
			} else {
				return new NovelInfo(
					$novel['novelId'],
					$novel['title'],
					$novel['userName'],
					$novel['userId'],
					$novel['cover']['urls']['original'] ?? '',
					$novel['caption'] ?? '',
					$tags,
					[
						'oneshotId' => $novel['id'],
						'oneshot' => false,
					]
				);
			}
		})->otherwise(function (\Throwable $e) {
			return null;
		})->wait();
	}
	
	function fetchChapterList(string $novelId, string $page = '1'): array {
		//https://www.pixiv.net/ajax/novel/series_content/10579180?limit=30&last_order=0&order_by=asc&lang=zh&version=42055d64ddbad5c0a69639e157b82e921bf63b31
		return $this->getRequest()->getAsync('/ajax/novel/series_content/' . $novelId, [
			'query' => [
				'limit' => 30,
				'last_order' => 30 * (intval($page) - 1),
				'order_by' => 'asc',
				'lang' => 'zh',
				'version' => '42055d64ddbad5c0a69639e157b82e921bf63b31'
			],
		])->then(function (ResponseInterface $response) {
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
		})->otherwise(function (\Throwable $e) {
			return null;
		})->wait();
	}
	
	function fetchChapterContent(string $novelId, string $chapterId): ?ChapterInfo {
		//https://www.pixiv.net/ajax/novel/20065569?lang=zh&version=42055d64ddbad5c0a69639e157b82e921bf63b31
		return $this->getRequest()->getAsync('/ajax/novel/' . $chapterId, [
			'query' => [
				'lang' => 'zh',
				'version' => '42055d64ddbad5c0a69639e157b82e921bf63b31',
			],
		])->then(function (ResponseInterface $response) use ($chapterId) {
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
		})->otherwise(function (\Throwable $e) {
			return null;
		})->wait();
	}
	
	function fetchAuthorInfo(string $authorId): ?AuthorInfo {
		//https://www.pixiv.net/ajax/user/3337300?full=1&lang=zh&version=42055d64ddbad5c0a69639e157b82e921bf63b31
		return $this->getRequest()->getAsync('/ajax/user/' . $authorId, [
			'query' => [
				'full' => 1,
				'lang' => 'zh',
				'version' => '42055d64ddbad5c0a69639e157b82e921bf63b31',
			],
		])->then(function (ResponseInterface $response) {
			$response = json_decode($response->getBody()->getContents(), true);
			$response = $response['body'];
			return new AuthorInfo(
				$response['userId'],
				$response['imageBig'],
				$response['name'],
				$response['comment']
			);
		})->otherwise(function (\Throwable $e) {
			return null;
		})->wait();
	}
}