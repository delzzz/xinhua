<?php

namespace App\Http\Controllers\Backend;

use App\Activity;
use App\Game;
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
        })->orderBy('level', 'asc')->paginate($perPage, ['id', 'name', 'description', 'url', 'level', 'click', 'is_link', 'uid', 'created_at', 'picture'], 'p', $p);
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
            'is_link' => 'required',
            'url' => 'required'
        ]);
        $name = $request->input('name');
        $isLink = $request->input('is_link');
        $fields = $request->all();
        $fields['uid'] = $userId;
        if ($isLink == 1) {
            //链接
            $topic = Topic::create($fields);
        } else {
            //活动/游戏
            $this->validate($request, [
                'type' => 'required',
                'item_ids' => 'required',
            ]);
            $topic = Topic::create($fields);
            $topicId = $topic->id;
            $itemIds = $request->input('item_ids');
            $type = $request->input('type');
            if (!empty($itemIds)) {
                $itemIdArr = explode(',', $itemIds);
                $topicLink = new TopicLink();
                foreach ($itemIdArr as $key => $itemid) {
                    if ($type == 'game') {
                        $game = new Game();
                        $gameInfo = $game->getInfo($itemid);
                        $arr['topic_id'] = $topicId;
                        $arr['itemid'] = $itemid;
                        $arr['name'] = $gameInfo->name;
                        $arr['picture'] = $gameInfo->picture;
                        $arr['type'] = 'game';
                        $arr['status'] = $gameInfo->status;
                        $arr['url'] = $gameInfo->url;
                        $arr['description'] = $gameInfo->description;
                        $arr['level'] = $key;
                    } elseif ($type == 'activity') {
                        $activity = new Activity();
                        $activityInfo = $activity->getInfo($itemid);
                        $arr['topic_id'] = $topicId;
                        $arr['itemid'] = $itemid;
                        $arr['name'] = $activityInfo->name;
                        $arr['picture'] = $activityInfo->picture;
                        $arr['type'] = 'activity';
                        $arr['status'] = $activityInfo->status;
                        $arr['url'] = $activityInfo->url;
                        $arr['description'] = $activityInfo->description;
                        $arr['level'] = $key;
                    }
                    $topic = $topicLink->create($arr);
                }
            }
        }
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
            'is_link' => 'required',
        ]);
        $topicId = $request->input('topic_id');
        $fields = $request->all();
        $isLink = $request->input('is_link');
        $tp = new Topic();
        $info = $tp->info($topicId);
        $description = '修改活动专题' . $info->name;
        $topic = Topic::find($topicId)->update($fields);
        if ($isLink == 1) {
            //链接
            //删除
            TopicLink::where('topic_id', $topicId)->delete();
        } else {
            //活动/游戏
            $itemIds = $request->input('item_ids');
            $type = $request->input('type');
            //原来的itemIds
            $oldItemIds = TopicLink::where('topic_id', $topicId)->pluck('itemid');
            $oldType = TopicLink::where('topic_id', $topicId)->value('type');
            $oldItemStr = '';
            foreach ($oldItemIds as $oldItemId) {
                $oldItemStr .= $oldItemId . ',';
            }
            if (!empty($itemIds) && !empty($type)) {
                if (substr($oldItemStr, 0, strlen($oldItemStr) - 1) == $itemIds && $oldType == $type) {
                    $topic = true;
                } else {
                    //删除
                    TopicLink::where('topic_id', $topicId)->delete();
                    $itemIdArr = explode(',', $itemIds);
                    $topicLink = new TopicLink();
                    foreach ($itemIdArr as $key => $itemid) {
                        $arr['topic_id'] = $topicId;
                        $arr['itemid'] = $itemid;
                        if ($type == 'game') {
                            $game = new Game();
                            $gameInfo = $game->getInfo($itemid);
                            $arr['name'] = $gameInfo->name;
                            $arr['picture'] = $gameInfo->picture;
                            $arr['type'] = 'game';
                            $arr['status'] = $gameInfo->status;
                            $arr['url'] = $gameInfo->url;
                            $arr['description'] = $gameInfo->description;
                            $arr['level'] = $key;
                        } elseif ($type == 'activity') {
                            $activity = new Activity();
                            $activityInfo = $activity->getInfo($itemid);
                            $arr['name'] = $activityInfo->name;
                            $arr['picture'] = $activityInfo->picture;
                            $arr['type'] = 'activity';
                            $arr['status'] = $activityInfo->status;
                            $arr['url'] = $activityInfo->url;
                            $arr['description'] = $activityInfo->description;
                            $arr['level'] = $key;
                        }
                        $topic = $topicLink->create($arr);
                    }
                }
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
        $topicArr[0]['is_link'] = $info->is_link;
        if ($info->is_link == 0) {
            $topicLink = new TopicLink();
            $linkList = $topicLink->lists($topicId);
            $itemList = array();
            foreach ($linkList as $key => $value) {
                $itemList[$key]['picture'] = $value->picture;
                $itemList[$key]['name'] = $value->name;
                $itemList[$key]['url'] = $value->url;
                $itemList[$key]['description'] = $value->description;
                $itemList[$key]['type'] = $value->type;
            }
        }
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
