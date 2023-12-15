<?php

namespace App\DataSet;

use Fukuball\Jieba\Jieba;
use Fukuball\Jieba\Finalseg;
use Fukuball\Jieba\Posseg;
use Fukuball\Jieba\JiebaAnalyse;

class DataSet {
	protected array $dataset = [];
	
	public function __construct() {
		ini_set('memory_limit', '-1');
		Jieba::init(array('mode' => 'default', 'dict' => 'big', 'cjk' => 'all'));
		Finalseg::init();
		Posseg::init();
		$this->load('Race');
		$this->load('Tag');
	}
	
	//$name
	function load($name): array {
		if (!isset($this->dataset[$name]) and file_exists(BASE_PATH . "/app/DataSet/$name.php")) {
			$this->dataset[$name] = include_once BASE_PATH . "/app/DataSet/$name.php";
		}
		if (!is_array($this->dataset[$name])) {
			$this->dataset[$name] = [];
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
				$k = strtolower($k);
				foreach (($v[$to] ?? $v['en_US']) as $translation) {
					foreach ($tags as &$tag) {
						if ($tag == $k) {
							$tag = $translation;
						}
					}
				}
			}
		}
		return array_values(array_unique($tags));
	}
	
	function convertToPattern(?string $dataset, array $tags): array {
		foreach ($this->dataset as $dn => $ds) {
			if ($dataset and $dn !== $dataset) {
				continue;
			}
			foreach ($ds as $k => $v) {
				foreach ($v as $locale => $translation) {
					foreach ($tags as &$tag) {
						if (in_array($tag, $translation)) {
							$tag = $k;
						}
					}
				}
			}
		}
		return array_values(array_unique(array_map('strtolower', $tags)));
	}
	
	
	function convertContentToPattern(?string $dataset, string $content): array {
		$tags = Posseg::cut($content);
		$tags = array_column(array_values(array_filter($tags, function ($token) {
			return in_array($token['tag'], [
				'n', 'nr', 'nr1', 'nr2', 'nrj', 'nrf', 'ns', 'nsf', 'nt', 'nz',
				'a', 'ad', 'an', 'ag', 'al'
			]);
		})), 'word');
		$counts = array_count_values($tags);
		arsort($counts);
		$tags = array_keys($counts);
		return $this->convertToPattern($dataset, $tags);
	}
}