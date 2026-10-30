<?php
declare(strict_types=1);
namespace Elang\Contracts;
use Symfony\Component\HttpFoundation\{Request, Response};
interface Responsable { public function toResponse(Request $request): Response; }
