<?php
declare(strict_types=1);
namespace Tests;
final class SmtpIntegrationTest extends TestCase
{
    public function testEncryptedQueuedMailUsesAnActualLocalSmtpConversation():void
    {
        $capture=$this->temporary.'/captured.eml';$process=proc_open(['python3',__DIR__.'/fixtures/smtp-capture.py',$capture],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);fclose($pipes[0]);$port=(int)trim(fgets($pipes[1]));self::assertGreaterThan(1024,$port);$app=$this->application(['key'=>base64_encode(random_bytes(32)),'mail'=>['dsn'=>'smtp://127.0.0.1:'.$port,'from'=>'sender@example.test']]);$db=$app->container->get(\Elang\Database\Database::class)->connection();\Elang\Auth\Schema::up($db);\Elang\Queue\Schema::up($db);\Elang\Notification\Schema::up($db);$transport=\Symfony\Component\Mailer\Transport::fromDsn('smtp://127.0.0.1:'.$port);$app->container->instance(\Symfony\Component\Mailer\MailerInterface::class,new \Symfony\Component\Mailer\Mailer($transport));$app->provider(new \Elang\Notification\Provider());$user=$app->container->get(\Elang\Auth\Users::class)->create('SMTP QA','recipient@example.test','An isolated QA passphrase','user',true);$app->container->get(\Elang\Notification\Notifications::class)->send($user,'Local transport check','A message captured on loopback only.',null,true);self::assertTrue($app->container->get(\Elang\Queue\Queue::class)->workOne());self::assertSame(0,(int)$db->fetchOne('SELECT COUNT(*) FROM elang_jobs'));self::assertFileExists($capture);$message=file_get_contents($capture);self::assertStringContainsString('Subject: Local transport check',$message);self::assertStringContainsString('A message captured on loopback only.',$message);$transport->stop();fclose($pipes[1]);$error=stream_get_contents($pipes[2]);fclose($pipes[2]);self::assertSame(0,proc_close($process),$error);
    }
}
