<?php

namespace Nimbly\Carton;

use Psr\Container\ContainerInterface;

class FactoryBuilder implements BuilderInterface
{
	/**
	 * The callable builder.
	 *
	 * @var callable
	 */
	protected $builder;

	/**
	 * FactoryBuilder constructor.
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
		return \call_user_func($this->builder, $container);
	}
}