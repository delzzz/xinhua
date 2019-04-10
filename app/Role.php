<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use PhpParser\Node\Expr\AssignOp\Mod;

class Role extends Model
{
    use SoftDeletes;
    protected $table = 'role';
    protected $primaryKey='rid';
    protected $fillable = ['rname','description'];
    protected $dates = ['deleted_at'];


    public function modules(){
        return $this->belongsToMany('App\Module','role_module','mid','rid')->as('role_module')->withTimestamps();
    }

    //角色列表
    function lists(){
        $roleList =  Role::all();
        return $roleList;
    }

    //角色信息
    function info($rid){
        return Role::find($rid);
    }

    //检查角色名
    function checkRoleName($roleName,$rid = null){
        if (!empty($rid)) {
            $count = Role::where('rname', $roleName)->where('rid','<>',$rid)->count();
        } else {
            $count = Role::where('rname', $roleName)->count();
        }
        if($count>0){
            return true;
        }
        else{
            return false;
        }
    }

    //添加修改角色
    function add($rid,$fields){
        return $flag = Role::updateOrCreate(
            ['rid' => $rid],
            $fields
        );
    }

    //删除角色
    function del($rid)
    {
        $idArr = explode(',',$rid);
        foreach ($idArr as $key => $value){
            $flag = Role::find($value)->delete();
        }
        return $flag;
    }

    //角色的模块列表
    function moduleList($rid){
        $moduleList = \DB::table('role_module')->join('module','role_module.mid','=','module.mid')->where('rid',$rid)->orderBy('module.mid','asc')->orderBy('level','asc')->get();
        $roleModuleList = array();
        if(!empty($moduleList)){
            foreach ($moduleList as $key => $value){
                $module = Module::find($value->mid);
                $roleModuleList[$key]['rmid'] = $value->rmid;
                $roleModuleList[$key]['mid'] = $value->mid;
                $roleModuleList[$key]['mod_name'] = $module->mod_name;
                $roleModuleList[$key]['mod_pid'] = $module->mod_pid;
                $roleModuleList[$key]['mod_url'] = $module->mod_url;
                $roleModuleList[$key]['interface_url'] = $module->interface_url;
                $roleModuleList[$key]['flag'] = $module->flag;
                $roleModuleList[$key]['parent_name'] = $module->parent_name;
            }
            return $roleModuleList;
        }
        else{
            return null;
        }
    }

    //添加修改角色模块
    function addModule($rid,$modules){
        //删除
        \DB::table('role_module')->where('rid',$rid)->delete();
        //添加
        $moduleArr = explode(',',$modules);
        foreach ($moduleArr as $key => $mid){
            $flag = \DB::table('role_module')->insertGetId(['rid'=>$rid,'mid'=>$mid,'created_at'=>date('Y-m-d H:i:s',time())]);
        }
        return $flag;
    }

    //根据rid查出角色名
    function getRoleName($rid){
        $role = Role::where('rid',$rid)->first();
        return $role['rname'];
    }

    //查询角色是否有某权限
    function hasModule($rid,$mid){
        $count = \DB::table('role_module')->where(['rid'=>$rid,'mid'=>$mid])->count();
        if($count>0){
            return true;
        }
        else{
            return false;
        }
    }

}
