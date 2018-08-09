<?php
/**
 * backend
 */

//管理员列表
Route::get('/adminList', 'AdminUserController@lists');
//管理员信息
Route::get('/adminInfo', 'AdminUserController@info');
//管理员添加
Route::post('/addAdmin', 'AdminUserController@addAdmin');
//管理员修改信息
Route::post('/editAdmin', 'AdminUserController@editAdmin');
//管理员重置密码
Route::post('/editAdminPassword', 'AdminUserController@editPassword');
//管理员修改自己密码
Route::post('/changePassword', 'AdminUserController@changePassword');
//启用禁用管理员
Route::post('/changeStatus', 'AdminUserController@changeStatus');
//删除管理员
Route::post('/delAdmin', 'AdminUserController@del');
Route::get('/logOut','AdminUserController@logOut');


Route::group(['middleware' => ['web']], function () {
    //管理员登录
    Route::post('/adminLogin', 'AdminUserController@login');
    //获取验证码
    Route::get('/getVerifyCode', 'AdminUserController@getVerifyCode');
});


//模块列表
Route::get('/moduleList', 'ModuleController@lists');
//模块添加
Route::post('/addModule', 'ModuleController@add');
//模块删除
Route::post('/delModule', 'ModuleController@del');

//部门列表
Route::get('/departmentList', 'DepartmentController@lists');
//部门详情
Route::get('/departmentInfo', 'DepartmentController@info');
//部门添加
Route::post('/addDepartment', 'DepartmentController@add');
//部门删除
Route::post('/delDepartment', 'DepartmentController@del');

//角色列表
Route::get('/roleList', 'RoleController@lists');
//角色信息
Route::get('/roleInfo', 'RoleController@info');
//角色添加修改
Route::post('/addRole', 'RoleController@add');
//角色删除
Route::post('/delRole', 'RoleController@del');
//角色模块列表
Route::get('/roleModuleList', 'RoleController@moduleList');


//用户列表
Route::get('/userList', 'UserController@lists');
//启用禁用用户
Route::post('/changeUserStatus', 'UserController@changeUserStatus');
//用户信息
Route::get('/userInfo', 'UserController@info');
//删除用户
Route::post('/delUser', 'UserController@del');
//重置密码
Route::post('editUserPassword','UserController@editPassword');

//banner列表
Route::get('/bannerList', 'BannerController@lists');
//banner详情
Route::get('/bannerInfo', 'BannerController@info');
//新增或修改banner
Route::post('/addBanner', 'BannerController@add');
//删除banner
Route::post('/delBanner', 'BannerController@del');
//点击广告次数增加
Route::get('/bannerAddClick', 'BannerController@addClick');
//上下移菜单
Route::post('/changeBannerLevel', 'BannerController@changeLevel');

//菜单列表
Route::get('/channelList', 'ChannelController@lists');
//菜单详情
Route::get('/channelInfo', 'ChannelController@info');
//新增或修改菜单
Route::post('/addChannel', 'ChannelController@add');
//删除菜单
Route::post('/delChannel', 'ChannelController@del');
//上下移菜单
Route::post('/changeChannelLevel', 'ChannelController@changeLevel');

//活动列表
Route::get('/topicList', 'TopicController@lists');
//添加活动
Route::post('/addTopic', 'TopicController@add');
//修改活动
Route::post('/editTopic', 'TopicController@edit');
//活动详情
Route::get('/topicInfo', 'TopicController@info');
//删除活动专题
Route::post('/delTopic', 'TopicController@del');
//点击专题次数增加
Route::get('/topicAddClick', 'TopicController@addClick');
//所有游戏列表
Route::get('/allGameList', 'GameController@allList');
//所有活动列表
Route::get('/allActivityList', 'ActivityController@allList');


//活动列表
Route::get('/activityList', 'ActivityController@lists');
//活动详情
Route::get('/activityInfo', 'ActivityController@info');
//新增或修改活动
Route::post('/addActivity', 'ActivityController@add');
//改变活动状态
Route::post('/changeActivityStatus', 'ActivityController@changeStatus');
//删除活动
Route::post('/delActivity', 'ActivityController@del');

//游戏列表
Route::get('/gameList', 'GameController@lists');
//游戏详情
Route::get('/gameInfo', 'GameController@info');
//新增或修改游戏
Route::post('/addGame', 'GameController@add');
//改变游戏状态
Route::post('/changeGameStatus', 'GameController@changeStatus');
//删除游戏
Route::post('/delGame', 'GameController@del');

//标签列表
Route::get('/tagList','TagController@lists');
//添加标签
Route::post('/addTag','TagController@add');
//删除标签
Route::post('/delTag','TagController@del');
//标签详情
Route::get('/tagInfo','TagController@info');

//图片上传
Route::post('/uploadImg', 'PictureController@upload');

//文章列表
Route::get('/articleList', 'ArticleController@lists');
//文章添加
Route::post('/addArticle', 'ArticleController@add');
//文章点击次数增加
Route::get('/articleAddClick', 'ArticleController@addClick');
//文章详情
Route::get('/articleInfo', 'ArticleController@info');
//删除文章
Route::post('/delArticle', 'ArticleController@del');


//首页-游戏/活动七天/三十天总人数统计
Route::get('/userAttendTotal', 'RankingController@userAttendTotal');
//首页-新增用户统计
Route::get('/newAddUser', 'RankingController@newAddUser');
//首页-地区分数榜
Route::get('/regionScoreTotal','RankingController@regionScoreTotal');
//用户来源统计
Route::get('/userSource','RankingController@userSource');
//游戏数据统计
Route::get('/gameStatistics','RankingController@gameStatistics');
//活动统计
Route::get('/activityStatistics','RankingController@activityStatistics');
//个人分数排行
Route::get('/userScoreList','RankingController@userScoreList');
//游戏排行
Route::get('/gameTotalList','RankingController@gameTotalList');

//日志列表
Route::get('/logList','LogController@lists');

Route::get('/test','LogController@insertData');


