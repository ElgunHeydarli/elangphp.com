<?php
declare(strict_types=1);
namespace ElangExample\Catalog;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
#[ORM\Table(name: 'example_categories')]
class Category
{
    #[ORM\Id,ORM\GeneratedValue,ORM\Column(type: 'integer')]
    public ?int $id=null;
    public function __construct(#[ORM\Column(length: 120)] public string $name) {}
}
