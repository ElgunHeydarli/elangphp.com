<?php
declare(strict_types=1);
namespace Elang\Orm;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
final class SoftDeleteFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity,string $targetTableAlias):string
    {
        if(!$targetEntity->getReflectionClass()->implementsInterface(SoftDeletable::class))return '';
        if(!$targetEntity->hasField('deletedAt'))throw new \LogicException('SoftDeletable entities must map the deletedAt field; use the SoftDeletes trait.');
        return $targetTableAlias.'.'.$this->getConnection()->quoteIdentifier($targetEntity->getColumnName('deletedAt')).' IS NULL';
    }
}
