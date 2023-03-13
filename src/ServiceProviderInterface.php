<?php

namespace Nimbly\Carton;

interface ServiceProviderInterface
{
	/**
	 * Register a service with the Container.
	 *
	 * @param Container $container
	 * @return void
	 */
	public function register(Container $container): void;
}