<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Amazon_competitive_asin_api extends CI_Model
{
  private $seller_id='';
  private $auth_token='';
  private $access_key='';
  private $secret_key='';
  private $market_id='';
  private $service_url='mws.amazonservices.com';
  public function  __construct()
  {
   		parent::__construct();
  }

  public function set_credentials($user_id,$seller_id,$auth_token,$access_key,$secret_key,$market_placeID)
  {
        $this->seller_id=$seller_id;
        $this->auth_token=$auth_token;
        $this->access_key=$access_key;
        $this->secret_key=$secret_key;
        $this->market_id=$market_placeID;  
        return TRUE;
  }
  public function get_product_to_match($limit=600000)
  {
    $query=$this->db->query("SELECT pro_asin  FROM product_info where bb_flag=0   limit 0,".$limit);
    return $query->result_array();
  }
  public function get_product_to_match_monitor($limit=600000)
  {
    $query=$this->db->query("SELECT pro_asin  FROM product_info where bb_flag=1   limit 0,".$limit);
    return $query->result_array();
  }
  public function fetch_product_details($user_id,$asin)
  { 
    try
    {
      $httpHeader=array();
      $httpHeader[]='Transfer-Encoding: chunked';
      $httpHeader[]='Content-Type: text/xml';
      $httpHeader[]='Expect:';
      $httpHeader[]='Accept:';
      $param['Action']=urlencode("GetCompetitivePricingForASIN");
      //$param['ExcludeMe']='true';
      // $asin=str_pad($asin,13,"0",STR_PAD_LEFT);
      $param['ASINList.ASIN.1']=$asin;
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $this->built_query_string($param));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_TIMEOUT, 15);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
      curl_setopt($ch, CURLOPT_POST, true);
      $response = curl_exec($ch);
      $res = simplexml_load_string($response);
      //print_r($res);
	   
      $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $payload=[];
      $payload['lm_asin']=$payload['comp_asin']=$payload['sales_rank']='';
      $payload['asin_counts']=-3;
      if($httpcode != 200)
      {
        if(preg_match('/throttled/',(string)$res->GetCompetitivePricingForASINResult->Error->Message ))
        {
          //sleep(1);
          echo "throttling occured;\n";
          $this->fetch_product_details($user_id,$asin);
        }
        
        
      }
      if(preg_match('/Invalid ASIN/',(string)$res->GetCompetitivePricingForASINResult->Error->Message))
      {
        
          echo "ERROR ".(string)$res->GetCompetitivePricingForASINResult->Error->Message;
          $data['status_code']=3;
          $data['status_text']="No Data";
          $payload['lm_asin']=$asin;
          $payload['asin_counts']=-3;
          $data['payload']=$payload;  
          return $data;
        //throw new Exception($res->GetMatchingProductForIdResult->Error->Message);   
      }
      
       if(isset($res->GetCompetitivePricingForASINResult[0]->Product))
      {
            $payload['lm_asin']=$asin;
	        $payload['comp_asin']= (string)$res->GetCompetitivePricingForASINResult[0]->Product->Identifiers->MarketplaceASIN->ASIN;
            $payload['sales_rank']= (string)$res->GetCompetitivePricingForASINResult [0]->Product->SalesRankings->SalesRank->Rank;
   
            }
            
      if(count($payload) > 0 && !empty($payload['comp_asin']))
      {
        $data['status_code']=1;
        $data['status_text']="Success";
        $data['payload']=$payload;  
      }
      else
      {
          $data['status_code']=3;
          $data['status_text']="No Data";
          $payload['lm_asin']=$asin;
          $payload['asin_counts']=-3;
          $data['payload']=$payload;  
      }
      return $data;
    }
    catch(Exception $e) 
    {
      
      $data['status_code']=0;
      $data['status_text']=$e->getMessage();
      return $data;
    }
 }


public function fetch_product_details_monitor($user_id,$asin)
  { 
    try
    {
      $httpHeader=array();
      $httpHeader[]='Transfer-Encoding: chunked';
      $httpHeader[]='Content-Type: text/xml';
      $httpHeader[]='Expect:';
      $httpHeader[]='Accept:';
      $param['Action']=urlencode("GetCompetitivePricingForASIN");
      //$param['ExcludeMe']='true';
      // $asin=str_pad($asin,13,"0",STR_PAD_LEFT);
      $param['ASINList.ASIN.1']=$asin;
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $this->built_query_string($param));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_TIMEOUT, 15);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
      curl_setopt($ch, CURLOPT_POST, true);
      $response = curl_exec($ch);
      $res = simplexml_load_string($response);
      //print_r($res);
	   
      $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $payload=[];
      $payload['lm_asin']=$payload['comp_asin']=$payload['sales_rank']='';
      $payload['asin_counts']=-3;
      if($httpcode != 200)
      {
        if(preg_match('/throttled/',(string)$res->GetCompetitivePricingForASINResult->Error->Message ))
        {
          //sleep(1);
          echo "throttling occured;\n";
          $this->fetch_product_details($user_id,$asin);
        }
        
        
      }
      if(preg_match('/Invalid ASIN/',(string)$res->GetCompetitivePricingForASINResult->Error->Message))
      {
        
          echo "ERROR ".(string)$res->GetCompetitivePricingForASINResult->Error->Message;
          $data['status_code']=3;
          $data['status_text']="No Data";
          $payload['lm_asin']=$asin;
          $payload['asin_counts']=-3;
          $data['payload']=$payload;  
          return $data;
        //throw new Exception($res->GetMatchingProductForIdResult->Error->Message);   
      }
      
       if(isset($res->GetCompetitivePricingForASINResult[0]->Product))
      {
            $payload['lm_asin']=$asin;
	        $payload['comp_asin']= (string)$res->GetCompetitivePricingForASINResult[0]->Product->Identifiers->MarketplaceASIN->ASIN;
            $payload['sales_rank']= (string)$res->GetCompetitivePricingForASINResult [0]->Product->SalesRankings->SalesRank->Rank;
   
            }
            
      if(count($payload) > 0 && !empty($payload['comp_asin']))
      {
        $data['status_code']=1;
        $data['status_text']="Success";
        $data['payload']=$payload;  
      }
      else
      {
          $data['status_code']=3;
          $data['status_text']="No Data";
          $payload['lm_asin']=$asin;
          $payload['asin_counts']=-3;
          $data['payload']=$payload;  
      }
      return $data;
    }
    catch(Exception $e) 
    {
      
      $data['status_code']=0;
      $data['status_text']=$e->getMessage();
      return $data;
    }
 }

 
 private function built_query_string($add_param)
 {
         $params = array(
                  'AWSAccessKeyId'=> urlencode($this->access_key),
				  //'MWSAuthToken'=>urlencode($this->auth_token),
                  'SellerId'=> urlencode($this->seller_id),
                  'SignatureMethod' => urlencode("HmacSHA256"),
                  'SignatureVersion'=> urlencode("2"),
                  'Timestamp'=>gmdate("Y-m-d\TH:i:s.\\0\\0\\0\\Z", time()),
                  'Version' => urlencode("2011-10-01"),
                  'MarketplaceId'=>$this->market_id

                 );
  if(!empty($this->auth_token))
    {
      $params['MWSAuthToken']=urlencode($this->auth_token);
    }
            $params=array_merge($params,$add_param);
          $url_parts = array();
        foreach(array_keys($params) as $key)
        {
            $url_parts[] = $key . "=" . str_replace('%7E', '~', rawurlencode($params[$key]));
        }
        sort($url_parts);
            $url_string = implode("&", $url_parts);
            $string_to_sign = "POST\nmws.amazonservices.com\n/Products/2011-10-01\n" . $url_string;
            
            $signature = hash_hmac("sha256", $string_to_sign, $this->secret_key, TRUE);
            $signature = urlencode(base64_encode($signature));
            $url = "https://mws.amazonservices.com/Products/2011-10-01?". $url_string . "&Signature=" . $signature;
			echo"uuuuu:$url";
            return $url; 
 }
}