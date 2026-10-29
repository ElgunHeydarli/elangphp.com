<?php
declare(strict_types=1);
namespace App\Cms;
use Doctrine\DBAL\{Connection,Schema\Table};
final class Schema
{
    public static function up(Connection $database): void
    {
        $schema=$database->createSchemaManager();
        if(!$schema->tablesExist(['cms_pages'])) {
            $pages=new Table('cms_pages');$pages->addColumn('id','integer',['autoincrement'=>true]);
            $pages->addColumn('locale','string',['length'=>2,'default'=>'en']);$pages->addColumn('section','string',['length'=>40]);$pages->addColumn('slug','string',['length'=>120]);
            $pages->addColumn('title','string',['length'=>160]);$pages->addColumn('description','string',['length'=>200]);
            $pages->addColumn('markdown','text',['notnull'=>false]);$pages->addColumn('draft_markdown','text');
            $pages->addColumn('draft_title','string',['length'=>160]);$pages->addColumn('draft_description','string',['length'=>200]);
            $pages->addColumn('revision','integer');$pages->addColumn('published_at','bigint',['notnull'=>false]);$pages->addColumn('modified_at','bigint');$pages->addColumn('published_modified_at','bigint',['notnull'=>false]);
            $pages->setPrimaryKey(['id']);$pages->addUniqueIndex(['locale','section','slug']);$schema->createTable($pages);
        }
        if(!$schema->tablesExist(['cms_revisions'])) {
            $revisions=new Table('cms_revisions');$revisions->addColumn('id','integer',['autoincrement'=>true]);$revisions->addColumn('page_id','integer');
            $revisions->addColumn('revision','integer');$revisions->addColumn('title','string',['length'=>160]);$revisions->addColumn('description','string',['length'=>200]);
            $revisions->addColumn('markdown','text');$revisions->addColumn('action','string',['length'=>20]);$revisions->addColumn('actor_id','integer');$revisions->addColumn('created_at','bigint');
            $revisions->setPrimaryKey(['id']);$revisions->addUniqueIndex(['page_id','revision']);$revisions->addForeignKeyConstraint('cms_pages',['page_id'],['id'],['onDelete'=>'CASCADE']);$schema->createTable($revisions);
        }
    }
}
