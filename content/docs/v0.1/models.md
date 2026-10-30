# Models, relationships, and pagination

Elang Data is a small model and repository layer over Doctrine DBAL. It makes common record operations explicit without claiming the full behavior of a mature ORM.

## Define a model

```bash
php elang make:model Post --table=posts
```

Edit the generated class:

```php
final class Post extends Elang\Data\Model
{
    protected const TABLE = 'posts';
    protected const FILLABLE = ['title', 'author_id', 'published'];
    protected const HIDDEN = ['internal_note'];
    protected const CASTS = ['id' => 'int', 'published' => 'bool'];
}
```

FILLABLE explicitly allows writes. Unexpected fields are rejected, rather than becoming assignments accidentally. HIDDEN excludes selected fields from JSON and array output; it does not replace authorization.

## Read and write records

Inject Elang\Data\Models into your service:

```php
$posts = $models->of(Post::class);
$post = $posts->create(['title' => 'Hello', 'author_id' => 1, 'published' => 0]);
$mine = $posts->where('author_id', 1);
$mine->update($post->id, ['title' => 'A clearer title']);
```

Repository filters are immutable: where and orderBy return a new scoped repository. Scoped update and delete include the ownership conditions in the statement. A create call must match every equality scope already applied to its repository.

## Safe identifiers and values

Table and column identifiers must be simple application-controlled names. Operators and sort directions use allowlists. User values are bound as parameters. Do not offer arbitrary SQL fragments as a user-controlled search or sort option.

## Pagination

```php
$page = $posts->where('published', 1)->orderBy('id', 'DESC')->paginate(15, 1);
```

Paginator exposes items, total, perPage, currentPage, and lastPage. Its JSON representation contains `data` and `meta`. PageRequest validates an incoming page number, and per-page limits are bounded.

## Relationships

Relationships are explicit queries, not invisible property loads:

```php
$comments = $models->hasMany($post, Comment::class, 'post_id')->get();
$author = $models->belongsTo($post, UserProfile::class, 'author_id');
```

Plan eager loading and query counts for large collections. Studio helps inspect the queries actually executed. This layer does not currently provide a unit of work, identity map, or automatic graph persistence.

## Factories and seeders

Factory accepts a repository and a definition callback. It supports bounded batch creation and overrides. Seeder classes implement Elang\Data\Seeder and run through `php elang db:seed 'App\Database\DemoSeeder'`. Production seeding requires review and the --force flag.


## Next step

[Optional Doctrine ORM](/docs/v0.1/orm).
