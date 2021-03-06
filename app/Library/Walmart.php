<?php

namespace App\Library;

use Config;

class Walmart
{
    // Name of Store in config
    // Can be upgraded to multipule by accepting through constructor
    protected String $store = "store1";

    // Authorization
    private String $auth;

    // A unique ID which identifies each API call and used to track and debug issues
    private $correlationID;

    // Generated Token to access methodologies
    private String $token;

    // URL
    private String $URL;

    // Feed method
    private String $feedType;

    /**
     * Basic Setup wit config
     * @param void
     */
    public function __construct()
    {
        $config = Config::get('walmart')[$this->store];
        $this->debug = $config['debug'];
        $this->URL = $this->debug === TRUE ? "https://sandbox.walmartapis.com/v3" : "https://marketplace.walmartapis.com/v3"; 

        // Generate Authorization
        $this->auth = base64_encode($config['ClientID'].":".$config['SecretKey']);

        // Generate Random Correlation ID 
        // NOTE: Correlation should be unique for based on particular token i.e generated by fn uniqid()
        $this->correlationID = $config['CorrelationID'];

        // Hardcoded Header
        $header = array('Authorization: Basic '.$this->auth,
                        'Content-Type: application/x-www-form-urlencoded',
                        'Accept: application/json',
                        'WM_SVC.NAME: Walmart Marketplace',
                        'WM_QOS.CORRELATION_ID: '. $this->correlationID,
                        'WM_SVC.VERSION: 1.0.0'
                    );
        
        $response = $this->request($header, 'post', '/token', 'grant_type=client_credentials');
        
        // if response is successfull
        if(isset($response['access_token']))
        {
            $this->token = $response['access_token'];
        }
        else
        {
            // Redirect to 404
            dd("Authentication error on Walmart API");
        }
        
    }

    /**
     * To create feed XML as per Quantity, Price and Lag Time (Handling Time)
     * @param Array $data
     * @param String $type
     * @return String $fileTxt
     * @throws void $fileTxt
     */
    public function create_feed(Array $data, String $type)
    {
        $fileTxt = "";

        if(count($data) > 10000)
        {
            return;
        }

        if($type === 'Quantity')
        {
            $this->feedType = "inventory";
            
            $inventoryArray = [];
            foreach($data as $col)
            {
                $inventoryArray[] = ["sku" => $col[0],"quantity" => ["unit" => "EACH","amount" => $col[1]]];
            }

            $fileTxt = json_encode(["InventoryHeader" => ["version" => "1.4"],"Inventory" => $inventoryArray]);
            
        }
        else if ($type === 'Price')
        {
            $this->feedType = "price";
            
            $priceArray = [];
            foreach($data as $col)
            {
                $priceArray[] = ["sku" => $col[0],"pricing" => [["currentPrice" => ["currency" => "USD","amount" => $col[1]]]]];
            }

            $fileTxt = json_encode(["PriceHeader" => ["version" => "1.7"],"Price" => $priceArray]);

        }
        else if ($type === 'Lag')
        {
            $this->feedType = "lagtime";
            
            $lagArray = [];
            foreach($data as $col)
            {
                $lagArray[] = [
                    "sku" => $col[0],
                    "fulfillmentLagTime" => $col[1]
                ];
            }

            $fileTxt = json_encode(["LagTimeHeader" => [ "version" => "1.0"],"lagTime" => $lagArray]);
        }
        else
        {
            return;
        }

        return $fileTxt;
    }

    /**
     * Send Feed File
     * NOTE: !! file path can be replace with valid String of JSON
     * @param String $filepath
     * @param Array $response
     */
    public function send_feed(String $filepath)
    {
        $header = array('Authorization: Basic ' . $this->auth,
                        'Content-Type: application/json',
                        'Accept: application/json',
                        'Host:marketplace.walmartapis.com',
                        'WM_SVC.NAME: Walmart Marketplace',
                        'WM_QOS.CORRELATION_ID: '.$this->correlationID,
                        'WM_SVC.VERSION: 1.0.0',
                        'WM_SEC.ACCESS_TOKEN: ' . $this->token
                    );
        
        $response = $this->request($header, 'POST', '/feeds?feedType='.$this->feedType, file_get_contents($filepath));
        return $response;
    }

    /**
     * Fetch feed Response
     * @param String $feedid
     * @return Array $response
     * @throws null
     */
    public function get_feed_result(String $feedid)
    {
        $header = array('Authorization: Basic ' . $this->auth,
                        'Accept: application/json',
                        'Host:marketplace.walmartapis.com',
                        'WM_SVC.NAME: Walmart Marketplace',
                        'WM_QOS.CORRELATION_ID: '.$this->correlationID,
                        'WM_SVC.VERSION: 1.0.0',
                        'WM_SEC.ACCESS_TOKEN: ' . $this->token
                    );
        
        /*$response = $this->request($header, 'GET', '/feeds/'.$feedid.'?includeDetails=true&limit=1000&offset=0');
        dd($response);
        return $response;*/

        $totalRows = 2;
        $limit = 1000;
        $totalPages = ceil($totalRows / $limit);

        $data = [];
        for($i = 0; $i < $totalPages; $i++)
        {
            $offset = $i == 0 ? 0 : ($i * $limit) + 1;
            $response = $this->request($header, 'GET', '/feeds/'.$feedid.'?includeDetails=true&limit='.$limit.'&offset='.$offset);
            
            if($response['feedStatus'] != "PROCESSED")
            {
                return null;
            }

            $data['feedId'] = $response['feedId'];
            $data['feedStatus'] = $response['feedStatus'];
            $data['total'] = $response['itemsReceived'];
            $data['success'] = $response['itemsSucceeded'];
            $data['itemsFailed'] = $response['itemsFailed'];
            if(isset($data['itemDetails']))
            {
                $data['itemDetails'] += $response['itemDetails']['itemIngestionStatus'];
            }
            else
            {
                $data['itemDetails'] = $response['itemDetails']['itemIngestionStatus'];
            }
        }
        return $data;
    }

    /**
     * Make cURL request to walmart
     * @param Array $header
     * @param String $method
     * @param String $endpoint
     * @param Array $data -- can be void for GET
     * @return Array $response
     * @throws String $error_msg
     */
    private function request($header, $method, $endpoint, $data = array())
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->URL.$endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if(!empty($data))
        {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $result = curl_exec($ch);
        $error_msg = curl_error($ch);
        curl_close($ch);

        $response = json_decode($result, TRUE);

        return (!empty($response)) ? $response : $error_msg;
    }
}