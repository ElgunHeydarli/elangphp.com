<?php
declare(strict_types=1);
namespace App\I18n;
use Symfony\Component\HttpFoundation\Request;
final class Html
{
    public function __construct(private Locale $locale) {}
    private function excluded(\DOMNode $node): bool
    {
        for($parent=$node->parentNode;$parent instanceof \DOMElement;$parent=$parent->parentNode) if(in_array(strtolower($parent->tagName),['pre','code','script','style','kbd','textarea'],true)) return true;
        return false;
    }
    public function render(string $html,Request $request): string
    {
        $dom=new \DOMDocument();@$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);$xpath=new \DOMXPath($dom);
        $language=$this->locale->current();$root=$dom->getElementsByTagName('html')->item(0);if($root) $root->setAttribute('lang',$language);
        $body=$dom->getElementsByTagName('body')->item(0);if($body) { $body->setAttribute('data-site-locale',$language);$body->setAttribute('data-ui',json_encode($this->locale->javascript(),JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)); }
        if($language!=='en') {
            foreach($xpath->query('//text()[normalize-space()]') as $node) {
                if($this->excluded($node)) continue;$before=$node->textContent;$translated=$this->locale->text($before);
                if($translated!==$before) { preg_match('/^\s*/u',$before,$leading);preg_match('/\s*$/u',$before,$trailing);$node->parentNode->replaceChild($dom->createTextNode($leading[0].$translated.$trailing[0]),$node); }
            }
            foreach($xpath->query('//*[@placeholder or @aria-label or @title or @alt]') as $node) if($node instanceof \DOMElement) foreach(['placeholder','aria-label','title','alt'] as $attribute) if($node->hasAttribute($attribute)) $node->setAttribute($attribute,$this->locale->text($node->getAttribute($attribute)));
            foreach($xpath->query('//meta[@name="description" or @name="twitter:title" or @name="twitter:description" or @property="og:title" or @property="og:description" or @property="og:image:alt"]') as $node) if($node instanceof \DOMElement) $node->setAttribute('content',$this->locale->text($node->getAttribute('content')));
        }
        foreach($xpath->query('//*[@href or @action]') as $node) {
            if(!$node instanceof \DOMElement||$node->tagName==='link') continue;
            foreach(['href','action'] as $attribute) if($node->hasAttribute($attribute)) $node->setAttribute($attribute,$this->locale->path($node->getAttribute($attribute)));
        }
        $container=$xpath->query('//header//*[contains(concat(" ",normalize-space(@class)," ")," header-actions ")]')->item(0)??$xpath->query('//header/nav')->item(0);
        if($container&&!$xpath->query('//details[contains(concat(" ",normalize-space(@class)," ")," language-picker ")]')->length) {
            $picker=$dom->createElement('details');$picker->setAttribute('class','language-picker');
            $summary=$dom->createElement('summary');$summary->setAttribute('aria-label',$this->locale->text('Choose language'));$summary->setAttribute('title',Locale::LANGUAGES[$language]);
            $flags=['en'=>'gb','az'=>'az','ru'=>'ru'];
            $flag=function(string $code)use($dom,$flags):\DOMElement { $img=$dom->createElement('img');$img->setAttribute('src','/assets/flags/'.$flags[$code].'.svg');$img->setAttribute('width','22');$img->setAttribute('height','16');$img->setAttribute('alt','');return $img; };
            $summary->appendChild($flag($language));$label=$dom->createElement('span',strtoupper($language));$label->setAttribute('class','language-code');$summary->appendChild($label);$chevron=$dom->createElement('span','⌄');$chevron->setAttribute('aria-hidden','true');$chevron->setAttribute('class','language-chevron');$summary->appendChild($chevron);$picker->appendChild($summary);
            $menu=$dom->createElement('nav');$menu->setAttribute('class','language-options');$menu->setAttribute('aria-label',$this->locale->text('Language'));
            $path=$request->attributes->get('_logical_path',$this->locale->logical($request->getPathInfo()));
            foreach(Locale::LANGUAGES as $code=>$name) {
                if($request->attributes->has('_available_languages')&&!in_array($code,$request->attributes->get('_available_languages'),true)) continue;
                $link=$dom->createElement('a');$link->setAttribute('href',$this->locale->path($path,$code));$link->setAttribute('hreflang',$code);$link->setAttribute('lang',$code);$link->appendChild($flag($code));$link->appendChild($dom->createElement('span',$name));
                if($code===$language) { $link->setAttribute('aria-current','page');$check=$dom->createElement('span','✓');$check->setAttribute('aria-hidden','true');$check->setAttribute('class','language-check');$link->appendChild($check); }$menu->appendChild($link);
            }$picker->appendChild($menu);$container->appendChild($picker);
            if(!$xpath->query('//script[contains(@src,"/assets/app.js")]')->length) { $script=$dom->createElement('script');$script->setAttribute('src','/assets/app.js');$script->setAttribute('defer','defer');$dom->getElementsByTagName('head')->item(0)?->appendChild($script); }
        }
        $out=$dom->saveHTML();return preg_replace('/<\?xml encoding="utf-8" \?>\s*/','',$out);
    }
}
