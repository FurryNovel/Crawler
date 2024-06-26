<?php

namespace App\FetchRule;

use App\FetchRule\FetchRule;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\Guzzle\CoroutineHandler;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Rct567\DomQuery\DomQuery;

/**
 * @throws GuzzleException
 */
class PixivAppFetchRule extends PixivFetchRule {
	const REFRESH_TOKEN = 'w9KH9_mPpMK1DIVUCrmu8yTZfPO1UR2efUoixWgWiB4';
	
	const CLIENT_ID = 'MOBrBDS8blbauoSck0ZfDbtuzpyT';
	const CLIENT_SECRET = 'lsACyCD94FhDUtGTXi3QzcFE2uU1hqtDaKeqrdwj';
	const HASH_SECRET = '28c1fdd170a5204386cb1313c7077b34f83e4aaf4aa829ce78c231e05b0bae2c';
	
	function getAccessToken(): string {
		//{"access_token":"wHmOiUDjhOXjHfZlRWmp_Bsn4efbdFomp9oU-vRyWn0","expires_in":3600,"token_type":"bearer","scope":"","refresh_token":"w9KH9_mPpMK1DIVUCrmu8yTZfPO1UR2efUoixWgWiB4","user":{"profile_image_urls":{"px_16x16":"https://s.pximg.net/common/images/no_profile_ss.png","px_50x50":"https://s.pximg.net/common/images/no_profile_s.png","px_170x170":"https://s.pximg.net/common/images/no_profile.png"},"id":"102396730","name":"TigerKK","account":"user_pyvz5333","mail_address":"678soft@gmail.com","is_premium":false,"x_restrict":2,"is_mail_authorized":true}}
		$cacheManager = \FriendsOfHyperf\Helpers\cache();
		$data = null;
		if ($cacheManager->has('pixiv_token_data')) {
			$data = $cacheManager->get('pixiv_token_data');
		}
		if (empty($data)) {
			$response = $this->getRequest(false)->post('https://oauth.secure.pixiv.net/auth/token', [
				'form_params' => [
					'client_id' => self::CLIENT_ID,
					'client_secret' => self::CLIENT_SECRET,
					'grant_type' => 'refresh_token',
					'refresh_token' => self::REFRESH_TOKEN,
					'get_secure_url' => 1,
				],
			]);
			$data = json_decode($response->getBody()->getContents(), true);
			$cacheManager->set('pixiv_token_data', $data, ($data['expires_in'] ?? 3600) - 10);
		}
		return $data['access_token'];
	}
	
	function getRequest($needAuth = true): Client {
		$handler = $this->getRequestHandler();
		if ($needAuth) {
			$handler->push(function (callable $handler) use ($needAuth) {
				return function (RequestInterface $request, array $options) use ($needAuth, $handler) {
					$request = $request
						->withAddedHeader('Authorization', 'Bearer ' . $this->getAccessToken())
						->withAddedHeader('User-Agent', 'PixivIOSApp/7.13.3 (iOS 14.6; iPhone13,2)')
						->withAddedHeader('App-OS', 'ios')
						->withAddedHeader('App-OS-Version', '14.6')
						->withAddedHeader('App-Version', '7.13.3')
						->withAddedHeader('Accept-Language', 'zh_CN')
						->withAddedHeader('X-Client-Time', time())
						->withAddedHeader('X-Client-Hash', md5(time() . self::HASH_SECRET));
					return $handler($request, $options);
				};
			}, 'pixiv_app');
		}
		return new Client([
			'handler' => $handler,
			'base_uri' => 'https://www.pixiv.net/',
			'timeout' => 15,
			'headers' => [
				'Accept' => 'application/json',
				'Accept-Language' => 'zh;zh-CN;q=0.9;en;q=0.8',
			],
		]);
	}
	
	
	function fetchNovelList(string $tag = 'furry', string $page = '1'): array {
		if (intval($page) > 30) {
			return [];
		}
		//https://app-api.pixiv.net/v1/search/novel?sort=date_desc&word=furry&include_translated_tag_results=true&merge_plain_keyword_results=true
		return $this->getRequest()
			->getAsync('https://app-api.pixiv.net/v1/search/novel', [
				'query' => [
					'word' => $tag,
					'search_target' => 'exact_match_for_tags',
					'include_translated_tag_results' => 'true',
					'merge_plain_keyword_results' => 'true',
					'sort' => 'date_desc',
					'offset' => (intval($page) - 1) * 30,
				],
			])->then(function (ResponseInterface $response) {
				$response = json_decode($response->getBody()->getContents(), true);
				$novels = $response['novels'];
				$novel_ids = [];
				$series_ids = [];
				foreach ($novels as $novel) {
					if (!empty($novel['series'])) {
						if (in_array($novel['series']['id'], $series_ids)) {
							continue;
						}
					}
					$novel_ids[] = $novel['id'];
				}
				$novels = array_values(array_filter($novels, function ($novel) use ($novel_ids, &$series_ids) {
					return in_array($novel['id'], $novel_ids);
				}));
				return array_map(function ($novel) {
					$tags = array_column($novel['tags'], 'name');
					foreach ($tags as &$tag) {
						if (str_contains($tag, '/')) {
							$_tags = explode('/', $tag);
							$tag = $_tags[0];
							$tags = array_merge($tags, $_tags);
						}
					}
					if (isset($novel['novel_ai_type']) and $novel['novel_ai_type'] == 2) {
						$tags[] = 'AI Generated';
					}
					if (isset($novel['x_restrict'])) {
						switch ($novel['x_restrict']) {
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
					$oneshot = empty($novel['series']);
					if (!$oneshot) {
						return new NovelInfo(
							$novel['series']['id'],
							$novel['series']['title'],
							$novel['user']['name'] ?? '',
							$novel['user']['id'],
							$novel['image_urls']['large'] ?? '',
							$novel['series']['caption'] ?? '',
							$tags,
							[
								'oneshot' => false,
							]
						);
					} else {
						return new NovelInfo(
							$novel['id'],
							$novel['title'],
							$novel['user']['name'] ?? '',
							$novel['user']['id'],
							$novel['image_urls']['large'] ?? '',
							$novel['caption'] ?? '',
							$tags,
							[
								'oneshotId' => crc32($novel['id']),
								'oneshot' => true,
							]
						);
					}
				}, $novels);
			})->otherwise(function (\Throwable $e) {
				var_dump($e->getMessage());
				return [];
			})->wait();
	}
	
	/**
	 * @param string $name
	 * @return array
	 * @deprecated 不建议使用
	 */
	function fetchAuthorList(string $name): array {
		//https://app-api.pixiv.net/v1/search/user?word=唐门&filter=for_ios&sort=date_desc
		//https://www.pixiv.net/ajax/user/16721009?full=1&lang=zh
		return $this->getRequest()
			->getAsync('https://app-api.pixiv.net/v1/search/user', [
				'query' => [
					'word' => $name,
					'filter' => 'for_ios',
					'sort' => 'date_desc',
				],
			])->then(function (ResponseInterface $response) {
				$response = $response->getBody()->getContents();
				$data = json_decode($response, true);
				return array_map(function ($data) {
					$user = $data['user'];
					return new AuthorInfo(
						$user['id'],
						$user['profile_image_urls']['medium'],
						$user['name'],
						''
					);
				}, $data['user_previews'] ?? []);
			})->otherwise(function (\Throwable $e) {
				return [];
			})->wait();
	}
	
	
	function fetchAuthorNovelList(string $authorId): array {
		$chapters = [];
		$page = 1;
		do {
			$data = $this->getRequest()
				->getAsync('https://app-api.pixiv.net/v1/user/novels', [
					'query' => [
						'user_id' => $authorId,
						'offset' => ($page - 1) * 30,
					],
				])->then(function (ResponseInterface $response) {
					$response = $response->getBody()->getContents();
					$data = json_decode($response, true);
					return $data['novels'] ?? [];
				})->otherwise(function (\Throwable $e) {
					return [];
				})->wait();
			$chapters = array_merge($chapters, $data);
			$page++;
		} while (!empty($data));
		
		$novels = [];
		$series = [];
		foreach ($chapters as $chapter) {
			if (!empty($chapter['series'])) {
				if (in_array($chapter['series']['id'], $series)) {
					continue;
				}
				$series[] = $chapter['series']['id'];
				continue;
			}
			$tags = $chapter['tags'] ?? [];
			$tags = array_column($tags, 'name') + array_column($tags, 'translated_name');
			if ($chapter['novel_ai_type'] == 2) {
				$tags[] = 'AI Generated';
			}
			if (isset($chapter['x_restrict'])) {
				switch ($chapter['x_restrict']) {
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
			$novels[] = new NovelInfo(
				$chapter['id'],
				$chapter['title'],
				$chapter['user']['name'] ?? '',
				$chapter['user']['id'],
				$chapter['image_urls']['large'] ?? '',
				$chapter['caption'] ?? '',
				$tags,
				[
					'oneshotId' => crc32($chapter['id']),
					'oneshot' => true,
				]
			);
		}
		foreach ($series as $id) {
			$novels[] = $this->getRequest()
				->getAsync('https://www.pixiv.net/ajax/novel/series/' . $id, [
					'query' => [
						'lang' => 'zh',
						'version' => '471f117fcde85f9ce382c9d945dc8dd854ff4358',
					],
				])->then(function (ResponseInterface $response) {
					$response = $response->getBody()->getContents();
					$data = json_decode($response, true);
					$novel = $data['body'] ?? null;
					if (empty($data)) {
						return null;
					}
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
				})->otherwise(function (\Throwable $e) {
					return null;
				})->wait();
		}
		return array_filter($novels, function ($novel) {
			return !empty($novel);
		});
	}
}
