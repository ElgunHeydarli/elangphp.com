<?php
declare(strict_types=1);
namespace ElangExample\Catalog;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
#[ORM\Table(name: 'example_products')]
class Product
{
    #[ORM\Id,ORM\GeneratedValue,ORM\Column(type: 'integer')]
    public ?int $id=null;
    public function __construct(
        #[ORM\Column(length: 160)] public string $title,
        #[ORM\Column(type: 'integer')] public int $quantity,
        #[ORM\ManyToOne(targetEntity: Category::class,cascade: ['persist'])]
        #[ORM\JoinColumn(nullable: false)] public Category $category,
        #[ORM\Column(length: 120)] private string $internalNote='Not for API responses',
    ) {}
}
