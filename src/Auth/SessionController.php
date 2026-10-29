<?php
declare(strict_types=1);
namespace Elang\Auth;
use Elang\{Http\Response,Http\HttpException,Security\Csrf,Security\Account\Passkeys,Support\Config,Validation\ValidationException};
use Symfony\Component\HttpFoundation\{Request,Response as HttpResponse};
final class SessionController
{
    public function __construct(private Auth $auth,private Users $users,private Csrf $csrf,private Config $config,private Recovery $recovery,private Notifications $notifications,private Throttle $throttle,private Passkeys $passkeys) {}
    private function input(Request $request,string $name):string { $value=$request->request->all()[$name]??'';if(!is_string($value)) throw new ValidationException([$name=>['Enter a text value.']]);return $value; }
    public function show(Request $request,int $status=200):HttpResponse
    {
        return Response::json(['user'=>$this->auth->user($request),'csrfToken'=>$this->csrf->token($request),'registration'=>$this->config->get('auth.registration',true),'mailAvailable'=>$this->notifications->available(),'mfaRequired'=>$this->auth->pending($request)!==null],$status,['Cache-Control'=>'private, no-store','X-Robots-Tag'=>'noindex']);
    }
    public function login(Request $request):HttpResponse
    {
        if(!$this->auth->attempt($request,$this->input($request,'email'),$this->input($request,'password'))) throw new HttpException(401,'The email address or password is incorrect.');return $this->show($request,$this->auth->pending($request)?202:200);
    }
    public function register(Request $request):HttpResponse
    {
        if(!$this->config->get('auth.registration',true)) throw new HttpException(404,'Registration is not available.');$this->throttle->consume('register',$request->getClientIp()??'unknown');$password=$this->input($request,'password');if($password!==$this->input($request,'password_confirmation')) throw new ValidationException(['password_confirmation'=>['The passwords do not match.']]);
        $user=$this->users->register($this->input($request,'name'),$this->input($request,'email'),$password);$this->auth->login($request,$user);if($this->notifications->available()) $this->recovery->sendVerification($user,$request->getClientIp()??'unknown');return $this->show($request,201);
    }
    public function factor(Request $request):HttpResponse { if(!$this->auth->secondFactor($request,$this->input($request,'code'))) throw new HttpException(401,'The verification code is invalid, expired, or already used.');return $this->show($request); }
    public function logout(Request $request):HttpResponse { $this->auth->logout($request);return $this->show($request); }
    public function passkeyOptions(Request $request):HttpResponse { $this->throttle->consume('passkey-options',$request->getClientIp()??'unknown');return Response::json(['options'=>$this->passkeys->authenticationOptions($request)],200,['Cache-Control'=>'private, no-store']); }
    public function passkeyLogin(Request $request):HttpResponse
    {
        $this->throttle->consume('passkey-login',$request->getClientIp()??'unknown');$credential=$request->request->all()['credential']??null;if(!is_array($credential)) throw new HttpException(422,'Supply a passkey credential.');$user=$this->passkeys->authenticate($request,$credential);if(!$user) throw new HttpException(401,'Passkey sign-in was not completed.');$this->auth->login($request,$user);return $this->show($request);
    }
}
