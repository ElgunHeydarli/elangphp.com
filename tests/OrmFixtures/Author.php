<?php
declare(strict_types=1);
namespace Tests\OrmFixtures;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
#[ORM\Table(name:'orm_authors')]
class Author
{
    #[ORM\Id,ORM\GeneratedValue,ORM\Column(type:'integer')]
    public ?int $id=null;
    public function __construct(#[ORM\Column(length:120)]public string $name) {}
}
