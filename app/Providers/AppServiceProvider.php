<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Game;
use App\Activity;
use App\Post;
use App\TopicLink;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Game::created(function ($game) {
            $arr['itemid'] = $game->id;
            $arr['name'] = $game->name;
            $arr['picture'] = $game->picture;
            $arr['type'] = 'game';
            $arr['status'] = $game->status;
            $arr['url'] = $game->url;
            Post::create($arr);
        });
        Game::updated(function ($game) {
            $arr['itemid'] = $game->id;
            $arr['description'] = $game->description;
            $arr['name'] = $game->name;
            $arr['picture'] = $game->picture;
            $arr['type'] = 'game';
            $arr['status'] = $game->status;
            $arr['url'] = $game->url;
            Post::where(['type'=>'game','itemid'=>$game->id])->update($arr);
            TopicLink::where(['type'=>'game','itemid'=>$game->id])->update($arr);
        });
        Game::deleted(function ($game) {
            Post::where(['itemid'=>$game->id,'type'=>'game'])->delete();
        });
        Activity::created(function ($activity) {
            $arr['itemid'] = $activity->id;
            $arr['name'] = $activity->name;
            $arr['picture'] = $activity->picture;
            $arr['type'] = 'activity';
            $arr['status'] = $activity->status;
            $arr['url'] = $activity->url;
            Post::create($arr);
        });
        Activity::updated(function ($activity) {
            $arr['itemid'] = $activity->id;
            $arr['description'] = $activity->description;
            $arr['name'] = $activity->name;
            $arr['picture'] = $activity->picture;
            $arr['type'] = 'activity';
            $arr['status'] = $activity->status;
            $arr['url'] = $activity->url;
            Post::where(['type'=>'activity','itemid'=>$activity->id])->update($arr);
            TopicLink::where(['type'=>'activity','itemid'=>$activity->id])->update($arr);
        });
        Activity::deleted(function($activity){
           Post::where(['itemid'=>$activity->id,'type'=>'activity'])->delete();
           TopicLink::where(['itemid'=>$activity->id,'type'=>'activity'])->delete();
        });

    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
