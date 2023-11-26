<?php

namespace App\Model;

use App\Model\Model;
use App\Model\Scope\AuthorScope;
use Carbon\Carbon;

/**
 */
class Author extends User {
	protected static function booted(): void
	{
		static::addGlobalScope(new AuthorScope);
	}
}