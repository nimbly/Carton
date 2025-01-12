<?php

namespace Nimbly\Carton\Tests;

use Nimbly\Carton\ConcreteBuilder;
use Nimbly\Carton\Container;
use Nimbly\Carton\ContainerException;
use Nimbly\Carton\FactoryBuilder;
use Nimbly\Carton\NotFoundException;
use Nimbly\Carton\SingletonBuilder;
use Nimbly\Carton\Tests\Mock\BarClass;
use Nimbly\Carton\Tests\Mock\FooClass;
use Nimbly\Carton\Tests\Mock\SampleProvider;
use DateTime;
use Nimbly\Carton\Tests\Mock\InvokableClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @covers Nimbly\Carton\Container
 * @covers Nimbly\Carton\NotFoundException
 * @covers Nimbly\Carton\ConcreteBuilder
 * @covers Nimbly\Carton\SingletonBuilder
 * @covers Nimbly\Carton\FactoryBuilder
 * @covers Nimbly\Carton\ContainerException
 */
class ContainerTest extends TestCase
{
	public function test_get_instance(): void
	{
		$container = Container::getInstance();

		$this->assertSame(
			$container,
			Container::getInstance()
		);
	}

	public function test_has_returns_true_on_id_found(): void
	{
		$container = new Container;
		$container->set("container", new \stdClass);

		$this->assertTrue(
			$container->has("container")
		);
	}

	public function test_has_returns_false_on_id_not_found(): void
	{
		$container = new Container;

		$this->assertFalse(
			$container->has("container")
		);
	}

	public function test_get(): void
	{
		$container = new Container;
		$container->set("container", new \stdClass);

		$this->assertEquals(
			new \stdClass,
			$container->get("container")
		);
	}

	public function test_get_throws_exception_on_id_not_found(): void
	{
		$container = new Container;

		$this->expectException(NotFoundException::class);
		$container->get("container");
	}

	public function test_get_passes_container_instance_into_builder(): void
	{
		$container = new Container;
		$container->set("foo", new \stdClass);
		$container->singleton("container", function(Container $container){
			$class = new \stdClass;
			$class->foo = $container->get("foo");
			return $class;
		});

		$this->assertSame(
			$container->get("container")->foo,
			$container->get("foo")
		);
	}

	public function test_set_converts_item_to_concrete_builder(): void
	{
		$container = new Container;
		$container->set("container", new \stdClass);

		$instance = new ReflectionClass($container);
		$property = $instance->getProperty("items");
		$property->setAccessible(true);

		$this->assertTrue(
			$property->getValue($container)["container"] instanceof ConcreteBuilder
		);
	}

	public function test_set_creates_alias(): void
	{
		$container = new Container;
		$container->set("container", new \stdClass, "alias");

		$this->assertTrue($container->has("alias"));
		$this->assertInstanceOf(
			\stdClass::class,
			$container->get("alias")
		);
	}

	public function test_set_creates_array_of_aliases(): void
	{
		$container = new Container;
		$container->set("container", new \stdClass, ["alias", "anotheralias"]);

		$this->assertTrue($container->has("alias"));
		$this->assertInstanceOf(
			\stdClass::class,
			$container->get("alias")
		);

		$this->assertTrue($container->has("anotheralias"));
		$this->assertInstanceOf(
			\stdClass::class,
			$container->get("anotheralias")
		);
	}

	public function test_singleton_creates_and_sets_singleton_builder(): void
	{
		$container = new Container;
		$container->singleton("container", function(): \stdClass {
			return new \stdClass;
		});

		$instance = new ReflectionClass($container);
		$property = $instance->getProperty("items");
		$property->setAccessible(true);

		$this->assertTrue(
			$property->getValue($container)["container"] instanceof SingletonBuilder
		);
	}

	public function test_factory_creates_and_sets_factory_builder(): void
	{
		$container = new Container;
		$container->factory("container", function(): \stdClass {
			return new \stdClass;
		});

		$instance = new ReflectionClass($container);
		$property = $instance->getProperty("items");
		$property->setAccessible(true);

		$this->assertTrue(
			$property->getValue($container)["container"] instanceof FactoryBuilder
		);
	}

	public function test_alias_of_non_existant_item_throws_not_found_exception(): void
	{
		$container = new Container;

		$this->expectException(NotFoundException::class);
		$container->alias("Alias", "Item");
	}

	public function test_alias_returns_item(): void
	{
		$container = new Container;

		$container->factory(
			FooClass::class,
			function(): FooClass {
				return new FooClass(new DateTime);
			}
		);

		$container->alias(BarClass::class, FooClass::class);

		$this->assertInstanceOf(
			FooClass::class,
			$container->get(BarClass::class)
		);
	}

	public function test_register_instance(): void
	{
		$container = new Container;
		$container->register([new SampleProvider]);

		$this->assertTrue(
			$container->has("Sample")
		);
	}

	public function test_register_string_reference(): void
	{
		$container = new Container;
		$container->register([SampleProvider::class]);

		$this->assertTrue(
			$container->has("Sample")
		);
	}

	public function test_register_invalid_provider(): void
	{
		$container = new Container;

		$this->expectException(ContainerException::class);
		$container->register([new \stdClass]);
	}

	public function test_call(): void
	{
		$container = new Container;
		$container->set(
			FooClass::class,
			new FooClass(new DateTime)
		);

		$result = $container->call(
			function(FooClass $foo, DateTime $date) {
				return $foo->getDateTime() > $date;
			},
			["date" => new DateTime("last week")]
		);

		$this->assertTrue($result);
	}

	public function test_add_container(): void
	{
		$container = new Container;

		$container2 = new Container;
		$container->addContainer($container2);

		$reflectionClass = new ReflectionClass($container);
		$reflectionProperty = $reflectionClass->getProperty("containers");

		$reflectionProperty->setAccessible(true);
		$containers = $reflectionProperty->getValue($container);

		$this->assertContains(
			$container2,
			$containers
		);
	}

	public function test_has_checks_other_containers(): void
	{
		$container = new Container;

		$container2 = new Container;
		$container2->set("foo", "bar");

		$container->addContainer($container2);

		$this->assertTrue(
			$container->has("foo")
		);
	}

	public function test_get_from_nested_container(): void
	{
		$container = new Container;

		$container2 = new Container;
		$container2->set("foo", "bar");

		$container->addContainer($container2);

		$this->assertEquals(
			"bar",
			$container->get("foo")
		);
	}

	public function test_make(): void
	{
		$container = new Container;
		$instance = $container->make(
			FooClass::class,
			["dateTime" => new DateTime]
		);

		$this->assertEquals(
			$instance::class,
			FooClass::class
		);
	}

	public function test_make_callable_with_function(): void
	{
		$container = new Container;
		$callable = $container->makeCallable(
			"strtolower"
		);

		$this->assertIsCallable($callable);
	}

	public function test_make_callable_with_invokable_class(): void
	{
		$container = new Container;
		$invokable = new InvokableClass;

		$callable = $container->makeCallable($invokable);

		$this->assertIsCallable($callable);
	}
}