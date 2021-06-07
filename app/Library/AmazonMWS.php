<?php

namespace App\Library;

use KeithBrink\AmazonMws\AmazonFeed;
use KeithBrink\AmazonMws\AmazonFeedResult;
use KeithBrink\AmazonMws\AmazonFeedList;

class AmazonMWS
{
    Protected $store = "store1";

    public function send_feed($path, $feedType = "_POST_FLAT_FILE_PRICEANDQUANTITYONLY_UPDATE_DATA_")
    {
        $amz = new AmazonFeed($this->store);
        $amz->loadFeedFile($path);
        $amz->setFeedType($feedType);
        
        if($amz->submitFeed() !== false)
        {
            return $amz->getResponse();    
        }
        else
        {
            return [];
        }
    }

    public function get_feed_result($feedid)
    {
        return "
        Feed Processing Summary:
        
        
        \tNumber of records processed\t\t2
        
        
        \tNumber of records successful\t\t2
        
        
        
        ";
        /*$response = '';
        $amz = new AmazonFeedResult($this->store, $feedid);
        if($amz->fetchFeedResult() !== false)
        {
            $response = $amz->getRawFeed();
        }
        return $response;*/
    }
}