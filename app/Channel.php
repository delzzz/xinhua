<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Channel extends Model
{
    use SoftDeletes;
    protected $table = 'menu';
    protected $fillable = ['name', 'pid', 'icon', 'url', 'level'];
    protected $dates = ['deleted_at'];

    //菜单列表
    function lists($pid){
        if(isset($pid)){
            $channel = Channel::where(['pid' => $pid])->orderBy('level', 'asc')->get();
        }
        else{
            $channel = Channel::orderBy('pid','asc')->orderBy('level','asc')->get();
        }
        return $channel;
    }

    //删除菜单
    function del($id)
    {
        $idArr = explode(',',$id);
        foreach ($idArr as $key => $value){
            $flag = Channel::find($value)->delete();
        }
        return $flag;
    }

    function changeLevel($id,$type){
        $info = Channel::find($id);
        $list = Channel::where('pid',$info->pid)->orderBy('level','asc')->get();
        $channel = array();
        foreach ($list as $key => $value){
            $channel[$key] = $value->id;
        }
        $index = array_search($id,$channel);
            if($type==1){
                if($index==0){
                    return false;
                }
                //上移
                $prev = $channel[$index-1];
                $channel[$index] = $prev;
                $channel[$index-1] =  $id;
            }
            else{
                if($index == count($channel)-1){
                    return false;
                }
                //下移
                $next = $channel[$index+1];
                $channel[$index] = $next;
                $channel[$index+1] =  $id;
            }
            foreach ($channel as $level => $cid){
               Channel::where('id',$cid)->update(['level'=>$level]);
            }
            return true;
    }

    //详情
    function getInfo($id){
        return Channel::find($id);
    }
}
