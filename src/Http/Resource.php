<?php
declare(strict_types=1);
namespace Elang\Http;
use Elang\Contracts\Responsable;
use Symfony\Component\HttpFoundation\{Request, Response as HttpResponse};

/** Explicit API fields. No automatic exposure of entity attributes or relations. */
abstract class Resource implements Responsable
{
    public function __construct(protected mixed $resource) {}
    abstract public function toArray(Request $request): array;
    public function toResponse(Request $request): HttpResponse { return Response::json(['data' => $this->toArray($request)]); }
    public static function collection(iterable $items): ResourceCollection { return new ResourceCollection(static::class, $items); }
}
