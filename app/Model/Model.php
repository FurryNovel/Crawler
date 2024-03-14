<?php

declare(strict_types = 1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Model;

use App\Model\Scope\AuthorScope;
use Carbon\Carbon;
use Hyperf\Context\ApplicationContext;
use Hyperf\Database\Model\Collection;
use Hyperf\DbConnection\Model\Model as BaseModel;
use Hyperf\ModelCache\Cacheable;
use Hyperf\ModelCache\CacheableInterface;
use Hyperf\ModelCache\Manager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

abstract class Model extends BaseModel implements CacheableInterface {
	use Cacheable;
	
	protected string $modelLanguage = 'zh';
	
	protected array $guarded = [];
	
	protected function boot(): void {
		parent::boot();
		$this->bootGlobalScope();
	}
	
	protected function bootGlobalScope(): void {}
	
	protected function asJson(mixed $value): string|false {
		return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}
	
	/**
	 * 不会触发更新时间
	 */
	function doBackground(callable $callback): bool {
		$this->timestamps = false;
		$callback();
		$bool = $this->save();
		$this->timestamps = true;
		return $bool;
	}
	
	/**
	 * 不会触发更新时间
	 */
	function touchField($field = 'fetched_at', $value = null): bool {
		$this->timestamps = false;
		if (is_null($value) and $this->{$field} instanceof Carbon) {
			$this->{$field} = Carbon::now();
		} else {
			$this->{$field} = $value;
		}
		$bool = $this->save();
		$this->timestamps = true;
		return $bool;
	}
	
	public static function findFromCache($id): ?static {
		$container = ApplicationContext::getContainer();
		$manager = $container->get(Manager::class);
		$model = $manager->findFromCache($id, static::class);
		if ($model) {
			$model->load($model->with);
		}
		return $model;
	}
	
	/**
	 * Fetch models from cache.
	 * @param array $ids
	 * @return Collection<int, self>
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public static function findManyFromCache(array $ids): Collection {
		$container = ApplicationContext::getContainer();
		$manager = $container->get(Manager::class);
		
		$ids = array_unique($ids);
		return $manager->findManyFromCache($ids, static::class)->each(function (Model $model) {
			$model->load($model->with);
		});
	}
	
	public function withLanguage(string $language): static {
		$this->modelLanguage = $language;
		return $this;
	}
}
