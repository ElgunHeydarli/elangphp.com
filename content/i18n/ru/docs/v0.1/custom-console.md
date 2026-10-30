# Консольные команды приложения

Зарегистрируйте Symfony Console-команду в приложении до создания console kernel. Service provider также может добавлять команды проекта.

## Рабочий пример


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

## Запуск и проверка команды

Выполните php elang stock:report. При ошибке возвращайте ненулевой статус. Для теста внутри процесса используйте CommandTester. Kernel::console возвращает настроенное консольное приложение; Kernel::run запускает его как раньше. Дубликаты и совпадения с командами framework вызывают ошибку, а не скрытую замену.

В реальных командах связывайте SQL-параметры, используйте сервисы приложения и приватные файлы. Явно обозначайте разрушительные действия и продумывайте восстановление. Не добавляйте выполнение кода и операции записи в документационный MCP; он остаётся read-only.

## Проверьте вместе

Запустите полный пример каталога из приложения с установленной ORM. Он использует временную базу в памяти и fake API партнёра. В результате ожидается `"checks":"passed"`. Пример объединяет связи, fetch join, ресурсы, коллекции и HTTP/kernel assertions.

```bash
php vendor/elang/framework/examples/orm-catalog/run.php
```


[Следующий шаг](/ru/docs/v0.1/orm) · [GitHub: complete runnable source](https://github.com/ElgunHeydarli/elangphp.com/tree/main/examples/orm-catalog)


## Частые ошибки

| Симптом | Исправление |
| --- | --- |
| Имя команды занято | Выберите уникальное имя: замена framework-команд отклоняется. |
| Команды нет в списке | Зарегистрируйте её до создания console kernel. |
