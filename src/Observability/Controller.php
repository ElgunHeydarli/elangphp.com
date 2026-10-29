<?php
declare(strict_types=1);
namespace Elang\Observability;
use Elang\{Http\Response,Http\HttpException,Queue\Queue,Security\Csrf,Audit\Audit,View\View};
use Symfony\Component\HttpFoundation\{Request,Response as HttpResponse};
final class Controller
{
    public function index(Request $request,Operations $ops,Csrf $csrf,View $view):HttpResponse { $response=$view->render('@elang/operations',['snapshot'=>$ops->snapshot(),'token'=>$csrf->token($request)]);$response->headers->set('Cache-Control','private, no-store');$response->headers->set('X-Robots-Tag','noindex');return $response; }
    public function metrics(Operations $ops):HttpResponse { return Response::json($ops->snapshot(),200,['Cache-Control'=>'private, no-store','X-Robots-Tag'=>'noindex']); }
    public function retry(Request $request,string $id,Queue $queue,Audit $audit):HttpResponse { if(!$queue->retry($id)) throw new HttpException(404,'Failed job not found.');$audit->append('operations','queue.retried',$request->attributes->get('_user')->id,['job_id'=>$id]);return Response::redirect('/operations',303); }
}
