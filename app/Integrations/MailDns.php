<?php
declare(strict_types=1);
namespace App\Integrations;

use Elang\Application;
use GuzzleHttp\ClientInterface;

/** Host-specific, additive publication of the reviewed mail records for this site. */
final class MailDns
{
    private const ENDPOINT='https://fornex.com/api/dns/domain/elangphp.com/entry_set/';
    public function __construct(private Application $app,private ClientInterface $http) {}

    public function records():array
    {
        $path=$this->app->basePath.'/config/mail-dns.json';
        if(!is_file($path)) return [];
        $records=json_decode(file_get_contents($path),true,16,JSON_THROW_ON_ERROR);
        if(!is_array($records)||count($records)!==3) throw new \LogicException('The reviewed mail DNS records are unavailable.');
        $hosts=[];
        foreach($records as $record) {
            if(!is_array($record)||!isset($record['host'],$record['type'],$record['value'],$record['ttl'])||!in_array($record['host'],['','mail._domainkey','_dmarc'],true)||$record['type']!=='TXT'||!is_string($record['value'])||strlen($record['value'])>2048||$record['ttl']!==3600) throw new \LogicException('The reviewed mail DNS records are unavailable.');
            $prefix=match($record['host']) { ''=>'v=spf1 ', 'mail._domainkey'=>'v=DKIM1;', '_dmarc'=>'v=DMARC1;' };
            if(!str_starts_with($record['value'],$prefix)||in_array($record['host'],$hosts,true)) throw new \LogicException('The reviewed mail DNS records are unavailable.');
            $hosts[]=$record['host'];
        }
        return $records;
    }

    public function publish(#[\SensitiveParameter] string $key):array
    {
        if(strlen($key)<16||strlen($key)>512||preg_match('/\s/',$key)) throw new \InvalidArgumentException('Enter the Fornex API key from your account settings.');
        $prepared=$this->records();
        if(count($prepared)!==3) throw new \RuntimeException('The reviewed mail DNS records are unavailable.');
        $options=['http_errors'=>false,'allow_redirects'=>false,'connect_timeout'=>2,'timeout'=>5,'headers'=>['Authorization'=>'Api-Key '.$key,'Accept'=>'application/json','User-Agent'=>'Elang-Mail-DNS/0.1 (+https://elangphp.com)']];
        try {
            $response=$this->http->request('GET',self::ENDPOINT,$options);
            if(in_array($response->getStatusCode(),[401,403],true)) throw new \RuntimeException('Fornex rejected the API key or DNS permission.');
            if($response->getStatusCode()!==200) throw new \RuntimeException('Fornex could not complete the request. Check domain access and try again.');
            $existing=json_decode((string)$response->getBody(),true,32,JSON_THROW_ON_ERROR);
            if(!is_array($existing)||!array_is_list($existing)||count($existing)>2000) throw new \UnexpectedValueException();
            $pending=[];$present=0;
            // Review every conflict before creating any record. Never replace or delete a record.
            foreach($prepared as $record) {
                $matched=false;
                foreach($existing as $entry) {
                    if(!is_array($entry)||!isset($entry['host'],$entry['type'],$entry['value'])||!is_string($entry['host'])||!is_string($entry['type'])||!is_string($entry['value'])) throw new \UnexpectedValueException();
                    $host=strtolower(rtrim($entry['host'],'.'));
                    if($host==='@'||$host==='elangphp.com') $host='';
                    if(str_ends_with($host,'.elangphp.com')) $host=substr($host,0,-strlen('.elangphp.com'));
                    if($host!==$record['host']) continue;
                    if(strtoupper($entry['type'])==='CNAME') throw new \RuntimeException('Existing mail DNS records conflict with this plan. Review them in Fornex; no records were changed.');
                    if(strtoupper($entry['type'])!=='TXT') continue;
                    $value=trim($entry['value'],'"');
                    if($host===''&&!preg_match('/^v=spf1(?:\s|$)/i',$value)) continue;
                    if($value!==$record['value']) throw new \RuntimeException('Existing mail DNS records conflict with this plan. Review them in Fornex; no records were changed.');
                    $matched=true;
                }
                if($matched) $present++;else $pending[]=$record;
            }
            foreach($pending as $record) {
                $response=$this->http->request('POST',self::ENDPOINT,$options+['json'=>$record]);
                if(!in_array($response->getStatusCode(),[200,201],true)) throw new \RuntimeException('Some DNS records may already have been created. Review Fornex and retry; existing records are preserved.');
            }
            return ['created'=>count($pending),'present'=>$present];
        }catch(\RuntimeException $error) {
            $safe=['Fornex rejected the API key or DNS permission.','Fornex could not complete the request. Check domain access and try again.','Existing mail DNS records conflict with this plan. Review them in Fornex; no records were changed.','Some DNS records may already have been created. Review Fornex and retry; existing records are preserved.'];
            if(in_array($error->getMessage(),$safe,true)) throw $error;
            throw new \RuntimeException('Fornex could not complete the request. Check domain access and try again.');
        }catch(\Throwable) {
            throw new \RuntimeException('Fornex could not complete the request. Check domain access and try again.');
        }
    }
}
