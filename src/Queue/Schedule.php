<?php
declare(strict_types=1);
namespace Elang\Queue;
use Elang\Database\Database;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
final class Schedule
{
    private array $tasks=[];
    public function __construct(private Database $database,private Queue $queue) {}
    public function everyMinute(string $id,string $job,array $payload=[]):void { $this->add($id,$job,$payload,'*'); }
    public function dailyAt(string $id,string $time,string $job,array $payload=[]):void
    {
        if(!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D',$time)) throw new \InvalidArgumentException('Use a UTC time in HH:MM format.');$this->add($id,$job,$payload,$time);
    }
    private function add(string $id,string $job,array $payload,string $at):void
    {
        if(!preg_match('/^[a-z][a-z0-9_.-]{1,119}$/D',$id)||isset($this->tasks[$id])) throw new \InvalidArgumentException('Use a unique schedule ID.');$this->tasks[$id]=compact('job','payload','at');
    }
    public function runDue(?\DateTimeImmutable $now=null):int
    {
        $now=($now??new \DateTimeImmutable())->setTimezone(new \DateTimeZone('UTC'));$count=0;$connection=$this->database->connection();
        foreach($this->tasks as $id=>$task) {
            if($task['at']!=='*'&&$task['at']!==$now->format('H:i')) continue;
            try {
                $connection->transactional(function()use($connection,$id,$task,$now):void {
                    $connection->insert('elang_schedule_runs',['run_key'=>hash('sha256',$id.'|'.$now->format('Y-m-d H:i')),'created_at'=>$now->getTimestamp()]);$this->queue->push($task['job'],$task['payload']);
                });$count++;
            } catch(UniqueConstraintViolationException) {}
        }
        $connection->executeStatement('DELETE FROM elang_schedule_runs WHERE created_at < ?',[$now->getTimestamp()-604800]);return $count;
    }
}
