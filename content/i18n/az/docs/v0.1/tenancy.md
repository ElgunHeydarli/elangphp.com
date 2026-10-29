# Təşkilatlar, komandalar və təşkilatlararası təcrid

Tenant biznes qeydləri digər təşkilatlardan ayrılan təşkilatdır. Elang açıq komanda identifikatorlarından, cari üzvlük yoxlamalarından və SQL tranzaksiyalarından istifadə edir. O, təşkilat kimliyini bütün proses üçün ortaq “cari komanda” dəyişənində gizlətmir. Bu, xüsusilə çoxlu istifadəçiyə xidmət edən növbələr və uzun müddət işləyən işçilər üçün vacibdir.

## Cədvəlləri hazırlayın və komanda yaradın

```bash
php elang platform:setup
```

```php
$teams = $app->container->get(Elang\Tenancy\Teams::class);
$team = $teams->create($user, 'Northwind');
$mine = $teams->list($user);
```

Əməli edən şəxs müvəqqəti parolu olmayan, hazırda etibarlı və təsdiqlənmiş hesaba malik olmalıdır. Komanda yaradılması yaradıcıya sahib rolunu verir. Komanda ID-ləri 32 təsadüfi onaltılıq simvoldan ibarətdir. Onların təxminedilməzliyi avtorizasiya sərhədi deyil: hər əməliyyat yenə də üzvlüyü yoxlayır.

| Rol | Məlumat oxumaq | Məlumat yazmaq | Adi üzvləri dəvət etmək/silmək | Rolları təyin etmək / sahibliyi ötürmək |
| --- | --- | --- | --- | --- |
| Sahib | Bəli | Bəli | Bəli | Bəli |
| Administrator | Bəli | Bəli | Bəli | Xeyr |
| Üzv | Bəli | Bəli | Xeyr | Xeyr |
| Baxışçı | Bəli | Xeyr | Xeyr | Xeyr |

Yalnız sahib administratorları təyin edir və ya silir. Sahib özünü silə və ya rolunu aşağı sala bilməz: əvvəlcə sahibliyi mövcud üzvə ötürün. Ötürmə bir tranzaksiyada əvvəlki sahibin rolunu administratora, seçilmiş üzvün rolunu isə sahibə dəyişir.

## Biznes sorğularını açıq şəkildə məhdudlaşdırın

```php
$result = $teams->within(
    $user,
    $teamId,
    'write',
    function (Doctrine\DBAL\Connection $db, array $team) use ($title) {
        $id = bin2hex(random_bytes(16));
        $db->insert('projects', [
            'id' => $id,
            'team_id' => $team['id'],
            'title' => $title,
        ]);
        return $id;
    },
);
```

Xarici açar/indeks olan `team_id` daxil olmaqla `projects` cədvəlini öz miqrasiyanızda yaradın. Bağlanmış dəyərlərdən istifadə edin. Hər oxuma, yeniləmə, silmə, əlaqə axtarışı, ixrac və tapşırıq SQL-i avtorizasiya edilmiş `team_id` ilə məhdudlaşdırmalıdır. Marşrut parametri və ya göndərilmiş `team_id` heç vaxt həmin dəyəri əvəz etməməlidir. Elang-ın SaaS başlanğıc layihəsi bu nümunəni layihə qeydləri və optimist versiyalarla nümayiş etdirir.

`within()` tranzaksiya açır, komanda sətrini kilidləyir, üzvlüyü yenidən yükləyir və tələb olunan icazəni yoxlayır. Üzvlük dəyişiklikləri eyni kiliddən istifadə edir, buna görə icazənin ləğvi və biznes yazması ardıcıllaşdırılır. Bu ehtiyatlı yanaşma bir komanda daxilində ağır yazma əməliyyatlarını ardıcıllaşdıra bilər. Daha böyük iş yükləri üçün kilid rəqabətini ölçün və avtorizasiya düzgünlüyünü qoruyaraq daha dar domen kilidləri layihələndirin. E-poçt çatdırılması, ləng S3 çağırışları və ya uzaq ödəniş sorğuları boyunca tranzaksiyanı açıq saxlamayın; sistemlərarası keçidlərdə çıxış qutusu/saga istifadə edin.

Üzv olmayan şəxs 404, qeyri-kafi rolu olan üzv isə 403 alır. Bu, təşkilatın mövcudluğunu yad şəxslərə açıqlamağın qarşısını alır. Keşlənmiş ön tərəf rolları yalnız təqdimat üçündür. Hər əməliyyat üzrə qərarı server yoxlamaları verir.

## Dəvətlər

```php
$raw = $teams->invite($owner, $teamId, 'member@example.com', 'member');
$accepted = $teams->accept($signedInVerifiedUser, $raw);
```

Dəvət rolları `admin`, `member` və ya `viewer` ola bilər; yalnız sahib administrator dəvət edə bilər. Tokenlər yeddi gündən sonra bitir və yalnız SHA-256 heşləri kimi saxlanılır. Eyni komanda/e-poçt üçün yeni dəvət verilməsi köhnə dəvəti əvəz edir. Qəbul etmək üçün normallaşdırılmış e-poçtu uyğun gələn, daxil olmuş təsdiqlənmiş hesab tələb olunur. Token bir dəfə istifadə edilir. Başqa dəvətin qəbul edilməsi ilə mövcud üzvün rolu səssizcə yüksəldilmir.

Tətbiqinizin şəxsi dəvəti çatdıra bilməsi üçün xidmət xam tokeni bir dəfə qaytarır. Onu analitika və ya jurnallara daxil etməyin. Saxlanmayan və istinad mənbəyini ötürməyən qəbul səhifəsi istifadə edin. SaaS başlanğıc layihəsi şəxsi paylaşıla bilən dəvət keçidini göstərir. Avtomatik e-poçt çatdırılması əlavə etməzdən əvvəl real poçt nəqli konfiqurasiya edin.

## Üzvlük dəyişiklikləri

```php
$teams->role($owner, $teamId, $memberId, 'viewer');
$teams->remove($owner, $teamId, $memberId);
$teams->transfer($owner, $teamId, $nextOwnerId);
```

Növbəyə alınmış iş komanda/istifadəçi/qeyd ID-lərini daşımalı, əməli edən şəxsi yenidən yükləməli və icra zamanı yenidən `within()` daxilinə keçməlidir. Növbəyə göndərildikdən sonra üzvlük dəyişmiş ola bilər. Xidmətə məxsus tapşırıqlar üçün süni administrator istifadəçisi yaratmaq əvəzinə ayrıca etibarlı xidmət siyasəti təyin edin.

Təşkilatlararası oxuma və yazmaları, saxta ID-ləri, silinmiş üzvləri, baxışçının yazma cəhdlərini, vaxtı bitmiş dəvətləri, yanlış e-poçtla qəbulu, paralel ləğvetməni və sahibliyin ötürülməsini sınaqdan keçirin. Ayrı verilənlər bazaları, sətir səviyyəli təhlükəsizlik, şifrələmə sərhədləri, audit saxlanma müddəti və məlumatların yerləşmə tələbləri yerləşdirmə/domen qərarlarıdır; bu ortaq cədvəl modeli onları avtomatik təqdim etmir.

Növbəti: [fayllar](/docs/v0.1/files), [bildirişlər](/docs/v0.1/notifications) və [SaaS istinad tətbiqi](/docs/v0.1/saas).
