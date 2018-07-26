<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;



class PictureController extends Controller
{
    function __construct(Request $request)
    {
        $this->pathArr=array('');
        parent::__construct($request);
    }

    //图片
    function upload(Request $request)
    {
        $path = $request->file('img')->store('uploads');
        if ($path) {
            $msg['success'] = 1;
            $msg['path'] = env("UPLOAD_HOST").$path; #asset($path);
        } else {
            $msg['success'] = 0;
        }
        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
    }


}
