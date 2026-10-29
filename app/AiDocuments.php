<?php
declare(strict_types=1);
namespace App;
use Elang\Ai\DocumentSource;
use Elang\Support\Config;
final class AiDocuments implements DocumentSource
{
    public function __construct(private ContentRepository $content,private Config $config) {}
    public function search(string $query,int $limit=10): array { return array_slice($this->content->search($query),0,$limit); }
    public function read(string $path): ?array
    {
        $path=trim($path,'/');
        foreach($this->content->catalog() as $section=>$items) foreach($items as $slug=>$title) if($path===$section.'/'.$slug) {
            $document=$this->content->document($section,$slug);
            return ['path'=>$path,'title'=>$document['title'],'url'=>rtrim($this->config->get('url','https://elangphp.com'),'/').'/'.$path,'markdown'=>$document['markdown'],'version'=>'v0.1'];
        }
        return null;
    }
    public function list(): array
    {
        $items=[];foreach($this->content->catalog() as $section=>$pages) foreach($pages as $slug=>$title) $items[]=['path'=>$section.'/'.$slug,'title'=>$title];return $items;
    }
}
