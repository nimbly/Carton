<?php

namespace Nimbly\Carton;

use Psr\Container\ContainerInterface;

interface BuilderInterface
{
	/**
	 * Build the instance.
	 *
	 * @param ContainerInterface $container
	 * @return mixed
	 */
	public function build(ContainerInterface $container): mixed;
}