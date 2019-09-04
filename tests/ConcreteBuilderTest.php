<?php

namespace Carton\Tests;

use Carton\ConcreteBuilder;
use Carton\Container;
use PHPUnit\Framework\TestCase;

/**
 * @covers Carton\ConcreteBuilder
 * @covers Carton\Container
 */
class ConcreteBuilderTest extends TestCase
{
	public function test_build_returns_same_instance()
	{
		$instance = new \stdClass;
		$builder = new ConcreteBuilder($instance);

		$container = new Container;

		$this->assertSame(
			$instance,
			$builder->build($container)
		);
	}
}