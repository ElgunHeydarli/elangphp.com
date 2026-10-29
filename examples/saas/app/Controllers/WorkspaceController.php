<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Projects;
use Elang\{Auth\User,Auth\Users,Tenancy\Teams,Storage\Files,Notification\Notifications,Http\Response,Http\HttpException,Security\Csrf,View\View,Support\Config,Realtime\Stream};
use Symfony\Component\HttpFoundation\{Request,Response as HttpResponse,StreamedResponse,HeaderUtils};
final class WorkspaceController
{
    public function __construct(private Teams $teams,private Projects $projects,private Files $files,private Notifications $notifications,private Csrf $csrf,private View $view,private Config $config) {}
    private function user(Request $request):User { return $request->attributes->get('_user')??throw new HttpException(401,'Sign in first.'); }
    private function input(Request $request,string $key,string $default=''):string { $v=$request->request->all()[$key]??$default;if(!is_string($v)) throw new HttpException(422,'Enter a text value for '.$key.'.');return $v; }
    private function number(Request $request,string $key):int { $value=filter_var($this->input($request,$key),FILTER_VALIDATE_INT);if($value===false||$value<1) throw new HttpException(422,'Enter a positive '.$key.'.');return $value; }
    private function back(string $team):HttpResponse { return Response::redirect('/workspace/team/'.$team,303); }
    public function index(Request $request,?string $team=null,array $extra=[]):HttpResponse
    {
        $user=$this->user($request);$selected=$team===null?null:$this->teams->within($user,$team,'read',fn($db,$row)=>$row);$response=$this->view->render('@elang/workspace',array_merge(['user'=>$user,'token'=>$this->csrf->token($request),'teams'=>$this->teams->list($user),'selected'=>$selected,'projects'=>$team===null?[]:$this->projects->list($user,$team),'members'=>$team===null?[]:$this->teams->members($user,$team),'files'=>$team===null?[]:$this->files->list($user,$team),'inbox'=>$this->notifications->inbox($user),'invite_url'=>null,'errors'=>[],'message'=>''],$extra));$response->headers->set('Cache-Control','private, no-store');$response->headers->set('X-Robots-Tag','noindex');$response->headers->set('Referrer-Policy','no-referrer');return $response;
    }
    public function createTeam(Request $request):HttpResponse { $team=$this->teams->create($this->user($request),$this->input($request,'name'));$this->notifications->send($this->user($request),'Workspace ready','Your new organization is ready.','/workspace/team/'.$team['id']);return $this->back($team['id']); }
    public function createProject(Request $request,string $team):HttpResponse { $this->projects->create($this->user($request),$team,$this->input($request,'title'));return $this->back($team); }
    public function updateProject(Request $request,string $team,string $project):HttpResponse { $this->projects->update($this->user($request),$team,$project,$this->input($request,'title'),$this->input($request,'status'),$this->number($request,'version'));return $this->back($team); }
    public function deleteProject(Request $request,string $team,string $project):HttpResponse { $this->projects->delete($this->user($request),$team,$project,$this->number($request,'version'));return $this->back($team); }
    public function invite(Request $request,string $team):HttpResponse { $raw=$this->teams->invite($this->user($request),$team,$this->input($request,'email'),$this->input($request,'role','member'));return $this->index($request,$team,['invite_url'=>rtrim($this->config->get('url'),'/').'/workspace/invite/'.$raw,'message'=>'Share this private link with the invited email owner. It expires in seven days.']); }
    public function invitation(Request $request,string $token):HttpResponse
    {
        $safe=htmlspecialchars($this->csrf->token($request),ENT_QUOTES,'UTF-8');$action='/workspace/invite/'.$token;return Response::html('<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Accept invitation</title><link rel="stylesheet" href="/_elang/assets/ui.css"><main class="product-shell"><h1>Join the workspace</h1><p>Continue only if you expected this invitation. Your verified email must match the invitation.</p><form method="post" action="'.$action.'"><input type="hidden" name="_token" value="'.$safe.'"><button>Accept invitation</button></form></main></html>',200,['Cache-Control'=>'private, no-store','X-Robots-Tag'=>'noindex','Referrer-Policy'=>'no-referrer']);
    }
    public function accept(Request $request,string $token):HttpResponse { $team=$this->teams->accept($this->user($request),$token);if(!$team) throw new HttpException(422,'This invitation is invalid, expired, already used, or belongs to another email.');return $this->back($team['id']); }
    public function role(Request $request,string $team,int $member):HttpResponse { $this->teams->role($this->user($request),$team,$member,$this->input($request,'role'));return $this->back($team); }
    public function remove(Request $request,string $team,int $member):HttpResponse { $this->teams->remove($this->user($request),$team,$member);return $this->back($team); }
    public function transfer(Request $request,string $team):HttpResponse { $this->teams->transfer($this->user($request),$team,$this->number($request,'user_id'));return $this->back($team); }
    public function upload(Request $request,string $team):HttpResponse
    {
        $file=$request->files->get('file');if(!$file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile||!$file->isValid()) throw new HttpException(422,'Choose a valid uploaded file.');$limit=$this->config->get('files.max_bytes',10485760);if($file->getSize()>$limit) throw new HttpException(413,'The file exceeds the configured limit.');$bytes=file_get_contents($file->getPathname(),false,null,0,$limit+1);if(!is_string($bytes)) throw new HttpException(422,'Could not read the upload.');$this->files->put($this->user($request),$file->getClientOriginalName(),$bytes,$team,$this->input($request,'image')==='1');return $this->back($team);
    }
    public function deleteFile(Request $request,string $team,string $file):HttpResponse
    {
        $this->teams->within($this->user($request),$team,'write',function()use($request,$team,$file):void { $listed=array_column($this->files->list($this->user($request),$team),'id');if(!in_array($file,$listed,true)) throw new HttpException(404,'File not found.');$this->files->delete($this->user($request),$file); });return $this->back($team);
    }
    public function file(Request $request,string $team,string $file):HttpResponse
    {
        $this->teams->within($this->user($request),$team,'read',function()use($request,$team,$file):void { if(!in_array($file,array_column($this->files->list($this->user($request),$team),'id'),true)) throw new HttpException(404,'File not found.'); });return Response::redirect($this->files->temporaryUrl($this->user($request),$file));
    }
    public function download(Request $request,string $file):HttpResponse
    {
        $user=$this->user($request);$expires=$request->query->all()['expires']??'';$signature=$request->query->all()['signature']??'';if(!is_scalar($expires)||!is_string($signature)||!ctype_digit((string)$expires)||!$this->files->verifyUrl($user,$file,(int)$expires,$signature)) throw new HttpException(403,'This download link is invalid or expired.');$download=$this->files->download($user,$file);$stream=$download['stream'];$row=$download['metadata'];return new StreamedResponse(function()use($stream):void { try { fpassthru($stream); }finally { fclose($stream); } },200,['Content-Type'=>'application/octet-stream','Content-Length'=>(string)$row['bytes'],'Content-Disposition'=>HeaderUtils::makeDisposition('attachment',$row['name'],'download'),'Cache-Control'=>'private, no-store','X-Robots-Tag'=>'noindex']);
    }
    public function read(Request $request,string $id):HttpResponse { $this->notifications->markRead($this->user($request),$id);return Response::redirect('/workspace',303); }
    public function events(Request $request,string $team,Stream $stream,Users $users):HttpResponse
    {
        $user=$this->user($request);$this->teams->within($user,$team,'read',fn()=>null);$request->getSession()->save();$check=function()use($user,$team,$users):bool { $fresh=$users->find($user->id);if(!$fresh||$fresh->authVersion!==$user->authVersion) return false;try { $this->teams->within($fresh,$team,'read',fn()=>null);return true; }catch(HttpException) { return false; } };return $stream->response($request,'team:'.$team.':projects',20,$check);
    }
}
