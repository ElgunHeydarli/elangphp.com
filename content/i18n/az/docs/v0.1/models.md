# Modellər, əlaqələr və səhifələmə

Elang Data Doctrine DBAL üzərində kiçik model və repozitoriya qatıdır. Yetkin ORM-in bütün davranışlarını təmin etdiyini iddia etmədən adi qeyd əməliyyatlarını açıq şəkildə ifadə edir.

## Model təyin edin

```bash
php elang make:model Post --table=posts
```

Yaradılmış sinfi redaktə edin:

```php
final class Post extends Elang\Data\Model
{
    protected const TABLE = 'posts';
    protected const FILLABLE = ['title', 'author_id', 'published'];
    protected const HIDDEN = ['internal_note'];
    protected const CASTS = ['id' => 'int', 'published' => 'bool'];
}
```

FILLABLE yazmağa açıq şəkildə icazə verir. Gözlənilməyən sahələr təsadüfən mənimsətməyə çevrilmək əvəzinə rədd edilir. HIDDEN seçilmiş sahələri JSON və massiv çıxışından çıxarır; avtorizasiyanı əvəz etmir.

## Qeydləri oxuyun və yazın

Xidmətinizə Elang\Data\Models yeridin:

```php
$posts = $models->of(Post::class);
$post = $posts->create(['title' => 'Hello', 'author_id' => 1, 'published' => 0]);
$mine = $posts->where('author_id', 1);
$mine->update($post->id, ['title' => 'A clearer title']);
```

Repozitoriya filtrləri dəyişməzdir: where və orderBy yeni, məhdudlaşdırılmış repozitoriya qaytarır. Məhdudlaşdırılmış yeniləmə və silmə əməliyyatları sahiblik şərtlərini ifadəyə daxil edir. create çağırışı öz repozitoriyasına artıq tətbiq edilmiş bütün bərabərlik məhdudiyyətlərinə uyğun gəlməlidir.

## Təhlükəsiz identifikatorlar və dəyərlər

Cədvəl və sütun identifikatorları tətbiqin idarə etdiyi sadə adlar olmalıdır. Operatorlar və sıralama istiqamətləri icazə siyahılarından istifadə edir. İstifadəçi dəyərləri parametr kimi bağlanır. İstifadəçinin idarə etdiyi axtarış və ya sıralama seçimi kimi ixtiyari SQL parçaları təklif etməyin.

## Səhifələmə

```php
$page = $posts->where('published', 1)->orderBy('id', 'DESC')->paginate(15, 1);
```

Paginator items, total, perPage, currentPage və lastPage təqdim edir. Onun JSON təsvirində `data` və `meta` var. PageRequest daxil olan səhifə nömrəsini yoxlayır və hər səhifə üçün limitlər sərhədlənir.

## Əlaqələr

Əlaqələr görünməz xüsusiyyət yükləmələri deyil, açıq sorğulardır:

```php
$comments = $models->hasMany($post, Comment::class, 'post_id')->get();
$author = $models->belongsTo($post, UserProfile::class, 'author_id');
```

Böyük kolleksiyalar üçün qabaqcadan yükləməni və sorğu sayını planlaşdırın. Studio həqiqətən icra olunmuş sorğuları yoxlamağa kömək edir. Bu qat hazırda iş vahidi, identiklik xəritəsi və ya obyekt qrafının avtomatik saxlanmasını təmin etmir.

## Fabriklər və ilkin məlumat yükləyiciləri

Factory repozitoriya və təyinat geriçağırışı qəbul edir. Məhdud paket yaradılmasını və dəyərlərin əvəzlənməsini dəstəkləyir. Seeder sinifləri Elang\Data\Seeder interfeysini reallaşdırır və `php elang db:seed 'App\Database\DemoSeeder'` vasitəsilə işləyir. İstehsal mühitində ilkin məlumatların yüklənməsi nəzərdən keçirmə və --force bayrağı tələb edir.
