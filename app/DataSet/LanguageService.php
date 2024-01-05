<?php

namespace App\DataSet;

use LanguageDetector\LanguageDetector;

class LanguageService {
	protected LanguageDetector $detector;
	
	public function __construct() {
		$this->detector = new LanguageDetector(null, ['zh-cn', 'zh-tw', 'en', 'ja']);
	}
	
	function detect(string $text): string {
		$scores = $this->detector->evaluate($text)->getScores();
		if (isset($scores['ja'])) {
			$scores['ja'] *= 2;
		}
		arsort($scores);
		return $scores ? array_key_first($scores) : 'en';
	}
}