<?php
declare(strict_types=1);
namespace App\Controllers;
use Elang\{Auth\User,Auth\Auth,Auth\ApiTokens,Http\HttpException,Http\Response,Realtime\SocketTokens,Realtime\Stream};
use Symfony\Component\HttpFoundation\{Request,Response as HttpResponse};
final class RealtimeController
{
    public function __construct(private \Elang\Auth\Users $users,private \Elang\Security\Account\Devices $devices,private \Elang\Support\Config $config) {}
    private function user(Request $request):User { $user=$request->attributes->get('_user');return $user instanceof User?$user:throw new HttpException(401,'Sign in first.'); }
    public function events(Request $request,Stream $stream,ApiTokens $tokens):HttpResponse
    {
        $user=$this->user($request);$header=$request->headers->get('Authorization','');$raw=substr($header,7);
        if($request->attributes->get('_auth_session')===true) {
            $identity=$request->getSession()->get('_auth');$request->getSession()->save();
            $check=function()use($user,$identity):bool { $fresh=$this->users->find($user->id);return $fresh!==null&&$fresh->authVersion===$user->authVersion&&$this->devices->valid($identity['device'],$fresh); };
        }else $check=fn()=>$tokens->authenticate($raw)!==null;
        return $stream->response($request,'user:'.$user->id.':notes',20,$check);
    }
    public function token(Request $request,SocketTokens $tokens):HttpResponse
    {
        $user=$this->user($request);$access=$request->attributes->get('_access_token');$write=$access?->allows('notes:write')||$request->attributes->get('_auth_session')===true&&in_array('notes:write',$this->config->get('auth.session_scopes',['notes:read','notes:write']),true);$commands=$write?['note.create']:[];
        $response=Response::json(['token'=>$tokens->issue($user,'user:'.$user->id.':notes',300,$commands),'expires_in'=>300,'protocol'=>'elang','path'=>'/realtime/socket']);$response->headers->set('Cache-Control','private, no-store');return $response;
    }
}
