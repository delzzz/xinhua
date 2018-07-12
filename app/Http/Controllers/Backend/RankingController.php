<?php

namespace App\Http\Controllers\Backend;

use App\Activity;
use App\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Jobs\RankingStatistics;
use App\Jobs\GameStatistics;
use App\Jobs\ActivityStatistics;
use App\User;
use App\Game;

class RankingController extends Controller
{
    //首页-活动/游戏参与总人数
    function userAttendTotal(Request $request)
    {
        $type = $request->input('type'); //0:游戏 1:活动
        $dateType = $request->input('date_type'); //0:7天 1:30天
        $ranking = array();
        $totalArr = array();
        if ($type == 1) {
            if ($dateType == 1) {
                //活动
                $totalList = Redis::Zrange('adminActivityTotalRanking_thirty', 0, -1, 'WITHSCORES');
            } else {
                //游戏
                $totalList = Redis::Zrange('adminActivityTotalRanking_seven', 0, -1, 'WITHSCORES');
            }
        } else {
            if ($dateType == 1) {
                //30天游戏
                $totalList = Redis::Zrange('adminGameTotalRanking_thirty', 0, -1, 'WITHSCORES');
            } else {
                //7天 游戏
                $totalList = Redis::Zrange('adminGameTotalRanking_seven', 0, -1, 'WITHSCORES');
            }
        }
        if ($dateType == 1) {
            for ($i = 30; $i >= 1; $i--) {
                $dt[date('Y-m-d', strtotime("-" . $i . " day"))]=null;
            }
        } else {
            for ($i = 7; $i >= 1; $i--) {
                $dt[date('Y-m-d', strtotime("-" . $i . " day"))]=null;
            }
        }
        ksort($totalList);
        foreach ($totalList as $timestamp => $total){
            $totalArr[date('Y-m-d', $timestamp)] = $total;
        }
        $arr = array_merge($dt,$totalArr);
        foreach ($arr as $date => $total){
            $ranking['date'][] = $date;
            $ranking['total'][] = $total;
        }
        return json_encode($ranking, JSON_UNESCAPED_UNICODE);
    }

    //首页-新增用户统计
    function newAddUser(Request $request)
    {
        $type = $request->input('date_type');
        $ranking = array();
        //全部
        if ($type == 1) {
            //30天
            $newUserList = Redis::Zrange('newAddUser_thirty', 0, -1, 'WITHSCORES');
            for ($i = 30; $i >= 1; $i--) {
                $dt[date('Y-m-d', strtotime("-" . $i . " day"))] = null;
            }
        } else {
            //7天
            $newUserList = Redis::Zrange('newAddUser_seven', 0, -1, 'WITHSCORES');
            for ($i = 7; $i >= 1; $i--) {
                $dt[date('Y-m-d', strtotime("-" . $i . " day"))] = null;
            }
        }
        ksort($newUserList);
        foreach ($newUserList as $timestamp => $total){
            $totalArr[date('Y-m-d', $timestamp)] = $total;
        }
        $arr = array_merge($dt,$totalArr);
        foreach ($arr as $date => $total){
            $ranking['date'][] = $date;
            $ranking['total'][] = $total;
        }
        return json_encode($ranking, JSON_UNESCAPED_UNICODE);
    }

    //首页-地区分数榜
    function regionScoreTotal(Request $request)
    {
        $num = $request->input('num')??5;
        $totalList = Redis::Zrevrange('regionScoreRanking', 0, $num - 1, 'WITHSCORES');
        $i = 0;
        foreach ($totalList as $regionName => $score) {
            $rName = '';
            $ranking['rank'][$i] = $i + 1;
            $regionArr = explode(',', $regionName);
            foreach ($regionArr as $key => $value) {
                $rName .= $value;
            }
            $ranking['region'][$i] = $rName;
            $ranking['score'][$i] = $score;
            $i++;
        }
        return json_encode($ranking, JSON_UNESCAPED_UNICODE);
    }

    //用户来源统计
    function userSource()
    {
        $user = new User();
        return json_encode($user->userSource(), JSON_UNESCAPED_UNICODE);
    }

    //游戏数据统计
    function gameStatistics()
    {
        $totalList = Redis::Zrevrange('gameTotalRanking', 0, -1, 'WITHSCORES');
        $gameTotalList = array();
        $game = new Game();
        $i = 0;
        foreach ($totalList as $gameId => $total) {
            $info = $game->getInfo($gameId);
            $gameTotalList[$i]['game_id'] = $gameId;
            $gameTotalList[$i]['name'] = $info->name;
            $gameTotalList[$i]['total'] = $total;
            $i++;
        }
        return json_encode($gameTotalList, JSON_UNESCAPED_UNICODE);
    }

    //活动数据统计
    function activityStatistics()
    {
        $activityList = Activity::all();
        $activityTotalList = array();
        $i = 0;
        foreach ($activityList as $key => $value) {
            $activityTotalList[$i]['activity_id'] = $value['id'];
            $activityTotalList[$i]['name'] = $value['name'];
            $activityTotalList[$i]['total'] = Redis::PFCOUNT($value['id']);
            $i++;
        }
        array_multisort(array_column($activityTotalList,'total'),SORT_DESC,$activityTotalList);
        return json_encode($activityTotalList, JSON_UNESCAPED_UNICODE);
    }

    //个人分数排行
    function userScoreList()
    {
        $user = new User();
        $i = 0;
        $scoreList = Redis::Zrevrange('userScoreRanking', 0, 9, 'WITHSCORES');
        $region = new Region();
        foreach ($scoreList as $userId => $score) {
            $info = $user->getInfo($userId);
            $ranking[$i]['rank'] = $i + 1;
            $ranking[$i]['score'] = $score;
            $ranking[$i]['nickname'] = $info['nickname'];
            $ranking[$i]['region'] = $region->getFullName($info['county']);
            $i++;
        }
        return json_encode($ranking, JSON_UNESCAPED_UNICODE);
    }

    //游戏排行榜
    function gameTotalList()
    {
        $totalList = Redis::Zrevrange('gameTotalRanking', 0, 9, 'WITHSCORES');
        $scoreList = Redis::Zrevrange('gameScoreRanking', 0, -1, 'WITHSCORES');
        $game = new Game();
        $i = 0;
        foreach ($totalList as $gameId => $total) {
            $info = $game->getInfo($gameId);
            $gameTotalList[$i]['rank'] = $i + 1;
            $gameTotalList[$i]['name'] = $info->name;
            $gameTotalList[$i]['total'] = $total;
            $gameTotalList[$i]['score'] = $scoreList[$info->id];
            $i++;
        }
        return json_encode($gameTotalList, JSON_UNESCAPED_UNICODE);
    }
}
