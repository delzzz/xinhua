<?php

namespace App\Http\Controllers\Backend;

use App\AdminUser;
use App\Article;
use Illuminate\Http\Request;
use App\Log;
use Illuminate\Support\Facades\Redis;


class ArticleController extends Controller
{
    function __construct(Request $request)
    {
        $this->pathArr=array('articleList');
        parent::__construct($request);
    }

    //文章列表
    function lists(Request $request)
    {
        $p = $request->input('p')??1;
        $perPage = $request->input('per_page')??15;
        $key = $request->input('key');
        $admin = new AdminUser();
        $articles = Article::join('admin_user','article.uid','admin_user.uid')
            ->where(function($query) use($key){
                $key && $query->where('title', 'like', '%' . $key . '%')->orWhere('real_name', 'like', '%' . $key . '%');
            })->orderBy('created_at','desc')
            ->paginate($perPage, ['aid', 'title', 'picture','remark','article.created_at','click','article.uid'], 'p', $p);
        foreach ($articles as $key => $article){
            $articles[$key]['adminUser'] = $admin->getUsername($article->uid);
            $articles[$key]['url'] = env('ARTICLE_URL').$article->aid;
        }
        return json_encode($articles,JSON_UNESCAPED_UNICODE);
    }

    //文章详情
    function info(Request $request){
        $this->validate($request, [
            'aid' => 'required',
        ]);
        $aid = $request->input('aid');
        $article = new Article();
        $info = $article->getInfo($aid);
        $admin = new AdminUser();
        $info['adminUser'] = $admin->getUsername($info->uid);
        return json_encode($info,JSON_UNESCAPED_UNICODE);
    }

    //添加修改文章
    function add(Request $request){
        $userId = $this->userId;
        $fields = $request->all();
        $aid = $request->input('aid');
        if(!$aid){
            $this->validate($request, [
                'title' => 'required',
                //'picture' => 'required',
                'content' => 'required',
            ]);
            $fields['uid'] = $userId;
            $description = '添加文章'.$request->input('title');
        }
        else{
            $article = new Article();
            $info = $article->getInfo($aid);
            $description = '修改文章'.$info->title;
        }
        $fields['content'] = urldecode($fields['content']);
        $article = Article::updateOrCreate(
            ['aid' => $aid],
            $fields
        );
        if ($article) {
            $msg['success'] = 1;
            $msg['msg'] = $description.'成功';
            $msg['data'] = $article;
            $type = 1;
        } else {
            $msg['success'] = 0;
            $msg['msg'] = $description.'失败';
            $type = 0;
        }
        $log = new Log();
        $log->addLog($this->userId,$description,$msg['success'],$type);
        return json_encode($msg,JSON_UNESCAPED_UNICODE);
    }

    //添加点击次数
    function addClick(Request $request){
        $this->validate($request, [
            'aid' => 'required',
        ]);
        $aid = $request->input('aid');
        $article = new Article();
        if($article->addClick($aid)){
            $msg['success'] = 1;
            $msg['msg'] = '增加点击次数成功';
        }
        else{
            $msg['success'] = 0;
            $msg['msg'] = '增加点击次数失败';
        }
        return json_encode($msg,JSON_UNESCAPED_UNICODE);
    }

    //删除文章
    function del(Request $request){
        $this->validate($request, [
            'id' => 'required',
        ]);
        $id = $request->input('id');
        $article = new Article();
        $info = $article->getInfo($id);
        $description = '删除文章'.$info->title;
        if ($article->del($id)){
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

}
