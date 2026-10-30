<?php
declare(strict_types=1);
namespace Elang\Http;
use Elang\Contracts\Responsable;
use Elang\Data\Paginator;
use Symfony\Component\HttpFoundation\{Request, Response as HttpResponse};
final class ResourceCollection implements Responsable
{
    /** @var class-string<Resource> */
    private string $class;
    public function __construct(string $class, private iterable $items)
    {
        if (!is_subclass_of($class, Resource::class)) throw new \InvalidArgumentException('A resource collection requires an Elang Resource class.');
        $this->class=$class;
    }
    public function toResponse(Request $request): HttpResponse
    {
        $data = []; foreach ($this->items as $item) $data[] = (new $this->class($item))->toArray($request);
        $payload = ['data' => $data];
        if ($this->items instanceof Paginator) $payload['meta'] = $this->items->jsonSerialize()['meta'];
        return Response::json($payload);
    }
}
