<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Banner extends Model
{
    use SoftDeletes;
    protected $table = 'banner';
    protected $fillable = ['name','picture', 'url', 'level','click','uid'];
    protected $dates = ['deleted_at'];

    //banner名
    function getInfo($id){
        return Banner::find($id);
    }

    //banner列表
    function lists(){
        $bannerList =  Banner::orderBy('level','asc')->orderBy('created_at','desc')->get();
        return $bannerList;
    }

    //删除banner
    function del($id){
        $idArr = explode(',',$id);
        foreach ($idArr as $key => $value){
            $flag = Banner::find($value)->delete();
        }
        return $flag;
    }

    //增加点击次数
    function addClick($id){
        return Banner::where('id',$id)->increment('click',1);
    }

    //上下移排序
    function changeLevel($id,$type){
        $list = Banner::orderBy('level','asc')->get();
        $banner = array();
        foreach ($list as $key => $value){
            $banner[$key] = $value->id;
        }
        $index = array_search($id,$banner);
        if($type==1){
            if($index==0){
                return false;
            }
            //上移
            $prev = $banner[$index-1];
            $banner[$index] = $prev;
            $banner[$index-1] =  $id;
        }
        else{
            if($index == count($banner)-1){
                return false;
            }
            //下移
            $next = $banner[$index+1];
            $banner[$index] = $next;
            $banner[$index+1] =  $id;
        }
        foreach ($banner as $level => $bid){
            Banner::where('id',$bid)->update(['level'=>$level]);
        }
        return true;
    }
}
