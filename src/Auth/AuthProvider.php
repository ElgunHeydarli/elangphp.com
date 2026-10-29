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
        $app->router->group('',[RateLimitMiddleware::class,SessionMiddleware::class,CsrfMiddleware::class],function($router) use($app):void {
            $controller=AuthController::class;
            $router->get('/login',[$controller,'loginForm'],'auth.login');$router->post('/login',[$controller,'login']);
            if($app->config->get('auth.registration',true)) { $router->get('/register',[$controller,'registerForm'],'auth.register');$router->post('/register',[$controller,'register']); }
            $router->get('/forgot-password',[$controller,'forgotForm'],'auth.forgot');$router->post('/forgot-password',[$controller,'forgot']);
            $router->get('/reset-password/{token}',[$controller,'resetForm'])->where('token','[a-f0-9]{64}');$router->post('/reset-password',[$controller,'reset']);
            $router->get('/verify/{token}',[$controller,'verify'])->where('token','[a-f0-9]{64}');
            $router->group('',[RequireAuth::class],function($router) use($controller):void {
                $router->get('/dashboard',[$controller,'dashboard'],'auth.dashboard');$router->post('/logout',[$controller,'logout'],'auth.logout');
                $router->get('/password',[$controller,'passwordForm'],'auth.password');$router->post('/password',[$controller,'password']);
                $router->post('/profile',[$controller,'profile']);$router->post('/verify-email',[$controller,'resend']);
                $router->post('/tokens',[$controller,'issueToken']);$router->post('/tokens/{id}/revoke',[$controller,'revokeToken'])->where('id','[0-9]+');
            });
        });
    }
}
