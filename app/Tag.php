<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Tag extends Model
{
    use SoftDeletes;
    protected $table = 'tag';
    protected $fillable = ['tag_name', 'uid','color'];
    protected $dates = ['deleted_at'];

    //标签列表
    function lists()
    {
        $tag = Tag::select('id','tag_name','created_at','uid','color')->get();
        return $tag;
    }

    //删除标签
    function del($id)
    {
        $idArr = explode(',', $id);
        foreach ($idArr as $key => $value) {
            $flag = Tag::find($value)->delete();
        }
        return $flag;
    }

    //详情
    function getInfo($id)
    {
        return Tag::find($id);
    }

    //标签对应中文
    function getTagNames($str){
        if(empty($str)){
           return null;
        }
        $strArr = explode(',',$str);
        $tagName = '';
        $tagColor = '';
        $tagArr = array();
        foreach ($strArr as $key=>$id){
            $tagInfo = $this->getInfo($id);
            $tagName= $tagInfo['tag_name'];
            $tagColor= $tagInfo['color'];
            $tagArr[$key]['tag_name'] = $tagName;
            $tagArr[$key]['color'] = $tagColor;
        }
        return $tagArr;
    }
}
