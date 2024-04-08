<?php

namespace App\FetchRule;

use App\FetchRule\FetchRule;
use App\Utils\BilibiliSignature;
use App\Utils\Utils;
use DOMNode;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\CoroutineHandler;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Rct567\DomQuery\DomQuery;

/**
 * @throws GuzzleException
 */
class BilibiliFetchRule extends FetchRule {
	#[Inject]
	protected BilibiliSignature $signature;
	
	static function getType(): string {
		return 'bilibili';
	}
	
	function getRequest(): Client {
		$handler = $this->getRequestHandler();
		$handler->push(function (callable $handler) {
			return function (RequestInterface $request, array $options) use ($handler) {
				parse_str($request->getUri()->getQuery(), $params);
				$request->withUri($request->getUri()->withQuery($this->signature->sign($params)));
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
	
	function fetchNovelDetail(string $novelId): ?NovelInfo {
		//https://api.bilibili.com/x/article/list/web/articles?id=605518
		return $this->getRequest()->getAsync('/x/article/list/web/articles', [
			'query' => [
				'id' => $novelId,
			],
		])->then(function (ResponseInterface $response) {
			$response = json_decode($response->getBody()->getContents(), true);
			$response = $response['data'];
			$novel = $response['list'];
			$tags = [];
			$tags[] = 'zh';
			$tags[] = 'SFW';
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
		})->otherwise(function (\Throwable $e) {
			Utils::err($e);
			return null;
		})->wait();
	}
	
	function fetchChapterList(string $novelId, string $page = '1'): array {
		//https://api.bilibili.com/x/article/list/web/articles?id=605518
		if ($page != '1') {
			return [];
		}
		return $this->getRequest()->getAsync('/x/article/list/web/articles', [
			'query' => [
				'id' => $novelId,
			],
		])->then(function (ResponseInterface $response) {
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
					null,
					[]
				);
			}, $chapters);
		})->otherwise(function (\Throwable $e) {
			Utils::err($e);
			return [];
		})->wait();
	}
	
	function fetchChapterContent(string $novelId, string $chapterId): ?ChapterInfo {
		//https://www.bilibili.com/read/cv18272730/
		return $this->getRequest()->getAsync("https://www.bilibili.com/read/cv$chapterId", [
			'query' => [
				'chapter_id' => $chapterId,
			],
		])->then(function (ResponseInterface $response) {
			$response = $response->getBody()->getContents();
			$pattern = '/window.__INITIAL_STATE__=(\{[\S\s\r\n]+});/';
			preg_match($pattern, $response, $matches);
			$chapter = json_decode($matches[1], true);
			$chapter = $chapter['readInfo'];
			$content = $this->processContent($chapter['content']);
			return new ChapterInfo(
				$chapter['id'],
				$chapter['title'] ?? '',
				$chapter['image_urls'][0] ?? '',
				$chapter['words'] ?? 0,
				$chapter['words'] ?? 0,
				[],
				$content,
				[]
			);
		})->otherwise(function (\Throwable $e) {
			Utils::err($e);
			return null;
		})->wait();
	}
	
	function processContent(string $content): string {
		$res = '';
		if (!\json_validate($content)) {
			$processor = function (DOMNode $node) use (&$res, &$processor) {
				switch ($node->nodeName) {
					case 'p':
						$res .= "{$node->textContent}\n";
						break;
					case 'img':
						$class = $node->getAttribute('class');
						if (str_starts_with($class, 'cut-off')) {
							$res .= "[hr][/hr]\n";
							break;
						}
						$src = $node->getAttribute('data-src');
						$res .= "[img]{$src}[/img]\n";
						break;
					case 'figure':
						foreach ($node->childNodes as $childNode) {
							$processor($childNode);
						}
						break;
					case 'figcaption':
						$res .= "[figcaption]{$node->textContent}[/figcaption]\n";
						break;
					case 'br':
						$res .= "\n";
						break;
				}
			};
			$dom = new DomQuery();
			$dom->loadContent($content);
			$dom->each($processor);
			unset($processor);
		} else {
			$data = json_decode($content, true);
			foreach ($data['ops'] as $line) {
				if (is_string($line['insert'])) {
					if (!empty($line['attributes']['bold'])) {
						$res .= "[b]{$line['insert']}[/b]\n";
					} else {
						$res .= $line['insert'];
					}
				} elseif (is_array($line['insert'])) {
					foreach ($line['insert'] as $k => $item) {
						if (str_contains($k, 'image')) {
							if (isset($item['url']))
								$res .= "[img]{$item['url']}[/img]\n";
						}
					}
				}
				$res .= "\n";
			}
		}
		return $res;
	}
	
	function fetchAuthorInfo(string $authorId): ?AuthorInfo {
		//https://api.bilibili.com/x/space/wbi/article?mid=123
		return $this->getRequest()->getAsync('https://api.vc.bilibili.com/account/v1/user/cards', [
			'query' => [
				'uids' => $authorId,
			],
		])->then(function (ResponseInterface $response) {
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
		})->otherwise(function (\Throwable $e) {
			Utils::err($e);
			return null;
		})->wait();
	}
	
	function fetchAuthorNovelList(string $authorId): array {
		return [];
	}
	
	function fetchAuthorList(string $name): array {
		return [];
	}
}
