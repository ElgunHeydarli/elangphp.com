<?php
declare(strict_types=1);
namespace ElangExample\Catalog;
use Elang\Http\Resource;
use Symfony\Component\HttpFoundation\Request;
final class ProductResource extends Resource
{
    public function toArray(Request $request):array
    {
        return ['id'=>$this->resource->id,'title'=>$this->resource->title,'quantity'=>$this->resource->quantity,'category'=>$this->resource->category->name];
    }
}
