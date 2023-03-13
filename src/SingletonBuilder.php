<?php

namespace Nimbly\Carton;

use Psr\Container\ContainerInterface;

class SingletonBuilder implements BuilderInterface
{
	/**
	 * The single instance.
	 *
	 * @var mixed
	 */
	protected mixed $instance = null;

	/**
	 * The callable builder.
	 *
	 * @var callable
	 */
	protected $builder;

	/**
	 * SingletonBuilder constructor.
	 *
	 * @param callable $builder
	 */
	public function __construct(callable $builder)
	{
		$this->builder = $builder;
	}

	/**
	 * @inheritDoc
	 */
	public function build(ContainerInterface $container): mixed
	{
		if( empty($this->instance) ){
			$this->instance = \call_user_func($this->builder, $container);
		}

		return $this->instance;
	}
}