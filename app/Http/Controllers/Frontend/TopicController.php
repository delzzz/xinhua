<?php

namespace App\Http\Controllers\Frontend;

use App\TopicLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Topic;


class TopicController extends Controller
{
    function __construct(Request $request)
    {

    }

    //活动列表
    function lists(Request $request)
    {
        $p = $request->input('p')??1;
        $perPage = $request->input('per_page')??15;
        $topic = new Topic();
        $topicList = array();
        $topics = $topic->orderBy('level','asc')->paginate($perPage, ['id','name', 'url','is_link'], 'p', $p);
        foreach ($topics as $key => $topic) {
            $topicList[$key]['id'] = $topic->id;
            $topicList[$key]['name'] = $topic->name;
            $topicList[$key]['url'] = $topic->url;
            $topicList[$key]['is_link'] = $topic->is_link;
        }
        echo json_encode($topicList, JSON_UNESCAPED_UNICODE);
    }

    //活动专题名称列表
    function topicNameList(Request $request){
        $this->validate($request, [
            'topic_id' => 'required',
        ]);
        $topicId = $request->input('topic_id');
        $topicList = array();
        $topicArr = TopicLink::where(['topic_id'=>$topicId,'status'=>1])->orderBy('level','asc')->orderBy('created_at','desc')->get();
        foreach ($topicArr as $key => $value){
            $topicList[$key]['id'] = $value->itemid;
            $topicList[$key]['name'] = $value->name;
            $topicList[$key]['url'] = $value->url;
        }
        echo json_encode($topicList, JSON_UNESCAPED_UNICODE);
    }

    //活动专题列表
    function topicList(Request $request){
        $this->validate($request, [
            'topic_id' => 'required',
        ]);
        $p = $request->input('p')??1;
        $perPage = $request->input('per_page')??15;
        $topicId = $request->input('topic_id');
        $topicList = array();
        $topicArr = TopicLink::where(['topic_id'=>$topicId,'status'=>1])->orderBy('level','asc')->orderBy('created_at','desc')->paginate($perPage, ['id', 'name', 'picture','url','itemid','type'], 'p', $p);
        foreach ($topicArr as $key => $value){
            $topicList[$key]['id'] = $value->itemid;
            $topicList[$key]['name'] = $value->name;
            $topicList[$key]['picture'] = $value->picture;
            $topicList[$key]['url'] = $value->url;
            $topicList[$key]['type'] = $value->type;
            if ($value->type == 'game') {
                $gameList = Redis::Zrange('gameTotalRanking', 0, -1, 'WITHSCORES');
                $total = $gameList[$value->itemid]??0;
            }
            if ($value->type == 'activity') {
                $activityList = Redis::Zrange('activityTotalRanking', 0, -1, 'WITHSCORES');
                $total = $activityList[$value->itemid]??0;
            }
            $topicList[$key]['total'] = $total;
        }
        echo json_encode($topicList, JSON_UNESCAPED_UNICODE);
    }

}
