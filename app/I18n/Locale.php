<?php
declare(strict_types=1);
namespace App\I18n;
use Elang\Application;
final class Locale
{
    public const LANGUAGES=['en'=>'English','az'=>'Azərbaycan dili','ru'=>'Русский'];
    private string $locale='en';
    private array $catalogs=[];
    public function __construct(private Application $app) {}
    public function set(string $locale): void { if(!isset(self::LANGUAGES[$locale])) throw new \InvalidArgumentException('Unsupported language.');$this->locale=$locale; }
    public function current(): string { return $this->locale; }
    public function text(string $text): string
    {
        if($this->locale==='en') return $text;
        if(!isset($this->catalogs[$this->locale])) {
            $file=$this->app->basePath.'/resources/lang/'.$this->locale.'.json';
            $this->catalogs[$this->locale]=is_file($file)?json_decode(file_get_contents($file),true,512,JSON_THROW_ON_ERROR):[];
        }
        $key=trim(preg_replace('/\s+/u',' ',$text));$catalog=$this->catalogs[$this->locale];
        if(isset($catalog[$key])) return $catalog[$key];
        if(preg_match('/^~ (\d+) min read$/',$key,$match)) return $this->locale==='az'?'~ '.$match[1].' dəq oxu':'~ '.$match[1].' мин чтения';
        if(preg_match('/^Updated ([0-9-]+)$/',$key,$match)) return ($this->locale==='az'?'Yenilənib: ':'Обновлено: ').$match[1];
        if(preg_match('/^Hello, (.+)\. Manage your profile and application access\.$/us',$key,$match)&&isset($catalog['Hello, {name}. Manage your profile and application access.'])) return str_replace('{name}',$match[1],$catalog['Hello, {name}. Manage your profile and application access.']);
        if(preg_match('/^Expires ([0-9-]+)$/',$key,$match)&&isset($catalog['Expires {date}'])) return str_replace('{date}',$match[1],$catalog['Expires {date}']);
        if(str_ends_with($key,' — Elang')) return $this->text(substr($key,0,-strlen(' — Elang'))).' — Elang';
        foreach([' — Elang PHP Documentation',' — Learn Elang',' — Elang examples',' — Elang PHP Guides',' — Elang updates'] as $suffix) if(str_ends_with($text,$suffix)&&isset($catalog[trim($suffix)])) return substr($text,0,-strlen($suffix)).' '.$catalog[trim($suffix)];
        return $text;
    }
    public function logical(string $path): string
    {
        if(preg_match('~^/(az|ru)(?=/|$)~',$path,$match)) return substr($path,strlen($match[0]))?:'/';return $path;
    }
    public function path(string $path,?string $locale=null): string
    {
        $locale??=$this->locale;
        if(!str_starts_with($path,'/')||str_starts_with($path,'//')||preg_match('~^/(assets|api|packages|_elang|mcp|\.well-known)(/|$)|^/(sitemap\.xml|llms\.txt)(\?|$)~',$path)) return $path;
        $path=$this->logical($path);
        if($locale==='en') return $path;
        return '/'.$locale.($path==='/'?'':(str_starts_with($path,'/?')||str_starts_with($path,'/#')?substr($path,1):$path));
    }
    public function javascript(): array
    {
        $keys=['Close navigation','Open navigation','Copy','Copied','Selected','Copy code example','Switch to dark theme','Switch to light theme','Searching…','Type at least two characters to search.','No results for “{query}”. Try routing, validation, or database.','Search is temporarily unavailable. Please try again.','Could not search. Please try again.','Mark lesson complete','Completed ✓','Browser storage unavailable','Waiting for a request','Sending request…','Request failed','Could not reach the backend. Please try again.'];
        $messages=[];foreach($keys as $key) $messages[$key]=$this->text($key);return $messages;
    }
}
