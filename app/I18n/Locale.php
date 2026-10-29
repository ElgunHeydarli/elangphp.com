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
        $keys=array_merge($keys,['Choose a passkey to continue.','Confirm the passkey request on your device.','Copied. Keep this setup key private.','Could not copy. Select the recovery codes and copy them manually.','Could not copy. Select the setup key and copy it manually.','Could not download. Copy or save the recovery codes manually.','Could not reach the server. Check your connection and try again.','Passkey added. Refreshing your security settings…','Passkeys are not available at this address. Open the application at its configured HTTPS address.','Passkeys require HTTPS or localhost in a compatible browser. You can still use your password.','Please check your account details and try again, or sign in with your password.','Recovery codes copied. Store them somewhere private.','Recovery codes downloaded. Store the file somewhere private.','Signed in. Opening your account…','The passkey request could not be completed. Reload the page and try again.','The passkey request could not be completed. Try again or use your password.','The passkey request was cancelled or timed out. Try again when you are ready.','This browser or authenticator does not support the requested passkey.','This passkey may already be registered. Try another device or security key.','Too many attempts. Please wait before trying again.','Your browser supports passkeys. Add one when you are ready.','Your session changed or this action could not be authorized. Reload the page and try again.']);
        $keys=array_merge($keys,['Submitting package…','Package submission could not be completed.','Submitted. Packagist indexing and version availability still need verification.','Packagist rejected the account token or permission. Use the main API token.','Packagist did not accept this package. Check namespace ownership and the repository manifest.','Enter a text value.','Choose a prepared official package.','Save the encrypted Packagist connection first.','That package name belongs to another source repository.','Packagist could not complete the request. Check the connection and try again.']);
        $messages=[];foreach($keys as $key) $messages[$key]=$this->text($key);return $messages;
    }
}
