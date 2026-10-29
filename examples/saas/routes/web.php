<?php
declare(strict_types=1);
use Elang\Http\Middleware\{SessionMiddleware,CsrfMiddleware,RateLimitMiddleware};
use Elang\Auth\{RequireAuth,RequireVerified,ApiIdentity,RequireScope};
use App\Controllers\{WorkspaceController as Workspace,TeamsApiController as Api};
require dirname(__DIR__).'/routes/notes.php';
$router->group('',[RateLimitMiddleware::class,SessionMiddleware::class,RequireAuth::class,RequireVerified::class,CsrfMiddleware::class],function($r):void {
    $r->get('/workspace',[Workspace::class,'index']);$r->post('/workspace/teams',[Workspace::class,'createTeam']);$r->get('/workspace/invite/{token}',[Workspace::class,'invitation'])->where('token','[a-f0-9]{64}');$r->post('/workspace/invite/{token}',[Workspace::class,'accept'])->where('token','[a-f0-9]{64}');
    $r->get('/workspace/team/{team}',[Workspace::class,'index'])->where('team','[a-f0-9]{32}');
    foreach(['projects'=>'createProject','invites'=>'invite','transfer'=>'transfer','files'=>'upload'] as $suffix=>$action) $r->post('/workspace/team/{team}/'.$suffix,[Workspace::class,$action])->where('team','[a-f0-9]{32}');
    foreach([''=>'updateProject','/delete'=>'deleteProject'] as $suffix=>$action) $r->post('/workspace/team/{team}/projects/{project}'.$suffix,[Workspace::class,$action])->where('team','[a-f0-9]{32}')->where('project','[a-f0-9]{32}');
    foreach(['role'=>'role','remove'=>'remove'] as $suffix=>$action) $r->post('/workspace/team/{team}/members/{member}/'.$suffix,[Workspace::class,$action])->where('team','[a-f0-9]{32}')->where('member','[0-9]+');
    $r->get('/workspace/team/{team}/files/{file}',[Workspace::class,'file'])->where('team','[a-f0-9]{32}')->where('file','[a-f0-9]{32}');$r->post('/workspace/team/{team}/files/{file}/delete',[Workspace::class,'deleteFile'])->where('team','[a-f0-9]{32}')->where('file','[a-f0-9]{32}');$r->get('/files/{file}/download',[Workspace::class,'download'])->where('file','[a-f0-9]{32}');$r->post('/workspace/notifications/{id}/read',[Workspace::class,'read'])->where('id','[a-f0-9]{32}');$r->get('/workspace/team/{team}/events',[Workspace::class,'events'])->where('team','[a-f0-9]{32}');
});
$router->group('/api/teams',[RateLimitMiddleware::class,SessionMiddleware::class,ApiIdentity::class,RequireScope::class,Elang\Tenancy\AuthorizeTeam::class,Elang\Http\Idempotency\Idempotency::class],function($r):void {
    $r->get('',[Api::class,'index'])->defaults(['_scope'=>'teams:read']);$r->post('',[Api::class,'create'])->defaults(['_scope'=>'teams:write']);$r->get('/{team}/projects',[Api::class,'projects'])->where('team','[a-f0-9]{32}')->defaults(['_scope'=>'teams:read']);$r->post('/{team}/projects',[Api::class,'store'])->where('team','[a-f0-9]{32}')->defaults(['_scope'=>'teams:write']);$r->put('/{team}/projects/{project}',[Api::class,'update'])->where('team','[a-f0-9]{32}')->where('project','[a-f0-9]{32}')->defaults(['_scope'=>'teams:write']);$r->delete('/{team}/projects/{project}',[Api::class,'delete'])->where('team','[a-f0-9]{32}')->where('project','[a-f0-9]{32}')->defaults(['_scope'=>'teams:write']);
});
