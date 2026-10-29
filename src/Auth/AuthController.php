<?php
declare(strict_types=1);
namespace Elang\Auth;
use Elang\Http\{HttpException,Response};
use Elang\Security\{Csrf,Password};
use Elang\Support\Config;
use Elang\Validation\ValidationException;
use Elang\View\View;
use Symfony\Component\HttpFoundation\{Request,Response as HttpResponse};
final class AuthController
{
    public function __construct(private Auth $auth,private Users $users,private Recovery $recovery,private Notifications $notifications,private ApiTokens $tokens,private Password $passwords,private Csrf $csrf,private Config $config,private View $view) {}
    private function input(Request $request,string $name): string
    {
        $value=$request->request->all()[$name]??'';
        if(!is_string($value)) throw new ValidationException([$name=>['Enter a text value.']]);
        return $value;
    }
    private function page(Request $request,string $mode,array $errors=[],string $message='',int $status=200,array $extra=[]): HttpResponse
    {
        $user=$this->auth->user($request);
        $response=$this->view->render($this->config->get('auth.template','@elang/auth'),array_merge([
            'mode'=>$mode,'errors'=>$errors,'message'=>$message,'token'=>$this->csrf->token($request),'user'=>$user,
            'registration'=>$this->config->get('auth.registration',true),'local'=>$this->config->get('environment')==='local',
            'mail_ready'=>$this->notifications->available(),'api_tokens'=>$user?$this->tokens->list($user):[],
            'allowed_scopes'=>$this->config->get('auth.token_scopes',['notes:read','notes:write']),
            'new_token'=>null,'reset_token'=>'','app_name'=>$this->config->get('name','Elang'),
        ],$extra),$status);
        $response->headers->set('Cache-Control','private, no-store');$response->headers->set('X-Robots-Tag','noindex, follow');return $response;
    }
    private function destination(Request $request): HttpResponse
    {
        if($this->auth->pending($request)) return Response::redirect('/two-factor',303);
        $user=$this->auth->user($request);
        if($user?->mustChangePassword) return Response::redirect('/password',303);
        $path=$request->getSession()->remove('_auth_intended')??$this->config->get('auth.home','/dashboard');
        try { return Response::redirect($path,303); } catch(\InvalidArgumentException) { return Response::redirect('/dashboard',303); }
    }
    public function loginForm(Request $request): HttpResponse { return $this->auth->user($request)?$this->destination($request):$this->page($request,'login'); }
    public function login(Request $request): HttpResponse
    {
        try {
            if(!$this->auth->attempt($request,$this->input($request,'email'),$this->input($request,'password'))) return $this->page($request,'login',['credentials'=>['The email address or password is incorrect.']],'',401);
            return $this->destination($request);
        } catch(ValidationException $error) { return $this->page($request,'login',$error->errors,'',422); }
    }
    public function registerForm(Request $request): HttpResponse { return $this->page($request,'register'); }
    public function register(Request $request,Throttle $throttle): HttpResponse
    {
        try {
            $throttle->consume('register',$request->getClientIp()??'unknown');
            $password=$this->input($request,'password');
            if($password!==$this->input($request,'password_confirmation')) throw new ValidationException(['password_confirmation'=>['The passwords do not match.']]);
            $user=$this->users->register($this->input($request,'name'),$this->input($request,'email'),$password);
            $this->auth->login($request,$user);
            if($this->notifications->available()) $this->recovery->sendVerification($user,$request->getClientIp()??'unknown');
            return $this->destination($request);
        } catch(ValidationException $error) { return $this->page($request,'register',$error->errors,'',422); }
    }
    public function dashboard(Request $request): HttpResponse { return $this->page($request,'dashboard'); }
    public function logout(Request $request): HttpResponse { $this->auth->logout($request);return Response::redirect('/login',303); }
    public function forgotForm(Request $request): HttpResponse { return $this->page($request,'forgot'); }
    public function forgot(Request $request): HttpResponse
    {
        try { $this->recovery->requestReset($this->input($request,'email'),$request->getClientIp()??'unknown');return $this->page($request,'forgot',[],'If an account matches that address, a recovery message has been prepared.'); }
        catch(ValidationException $error) { return $this->page($request,'forgot',$error->errors,'',422); }
    }
    public function resetForm(Request $request,string $token): HttpResponse
    {
        if(!preg_match('/^[a-f0-9]{64}$/D',$token)) throw new HttpException(400,'This reset link is invalid.');
        return $this->page($request,'reset',[],'',200,['reset_token'=>$token]);
    }
    public function reset(Request $request): HttpResponse
    {
        $token=$this->input($request,'token');
        try {
            $password=$this->input($request,'password');
            if($password!==$this->input($request,'password_confirmation')) throw new ValidationException(['password_confirmation'=>['The passwords do not match.']]);
            if(!$this->recovery->reset($token,$password)) return $this->page($request,'forgot',['link'=>['That link is invalid, expired, or already used. Request a new one.']],'',422);
            $this->auth->logout($request);return $this->page($request,'login',[],'Your password was updated. Sign in with the new password.');
        } catch(ValidationException $error) { return $this->page($request,'reset',$error->errors,'',422,['reset_token'=>$token]); }
    }
    public function verify(Request $request,string $token): HttpResponse
    {
        return $this->recovery->verify($token)?$this->page($request,'message',[],'Your email address is verified. You can continue to your dashboard.'):$this->page($request,'message',['link'=>['That verification link is invalid, expired, or already used.']],'',422);
    }
    public function resend(Request $request): HttpResponse
    {
        $user=$this->auth->user($request)??throw new HttpException(401,'Sign in first.');
        $this->recovery->sendVerification($user,$request->getClientIp()??'unknown');return $this->page($request,'dashboard',[],'A verification message has been prepared.');
    }
    public function passwordForm(Request $request): HttpResponse { return $this->page($request,'password'); }
    public function password(Request $request): HttpResponse
    {
        try {
            $user=$this->auth->user($request)??throw new HttpException(401,'Sign in first.');
            $row=$this->users->rowByEmail($user->email);
            if(!$row||!$this->passwords->verify($this->input($request,'current_password'),$row['password'])) throw new ValidationException(['current_password'=>['The current password is incorrect.']]);
            $password=$this->input($request,'password');
            if($password!==$this->input($request,'password_confirmation')) throw new ValidationException(['password_confirmation'=>['The passwords do not match.']]);
            $updated=$this->users->changePassword($user,$password);$this->auth->login($request,$updated);return $this->destination($request);
        } catch(ValidationException $error) { return $this->page($request,'password',$error->errors,'',422); }
    }
    public function profile(Request $request): HttpResponse
    {
        try { $user=$this->auth->user($request)??throw new HttpException(401,'Sign in first.');$this->users->updateProfile($user,$this->input($request,'name'));return $this->page($request,'dashboard',[],'Your profile was updated.'); }
        catch(ValidationException $error) { return $this->page($request,'dashboard',$error->errors,'',422); }
    }
    public function issueToken(Request $request): HttpResponse
    {
        try {
            $user=$this->auth->user($request)??throw new HttpException(401,'Sign in first.');$row=$this->users->rowByEmail($user->email);
            if(!$row||!$this->passwords->verify($this->input($request,'current_password'),$row['password'])) throw new ValidationException(['current_password'=>['Confirm your current password before creating a token.']]);
            $scopes=$request->request->all()['scopes']??[];
            if(!is_array($scopes)) throw new ValidationException(['scopes'=>['Choose the required scopes.']]);
            $token=$this->tokens->issue($user,$this->input($request,'name'),$scopes);
            return $this->page($request,'dashboard',[],'Copy this token now. It is displayed once.',200,['new_token'=>$token]);
        } catch(ValidationException $error) { return $this->page($request,'dashboard',$error->errors,'',422); }
        catch(\InvalidArgumentException $error) { return $this->page($request,'dashboard',['token'=>[$error->getMessage()]],'',422); }
    }
    public function revokeToken(Request $request,int $id): HttpResponse
    {
        $user=$this->auth->user($request)??throw new HttpException(401,'Sign in first.');
        if(!$this->tokens->revoke($user,$id)) throw new HttpException(404,'Token not found.');
        return Response::redirect('/dashboard',303);
    }
}
