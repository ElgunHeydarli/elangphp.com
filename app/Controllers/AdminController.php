<?php
declare(strict_types=1);
namespace App\Controllers;
use App\{ContentRepository,Cms\ContentStore};
use Elang\Auth\User;
use Elang\Http\{HttpException,Response};
use Elang\Security\Csrf;
use Elang\Validation\ValidationException;
use Elang\View\View;
use Symfony\Component\HttpFoundation\{Request,Response as HttpResponse};
final class AdminController
{
    public function __construct(private View $view,private ContentRepository $content,private ContentStore $store,private Csrf $csrf,private \App\I18n\Locale $locale) {}
    private function page(Request $request,string $mode,array $data=[]): HttpResponse
    {
        $response=$this->view->render('admin',array_merge(['languages'=>\App\I18n\Locale::LANGUAGES,'content_locale'=>$this->locale->current(),'mode'=>$mode,'token'=>$this->csrf->token($request),'user'=>$request->attributes->get('_user'),'pages'=>$this->store->all(),'catalog'=>$this->content->catalog(),'sections'=>ContentStore::SECTIONS,'record'=>null,'history'=>[],'errors'=>[],'message'=>'','preview'=>''],$data));
        $response->headers->set('Cache-Control','private, no-store');$response->headers->set('X-Robots-Tag','noindex, nofollow');return $response;
    }
    public function index(Request $request): HttpResponse { return $this->page($request,'index'); }
    public function edit(Request $request): HttpResponse
    {
        $input=$request->query->all();$section=$input['section']??'docs/v0.1';$slug=$input['slug']??'';$locale=$input['locale']??$this->locale->current();
        if(!is_string($locale)||!isset(\App\I18n\Locale::LANGUAGES[$locale])) throw new HttpException(400,'Unsupported content language.');
        if(!is_string($section)||!in_array($section,ContentStore::SECTIONS,true)||!is_string($slug)||$slug!==''&&!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$slug)) throw new HttpException(400,'Invalid page selection.');
        $record=$this->store->find($section,$slug,$locale);
        if(!$record&&$slug!=='') {
            try { $document=$this->content->document($section,$slug,$locale);$record=['locale'=>$locale,'section'=>$section,'slug'=>$slug,'draft_title'=>$document['title'],'draft_description'=>$document['description'],'draft_markdown'=>$document['markdown'],'revision'=>0]; }
            catch(HttpException $error) { if($error->status!==404) throw $error; }
        }
        $record??=['locale'=>$locale,'section'=>$section,'slug'=>$slug,'draft_title'=>'','draft_description'=>'','draft_markdown'=>"# Your article title\n\nStart with a clear explanation.\n",'revision'=>0];
        return $this->page($request,'edit',['record'=>$record,'history'=>$slug!==''?$this->store->history($section,$slug,$locale):[]]);
    }
    public function save(Request $request): HttpResponse
    {
        $data=$request->request->all();
        foreach(['locale','section','slug','title','description','markdown','action','revision'] as $field) if(!isset($data[$field])||!is_string($data[$field])) throw new HttpException(400,'Invalid editorial form.');
        $user=$request->attributes->get('_user');if(!$user instanceof User) throw new HttpException(403,'Administrator access is required.');
        $record=['locale'=>$data['locale'],'section'=>$data['section'],'slug'=>$data['slug'],'draft_title'=>$data['title'],'draft_description'=>$data['description'],'draft_markdown'=>$data['markdown'],'revision'=>(int)$data['revision']];
        try {
            if($data['action']==='preview') return $this->page($request,'edit',['record'=>$record,'preview'=>$this->content->render($data['markdown']),'message'=>'Preview only. This content has not been published.']);
            $saved=$this->store->save($data['section'],$data['slug'],['title'=>$data['title'],'description'=>$data['description'],'markdown'=>$data['markdown']],(int)$data['revision'],$user->id,$data['action'],$data['locale']);
            return Response::redirect('/admin/edit?'.http_build_query(['section'=>$saved['section'],'slug'=>$saved['slug'],'locale'=>$saved['locale']]),303);
        } catch(ValidationException $error) { return $this->page($request,'edit',['record'=>$record,'errors'=>$error->errors]); }
    }
    public function restore(Request $request): HttpResponse
    {
        $data=$request->request->all();$user=$request->attributes->get('_user');
        if(!$user instanceof User||!isset($data['locale'],$data['section'],$data['slug'],$data['revision'],$data['expected'])||!is_string($data['locale'])||!is_string($data['section'])||!is_string($data['slug'])||!is_scalar($data['revision'])||!is_scalar($data['expected'])) throw new HttpException(400,'Invalid restore request.');
        $this->store->restore($data['section'],$data['slug'],(int)$data['revision'],(int)$data['expected'],$user->id,$data['locale']);
        return Response::redirect('/admin/edit?'.http_build_query(['section'=>$data['section'],'slug'=>$data['slug'],'locale'=>$data['locale']]),303);
    }
}
