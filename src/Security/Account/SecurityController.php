<?php
declare(strict_types=1);
namespace Elang\Security\Account;
use Elang\{Auth\Auth,Auth\Users,Auth\User,Auth\Throttle,Audit\Audit,Http\HttpException,Http\Response,Security\Password,Security\Csrf,Support\Config,View\View,Validation\ValidationException};
use Symfony\Component\HttpFoundation\{Request,Response as HttpResponse};
final class SecurityController
{
    public function __construct(private Auth $auth,private Users $users,private Mfa $mfa,private Passkeys $passkeys,private Devices $devices,private Audit $audit,private Password $passwords,private Csrf $csrf,private View $view,private Config $config,private \Elang\Database\Database $database,private Throttle $throttle) {}
    private function input(Request $request,string $name):string { $value=$request->request->all()[$name]??'';if(!is_string($value)) throw new HttpException(422,'Enter a text value.');return $value; }
    private function user(Request $request):User { return $this->auth->user($request)??throw new HttpException(401,'Sign in first.'); }
    private function password(Request $request,User $user):void
    {
        $this->throttle->consume('security-password',$request->getClientIp()??'unknown',(string)$user->id);$row=$this->users->rowByEmail($user->email);if(!$row||!$this->passwords->verify($this->input($request,'current_password'),$row['password'])) throw new ValidationException(['current_password'=>['The current password is incorrect.']]);
    }
    private function page(Request $request,array $data=[]):HttpResponse
    {
        $user=$this->user($request);$response=$this->view->render('@elang/security',array_merge(['user'=>$user,'token'=>$this->csrf->token($request),'mfa_enabled'=>$this->mfa->enabled($user),'pending_setup'=>$this->mfa->pending($request,$user),'recovery_codes'=>[],'devices'=>$this->devices->list($user,$request),'passkeys'=>$this->passkeys->list($user),'errors'=>[],'message'=>''],$data));$response->headers->set('Cache-Control','private, no-store');$response->headers->set('X-Robots-Tag','noindex, nofollow');return $response;
    }
    public function index(Request $request):HttpResponse { return $this->page($request); }
    public function start(Request $request):HttpResponse
    {
        try { $user=$this->user($request);$this->password($request,$user);if(!$user->verified) throw new HttpException(403,'Verify your email address first.');$this->mfa->begin($request,$user);return $this->page($request); }catch(ValidationException $error) { return $this->page($request,['errors'=>$error->errors]); }
    }
    public function confirm(Request $request):HttpResponse
    {
        $user=$this->user($request);$this->throttle->consume('mfa-confirm',$request->getClientIp()??'unknown',(string)$user->id);
        $result=$this->mutation($user,function()use($request,$user):?array { $codes=$this->mfa->enable($request,$user,$this->input($request,'code'));if($codes===null) return null;$updated=$this->users->revokeCredentials($user);$this->audit->append('user:'.$user->id,'security.mfa_enabled',$user->id);return ['user'=>$updated,'codes'=>$codes]; });
        if($result===null) return $this->page($request,['errors'=>['code'=>['The code is invalid or the setup expired.']]]);
        $this->auth->login($request,$result['user']);return $this->page($request,['recovery_codes'=>$result['codes'],'message'=>'Two-step verification is enabled. Save your recovery codes privately.']);
    }
    public function qr(Request $request):HttpResponse
    {
        $pending=$this->mfa->pending($request,$this->user($request));if(!$pending) throw new HttpException(404,'Setup not found.');$writer=new \BaconQrCode\Writer(new \BaconQrCode\Renderer\ImageRenderer(new \BaconQrCode\Renderer\RendererStyle\RendererStyle(240),new \BaconQrCode\Renderer\Image\SvgImageBackEnd()));return new HttpResponse($writer->writeString($pending['uri']),200,['Content-Type'=>'image/svg+xml','Cache-Control'=>'private, no-store','X-Robots-Tag'=>'noindex']);
    }
    public function disable(Request $request):HttpResponse
    {
        $user=$this->user($request);$updated=$this->mutation($user,function()use($request,$user):?User { $this->password($request,$user);if(!$this->mfa->verify($user,$this->input($request,'code'))) return null;$this->mfa->disable($user);$updated=$this->users->revokeCredentials($user);$this->audit->append('user:'.$user->id,'security.mfa_disabled',$user->id);return $updated; });
        if(!$updated) return $this->page($request,['errors'=>['code'=>['Enter a new authenticator code or an unused recovery code.']]]);$this->auth->login($request,$updated);return $this->page($request,['message'=>'Two-step verification is disabled.']);
    }
    public function recovery(Request $request):HttpResponse
    {
        $user=$this->user($request);$codes=$this->mutation($user,function()use($request,$user):?array { $this->password($request,$user);if(!$this->mfa->verify($user,$this->input($request,'code'))) return null;$codes=$this->mfa->generateRecovery($user);$this->audit->append('user:'.$user->id,'security.recovery_replaced',$user->id);return $codes; });if(!$codes) return $this->page($request,['errors'=>['code'=>['Enter a new authenticator code or an unused recovery code.']]]);return $this->page($request,['recovery_codes'=>$codes,'message'=>'New recovery codes replace every previous code.']);
    }
    public function revoke(Request $request,string $id):HttpResponse
    {
        $user=$this->user($request);if(!$this->devices->revoke($user,$id)) throw new HttpException(404,'Device session not found.');$this->audit->append('user:'.$user->id,'security.device_revoked',$user->id);return Response::redirect('/security',303);
    }
    public function registrationOptions(Request $request):HttpResponse
    {
        $user=$this->user($request);$this->password($request,$user);return $this->privateJson(['options'=>$this->passkeys->registrationOptions($request,$user,$this->input($request,'name'))]);
    }
    public function register(Request $request):HttpResponse
    {
        $user=$this->user($request);$value=$request->request->all()['credential']??null;if(!is_array($value)) throw new HttpException(422,'Supply a passkey credential.');$this->passkeys->register($request,$user,$value);$this->audit->append('user:'.$user->id,'security.passkey_registered',$user->id);return $this->privateJson(['registered'=>true]);
    }
    public function remove(Request $request,string $id):HttpResponse
    {
        $user=$this->user($request);$this->password($request,$user);if(!$this->passkeys->remove($user,$id)) throw new HttpException(404,'Passkey not found.');$this->audit->append('user:'.$user->id,'security.passkey_removed',$user->id);return Response::redirect('/security',303);
    }
    public function loginOptions(Request $request,Throttle $throttle):HttpResponse { $throttle->consume('passkey-options',$request->getClientIp()??'unknown');return $this->privateJson(['options'=>$this->passkeys->authenticationOptions($request)]); }
    public function passkeyLogin(Request $request,Throttle $throttle):HttpResponse
    {
        $throttle->consume('passkey-login',$request->getClientIp()??'unknown');$value=$request->request->all()['credential']??null;if(!is_array($value)) throw new HttpException(422,'Supply a passkey credential.');$user=$this->passkeys->authenticate($request,$value);if(!$user) throw new HttpException(401,'Passkey sign-in was not completed.');$this->auth->login($request,$user);$this->audit->append('user:'.$user->id,'auth.passkey_login',$user->id);return $this->privateJson(['redirect'=>$this->config->get('auth.home','/dashboard')]);
    }
    private function mutation(User $user,callable $action):mixed
    {
        return $this->database->transaction(function($db)use($user,$action):mixed { \Elang\Database\Atomic::lockRow($db,'auth_users','id',$user->id);$fresh=$this->users->find($user->id);if(!$fresh||$fresh->authVersion!==$user->authVersion) throw new HttpException(401,'Sign in again before changing account security.');return $action(); });
    }
    private function privateJson(array $data):HttpResponse { return Response::json($data,200,['Cache-Control'=>'private, no-store','X-Robots-Tag'=>'noindex']); }
    public function factorForm(Request $request):HttpResponse
    {
        if(!$this->auth->pending($request)) return Response::redirect('/login');$response=$this->view->render('@elang/auth',['mode'=>'two-factor','errors'=>[],'message'=>'','token'=>$this->csrf->token($request),'user'=>null,'registration'=>false,'local'=>false,'mail_ready'=>false,'api_tokens'=>[],'allowed_scopes'=>[],'new_token'=>null,'reset_token'=>'']);$response->headers->set('Cache-Control','private, no-store');$response->headers->set('X-Robots-Tag','noindex');return $response;
    }
    public function factor(Request $request):HttpResponse
    {
        if(!$this->auth->secondFactor($request,$this->input($request,'code'))) throw new HttpException(401,'The verification code is invalid, expired, or already used.');return Response::redirect($this->config->get('auth.home','/dashboard'),303);
    }
}
