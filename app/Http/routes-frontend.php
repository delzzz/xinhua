<?php
/**
 * frontend
 */

//用户注册
Route::post('/register','UserController@register');
//用户登录
Route::post('/login','UserController@login');
//忘记密码
Route::post('/changePassword','UserController@changePassword');
//修改密码
Route::post('/editPassword','UserController@loginChangePassword');
//检查验证码
Route::post('/verifyCode','UserController@verifyCode');
//发送验证码
Route::post('/sendMessage','SendMessageController@send');
//检查用户名
Route::post('/checkUsername','UserController@checkUsername');


//地区列表
Route::get('/regionList','RegionController@lists');

//banner列表
Route::get('/bannerList','BannerController@lists');


//菜单列表
Route::get('/channelList','ChannelController@lists');
//活动列表
Route::get('/topicList','TopicController@lists');
//活动专题名称列表
Route::get('/topicNameList','TopicController@topicNameList');
//活动专题列表
Route::get('/topicLinkList','TopicController@topicList');


//首页游戏活动列表
Route::get('/indexList','GameController@indexList');
//所有游戏列表
Route::get('/gameList','GameController@lists');
//所有活动列表
Route::get('/activityList','ActivityController@lists');



//用户轨迹
Route::get('/userGameList','UserController@userGameList');
//获取用户信息
Route::get('/userInfo','UserController@info');
//修改用户信息
Route::post('/editUserInfo','UserController@editInfo');
//头像上传
Route::post('/uploadAvatar','UserController@uploadAvatar');
//用户排行（游戏积分列表）
Route::get('/gameScoreList','UserController@gameScoreList');
//用户游戏分数添加
Route::post('/addScore','UserController@addScore');
//用户参与活动
Route::post('/attendActivity','UserController@attendActivity');


//redis设置总排行榜
Route::get('/setRanking','RankingController@setRanking');
//redis设置单个游戏排行榜
Route::get('/setGameRanking','RankingController@setGameRanking');
//redis设置后台数据
Route::get('/setAdminRanking','RankingController@setAdminRanking');

//地区人数总榜
Route::post('/getRegionTotal','RankingController@getRegionTotal');
//地区分数总榜
Route::post('/getRegionScore','RankingController@getRegionScore');
//用户分数总榜
Route::post('/getUserScore','RankingController@getUserScore');
//游戏人数总榜
Route::post('/getGameTotal','RankingController@getGameTotal');
//用户游戏分数榜
Route::post('/getGameUserScore','RankingController@getGameUserScore');
//地区游戏人数榜
Route::post('/getGameRegionTotal','RankingController@getGameRegionTotal');
//地区游戏分数榜
Route::post('/getGameRegionScore','RankingController@getGameRegionScore');

//总排行榜-获取地区人数总榜-自己的排行
Route::get('/userRankRegionTotal','UserController@userRankRegionTotal');
//总排行榜-获取地区分数总榜-自己的排行
Route::get('/userRankRegionScore','UserController@userRankRegionScore');
//总排行榜-获取用户分数总榜-自己的排行
Route::get('/userRankUserScore','UserController@userRankUserScore');
//游戏排行榜-获取用户分数榜自己的排行
Route::get('/userRankScore','UserController@userRankScore');
//游戏排行榜-获取地区人数榜-自己的排行
Route::get('/userRankGameRegion','UserController@userRankGameRegion');
//游戏排行榜-获取地区分数榜-自己的排行
Route::get('/userRankGameScore','UserController@userRankGameScore');



//微信接口
Route::get('/weixinGetInfo','WeixinController@getInfo');
Route::get('/weixinOfficialGetInfo','WeixinController@serve');
Route::get('/getSign','WeixinController@getSign');

//访问活动
Route::get('addActivityVisit','ActivityController@addVisit');

////微信公众号
//Route::any('/wechat','WeixinController@serve');
//Route::group(['middleware' => ['wechat.oauth']], function () {
//    Route::get('/test','WeixinController@test');
//
//    Route::get('/user','WeixinController@serve');
//
//    Route::get('/users', function () {
//        $user = session('wechat.oauth_user'); // 拿到授权用户资料
//        dd($user);
//    });
//});