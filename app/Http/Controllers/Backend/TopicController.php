<?php

namespace App\Http\Controllers\Backend;

use App\Activity;
use App\Game;
use App\Tag;
use Illuminate\Http\Request;
use App\Topic;
use App\AdminUser;
use App\TopicLink;
use App\Log;


class TopicController extends Controller
{
    function __construct(Request $request)
    {
        $this->pathArr = array('topicList');
        parent::__construct($request);
    }

    //活动列表
    function lists(Request $request)
    {
        $p = $request->input('p')??1;
        $perPage = $request->input('per_page')??15;
        $key = $request->input('key');
        $topic = new Topic();
        $topics = $topic->where(function ($query) use ($key) {
            $key && $query->where('name', 'like', '%' . $key . '%');
        })->where('status', 1)->orderBy('level', 'asc')->paginate($perPage, ['id', 'name', 'description', 'url', 'level', 'click', 'uid', 'created_at', 'picture'], 'p', $p);
        $admin = new AdminUser();
        foreach ($topics as $key => $topic) {
            $topics[$key]['adminUser'] = $admin->getUsername($topic->uid);
        }
        return json_encode($topics, JSON_UNESCAPED_UNICODE);
    }

    //新增活动
    function add(Request $request)
    {
        $userId = $this->userId;
        $this->validate($request, [
            'name' => 'required',
            //'is_link' => 'required',
            //'url' => 'required',
            //'content'=>'required'
            //'type' => 'required',
            //'item_ids' => 'required',
        ]);
        $name = $request->input('name');
        //$isLink = $request->input('is_link');
        $fields = $request->all();
        $fields['uid'] = $userId;
//        if ($isLink == 1) {
//            //链接
//            $topic = Topic::create($fields);
//        } else {
        //活动/游戏

        $topic = Topic::create($fields);
        $topicId = $topic->id;
        //$itemIds = $request->input('item_ids');
        //$type = $request->input('type');
        //if (!empty($itemIds)) {
        //$itemIdArr = explode(',', $itemIds);
        if ($name !== '排行榜') {
            $this->validate($request, [
                'content' => 'required',
            ]);
        }
        $content = $request->input('content');
        if(!empty($content)){
            $tpArr = json_decode($content);
            $topicLink = new TopicLink();
            foreach ($tpArr as $key => $tpItem) {
                if ($tpItem->type == 'game') {
                    $game = new Game();
                    $arr = $game->setGameInfo($tpItem->item_id,$topicId);
                    $arr['level'] = $key;
                } elseif ($tpItem->type == 'activity') {
                    $activity = new Activity();
                    $arr = $activity->setActivityInfo($tpItem->item_id,$topicId);
                    $arr['level'] = $key;
                }
                $topic = $topicLink->create($arr);
            }
        }
        Topic::find($topic['topic_id'])->update(array('url'=>'http://xinhua.test.qyuedai.com/topicLinkList?topic_id='.$topic['topic_id']));
        $description = '添加活动专题' . $name;
        if ($topic) {
            $msg['success'] = 1;
            $msg['data'] = $topic;
            $msg['msg'] = $description . '成功';
            $type = 1;
        } else {
            $msg['success'] = 0;
            $msg['msg'] = $description . '失败';
            $type = 0;
        }
        $log = new Log();
        $log->addLog($this->userId, $description, $msg['success'], $type);
        return json_encode($msg, JSON_UNESCAPED_UNICODE);
    }

    //修改活动
    function edit(Request $request)
    {
        $this->validate($request, [
            'topic_id' => 'required',
            //'is_link' => 'required',
        ]);
        $topicId = $request->input('topic_id');
        $fields = $request->all();
        //$isLink = $request->input('is_link');
        $tp = new Topic();
        $topicLink = new TopicLink();
        $info = $tp->info($topicId);
        $description = '修改活动专题' . $info->name;
        $topic = Topic::find($topicId)->update($fields);
        $content = $request->input('content');
        if(!empty($content)){
            $tpArr = json_decode($content);
            //删除
            TopicLink::where('topic_id', $topicId)->delete();
            foreach ($tpArr as $key => $tpItem) {
                if ($tpItem->type == 'game') {
                    $game = new Game();
                    $arr = $game->setGameInfo($tpItem->item_id,$topicId);
                    $arr['level'] = $key;
                } elseif ($tpItem->type == 'activity') {
                    $activity = new Activity();
                    $arr = $activity->setActivityInfo($tpItem->item_id,$topicId);
                    $arr['level'] = $key;
                }
                $topic = $topicLink->create($arr);
            }
        }
        if ($topic) {
            $msg['success'] = 1;
            $msg['data'] = $topic;
            $msg['msg'] = $description . '成功';
            $type = 1;
        } else {
            $msg['success'] = 0;
            $msg['msg'] = $description . '失败';
            $type = 0;
        }
        $log = new Log();
        $log->addLog($this->userId, $description, $msg['success'], $type);
        return json_encode($msg, JSON_UNESCAPED_UNICODE);
    }


    //删除活动
    function del(Request $request)
    {
        $this->validate($request, [
            'topic_id' => 'required',
        ]);
        $id = $request->input('topic_id');
        $topic = new Topic();
        $info = $topic->info($id);
        $description = '删除活动专题' . $info->name;
        if ($topic->del($id)) {
            $msg['success'] = 1;
            $msg['msg'] = $description . '成功';
            $type = 1;
        } else {
            $msg['success'] = 0;
            $msg['msg'] = $description . '失败';
            $type = 0;
        }
        $log = new Log();
        $log->addLog($this->userId, $description, $msg['success'], $type);
        return json_encode($msg, JSON_UNESCAPED_UNICODE);
    }

    //活动详情
    function info(Request $request)
    {
        $this->validate($request, [
            'topic_id' => 'required',
        ]);
        $topicId = $request->input('topic_id');
        $topic = new Topic();
        $info = $topic->info($topicId);
        $topicArr[0]['name'] = $info->name;
        $topicArr[0]['description'] = $info->description;
        $topicArr[0]['url'] = $info->url;
        //$topicArr[0]['is_link'] = $info->is_link;
        //if ($info->is_link == 0) {
            $topicLink = new TopicLink();
            $linkList = $topicLink->lists($topicId);
            $itemList = array();
        $tag = new Tag();
            foreach ($linkList as $key => $value) {
                $itemList[$key]['picture'] = $value->picture;
                $itemList[$key]['name'] = $value->name;
                $itemList[$key]['url'] = $value->url;
                $itemList[$key]['description'] = $value->description;
                $itemList[$key]['tag_id'] = $value->tag_id;
                $itemList[$key]['tag_info'] = $tag->getTagNames($value->tag_id);
                $itemList[$key]['type'] = $value->type;
            }
        //}
        $topicArr[0]['itemList'] = $itemList;
        return json_encode($topicArr, JSON_UNESCAPED_UNICODE);
    }

    //点击专题
    function addClick(Request $request)
    {
        $this->validate($request, [
            'topic_id' => 'required',
        ]);
        $topicId = $request->input('topic_id');
        $topic = new Topic();
        if ($topic->addClick($topicId)) {
            $msg['success'] = 0;
            $msg['msg'] = '增加点击次数成功';
        } else {
            $msg['success'] = 0;
            $msg['msg'] = '增加点击次数失败';
        }
        return json_encode($msg, JSON_UNESCAPED_UNICODE);
    }


}
