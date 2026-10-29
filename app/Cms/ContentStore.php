<?php
declare(strict_types=1);
namespace App\Cms;
use Elang\Database\Database;
use Elang\Http\HttpException;
use Elang\Validation\{Validator,ValidationException};
use Symfony\Component\Validator\Constraints as Assert;
final class ContentStore
{
    public const SECTIONS=['docs/v0.1','learn','examples','guides','updates'];
    private ?bool $ready=null;
    private array $publishedCache=[];
    public function __construct(private Database $database,private Validator $validator) {}
    public function available(): bool { return $this->ready??=$this->database->connection()->createSchemaManager()->tablesExist(['cms_pages']); }
    public function published(string $section,string $locale='en'): array
    {
        if(!$this->available()) return [];
        return $this->publishedCache[$locale.'|'.$section] ??= $this->database->connection()->fetchAllAssociative('SELECT * FROM cms_pages WHERE locale = ? AND section = ? AND markdown IS NOT NULL ORDER BY published_at DESC, id DESC',[$locale,$section]);
    }
    public function publishedPage(string $section,string $slug,string $locale='en'): ?array { foreach($this->published($section,$locale) as $page) if($page['slug']===$slug) return $page;return null; }
    public function find(string $section,string $slug,string $locale='en'): ?array
    {
        if(!$this->available()) return null;
        return $this->database->connection()->fetchAssociative('SELECT * FROM cms_pages WHERE locale = ? AND section = ? AND slug = ?',[$locale,$section,$slug])?:null;
    }
    public function all(): array { return $this->available()?$this->database->connection()->fetchAllAssociative('SELECT id, locale, section, slug, title, draft_title, revision, published_at, modified_at FROM cms_pages ORDER BY modified_at DESC'):[]; }
    public function save(string $section,string $slug,array $input,int $expectedRevision,int $actorId,string $action,string $locale='en'): array
    {
        if(!in_array($locale,['en','az','ru'],true)) throw new ValidationException(['locale'=>['Choose English, Azerbaijani, or Russian.']]);
        if(!in_array($section,self::SECTIONS,true)||!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$slug)||strlen($slug)>120) throw new ValidationException(['page'=>['Choose a valid section and a lowercase hyphen-separated slug.']]);
        if(!in_array($action,['draft','publish','restore'],true)) throw new \InvalidArgumentException('Unsupported editorial action.');
        $valid=$this->validator->validate($input,[
            'title'=>[new Assert\NotBlank(),new Assert\Type('string'),new Assert\Length(max:160)],
            'description'=>[new Assert\NotBlank(),new Assert\Type('string'),new Assert\Length(min:40,max:200)],
            'markdown'=>[new Assert\NotBlank(),new Assert\Type('string'),new Assert\Length(max:180000)],
        ]);
        if(!str_starts_with(ltrim($valid['markdown']),'# ')) throw new ValidationException(['markdown'=>['Begin the article with a level-one heading: # Your title.']]);
        $html=(string)(new \League\CommonMark\CommonMarkConverter(['html_input'=>'strip','allow_unsafe_links'=>false]))->convert($valid['markdown']);
        $dom=new \DOMDocument();@$dom->loadHTML($html);
        if($dom->getElementsByTagName('h1')->length!==1) throw new ValidationException(['markdown'=>['Use exactly one primary heading. Use ## for sections.']]);
        $saved = $this->database->connection()->transactional(function($connection) use($section,$slug,$valid,$expectedRevision,$actorId,$action,$locale):array {
            $page=$this->find($section,$slug,$locale);$now=time();
            if($page&&(int)$page['revision']!==$expectedRevision||!$page&&$expectedRevision!==0) throw new HttpException(409,'This page changed in another session. Reload it before saving.');
            $revision=$expectedRevision+1;
            $data=['draft_title'=>$valid['title'],'draft_description'=>$valid['description'],'draft_markdown'=>$valid['markdown'],'revision'=>$revision,'modified_at'=>$now];
            if($action==='publish') $data+=['title'=>$valid['title'],'description'=>$valid['description'],'markdown'=>$valid['markdown'],'published_at'=>$page['published_at']??$now,'published_modified_at'=>$now];
            if($page) {
                $changed=$connection->update('cms_pages',$data,['id'=>$page['id'],'revision'=>$expectedRevision]);
                if($changed!==1) throw new HttpException(409,'A concurrent edit was detected. Reload before saving.');$id=(int)$page['id'];
            } else {
                $connection->insert('cms_pages',array_merge(['locale'=>$locale,'section'=>$section,'slug'=>$slug,'title'=>$valid['title'],'description'=>$valid['description'],'markdown'=>null,'published_at'=>null],$data));$id=(int)$connection->lastInsertId();
            }
            $connection->insert('cms_revisions',['page_id'=>$id,'revision'=>$revision,'title'=>$valid['title'],'description'=>$valid['description'],'markdown'=>$valid['markdown'],'action'=>$action,'actor_id'=>$actorId,'created_at'=>$now]);
            return $this->find($section,$slug,$locale)??throw new \RuntimeException('Saved page not found.');
        });
        $this->publishedCache=[];return $saved;
    }
    public function history(string $section,string $slug,string $locale='en'): array
    {
        $page=$this->find($section,$slug,$locale);return $page?$this->database->connection()->fetchAllAssociative('SELECT id, revision, title, action, actor_id, created_at FROM cms_revisions WHERE page_id = ? ORDER BY revision DESC',[$page['id']]):[];
    }
    public function restore(string $section,string $slug,int $revision,int $expected,int $actor,string $locale='en'): array
    {
        $page=$this->find($section,$slug,$locale);if(!$page) throw new HttpException(404,'Page not found.');
        $record=$this->database->connection()->fetchAssociative('SELECT * FROM cms_revisions WHERE page_id = ? AND revision = ?',[$page['id'],$revision]);
        if(!$record) throw new HttpException(404,'Revision not found.');
        return $this->save($section,$slug,['title'=>$record['title'],'description'=>$record['description'],'markdown'=>$record['markdown']],$expected,$actor,'restore',$locale);
    }
}
