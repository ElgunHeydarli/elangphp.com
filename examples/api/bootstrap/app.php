<?php
declare(strict_types=1);
$app=Elang\Application::create(dirname(__DIR__));
$app->middleware(Elang\Http\Middleware\Cors::class);
$app->container->get(Elang\Realtime\Messages::class)->register('note.create',function(array $payload,Elang\Auth\User $user)use($app):array {
    $request=Symfony\Component\HttpFoundation\Request::create('/api/notes','POST',[],[],[],['CONTENT_TYPE'=>'application/json'],json_encode($payload,JSON_THROW_ON_ERROR));$request->attributes->set('_user',$user);$response=$app->container->get(App\Controllers\NotesApiController::class)->create($request);return json_decode($response->getContent(),true,512,JSON_THROW_ON_ERROR);
});
$app->provider(new Elang\Auth\AuthProvider());
$router=$app->router;
require dirname(__DIR__).'/routes/web.php';
return $app;
