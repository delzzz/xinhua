<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TopicLink extends Model
{
    use SoftDeletes;
    protected $table = 'topic_link';
    protected $fillable = ['level','description','topic_id','name','picture','type','url','itemid','tag_id'];
    protected $dates = ['deleted_at'];

//    function lists($p,$perPage){
//       return TopicLink::where('status',1)->orderBy('created_at','desc')->paginate($perPage, ['id', 'name', 'picture','url','type','itemid'], 'p', $p);
//    }

    function lists($topicId){
        return TopicLink::where(['topic_id'=>$topicId,'status'=>1])->orderBy('level','asc')->get();
    }
}
