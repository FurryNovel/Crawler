<?php

declare(strict_types = 1);

namespace App\Command;

use App\FetchRule\FetchRule;
use App\Model\Novel;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;

#[Command(name: "linpx:import")]
class ImportCommand extends HyperfCommand {
	public function handle() {
		$rule = FetchRule::getRule('pixiv_app');
		$pixiv = FetchRule::getRule('pixiv');
		if (!$rule) {
			return;
		}
		
		$users = json_decode(file_get_contents(BASE_PATH . '/app/DataSet/library/user.json'), true);
		
		$this->line('Importing users...');
		foreach ($users as $user) {
			$user = array_merge([
				'id' => 0,
				'name' => '',
			], (array)$user);
			$this->line('Importing user ' . $user['name'] . ':');
			foreach ($rule->fetchAuthorNovelList($user['id']) as $novelInfo) {
				$novel = Novel::fromFetchRule($pixiv, $novelInfo);
				$this->line('Importing novel ' . $novel->name . '->' . $novel->id . '...');
			}
		}
	}
}
