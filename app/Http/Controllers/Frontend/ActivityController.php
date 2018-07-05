<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Activity;


class ActivityController extends Controller
{
//    function __construct(Request $request)
//    {
//        $this->pathArr=array('activityList');
//        parent::__construct($request);
//    }


    //活动列表
    function lists(Request $request)
    {
        $p = $request->input('p')??1;
        $perPage = $request->input('per_page')??15;
        $activities = Activity::where(['status' => 1])->orderBy('level', 'asc')->orderBy('created_at', 'desc')->paginate($perPage,['id','name','picture','url'],'p',$p);
        foreach ($activities as $key=>$activity) {
            $activityList[$key]['id'] = $activity->id;
            $activityList[$key]['name'] = $activity->name;
            $activityList[$key]['picture'] = $activity->picture;
            $activityList[$key]['url'] = $activity->url;
            $activityTotalList = Redis::Zrange('activityTotalRanking', 0, -1, 'WITHSCORES');
            $activityList[$key]['total'] = $activityTotalList[$activity->id]??0;
        }
        echo json_encode($activityList??array(), JSON_UNESCAPED_UNICODE);
    }

    //访问量添加
    function addVisit(Request $request){
        $activityId = $request->input('activity_id');
        $userAddr = $_SERVER["REMOTE_ADDR"];
        $userAgent = $request->header('user_agent');
        Redis::PFADD($activityId,$userAddr.$userAgent);
        $msg['success'] = 1;
        $msg['msg'] = '添加成功';
        return json_encode($msg,JSON_UNESCAPED_UNICODE);
    }

}
