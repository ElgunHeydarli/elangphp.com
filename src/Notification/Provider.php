<?php
declare(strict_types=1);
namespace Elang\Notification;
use Elang\{Application,Container\Container,Contracts\ServiceProvider,Queue\Jobs,Security\Encryption,Support\Config};
use Symfony\Component\{Mailer\MailerInterface,Mime\Email};
final class Provider implements ServiceProvider
{
    public function register(Container $container):void
    {
        $jobs=$container->get(Jobs::class);if($jobs->has('notification.mail')) return;$jobs->register('notification.mail',function(array $payload,Encryption $encryption,MailerInterface $mailer,Config $config):void {
            $sealed=$payload['sealed']??null;if(!is_string($sealed)) throw new \UnexpectedValueException('Invalid mail job.');$message=json_decode($encryption->decrypt($sealed),true,8,JSON_THROW_ON_ERROR);if(!is_array($message)||!isset($message['to'],$message['subject'],$message['body'])) throw new \UnexpectedValueException('Invalid mail message.');
            if($config->get('mail.dsn','null://null')==='null://null') throw new \RuntimeException('Configure a real mail transport before processing mail jobs.');$mailer->send((new Email())->from($config->get('mail.from','noreply@localhost'))->to($message['to'])->subject($message['subject'])->text($message['body']));
        });
    }
    public function boot(Application $app):void {}
}
