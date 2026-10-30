# Application console commands

Register Symfony Console commands on the application before constructing the console kernel. This also lets service providers contribute project-specific commands.

## Working example


```php
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

// In bootstrap/app.php, after creating $app:
$app->command((new Command('stock:report'))
    ->setDescription('Run the stock report.')
    ->setCode(function (InputInterface $input, OutputInterface $output): int {
        $output->writeln('<info>Report ready</info>');
        return Command::SUCCESS;
    }));
```

## Run and test the command

Run `php elang stock:report`. Return a nonzero status on failure. Use CommandTester for an in-process command test. `Kernel::console()` exposes the configured console application; `Kernel::run()` still runs it normally. Duplicate command names and collisions with framework commands fail explicitly rather than replacing a command silently.

Use bound SQL values, application services and private files in real commands. Keep destructive actions explicit and review their input and failure recovery. Do not put admin execution or write capabilities into the documentation MCP server; it remains read-only.

## Try it together

Run the complete catalogue example from an application with the optional ORM installed. It uses a disposable in-memory database and a fake partner API. Expected output includes `"checks":"passed"`. The example covers entity relationships, fetch joins, resources, collections and HTTP/kernel assertions.

```bash
php vendor/elang/framework/examples/orm-catalog/run.php
```


[Next step](/docs/v0.1/orm) · [GitHub: complete runnable source](https://github.com/ElgunHeydarli/elangphp.com/tree/main/examples/orm-catalog)


## Common mistakes

| Symptom | Fix |
| --- | --- |
| Command name is already registered | Choose a unique name; overriding framework commands is rejected. |
| Command is not listed | Register it before constructing the console kernel. |
