<?php
declare(strict_types=1);
namespace Elang\Container;
final class NotFoundException extends ContainerException implements \Psr\Container\NotFoundExceptionInterface {}
