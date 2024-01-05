<?php

namespace App\DataSet;

use LanguageDetector\LanguageDetector;

class LanguageService {
	protected LanguageDetector $detector;
	
	public function __construct() {
		$this->detector = new LanguageDetector(null, ['zh-cn', 'zh-tw', 'en', 'ja', 'ko']);
	}
	
	function detectDeep(
		string $text,
		array  $mulScores = ['zh-cn' => 1, 'zh-tw' => 1, 'en' => 1, 'ja' => 2, 'ko' => 1]
	): string {
		$scores = $this->detector->evaluate($text)->getScores();
		foreach ($mulScores as $lang => $mul) {
			if (isset($scores[$lang])) {
				$scores[$lang] *= $mul;
			}
		}
		arsort($scores);
		return $scores ? array_key_first($scores) : 'en';
	}
	
	function detect(string $text): string {
		$check = max(mb_strlen($text) / 2, 1);
		// 检查是否包含日文
		if (preg_match_all('/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{31F0}-\x{31FF}\x{FF65}-\x{FF9F}]/u', $text) >= $check) {
			return 'ja';
		}
		// 检查是否包含韩文
		if (preg_match_all('/[\x{1100}-\x{11FF}\x{3130}-\x{318F}\x{AC00}-\x{D7AF}]/u', $text) >= $check) {
			return 'ko';
		}
		// 检查是否包含中文
		if (preg_match_all('/[\x{4E00}-\x{9FBF}]/u', $text) >= $check) {
			//检查是否包含繁体中文
			return $this->detectDeep($text, ['zh-cn' => 2, 'zh-tw' => 2]);
		}
		return $this->detectDeep($text);
	}
}