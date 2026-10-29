# Elang ilə tam tapşırıq lövhəsi qurun

Bu layihə marşrutları, aralıq proqram təminatını, kontrollerləri, yoxlamanı, sessiyaları, CSRF-ni və HTML şablonlarını bir kiçik tətbiqdə birləşdirir. JavaScript freymvorku olmadan tapşırıq əlavə edəcək, onu tamamlanmış kimi işarələyəcək və siləcəksiniz.

Nümunə brauzer sessiyanızda 20-yə qədər tapşırıq saxlayır. Onun hesabları və ya ortaq verilənlər bazası yoxdur və sessiyanın vaxtı bitəndə tapşırıqlar itə bilər. Bu sərhəd ilk tam layihəni HTTP həyat dövrünə yönəlmiş saxlayır.

## Əvvəlcə nə bilməlisiniz

[Mühitin hazırlanması](/learn/environment), [ilk marşrutunuz](/learn/first-route) və [yoxlama](/learn/validate-input) dərslərini tamamlayın. PHP və Composer-i işlətməyi, PHP faylını redaktə etməyi və HTML formasını tanımağı bacarmalısınız.

Kodu oxumazdan əvvəl [canlı tapşırıq lövhəsini sınaya bilərsiniz](/demo/task-board). Hər brauzer sessiyasının öz tapşırıq siyahısı var; nümunə hesabı və ya giriş parolu lazım deyil.

## Addım 1: Tam tətbiqi yaradın

Layihəni istədiyiniz ana qovluqdan yerinə yetirin:

```bash
composer create-project elang/task-board task-board dev-main
cd task-board
php elang doctor
php elang serve
```

`http://127.0.0.1:8000` açın. Tapşırıq lövhəsini, boş vəziyyəti və “What would you like to do?” (“Nə etmək istərdiniz?”) etiketli giriş sahəsini görməlisiniz. Bu konkret nümunə üçün verilənlər bazası miqrasiyası və ya Node.js quraşdırılması lazım deyil.

Hədəf qovluqda artıq fayllar varsa, generator onların üzərinə yazmaqdan imtina edir. Əlaqəsiz işi silmək əvəzinə yeni qovluq seçin.

Qlobal CLI-nin birdəfəlik hazırlanmasından sonra bu layihəni `elang new task-board --example=task-board` ilə də yarada bilərsiniz. Composer asılılıqları quraşdırır və tətbiq açarını avtomatik yaradır.

## Addım 2: Layihə fayllarını anlayın

```text
app/Controllers/TaskBoardController.php  Read and change the task list
bootstrap/app.php                      Create Elang and load routes
config/app.php                         Set the board's base path
routes/web.php                         Register GET and POST routes
resources/views/task-board.twig        Render the HTML page
public/assets/task-board.css           Style the page
storage/sessions/                      Private server-side session files
```

Bu faylları həmin ardıcıllıqla açın. Yaradılmış tətbiq tam mənbə kodunu ehtiva edir, buna görə ayrı-ayrı kod parçalarından çatışmayan metodları yenidən qurmağa ehtiyac yoxdur.

## Addım 3: Bir sorğunu izləyin

GET marşrutu cari siyahını göstərir. POST marşrutları tapşırıqları yaradır, tamamlanma vəziyyətini dəyişir və silir:

```php
$router->get('/', [TaskBoardController::class, 'index'], 'board');
$router->post('/add', [TaskBoardController::class, 'store']);
$router->post('/toggle/{id}', [TaskBoardController::class, 'toggle'])
    ->where('id', '[a-f0-9]{16}');
$router->post('/delete/{id}', [TaskBoardController::class, 'delete'])
    ->where('id', '[a-f0-9]{16}');
```

Onlar sorğu tezliyi məhdudlaşdırıcısı, sessiya aralıq proqram təminatı və CSRF aralıq proqram təminatı olan qrup daxilində işləyir. Sessiya aralıq proqram təminatı CSRF tokeni yoxlamazdan əvvəl sessiyanı yaradır. Daha sonra marşrut kontrolleri və onun tipləndirilmiş asılılıqlarını həll edir.

## Addım 4: Saxlamazdan əvvəl yoxlayın

store emaledicisi başlığı oxuyur və üç məhdudiyyət tətbiq edir: boş olmamalı, sətir olmalı və 100 simvoldan uzun olmamalıdır.

```php
$valid = $validator->validate(['title' => $title], [
    'title' => [
        new Assert\NotBlank(),
        new Assert\Type('string'),
        new Assert\Length(max: 100),
    ],
]);
```

Tam kontroller ValidationException tutur və formanı faydalı sahə xətaları və ilkin girişlə göstərir. Server etibarsız məlumat üçün 422 qaytarır. Brauzerin required və maxlength atributları rahatdır, lakin çağıran tərəf brauzer yoxlamasını keçə bildiyi üçün arxa tərəf yoxlamaları zəruri olaraq qalır.

## Addım 5: Dəyişiklikləri sessiyada saxlayın

Kontroller cari sessiyadan `tasks` oxuyur. Təsadüfi tapşırıq ID-si yaradır, təsdiqlənmiş başlığı saxlayır və tapşırığı tamamlanmamış vəziyyətdə başladır:

```php
$id = bin2hex(random_bytes(8));
$tasks[$id] = ['id' => $id, 'title' => $valid['title'], 'done' => false];
$request->getSession()->set('tasks', $tasks);
```

Vəziyyətin dəyişdirilməsi və silmə yalnız həmin sessiyanın siyahısında işləyir. Başqa brauzerin siyahısından köçürülən ID sizin sessiyanızdakı tapşırıq deyil və 404 qaytarır. Sessiya təcridi burada faydalıdır, lakin istifadəçinin autentifikasiyası ilə eyni deyil.

## Addım 6: Formaları qoruyun

Hər formada cari sessiyadan yaradılmış gizli `_token` dəyəri var:

```twig
<form action="/add" method="post">
  <input type="hidden" name="_token" value="{{ token }}">
  <label for="title">Task title</label>
  <input id="title" name="title" maxlength="100" required>
  <button type="submit">Add task</button>
</form>
```

Çatışmayan və ya yanlış token emaledici vəziyyəti dəyişməzdən əvvəl 419 yaradır. Tapşırıq başlıqları Twig-in avtomatik HTML ekranlaşdırması ilə göstərilir. İstifadəçinin təqdim etdiyi başlıqlara `|raw` əlavə etməyin.

## Addım 7: Uğurdan sonra yönləndirin

Uğurlu POST-dan sonra kontroller lövhəyə 303 yönləndirməsi qaytarır. Brauzer onu GET ilə izləyir. Buna görə nəticə səhifəsinin yenilənməsi əvvəlki formanı yenidən göndərmək əvəzinə lövhəni oxuyur.

Etibarsız giriş xətalarını birbaşa göstərir ki, istifadəçi başlığı düzəldə bilsin. Bu, kiçik nümunədə uğurlu keçidlə yoxlama rəyinin fərqini görünən edir.

## Tamamlanmış davranışı yoxlayın

1. “Marşrutlaşdırma təlimatını oxu.” əlavə edin. Say bir olmaqla görünməlidir.
2. Onu tamamlanmış kimi işarələyin. Mətnin üstündən xətt çəkilməli və tamamlanmış tapşırıq sayı artmalıdır.
3. Onu silin. Boş vəziyyət geri qayıtmalıdır.
4. Başlıq kimi boşluq simvolları göndərin. Arxa tərəf tapşırıq əlavə etmədən yoxlama xətası göstərməlidir.
5. Lövhəni ayrıca şəxsi brauzer sessiyasında açın. Onun ayrıca siyahısı olmalıdır.
6. Formanı yoxlayın və `_token` olduğunu təsdiqləyin. Göndərməzdən əvvəl onu silmək 419 verməlidir.

Bu yoxlamalar yalnız səhifənin görünüşünü deyil, həqiqi arxa tərəfi sınaqdan keçirir.

## Tez-tez verilən suallar

**Siyahım niyə yox oldu?** Sessiyanın vaxtı bitə bilər və ya brauzer profilini dəyişmiş və ya sessiya kukisini silmiş ola bilərsiniz. Bu, müvəqqəti öyrənmə vəziyyətidir.

**Niyə dərhal verilənlər bazası istifadə etməyək?** [Verilənlər bazası dərsi](/learn/database) daimi sxemi və parametr bağlanmış sorğuları ayrıca təqdim edir. İlk addımda bütün anlayışları birləşdirmək uğursuzluqların yerini tapmağı çətinləşdirir.

**Dəyişikliklər niyə POST istifadə edir?** Əlavəetmə, vəziyyət dəyişdirmə və silmə tətbiqin vəziyyətini dəyişir. GET keçidi tapşırıq siyahısını dəyişməməlidir.

**Bunu həqiqi çoxistifadəçili məhsula çevirə bilərəm?** Bəli, lakin bu, yeni tətbiq layihələndirilməsidir: onu ortaq tapşırıq xidməti kimi qəbul etməzdən əvvəl daimi saxlama, autentifikasiya, sahiblik yoxlamaları, avtorizasiya və istifadəçilərarası giriş testləri əlavə edin.

## Növbəti çalışmanız

Başlığı və boş vəziyyət mətnini dəyişin, sonra tamamlanmış və tamamlanmamış tapşırıqlar üçün görünüş filtri əlavə edin. Server tərəfdəki yoxlamanı və CSRF davranışını qoruyun. Nəsə uğursuz olduqda bir neçə qatı eyni vaxtda dəyişmək əvəzinə [sazlama qaydasını](/learn/debugging) izləyin.
