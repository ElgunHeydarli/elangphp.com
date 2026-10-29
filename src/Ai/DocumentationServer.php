<?php
declare(strict_types=1);
namespace Elang\Ai;
use Elang\Application;
use Elang\Support\Config;
use Symfony\Component\HttpFoundation\{Request,Response,JsonResponse};
final class DocumentationServer
{
    public const PROTOCOL='2025-11-25';
    public function __construct(private DocumentSource $source,private Config $config) {}
    public function http(Request $request): Response
    {
        $origin=$request->headers->get('Origin');$allowed=rtrim($this->config->get('url','https://elangphp.com'),'/');
        $hosts=[parse_url($allowed,PHP_URL_HOST)];
        if($this->config->get('environment')==='local'&&in_array($request->server->get('REMOTE_ADDR'),['127.0.0.1','::1'],true)) $hosts=array_merge($hosts,['localhost','127.0.0.1','::1','[::1]']);
        if(!in_array($request->getHost(),$hosts,true)) return new JsonResponse(['error'=>'Host is not allowed.'],403);
        if($origin!==null&&$origin!==$allowed) return new JsonResponse(['error'=>'Origin is not allowed.'],403);
        if(!$request->isMethod('POST')) return new Response('',405,['Allow'=>'POST','X-Robots-Tag'=>'noindex']);
        if(!str_contains($request->headers->get('Content-Type',''),'application/json')) return new JsonResponse(['error'=>'Use application/json.'],415);
        if(strlen($request->getContent())>65536) return new JsonResponse(['error'=>'Request is too large.'],413);
        try { $message=json_decode($request->getContent(),true,64,JSON_THROW_ON_ERROR); }
        catch(\JsonException) { return new JsonResponse($this->error(null,-32700,'Parse error.'),400); }
        $result=$this->handle($message);
        $headers=['Cache-Control'=>'no-store','X-Robots-Tag'=>'noindex','MCP-Protocol-Version'=>self::PROTOCOL];
        if($result===null) return new Response('',202,$headers);
        return new JsonResponse($result,200,$headers);
    }
    private function error(mixed $id,int $code,string $message): array { return ['jsonrpc'=>'2.0','id'=>$id,'error'=>['code'=>$code,'message'=>$message]]; }
    public function handle(mixed $message): ?array
    {
        if(!is_array($message)||array_is_list($message)||($message['jsonrpc']??null)!=='2.0'||!isset($message['method'])||!is_string($message['method'])) return $this->error(null,-32600,'Invalid request.');
        $hasId=array_key_exists('id',$message);$id=$message['id']??null;
        if($hasId&&!is_int($id)&&!is_string($id)&&$id!==null) return $this->error(null,-32600,'Invalid request ID.');
        if(!$hasId) return null;
        $params=$message['params']??[];if(!is_array($params)||($params!==[]&&array_is_list($params))) return $this->error($id,-32602,'Invalid parameters.');
        try {
            $result=match($message['method']) {
                'initialize'=>['protocolVersion'=>self::PROTOCOL,'capabilities'=>['tools'=>(object)[],'resources'=>(object)[]],'serverInfo'=>['name'=>'elang-docs','version'=>Application::VERSION],'instructions'=>'Read-only public Elang documentation. Use the versioned source; never assume Laravel-only APIs.'],
                'ping'=>(object)[],
                'tools/list'=>['tools'=>$this->tools()],
                'tools/call'=>$this->call($params),
                'resources/list'=>$this->resources($params),
                'resources/read'=>$this->resource($params),
                default=>null,
            };
            return $result===null?$this->error($id,-32601,'Method not found.'):['jsonrpc'=>'2.0','id'=>$id,'result'=>$result];
        }catch(\InvalidArgumentException $error){return $this->error($id,-32602,$error->getMessage());}
        catch(\Throwable){return $this->error($id,-32603,'Documentation request failed.');}
    }
    private function tools(): array
    {
        $annotations=['readOnlyHint'=>true,'destructiveHint'=>false,'idempotentHint'=>true,'openWorldHint'=>false];
        return [
            ['name'=>'search_docs','description'=>'Search published Elang documentation, lessons, and guides.','inputSchema'=>['type'=>'object','properties'=>['query'=>['type'=>'string','minLength'=>2,'maxLength'=>100],'limit'=>['type'=>'integer','minimum'=>1,'maximum'=>10]],'required'=>['query'],'additionalProperties'=>false],'annotations'=>$annotations],
            ['name'=>'read_doc','description'=>'Read one published document by its exact path, such as docs/v0.1/routing.','inputSchema'=>['type'=>'object','properties'=>['path'=>['type'=>'string','maxLength'=>200]],'required'=>['path'],'additionalProperties'=>false],'annotations'=>$annotations],
        ];
    }
    private function call(array $params): array
    {
        $name=$params['name']??null;$arguments=$params['arguments']??[];
        if(!is_array($arguments)) throw new \InvalidArgumentException('Tool arguments must be an object.');
        if($name==='search_docs') {
            if(array_diff(array_keys($arguments),['query','limit'])) throw new \InvalidArgumentException('Unexpected search argument.');
            $query=$arguments['query']??null;$limit=$arguments['limit']??10;
            if(!is_string($query)||mb_strlen(trim($query))<2||mb_strlen($query)>100||!is_int($limit)||$limit<1||$limit>10) throw new \InvalidArgumentException('Use a 2–100 character query and a limit of 1–10.');
            $data=['results'=>$this->source->search($query,$limit)];
        } elseif($name==='read_doc') {
            if(array_diff(array_keys($arguments),['path'])||!isset($arguments['path'])||!is_string($arguments['path'])||strlen($arguments['path'])>200) throw new \InvalidArgumentException('Supply one document path.');
            $data=$this->source->read($arguments['path']);
            if(!$data) return ['content'=>[['type'=>'text','text'=>'That published document was not found. Search the documentation for an exact path.']],'isError'=>true];
        } else throw new \InvalidArgumentException('Unknown documentation tool.');
        return ['content'=>[['type'=>'text','text'=>json_encode($data,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]],'structuredContent'=>$data,'isError'=>false];
    }
    private function resources(array $params): array
    {
        $cursor=$params['cursor']??'0';if(!is_string($cursor)||!ctype_digit($cursor)||strlen($cursor)>8) throw new \InvalidArgumentException('Invalid resource cursor.');
        $all=$this->source->list();$offset=(int)$cursor;$items=[];
        foreach(array_slice($all,$offset,50) as $item) $items[]=['uri'=>'elang://'.$item['path'],'name'=>$item['path'],'title'=>$item['title'],'mimeType'=>'text/markdown'];
        $result=['resources'=>$items];if($offset+50<count($all)) $result['nextCursor']=(string)($offset+50);return $result;
    }
    private function resource(array $params): array
    {
        $uri=$params['uri']??null;if(!is_string($uri)||!str_starts_with($uri,'elang://')||strlen($uri)>208) throw new \InvalidArgumentException('Use a listed elang:// resource URI.');
        $document=$this->source->read(substr($uri,8));if(!$document) throw new \InvalidArgumentException('Resource not found.');
        return ['contents'=>[['uri'=>$uri,'mimeType'=>'text/markdown','text'=>$document['markdown']]]];
    }
}
