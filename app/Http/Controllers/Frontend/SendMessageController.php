<?php

namespace App\Http\Controllers\Frontend;

use Aliyun;
use App\Sms;
use Illuminate\Filesystem\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache as C;

class SendMessageController extends Controller
{
    //发送短信
    function send(Request $request)
    {
        $this->validate($request, [
            'mobile' => 'required',
        ]);
        $code = rand(1000, 9999);
        $mobile = $request->input('mobile');
        $response = Sms::sendSms($mobile, $code);
        if ($response->Code == 'OK') {
            C::put($mobile, $code, 15);
            $response->success = 1;
        } else {
            $msg['success'] = 0;
            $msg['msg'] = '发送失败';
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }

}
