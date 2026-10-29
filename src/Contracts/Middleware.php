<?php
declare(strict_types=1);
namespace Elang\Contracts;
use Closure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
interface Middleware { public function process(Request $request, Closure $next): Response; }
