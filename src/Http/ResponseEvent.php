<?php
declare(strict_types=1);
namespace Elang\Http;
use Symfony\Component\HttpFoundation\{Request,Response};
final readonly class ResponseEvent { public function __construct(public Request $request,public Response $response) {} }
