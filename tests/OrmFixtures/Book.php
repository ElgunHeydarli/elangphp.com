<?php
declare(strict_types=1);
namespace Tests\OrmFixtures;
use Doctrine\ORM\Mapping as ORM;
use Elang\Orm\{SoftDeletable,SoftDeletes,Timestamped,Timestamps};
#[ORM\Entity]
#[ORM\Table(name:'orm_books')]
class Book implements SoftDeletable,Timestamped
{
    use SoftDeletes,Timestamps;
    #[ORM\Id,ORM\GeneratedValue,ORM\Column(type:'integer')]
    public ?int $id=null;
    #[ORM\Version,ORM\Column(type:'integer')]
    public int $version=1;
    public function __construct(#[ORM\Column(length:180)]public string $title,#[ORM\ManyToOne(targetEntity:Author::class,cascade:['persist'])]#[ORM\JoinColumn(nullable:false)]public Author $author) {}
}
