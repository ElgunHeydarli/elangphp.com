<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Models\Post;
use Elang\Auth\Auth;
use Elang\Data\{Models,PageRequest};
use Elang\Http\{Response,HttpException};
use Elang\Security\Csrf;
use Elang\Validation\{Validator,ValidationException};
use Elang\View\View;
use Symfony\Component\HttpFoundation\{Request,Response as HttpResponse};
use Symfony\Component\Validator\Constraints as Assert;
final class BlogController
{
    public function __construct(private Models $models,private Auth $auth,private View $view,private Csrf $csrf,private Validator $validator) {}
    private function page(Request $request,string $mode,array $data=[]): HttpResponse
    {
        $user=$this->auth->user($request);
        return $this->view->render('blog',array_merge(['title'=>'A small publication','mode'=>$mode,'user'=>$user,'token'=>$user?$this->csrf->token($request):'','errors'=>[],'post'=>null,'posts'=>null],$data));
    }
    public function index(Request $request): HttpResponse
    {
        return $this->page($request,'index',['posts'=>$this->models->of(Post::class)->where('published',1)->orderBy('id','DESC')->paginate(10,PageRequest::number($request->query->all()['page']??1)),'canonical_path'=>'/']);
    }
    public function show(Request $request,int $id): HttpResponse
    {
        $post=$this->models->of(Post::class)->where('published',1)->find($id)??throw new HttpException(404,'Article not found.');
        return $this->page($request,'show',['title'=>$post->title,'post'=>$post,'canonical_path'=>'/posts/'.$id]);
    }
    public function create(Request $request): HttpResponse { return $this->page($request,'edit',['title'=>'Write an article']); }
    public function edit(Request $request,int $id): HttpResponse
    {
        $user=$this->auth->user($request)??throw new HttpException(401,'Sign in first.');
        $post=$this->models->of(Post::class)->where('author_id',$user->id)->find($id)??throw new HttpException(404,'Article not found.');
        return $this->page($request,'edit',['title'=>'Edit article','post'=>$post]);
    }
    public function save(Request $request,?int $id=null): HttpResponse
    {
        $user=$this->auth->user($request)??throw new HttpException(401,'Sign in first.');$repository=$this->models->of(Post::class);
        if($id!==null&&!$repository->where('author_id',$user->id)->find($id)) throw new HttpException(404,'Article not found.');
        try {
            $data=$this->validator->validate($request->request->all(),['title'=>[new Assert\NotBlank(),new Assert\Type('string'),new Assert\Length(max:160)],'body'=>[new Assert\NotBlank(),new Assert\Type('string'),new Assert\Length(max:20000)]]);
            $data['published']=$request->request->get('published')==='1'?1:0;
            $post=$id===null?$repository->create($data+['author_id'=>$user->id,'created_at'=>time()]):$repository->where('author_id',$user->id)->update($id,$data);
            return Response::redirect('/posts/'.($post??throw new HttpException(409,'The article changed during this request.'))->id.'/edit',303);
        }catch(ValidationException $error) { return $this->page($request,'edit',['title'=>'Check your article','errors'=>$error->errors,'post'=>new Post(['id'=>$id,'title'=>is_string($request->request->all()['title']??null)?$request->request->all()['title']:'','body'=>is_string($request->request->all()['body']??null)?$request->request->all()['body']:'','published'=>false])]); }
    }
    public function delete(Request $request,int $id): HttpResponse
    {
        $user=$this->auth->user($request)??throw new HttpException(401,'Sign in first.');
        if(!$this->models->of(Post::class)->where('author_id',$user->id)->delete($id)) throw new HttpException(404,'Article not found.');
        return Response::redirect('/',303);
    }
}
