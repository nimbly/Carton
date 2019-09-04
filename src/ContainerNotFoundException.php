<?php

namespace Carton;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

class ContainerNotFoundException extends Exception implements NotFoundExceptionInterface
{
}