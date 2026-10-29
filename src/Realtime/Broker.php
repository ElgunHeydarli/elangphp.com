<?php
declare(strict_types=1);
namespace Elang\Realtime;
use Elang\Infrastructure\Runtime;
/** Redis carries committed wakeups; authorization and replay remain in SQL. */
final class Broker
{
    private string $cursor='0-0';
    public function __construct(private Runtime $runtime) {}
    public function changes():array
    {
        if(!$this->runtime->distributed()) return [];
        $result=$this->runtime->command(['XREAD','COUNT','200','STREAMS',$this->runtime->prefix().':{outbox}:events',$this->cursor]);$channels=[];
        if(!is_array($result)||!isset($result[0][1])||!is_array($result[0][1])) return [];
        foreach($result[0][1] as $entry) {
            if(!is_array($entry)||!is_string($entry[0]??null)||!is_array($entry[1]??null)) continue;$this->cursor=$entry[0];$fields=$entry[1];
            for($i=0;$i<count($fields)-1;$i+=2) if($fields[$i]==='payload') {
                try { $notice=json_decode($fields[$i+1],true,8,JSON_THROW_ON_ERROR);if(is_array($notice)&&is_string($notice['channel']??null)) $channels[$notice['channel']]=true; }catch(\JsonException){}
            }
        }return $channels;
    }
}
