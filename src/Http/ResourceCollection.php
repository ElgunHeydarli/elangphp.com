<?php
declare(strict_types=1);
namespace Elang\Http;
use Elang\Contracts\Responsable;
use Elang\Data\Paginator;
use Symfony\Component\HttpFoundation\{Request, Response as HttpResponse};
final class ResourceCollection implements Responsable
{
    /** @param class-string<Resource> $class */
    public function __construct(private string $class, private iterable $items)
    {
        if (!is_subclass_of($class, Resource::class)) throw new \InvalidArgumentException('A resource collection requires an Elang Resource class.');
    }
    public function toResponse(Request $request): HttpResponse
    {
        $data = []; foreach ($this->items as $item) $data[] = (new $this->class($item))->toArray($request);
        $payload = ['data' => $data];
        if ($this->items instanceof Paginator) $payload['meta'] = $this->items->jsonSerialize()['meta'];
        return Response::json($payload);
    }
}
