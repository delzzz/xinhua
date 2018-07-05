<?php

namespace App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Curl;


class UserWeixin extends Model
{
    use SoftDeletes;
    protected $table = 'user_weixin';
    protected $fillable = ['uid','openid','expires_in','access_token','refresh_token','scope','headimgurl','nickname','sex','province','city','country','latitude','longitude','location_update','unionid'];
    protected $dates = ['deleted_at'];

    //获取access_token
    function get_access_token($appId,$appSecret,$code){
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$appId.'&secret='.$appSecret.'&code='.$code.'&grant_type=authorization_code';
        $curl = new Curl();
        $data = $curl->curl($url);
        return json_decode($data,true);
    }

    //获取用户信息
    function get_user_info($access_token,$openid){
        $url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid;
        $curl = new Curl();
        $info = $curl->curl($url);
        return json_decode($info,true);
    }

    //根据unionid判断是否填过
    function is_exist($unionid){
        $count = UserWeixin::where(['unionid'=>$unionid,'deleted_at'=>null])->count();
        if($count>0){
            return true;
        }
        else{
            return false;
        }
    }

    //根据unionid判断有没有uid
    function is_regitered($unionid){
        $info = UserWeixin::where(['unionid'=>$unionid,'deleted_at'=>null])->first();
        if($info->uid>0 && !empty($info->uid)){
            return $info->uid;
        }
        else{
            return false;
        }
    }

    //根据unionid修改信息
    function updateByUnionid($unionid,$arr){
        $flag = UserWeixin::where(['unionid'=>$unionid,'deleted_at'=>null])->update($arr);
        if ($flag){
            return true;
        }
        else{
            return false;
        }
    }

    //插入信息
    function insertInfo($arr){
        $flag = UserWeixin::create($arr);
        if ($flag){
            return true;
        }
        else{
            return false;
        }
    }

    //jsapi获取access_token
    function js_get_access_token($appId,$appSecret){
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appId.'&secret='.$appSecret;
        $curl = new Curl();
        $data = $curl->curl($url);
        return json_decode($data,true);
    }

    //jsapi获取签名
    function js_get_sign($access_token){
        $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.$access_token.'&type=jsapi';
        $curl = new Curl();
        $data = $curl->curl($url);
        return json_decode($data,true);
    }
}
