<?php
declare(strict_types=1);
namespace App\Controllers;
use Elang\{Auth\User,Auth\Auth,Auth\ApiTokens,Http\HttpException,Http\Response,Realtime\SocketTokens,Realtime\Stream};
use Symfony\Component\HttpFoundation\{Request,Response as HttpResponse};
final class RealtimeController
{
    private function user(Request $request):User { $user=$request->attributes->get('_user');return $user instanceof User?$user:throw new HttpException(401,'Sign in first.'); }
    public function events(Request $request,Stream $stream,ApiTokens $tokens):HttpResponse
    {
        $user=$this->user($request);$header=$request->headers->get('Authorization','');$raw=substr($header,7);
        return $stream->response($request,'user:'.$user->id.':notes',20,fn()=>$tokens->authenticate($raw)!==null);
    }
    public function token(Request $request,SocketTokens $tokens):HttpResponse
    {
        $user=$this->user($request);$access=$request->attributes->get('_access_token');$commands=$access?->allows('notes:write')?['note.create']:[];
        $response=Response::json(['token'=>$tokens->issue($user,'user:'.$user->id.':notes',300,$commands),'expires_in'=>300,'protocol'=>'elang','path'=>'/realtime/socket']);$response->headers->set('Cache-Control','private, no-store');return $response;
    }
}
