<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use SoftDeletes;
    protected $table = 'post';
    protected $fillable = ['description','name','picture','type','url','itemid'];
    protected $dates = ['deleted_at'];

    function lists($p,$perPage){
       return Post::where('status',1)->orderBy('created_at','desc')->paginate($perPage, ['id', 'name', 'picture','url','type','itemid'], 'p', $p);
    }
}
