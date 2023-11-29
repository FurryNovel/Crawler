<?php

namespace App\DataSet;

use Fukuball\Jieba\Jieba;
use Fukuball\Jieba\Finalseg;
use Fukuball\Jieba\JiebaAnalyse;

class DataSet {
	protected array $dataset = [];
	
	public function __construct() {
		ini_set('memory_limit', '-1');
		Jieba::init(array('mode' => 'default', 'dict' => 'big', 'cjk' => 'all'));
		
		$this->load('Race');
		$this->load('Tag');
	}
	
	//$name
	function load($name): array {
		if (!isset($this->$dataset[$name]) and file_exists(BASE_PATH . "/app/DataSet/$name.php")) {
			$this->dataset[$name] = include_once BASE_PATH . "/app/DataSet/$name.php";
		}
		return $this->dataset[$name] ?? [];
	}
	
	function convertTo(string $to, ?string $dataset, array $tags): array {
		$tags = $this->convertToPattern($dataset, $tags);
		foreach ($this->dataset as $dn => $ds) {
			if ($dataset and $dn !== $dataset) {
				continue;
			}
			foreach ($ds as $k => $v) {
				foreach ($tags as &$tag) {
					if (
						$k == $tag
						or in_array($tag, $v)
					) {
						switch ($to) {
							case 'zh-cn':
								$tag = $this->filterByZHCN($tags)[0] ?? $v;
								break;
							case 'zh-tw':
								$tag = $this->filterByZHTW($tags)[0] ?? $v;
								break;
							default:
							case 'en-us':
								$tag = $k;
								break;
						}
					}
				}
			}
		}
		return $tags;
	}
	
	function convertToPattern(?string $dataset, array $tags): array {
		foreach ($this->dataset as $dn => $ds) {
			if ($dataset and $dn !== $dataset) {
				continue;
			}
			
			foreach ($ds as $k => $v) {
				foreach ($tags as &$tag) {
					if (
						$k == $tag
						or in_array($tag, $v)
					) {
						$tag = $k;
					}
				}
			}
		}
		return array_values(array_unique($tags));
	}
	
	
	private function filterByZHCN($tags): array {
		return array_values(array_filter($tags, function ($tag) {
			return preg_match_all("/^([\x81-\xfe][\x40-\xfe])+$/", $tag, $matches);
		}));
	}
	
	
	private function filterByZHTW($tags): array {
		return array_values(array_filter($tags, function ($tag) {
			return @iconv('UTF-8', 'GB2312', $tag) === false;
		}));
	}
	
	
	function convertContentToPattern(?string $dataset, string $content): array {
		$tags = JiebaAnalyse::extractTags($content);
		return $this->convertToPattern($dataset, $tags);
	}
}