<?php
declare(strict_types=1);
namespace Elang\Testing;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
final class MailRecorder extends AbstractTransport
{
    private array $messages=[];
    protected function doSend(SentMessage $message):void { $this->messages[]=$message; }
    public function __toString():string { return 'elang-test://memory'; }
    public function messages():array { return $this->messages; }
    public function assertSentCount(int $count):void { Assert::same($count,count($this->messages),'Unexpected test email count.'); }
    public function assertSentTo(string $address):void
    {
        $found=false;foreach($this->messages as $message)foreach($message->getEnvelope()->getRecipients() as $recipient)if($recipient->getAddress()===$address)$found=true;
        Assert::truth($found,'Expected test email recipient was not recorded.');
    }
    public function __debugInfo():array { return ['recorded_messages'=>count($this->messages)]; }
}
