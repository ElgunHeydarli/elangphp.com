<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Integrations\{Packagist,MailDns};
use Elang\{Auth\Users,Auth\Throttle,Security\Password,Security\Csrf,Audit\Audit,View\View,Http\Response,Http\HttpException};
use Symfony\Component\HttpFoundation\{Request,Response as HttpResponse};
final class IntegrationsController
{
    public function __construct(private Packagist $packagist,private Csrf $csrf,private View $view,private Users $users,private Password $passwords,private Throttle $throttle,private Audit $audit) {}
    private function text(Request $r,string $name):string { $v=$r->request->all()[$name]??'';if(!is_string($v)) throw new HttpException(422,'Enter a text value.');return $v; }
    public function index(Request $r,MailDns $dns):HttpResponse { $response=$this->view->render('integrations',['settings'=>$this->packagist->settings(),'packages'=>$this->packagist->manifest(),'mail_records'=>$dns->records(),'token'=>$this->csrf->token($r),'saved'=>$r->query->get('saved')==='1','dns_submitted'=>$r->query->get('mail_dns')==='submitted']);$response->headers->set('Cache-Control','private, no-store');$response->headers->set('X-Robots-Tag','noindex, nofollow');$response->headers->set('Referrer-Policy','no-referrer');return $response; }
    public function save(Request $r):HttpResponse
    {
        if(!$r->isSecure()) throw new HttpException(403,'Use HTTPS for integration credentials.');$user=$r->attributes->get('_user');$this->throttle->consume('integration-save',$r->getClientIp()??'unknown',(string)$user->id);$row=$this->users->rowByEmail($user->email);if(!$row||!$this->passwords->verify($this->text($r,'current_password'),$row['password'])) throw new HttpException(422,'The current account password is incorrect.');try { $this->packagist->save($this->text($r,'username'),$this->text($r,'api_token')); }catch(\InvalidArgumentException $error) { throw new HttpException(422,$error->getMessage()); }$this->audit->append('operations','integration.packagist_saved',$user->id);return Response::redirect('/admin/integrations?saved=1',303);
    }
    public function publish(Request $r):HttpResponse
    {
        $this->throttle->consume('package-publish',$r->getClientIp()??'unknown');try { $result=$this->packagist->submit($this->text($r,'package'));$this->audit->append('operations','package.submitted',$r->attributes->get('_user')->id,['package'=>$result['name'],'state'=>$result['state']]);return Response::json($result,200,['Cache-Control'=>'private, no-store']); }catch(\RuntimeException|\InvalidArgumentException $e) { throw new HttpException(422,$e->getMessage()); }
    }
    public function publishMailDns(Request $r,MailDns $dns,\Symfony\Component\Lock\LockFactory $locks):HttpResponse
    {
        if(!$r->isSecure()) throw new HttpException(403,'Use HTTPS for integration credentials.');
        $user=$r->attributes->get('_user');$this->throttle->consume('mail-dns-publish',$r->getClientIp()??'unknown',(string)$user->id);
        $row=$this->users->rowByEmail($user->email);
        if(!$row||!$this->passwords->verify($this->text($r,'current_password'),$row['password'])) throw new HttpException(422,'The current account password is incorrect.');
        $lock=$locks->createLock('site-mail-dns-publication',60);
        if(!$lock->acquire()) throw new HttpException(409,'Mail DNS publication is already running. Try again shortly.');
        try { $result=$dns->publish($this->text($r,'fornex_api_key')); }catch(\RuntimeException|\InvalidArgumentException $error) { throw new HttpException(422,$error->getMessage()); }finally { $lock->release(); }
        $this->audit->append('operations','integration.mail_dns_submitted',$user->id,['created'=>$result['created'],'present'=>$result['present']]);
        return Response::redirect('/admin/integrations?mail_dns=submitted#mail-dns',303);
    }
}
