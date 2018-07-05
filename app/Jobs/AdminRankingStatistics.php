<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;


class AdminRankingStatistics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //后台-游戏参与总人数
        Redis::del('adminGameTotalRanking_seven');
        Redis::del('adminGameTotalRanking_thirty');
        $adminGameTotalsSeven = \DB::select('SELECT DATE(created_at) as created_at,COUNT(DISTINCT(user_id)) as total FROM user_game  WHERE DATE_SUB(CURDATE(), INTERVAL 7 DAY) <= DATE(created_at) AND created_at < CURDATE() GROUP BY DATE(created_at)');
        foreach ($adminGameTotalsSeven as $adminGameTotal) {
            Redis::Zadd('adminGameTotalRanking_seven',$adminGameTotal->total,strtotime($adminGameTotal->created_at));
        }
        $adminGameTotalsThirty = \DB::select('SELECT DATE(created_at) as created_at,COUNT(DISTINCT(user_id)) as total FROM user_game  WHERE DATE_SUB(CURDATE(), INTERVAL 30 DAY) <= DATE(created_at) AND created_at < CURDATE() GROUP BY DATE(created_at)');
        foreach ($adminGameTotalsThirty as $adminGameTotal) {
            Redis::Zadd('adminGameTotalRanking_thirty',$adminGameTotal->total,strtotime($adminGameTotal->created_at));
        }

        //后台-活动参与总人数
        Redis::del('adminActivityTotalRanking_seven');
        Redis::del('adminActivityTotalRanking_thirty');
        $adminActivityTotalsSeven = \DB::select('SELECT DATE(created_at) AS created_at,COUNT(DISTINCT(user_id)) AS total FROM user_activity  WHERE DATE_SUB(CURDATE(), INTERVAL 7 DAY) <= DATE(created_at) AND created_at < CURDATE() GROUP BY DATE(created_at)');
        foreach($adminActivityTotalsSeven as $adminActivityTotal){
            Redis::zadd('adminActivityTotalRanking_seven', $adminActivityTotal->total, strtotime($adminActivityTotal->created_at));
        }
        $adminActivityTotalsThirty = \DB::select('SELECT DATE(created_at) AS created_at,COUNT(DISTINCT(user_id)) AS total FROM user_activity  WHERE DATE_SUB(CURDATE(), INTERVAL 30 DAY) <= DATE(created_at) AND created_at < CURDATE() GROUP BY DATE(created_at)');
        foreach($adminActivityTotalsThirty as $adminActivityTotal){
            Redis::zadd('adminActivityTotalRanking_thirty', $adminActivityTotal->total, strtotime($adminActivityTotal->created_at));
        }


        //后台-新增用户统计
        Redis::del('newAddUser_thirty');
        Redis::del('newAddUser_seven');
        $newAddUserTotalsSeven= \DB::select('SELECT DATE(created_at) AS created_at ,COUNT(DISTINCT(id)) AS total FROM `user` WHERE DATE_SUB(CURDATE(), INTERVAL 7 DAY) <= DATE(created_at) AND created_at < CURDATE() GROUP BY DATE(created_at)');
        foreach ($newAddUserTotalsSeven as $newAddUserTotal) {
            Redis::Zadd('newAddUser_seven',$newAddUserTotal->total,strtotime($newAddUserTotal->created_at));
        }
        $newAddUserTotalsThirty = \DB::select('SELECT DATE(created_at) AS created_at ,COUNT(DISTINCT(id)) AS total FROM `user` WHERE DATE_SUB(CURDATE(), INTERVAL 30 DAY) <= DATE(created_at) AND created_at < CURDATE() GROUP BY DATE(created_at)');
        foreach ($newAddUserTotalsThirty as $newAddUserTotal) {
            Redis::Zadd('newAddUser_thirty',$newAddUserTotal->total,strtotime($newAddUserTotal->created_at));
        }


    }
}
