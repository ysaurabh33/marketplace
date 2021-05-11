<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use KeithBrink\AmazonMws\AmazonFeedResult;


class AmazonMWS extends Controller
{
    public function index()
    {
        /*$feedID = 123456;
        $amz = new AmazonFeedResult("store1", $feedID);
        $amz->fetchFeedResult();
        dd($amz->saveFeed('save.txt'));*/
        
        return view('main');
    }

    public function fileupload(Request $request){
        dd($request);
    }
}
