<?php
declare(strict_types=1);
namespace Elang\Auth;
use Elang\{Application,Container\Container};
use Elang\Contracts\ServiceProvider;
use Elang\Http\Middleware\{SessionMiddleware,CsrfMiddleware,RateLimitMiddleware};
final class AuthProvider implements ServiceProvider
{
    public function register(Container $container): void {}
    public function boot(Application $app): void
    {
        $app->router->group('',[RateLimitMiddleware::class,SessionMiddleware::class,\Elang\Http\Middleware\JsonInput::class,CsrfMiddleware::class],function($router) use($app):void {
            $controller=AuthController::class;$security=\Elang\Security\Account\SecurityController::class;
            $router->get('/two-factor',[$security,'factorForm']);$router->post('/two-factor',[$security,'factor']);$router->post('/passkeys/options',[$security,'loginOptions']);$router->post('/passkeys/login',[$security,'passkeyLogin']);
            $router->get('/login',[$controller,'loginForm'],'auth.login');$router->post('/login',[$controller,'login']);
            if($app->config->get('auth.registration',true)) { $router->get('/register',[$controller,'registerForm'],'auth.register');$router->post('/register',[$controller,'register']); }
            $router->get('/forgot-password',[$controller,'forgotForm'],'auth.forgot');$router->post('/forgot-password',[$controller,'forgot']);
            $router->get('/reset-password/{token}',[$controller,'resetForm'])->where('token','[a-f0-9]{64}');$router->post('/reset-password',[$controller,'reset']);
            $router->get('/verify/{token}',[$controller,'verify'])->where('token','[a-f0-9]{64}');
            $router->group('',[RequireAuth::class],function($router) use($controller,$security):void {
                $router->get('/security',[$security,'index']);$router->post('/security/totp/start',[$security,'start']);$router->post('/security/totp/confirm',[$security,'confirm']);$router->get('/security/totp/qr',[$security,'qr']);$router->post('/security/totp/disable',[$security,'disable']);$router->post('/security/recovery',[$security,'recovery']);$router->post('/security/devices/{id}/revoke',[$security,'revoke'])->where('id','[a-f0-9]{64}');$router->post('/security/passkeys/options',[$security,'registrationOptions']);$router->post('/security/passkeys/register',[$security,'register']);$router->post('/security/passkeys/{id}/delete',[$security,'remove'])->where('id','[a-f0-9]{64}');
                $router->get('/dashboard',[$controller,'dashboard'],'auth.dashboard');$router->post('/logout',[$controller,'logout'],'auth.logout');
                $router->get('/password',[$controller,'passwordForm'],'auth.password');$router->post('/password',[$controller,'password']);
                $router->post('/profile',[$controller,'profile']);$router->post('/verify-email',[$controller,'resend']);
                $router->post('/tokens',[$controller,'issueToken']);$router->post('/tokens/{id}/revoke',[$controller,'revokeToken'])->where('id','[0-9]+');
            });
        });
        $app->router->group('/api/session',[RateLimitMiddleware::class,SessionMiddleware::class,\Elang\Http\Middleware\JsonInput::class,CsrfMiddleware::class],function($router):void {
            $controller=SessionController::class;$router->get('',[$controller,'show']);$router->post('/login',[$controller,'login']);$router->post('/register',[$controller,'register']);$router->post('/mfa',[$controller,'factor']);$router->post('/logout',[$controller,'logout']);$router->post('/passkeys/options',[$controller,'passkeyOptions']);$router->post('/passkeys/login',[$controller,'passkeyLogin']);
        });
    }
}
