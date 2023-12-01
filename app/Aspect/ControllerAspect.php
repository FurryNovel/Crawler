<?php

namespace App\Aspect;

use Hyperf\Context\ApplicationContext;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Contract\RequestInterface;

#[Aspect]
class ControllerAspect extends AbstractAspect {
	public array $classes = [
		'Hyperf\HttpServer\CoreMiddleware::parseMethodParameters',
	];
	
	
	public function process(ProceedingJoinPoint $proceedingJoinPoint) {
		list($controller, $action, $arguments) = $proceedingJoinPoint->getArguments();
		$request = ApplicationContext::getContainer()->get(RequestInterface::class);
		$query = $request->all();
		$arguments = array_merge($arguments, $query);
		$proceedingJoinPoint->arguments['keys']['arguments'] = $arguments;
		return $proceedingJoinPoint->process();
//
//		$injects = [];
//		foreach ($reflect->getParameters() as $parameter) {
//			$name = $parameter->getName();
//			if (isset($arguments[$name])) {
//				$injects[] = $arguments[$name];
//			} else if (isset($query[$name])) {
//				$injects[] = $query[$name];
//			} else if ($parameter->isOptional()) {
//				$injects[] = $parameter->getDefaultValue();
//			} else {
//				throw new \InvalidArgumentException('method param miss:' . $name);
//			}
//		}
	}
}