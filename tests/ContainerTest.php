<?php

namespace Carton\Tests;

use Carton\CallableResolutionException;
use Carton\ConcreteBuilder;
use Carton\Container;
use Carton\ContainerException;
use Carton\FactoryBuilder;
use Carton\NotFoundException;
use Carton\ParameterResolutionException;
use Carton\SingletonBuilder;
use Carton\Tests\Mock\BarClass;
use Carton\Tests\Mock\FooClass;
use Carton\Tests\Mock\InvocableClass;
use Carton\Tests\Providers\SampleProvider;
use DateInterval;
use DateTime;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @covers Carton\Container
 * @covers Carton\NotFoundException
 * @covers Carton\ConcreteBuilder
 * @covers Carton\SingletonBuilder
 * @covers Carton\FactoryBuilder
 * @covers Carton\ParameterResolutionException
 * @covers Carton\CallableResolutionException
 * @covers Carton\ContainerException
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
		$container->set('container', new \stdClass);

		$this->assertTrue(
			$container->has('container')
		);
	}

	public function test_has_returns_false_on_id_not_found(): void
	{
		$container = new Container;

		$this->assertFalse(
			$container->has('container')
		);
	}

	public function test_get(): void
	{
		$container = new Container;
		$container->set('container', new \stdClass);

		$this->assertEquals(
			new \stdClass,
			$container->get('container')
		);
	}

	public function test_get_throws_exception_on_id_not_found(): void
	{
		$container = new Container;

		$this->expectException(NotFoundException::class);
		$container->get('container');
	}

	public function test_get_passes_container_instance_into_builder(): void
	{
		$container = new Container;
		$container->set('foo', new \stdClass);
		$container->singleton('container', function(Container $container){
			$class = new \stdClass;
			$class->foo = $container->get('foo');
			return $class;
		});

		$this->assertSame(
			$container->get('container')->foo,
			$container->get('foo')
		);
	}

	public function test_set_converts_item_to_concrete_builder(): void
	{
		$container = new Container;
		$container->set('container', new \stdClass);

		$instance = new ReflectionClass($container);
		$property = $instance->getProperty('items');
		$property->setAccessible(true);

		$this->assertTrue(
			$property->getValue($container)['container'] instanceof ConcreteBuilder
		);
	}

	public function test_singleton_creates_and_sets_singleton_builder(): void
	{
		$container = new Container;
		$container->singleton('container', function(): \stdClass {
			return new \stdClass;
		});

		$instance = new ReflectionClass($container);
		$property = $instance->getProperty('items');
		$property->setAccessible(true);

		$this->assertTrue(
			$property->getValue($container)['container'] instanceof SingletonBuilder
		);
	}

	public function test_factory_creates_and_sets_factory_builder(): void
	{
		$container = new Container;
		$container->factory('container', function(): \stdClass {
			return new \stdClass;
		});

		$instance = new ReflectionClass($container);
		$property = $instance->getProperty('items');
		$property->setAccessible(true);

		$this->assertTrue(
			$property->getValue($container)['container'] instanceof FactoryBuilder
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

	public function test_register_single_instance(): void
	{
		$container = new Container;
		$container->register(new SampleProvider);

		$this->assertTrue(
			$container->has('Sample')
		);
	}

	public function test_register_string_reference(): void
	{
		$container = new Container;
		$container->register(SampleProvider::class);

		$this->assertTrue(
			$container->has('Sample')
		);
	}

	public function test_register_array(): void
	{
		$container = new Container;
		$container->register([
			SampleProvider::class
		]);

		$this->assertTrue(
			$container->has('Sample')
		);
	}

	public function test_register_invalid_provider(): void
	{
		$container = new Container;

		$this->expectException(ContainerException::class);
		$container->register(new \stdClass);
	}

	public function test_make_class_with_empty_constructor(): void
	{
		$container = new Container;

		$this->assertInstanceOf(
			\stdClass::class,
			$container->make(\stdClass::class)
		);
	}

	public function test_make_with_constructor_parameters(): void
	{
		$container = new Container;

		$this->assertInstanceOf(
			FooClass::class,
			$container->make(FooClass::class, ["dateTime" => new DateTime])
		);
	}

	public function test_make_with_retrieving_dependencies_from_container(): void
	{
		$container = new Container;

		$fooClass = new FooClass(new DateTime);

		$container->set(
			FooClass::class,
			$fooClass
		);

		$this->assertSame(
			$fooClass,
			$container->make(BarClass::class)->getFoo()
		);
	}

	public function test_make_with_user_parameters(): void
	{
		$container = new Container;

		/** @var DateTime $dateTime */
		$dateTime = $container->make(DateTime::class, [
			'time' => '2019-01-01 12:00:00', // PHP 7.4
			'datetime' => '2019-01-01 12:00:00', // PHP 8.0
			'timezone' => new DateTimeZone('America/Los_Angeles')
		]);

		$this->assertEquals(
			'2019-01-01 12:00:00',
			$dateTime->format('Y-m-d H:i:s')
		);

		$this->assertEquals(
			'America/Los_Angeles',
			$dateTime->getTimezone()->getName()
		);
	}

	public function test_make_cannot_resolve_parameter_throws(): void
	{
		$container = new Container;

		$this->expectException(ParameterResolutionException::class);
		$container->make(DateInterval::class);
	}

	public function test_call_on_array_callable(): void
	{
		$fooClass = new FooClass(new \DateTime);
		$dateTime = new DateTime("2000-01-01");

		$container = new Container;
		$returnedDateTime = $container->call([$fooClass, "echoDateTime"], ["dateTime" => $dateTime]);

		$this->assertSame(
			$dateTime,
			$returnedDateTime
		);
	}

	public function test_call_on_invocable(): void
	{
		$dateTime = new DateTime;

		$invocable = new class {
			public function __invoke(DateTime $dateTime): DateTime {
				return $dateTime;
			}
		};

		$container = new Container;
		$returnedDateTime = $container->call($invocable, ["dateTime" => $dateTime]);

		$this->assertSame(
			$dateTime,
			$returnedDateTime
		);
	}

	public function test_make_callable_on_class_method_string(): void
	{
		$container = new Container;

		$callable = $container->makeCallable(
			"Carton\Tests\Mock\FooClass@getDateTime",
			["dateTime" => new DateTime]
		);

		$this->assertIsCallable($callable);
	}

	public function test_make_callable_on_invocable_class_string(): void
	{
		$container = new Container;

		$callable = $container->makeCallable(
			"Carton\Tests\Mock\InvocableClass",
			["dateTime" => new DateTime]
		);

		$this->assertIsCallable($callable);
	}

	public function test_make_callable_on_callable(): void
	{
		$container = new Container;

		$callable = $container->makeCallable(
			new InvocableClass(new DateTime)
		);

		$this->assertIsCallable($callable);
	}

	public function test_make_callable_on_non_resolvable_callable_throws_callable_resolution_exception(): void
	{
		$container = new Container;

		$this->expectException(CallableResolutionException::class);
		$container->makeCallable(new \stdClass);
	}

	public function test_get_callable_arguments_for_array_callable(): void
	{
		$callable = [
			new class {
				public function getTimestamp(DateTime $dateTime): string {
					return $dateTime->format("c");
				}
			},
			"getTimestamp"
		];

		$container = new Container;
		$dateTime = new DateTime;

		$arguments = $container->getCallableArguments(
			$callable,
			[
				"dateTime" => $dateTime,
				"unusedParameter" => "UNUSED"
			]
		);

		$this->assertEquals(
			[$dateTime],
			$arguments
		);
	}

	public function test_get_callable_arguments_for_invocable(): void
	{
		$callable = new class {
			public function __invoke(DateTime $dateTime): string {
				return $dateTime->format("c");
			}
		};

		$container = new Container;

		$dateTime = new DateTime;

		$arguments = $container->getCallableArguments(
			$callable,
			[
				"dateTime" => $dateTime,
				"unusedParameter" => "UNUSED"
			]
		);

		$this->assertEquals(
			[$dateTime],
			$arguments
		);
	}

	public function test_get_callable_arguments_for_string(): void
	{
		$container = new Container;

		$arguments = $container->getCallableArguments(
			"\\strtolower",
			[
				"str" => "CARTON",
				"string" => "CARTON",
				"unusedParameter" => "UNUSED"
			]
		);

		$this->assertEquals(
			["CARTON"],
			$arguments
		);
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
}