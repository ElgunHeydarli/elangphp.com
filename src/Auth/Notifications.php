<?php
declare(strict_types=1);
namespace Elang\Auth;
use Elang\Support\Config;
use Elang\Security\Encryption;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
final class Notifications
{
    public function __construct(private Config $config,private MailerInterface $mailer,private Encryption $encryption) {}
    public function available(): bool { return $this->config->get('environment')==='local'||$this->config->get('mail.dsn','null://null')!=='null://null'; }
    public function send(User $user,string $purpose,#[\SensitiveParameter] string $token): void
    {
        if(!$this->available()) throw new \Elang\Http\HttpException(503,'Account email is temporarily unavailable. Contact the site administrator.');
        $path=$purpose==='verify'?'/verify/':'/reset-password/';
        $url=rtrim($this->config->get('url','http://localhost:8000'),'/').$path.$token;
        $subject=$purpose==='verify'?'Verify your email address':'Reset your password';
        $body=$subject."\n\nOpen this one-time link within one hour:\n".$url."\n\nIf you did not request this message, you can ignore it.\n";
        if($this->config->get('environment')==='local'&&$this->config->get('mail.dsn','null://null')==='null://null') {
            $directory=$this->config->get('paths.storage').'/mailbox';if(!is_dir($directory)) mkdir($directory,0770,true);
            $payload=json_encode(['to'=>$user->email,'subject'=>$subject,'body'=>$body,'created_at'=>time()],JSON_THROW_ON_ERROR);
            $file=$directory.'/'.bin2hex(random_bytes(12)).'.mail';file_put_contents($file,$this->encryption->encrypt($payload),LOCK_EX);chmod($file,0600);
            $files=glob($directory.'/*.mail')?:[];usort($files,fn($a,$b)=>filemtime($b)<=>filemtime($a));foreach(array_slice($files,50) as $old) unlink($old);
            return;
        }
        try { $this->mailer->send((new Email())->from($this->config->get('mail.from','noreply@localhost'))->to($user->email)->subject($subject)->text($body)); }
        catch(\Throwable) { throw new \Elang\Http\HttpException(503,'Account email could not be delivered. Please try again later.'); }
    }
}
