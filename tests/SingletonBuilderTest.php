<?php

namespace Carton\Tests;

use Carton\SingletonBuilder;
use Carton\Container;
use PHPUnit\Framework\TestCase;

/**
 * @covers Carton\SingletonBuilder
 * @covers Carton\Container
 */
class SingletonBuilderTest extends TestCase
{
	public function test_build_returns_same_instance()
	{
		$builder = new SingletonBuilder(function(){
			return new \stdClass;
		});

		$container = new Container;

		$instance = $builder->build($container);

		$this->assertSame(
			$instance,
			$builder->build($container)
		);
	}
}