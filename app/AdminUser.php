<?php

namespace App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class AdminUser extends Model
{
    use SoftDeletes;
    protected $table = 'admin_user';
    protected $primaryKey = 'uid';
    protected $fillable = ['username','email','mobile','real_name','status','department_id','rid','last_ip','last_login'];
    protected $dates = ['deleted_at'];
    protected $hidden=['password'];

    //检查账号是否重复
    public function checkName($username,$uid = null){
        if(!empty($uid)){
            $count = AdminUser::where('username',$username)->where('uid','<>',$uid)->count();
        }
        else{
            $count = AdminUser::where('username',$username)->count();
        }
        if($count>0){
            return true;
        }
        else{
            return false;
        }
    }

    //修改密码
    function editPassword($uid,$password){
        $user = AdminUser::find($uid);
        $user->password = md5($password);
        return $user->save();
    }

    //启用禁用管理员
    function changeStatus($uid,$status){
        return AdminUser::where('uid',$uid)->update(['status'=>$status]);
    }

    //根据uid查出管理员姓名
    function getUsername($uid){
        $user = new AdminUser();
        return $user->where('uid',$uid)->first()['real_name'];
    }

    //删除管理员
    function del($uid){
        $idArr = explode(',',$uid);
        foreach ($idArr as $key => $value){
            $flag = AdminUser::find($value)->delete();
        }
        return $flag;
    }

    //管理员信息
    function info($uid){
        return AdminUser::find($uid);
    }

    //检查密码是否正确
    function checkPassword($uid,$password){
        $count = AdminUser::where(['uid'=>$uid,'password'=>md5($password)])->count();
        if($count>0){
            return true;
        }
        else{
            return false;
        }
    }
}
