# Tətbiqə məxsus konsol əmrləri

Konsol kernel-i yaradılmadan əvvəl Symfony Console əmrini tətbiqdə qeydiyyatdan keçirin. Service provider də layihəyə məxsus əmrlər əlavə edə bilər.

## İşlək nümunə


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

## Əmri işə salın və test edin

`php elang stock:report` işlədin. Xətada sıfırdan fərqli status qaytarın. Proses daxilində yoxlama üçün CommandTester istifadə edin. Kernel::console konfiqurasiya edilmiş konsolu qaytarır; Kernel::run əvvəlki kimi işlədir. Təkrar ad və framework əmri ilə toqquşma açıq xəta yaradır, mövcud əmri səssiz əvəz etmir.

Real əmrdə SQL dəyərlərini parametr kimi bağlayın, tətbiq xidmətlərini və məxfi faylları istifadə edin. Dağıdıcı əməliyyatın girişini və bərpasını əvvəlcədən düşünün. Sənədləşmə MCP serverinə icra və yazma imkanı əlavə etməyin; o yalnız oxuma üçündür.

## Birlikdə yoxlayın

ORM quraşdırılmış tətbiqdə tam kataloq nümunəsini işlədin. Müvəqqəti yaddaş bazası və fake partnyor API istifadə olunur. Nəticədə `"checks":"passed"` görünməlidir. Nümunə entity əlaqələri, fetch join, resurs, kolleksiya və HTTP/kernel yoxlamalarını birləşdirir.

```bash
php vendor/elang/framework/examples/orm-catalog/run.php
```


[Növbəti addım](/az/docs/v0.1/orm) · [GitHub: complete runnable source](https://github.com/ElgunHeydarli/elangphp.com/tree/main/examples/orm-catalog)


## Tez-tez edilən səhvlər

| Əlamət | Düzəliş |
| --- | --- |
| Əmr adı artıq var | Fərqli ad seçin; framework əmrini əvəz etmək rədd edilir. |
| Əmr siyahıda yoxdur | Konsol kernel-i yaradılmadan əvvəl qeydiyyatdan keçirin. |
