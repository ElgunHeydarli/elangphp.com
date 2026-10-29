<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Projects;
use Elang\{Tenancy\Teams,Http\Response,Http\HttpException};
use Symfony\Component\HttpFoundation\{Request,Response as HttpResponse};
final class TeamsApiController
{
    public function __construct(private Teams $teams,private Projects $projects) {}
    private function data(Request $r):array { if(!str_contains($r->headers->get('Content-Type',''),'application/json')) throw new HttpException(415,'Use application/json.');try { $data=json_decode($r->getContent(),true,16,JSON_THROW_ON_ERROR); }catch(\JsonException) { throw new HttpException(400,'Invalid JSON.'); }if(!is_array($data)||array_is_list($data)) throw new HttpException(400,'Supply a JSON object.');return $data; }
    private function text(array $data,string $key):string { $value=$data[$key]??'';if(!is_string($value)) throw new HttpException(422,'Supply text for '.$key.'.');return $value; }
    public function index(Request $r):HttpResponse { return Response::json(['data'=>$this->teams->list($r->attributes->get('_user'))]); }
    public function create(Request $r):HttpResponse { return Response::json(['data'=>$this->teams->create($r->attributes->get('_user'),$this->text($this->data($r),'name'))],201); }
    public function projects(Request $r,string $team):HttpResponse { return Response::json(['data'=>$this->projects->list($r->attributes->get('_user'),$team)]); }
    public function store(Request $r,string $team):HttpResponse { return Response::json(['data'=>$this->projects->create($r->attributes->get('_user'),$team,$this->text($this->data($r),'title'))],201); }
    public function update(Request $r,string $team,string $project):HttpResponse { $data=$this->data($r);if(!is_int($data['version']??null)) throw new HttpException(422,'Supply the integer project version.');$this->projects->update($r->attributes->get('_user'),$team,$project,$this->text($data,'title'),$this->text($data,'status'),$data['version']);return Response::json(['updated'=>true]); }
    public function delete(Request $r,string $team,string $project):HttpResponse { $data=$this->data($r);if(!is_int($data['version']??null)) throw new HttpException(422,'Supply the integer project version.');$this->projects->delete($r->attributes->get('_user'),$team,$project,$data['version']);return new HttpResponse('',204); }
}
