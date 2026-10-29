<?php
declare(strict_types=1);
namespace App\Models;
final class Note extends \Elang\Data\Model
{
    protected const TABLE='notes';protected const FILLABLE=['title','body','owner_id','completed','created_at'];protected const CASTS=['id'=>'int','owner_id'=>'int','completed'=>'bool','created_at'=>'int'];
}
