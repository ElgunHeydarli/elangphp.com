<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Models\Note;
use Elang\Auth\Auth;
use Elang\Data\{Models,PageRequest};
use Elang\Http\{Response,HttpException};
use Elang\Security\Csrf;
use Elang\Validation\{Validator,ValidationException};
use Elang\View\View;
use Symfony\Component\HttpFoundation\{Request,Response as HttpResponse};
use Symfony\Component\Validator\Constraints as Assert;
final class WorkspaceController
{
    public function __construct(private Models $models,private Auth $auth,private View $view,private Csrf $csrf,private Validator $validator) {}
    public function index(Request $request,array $errors=[]): HttpResponse
    {
        $user=$this->auth->user($request)??throw new HttpException(401,'Sign in first.');$repository=$this->models->of(Note::class)->where('owner_id',$user->id);
        $response=$this->view->render('workspace',['title'=>'Your workspace','user'=>$user,'token'=>$this->csrf->token($request),'errors'=>$errors,'notes'=>$repository->orderBy('id','DESC')->paginate(10,PageRequest::number($request->query->all()['page']??1)),'total'=>$repository->count(),'completed'=>$repository->where('completed',1)->count()]);$response->headers->set('Cache-Control','private, no-store');return $response;
    }
    public function create(Request $request): HttpResponse
    {
        $user=$this->auth->user($request)??throw new HttpException(401,'Sign in first.');
        try { $data=$this->validator->validate($request->request->all(),['title'=>[new Assert\NotBlank(),new Assert\Type('string'),new Assert\Length(max:160)],'body'=>[new Assert\Type('string'),new Assert\Length(max:5000)]]);$data['body']??='';$this->models->of(Note::class)->create($data+['owner_id'=>$user->id,'completed'=>0,'created_at'=>time()]);return Response::redirect('/workspace',303); }
        catch(ValidationException $error) { return $this->index($request,$error->errors); }
    }
    public function toggle(Request $request,int $id): HttpResponse
    {
        $user=$this->auth->user($request)??throw new HttpException(401,'Sign in first.');$repository=$this->models->of(Note::class)->where('owner_id',$user->id);$note=$repository->find($id)??throw new HttpException(404,'Note not found.');$repository->update($id,['completed'=>$note->completed?0:1]);return Response::redirect('/workspace',303);
    }
    public function delete(Request $request,int $id): HttpResponse
    {
        $user=$this->auth->user($request)??throw new HttpException(401,'Sign in first.');if(!$this->models->of(Note::class)->where('owner_id',$user->id)->delete($id)) throw new HttpException(404,'Note not found.');return Response::redirect('/workspace',303);
    }
}
