<?php
declare(strict_types=1);
namespace Tests;
final class DoctorTest extends TestCase
{
    public function testDoctorReportsMissingKeyAndBrokenConnectionWithoutSecrets(): void
    {
        $app = $this->application(['key' => '', 'database' => ['url' => 'mysql://private_user:private_password@127.0.0.1:1/missing?connect_timeout=1']]);
        $checks = (new \Elang\Console\Doctor())->inspect($app);
        self::assertContains('error', array_column($checks, 'status'));
        $json = json_encode($checks); self::assertStringNotContainsString('private_password', $json); self::assertStringNotContainsString('private_user', $json);
    }
    public function testReadyEnvironmentPasses(): void
    {
        mkdir($this->temporary.'/vendor');
        file_put_contents($this->temporary.'/vendor/autoload.php', '<?php return true;');
        $app = $this->application(['key' => base64_encode(random_bytes(32))]);
        self::assertNotContains('error', array_column((new \Elang\Console\Doctor())->inspect($app), 'status'));
    }
}
