<?php

namespace App\Http\Controllers\Backend;

use App\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Activity;
use App\AdminUser;


class ActivityController extends Controller
{
    function __construct(Request $request)
    {
        $this->pathArr=array('activityList','allActivityList');
        parent::__construct($request);
    }

    //添加/修改活动
    function add(Request $request)
    {
        $userId = $this->userId;
        $fields = $request->all();
        $id = $request->input('id');
        $appId = $request->input('appid');
        $activity = new Activity();
        if(!$id){
            $this->validate($request, [
                'name' => 'required',
                'picture' => 'required',
                'url' => 'required',
                'appid' => 'required',
                'appsecret' => 'required'
            ]);
            //appId重复
            if($activity->checkAppID($appId)){
                $msg['success'] = -1;
                $msg['msg'] = 'appID重复';
                return json_encode($msg, JSON_UNESCAPED_UNICODE);
            }
            $fields['uid'] = $userId;
            $description = '添加H5链接'.$request->input('name');
        }
        else{
            //appId重复
            if($activity->checkAppID($appId,$id)){
                $msg['success'] = -1;
                $msg['msg'] = 'appID重复';
                return json_encode($msg, JSON_UNESCAPED_UNICODE);
            }
            $info = $activity->getInfo($id);
            $description = '修改H5链接'.$info->name;
        }
        $activity = Activity::updateOrCreate(
            ['id' => $id],
            $fields
        );
        if ($activity->save()) {
            $msg['success'] = 1;
            $msg['data'] = $activity;
            $msg['msg'] = $description.'成功';
            $type=1;
        } else {
            $msg['success'] = 0;
            $msg['msg'] = $description.'失败';
            $type=0;
        }
        $log = new Log();
        $log->addLog($this->userId,$description,$msg['success'],$type);
        return json_encode($msg, JSON_UNESCAPED_UNICODE);
    }

    //活动列表
    function lists(Request $request)
    {
        $p = $request->input('p')??1;
        $perPage = $request->input('per_page')??15;
        $key = $request->input('key');
        $activity = new Activity();
        $activities = $activity->where(function ($query) use ($key) {
            $key && $query->where('name', 'like', '%' . $key . '%');
        })->paginate($perPage, ['id', 'name', 'picture', 'url','created_at','description','uid','status'], 'p', $p);
        $admin = new AdminUser();
        foreach ($activities as $key => $activity) {
            $activities[$key]['adminUser'] = $admin->getUsername($activity->uid);
        }
        return json_encode($activities, JSON_UNESCAPED_UNICODE);
    }

    //活动详情
    function info(Request $request){
        $this->validate($request, [
            'id' => 'required',
        ]);
        $id = $request->input('id');
        $activity = new Activity();
        $info = $activity->getInfo($id);
        return json_encode($info, JSON_UNESCAPED_UNICODE);
    }

    //改变状态
    function changeStatus(Request $request){
        $this->validate($request, [
            'id' => 'required',
            'status' => 'required',
        ]);
        $id = $request->input('id');
        $status = $request->input('status');
        $activity = new Activity();
        $info = $activity->getInfo($id);
        if($status==1){
            $description = '上架H5链接'.$info->name;
        }
        else{
            $description = '下架H5链接'.$info->name;
        }
        if($activity->changeStatus($id,$status)){
            $msg['success'] = 1;
            $msg['msg'] = $description.'成功';
            $type = 1;
        }
        else{
            $msg['success'] = 0;
            $msg['msg'] = $description.'失败';
            $type = 0;
        }
        $log = new Log();
        $log->addLog($this->userId,$description,$msg['success'],$type);
        return json_encode($msg, JSON_UNESCAPED_UNICODE);
    }

    //删除活动
    function del(Request $request){
        $this->validate($request, [
            'id' => 'required',
        ]);
        $id = $request->input('id');
        $activity = new Activity();
        $info = $activity->getInfo($id);
        $description = '删除H5链接'.$info->name;
        if($activity->del($id)){
            $msg['success'] = 1;
            $msg['msg'] = $description.'成功';
            $type = 1;
        }
        else{
            $msg['success'] = 0;
            $msg['msg'] = $description.'失败';
            $type = 0;
        }
        $log = new Log();
        $log->addLog($this->userId,$description,$msg['success'],$type);
        return json_encode($msg,JSON_UNESCAPED_UNICODE);
    }

    //所有列表
    function allList(){
        $activity = new Activity();
        $activityList = array();
        $activities = $activity->getList();
        //$admin = new AdminUser();
        foreach ($activities as $key => $activity) {
            $activityList[$key]['id'] = $activity->id;
            $activityList[$key]['name'] = $activity->name;
            $activityList[$key]['picture'] = $activity->picture;
            $activityList[$key]['url'] = $activity->url;
            $activityList[$key]['description'] = $activity->description;
            //$activityList[$key]['adminUser'] = $admin->getUsername($activity->uid);
        }
        return json_encode($activityList, JSON_UNESCAPED_UNICODE);
    }

}
