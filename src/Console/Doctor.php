<?php
declare(strict_types=1);
namespace Elang\Console;
use Elang\Application;
use Elang\Database\Database;
use Elang\Security\Encryption;
final class Doctor
{
    public function inspect(Application $app): array
    {
        $checks = [];
        $checks[] = ['name' => 'PHP version', 'status' => 'ok', 'detail' => PHP_VERSION.' (Composer platform check passed)', 'help' => 'Use PHP 8.3 or newer within PHP 8.x.'];
        foreach (['ctype', 'json', 'mbstring', 'PDO', 'sodium','fileinfo'] as $extension) $checks[] = ['name' => 'Extension: '.$extension, 'status' => extension_loaded($extension) ? 'ok' : 'error', 'detail' => extension_loaded($extension) ? 'Loaded' : 'Missing', 'help' => 'Enable the extension in the CLI PHP configuration shown by php --ini.'];
        $checks[] = ['name' => 'Argon2id passwords', 'status' => in_array('argon2id', password_algos(), true) ? 'ok' : 'error', 'detail' => 'Password hashing support', 'help' => 'Install a PHP build with Argon2id support.'];
        $checks[] = ['name' => 'Composer dependencies', 'status' => is_file($app->basePath.'/vendor/autoload.php') ? 'ok' : 'error', 'detail' => 'Autoloader', 'help' => 'Run composer install from the application directory.'];
        foreach (['cache', 'logs', 'sessions', 'locks'] as $directory) {
            $path = $app->config->get('paths.storage').'/'.$directory;
            $checks[] = ['name' => 'Writable storage: '.$directory, 'status' => is_writable($path) ? 'ok' : 'error', 'detail' => is_writable($path) ? 'Writable by this PHP process' : 'Not writable', 'help' => 'Grant the PHP runtime user write access to storage; keep application source private.'];
        }
        try { $app->container->get(Encryption::class); $key = true; } catch (\Throwable) { $key = false; }
        $checks[] = ['name' => 'Application encryption key', 'status' => $key ? 'ok' : 'error', 'detail' => $key ? 'Valid; value hidden' : 'Missing or invalid', 'help' => 'Run php elang key:generate. Preserve an existing valid key.'];
        try { $app->container->get(Database::class)->connection()->fetchOne('SELECT 1'); $database = true; } catch (\Throwable) { $database = false; }
        $checks[] = ['name' => 'Database connection', 'status' => $database ? 'ok' : 'error', 'detail' => $database ? 'Connection succeeded' : 'Connection failed; credentials hidden', 'help' => 'Check DATABASE_URL and the relevant PDO driver; see /docs/v0.1/troubleshooting.'];
        $debug = $app->config->get('environment') === 'production' && $app->config->get('debug', false);
        $checks[] = ['name' => 'Production error output', 'status' => $debug ? 'error' : 'ok', 'detail' => $debug ? 'APP_DEBUG is enabled in production' : 'Safe for the selected environment', 'help' => 'Set APP_DEBUG=false in production.'];
        $runtime=$app->container->get(\Elang\Infrastructure\Runtime::class);
        if($runtime->distributed()) { try { $healthy=$runtime->command(['PING'])==='PONG'; }catch(\Throwable) { $healthy=false; }$checks[]=['name'=>'Shared Redis','status'=>$healthy?'ok':'error','detail'=>$healthy?'Connected; credentials hidden':'Connection or ACL check failed','help'=>'Check the private REDIS_URL and ACL. Do not paste credentials into support messages.']; }
        return $checks;
    }
}
