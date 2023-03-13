<?php

namespace Nimbly\Carton\Tests;

use Nimbly\Carton\SingletonBuilder;
use Nimbly\Carton\Container;
use PHPUnit\Framework\TestCase;

/**
 * @covers Nimbly\Carton\SingletonBuilder
 * @covers Nimbly\Carton\Container
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