<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/vendor/autoload.php';
$base=dirname(__DIR__,2);$app=require $base.'/bootstrap/app.php';$content=$app->container->get(App\ContentRepository::class);$messages=[];
$add=function(string $text) use(&$messages):void {
    $text=trim(preg_replace('/\s+/u',' ',$text));
    if($text===''||strlen($text)>600||!preg_match('/[a-zA-Z]{2}/',$text)||preg_match('~^(https?://|/|[A-Za-z]+\\\\)|^[vV]?[0-9]+[.0-9a-z+-]*$~',$text)) return;
    $messages[$text]=$text;
};
foreach($content->urls() as $path) {
    $response=$app->handle(Symfony\Component\HttpFoundation\Request::create($path));if($response->getStatusCode()!==200) continue;
    $dom=new DOMDocument();@$dom->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());$xpath=new DOMXPath($dom);
    foreach($xpath->query('//text()[normalize-space()]') as $node) {
        $skip=false;for($parent=$node->parentNode;$parent instanceof DOMElement;$parent=$parent->parentNode) if(in_array(strtolower($parent->tagName),['pre','code','script','style','kbd'],true)||($parent->tagName==='article'&&str_contains($parent->getAttribute('class'),'prose'))) {$skip=true;break;}
        if(!$skip) $add($node->textContent);
    }
    foreach($xpath->query('//*[@placeholder or @aria-label or @title or @alt]') as $node) foreach(['placeholder','aria-label','title','alt'] as $attribute) if($node->hasAttribute($attribute)) $add($node->getAttribute($attribute));
    foreach($xpath->query('//meta[@name="description" or @name="twitter:title" or @name="twitter:description" or @property="og:title" or @property="og:description" or @property="og:image:alt"]') as $node) $add($node->getAttribute('content'));
}
foreach(array_merge(glob($base.'/resources/views/*.twig'),glob($base.'/resources/elang/*.twig')) as $file) {
    $source=file_get_contents($file);
    preg_match_all('~>([^<>]+)<~s',$source,$matches);
    foreach($matches[1] as $text) {
        $text=preg_replace('~\{[{%#].*?[%#}]\}~s','',$text);
        if(str_contains($text,'{')||str_contains($text,'$')||str_contains($text,'=>')) continue;
        $add(html_entity_decode($text,ENT_QUOTES|ENT_HTML5,'UTF-8'));
    }
    preg_match_all("~'([^'\n]{2,180})'~",$source,$matches);
    foreach($matches[1] as $text) if(preg_match('/^[A-Z][a-z]/',$text)&&str_contains($text,' ')) $add($text);
}
foreach(['Home','Documentation','Learn','Guides','Examples','Updates','Elang PHP Documentation','Learn Elang','Elang PHP Guides','Elang examples','Practical guides','Learning path','Start here','Build your application','Build real projects','Go further','Find an answer','More documentation','English','Azerbaijani','Russian','Language','Search documentation','Read in another language','Edit this page','Read-only documentation server','Auth Starter','Blog Starter','Dashboard Starter','API Starter','Registration, verification, password recovery, roles, and scoped tokens.','A complete publication with verified authors and ownership checks.','Private notes, account settings, and persistent per-user data.','A scoped bearer-token API with validated, owner-restricted records.'] as $text) $add($text);
ksort($messages);file_put_contents($base.'/resources/lang/en.json',json_encode($messages,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");echo count($messages)." UI messages extracted.\n";
