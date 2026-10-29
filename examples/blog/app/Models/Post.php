<?php
declare(strict_types=1);
namespace App\Models;
final class Post extends \Elang\Data\Model
{
    protected const TABLE='posts';
    protected const FILLABLE=['title','body','author_id','published','created_at'];
    protected const CASTS=['id'=>'int','author_id'=>'int','published'=>'bool','created_at'=>'int'];
}
