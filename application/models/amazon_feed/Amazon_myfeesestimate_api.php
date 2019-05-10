<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Amazon_myfeesestimate_api extends CI_Model
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
  public function get_product_to_match($limit=600000,$usr)
  {
    $query=$this->db->query("SELECT pro_asin FROM product_info WHERE fee_flag='0' and pro_user={$usr}  limit 0,".$limit);
    return $query->result_array();
  }
  public function get_product_to_match_monitor($limit=600000,$usr)
  {
    $query=$this->db->query("SELECT pro_asin FROM product_info WHERE fee_flag='1' and pro_user={$usr}  limit 0,".$limit);
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
      $param['Action']=urlencode("GetMyFeesEstimate");
      $param['FeesEstimateRequestList.FeesEstimateRequest.1.MarketplaceId']=$this->market_id;
	  $param['FeesEstimateRequestList.FeesEstimateRequest.1.IdType']='ASIN';
	  $param['FeesEstimateRequestList.FeesEstimateRequest.1.IdValue']=$asin;
	  $param['FeesEstimateRequestList.FeesEstimateRequest.1.IsAmazonFulfilled']='true';
	  $param['FeesEstimateRequestList.FeesEstimateRequest.1.Identifier']='request1';
	  $param['FeesEstimateRequestList.FeesEstimateRequest.1.PriceToEstimateFees.ListingPrice.Amount']='0.00';
	  $param['FeesEstimateRequestList.FeesEstimateRequest.1.PriceToEstimateFees.ListingPrice.CurrencyCode']='USD';
	  $param['FeesEstimateRequestList.FeesEstimateRequest.1.PriceToEstimateFees.Shipping.Amount']='0.00';
	  $param['FeesEstimateRequestList.FeesEstimateRequest.1.PriceToEstimateFees.Shipping.CurrencyCode']='USD';
	  $param['FeesEstimateRequestList.FeesEstimateRequest.1.PriceToEstimateFees.Points.PointsNumber']='0';
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $this->built_query_string($param));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_TIMEOUT, 15);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
      curl_setopt($ch, CURLOPT_POST, true);
      $response = curl_exec($ch);
	  //echo"$response";
      $res = simplexml_load_string($response);
      //print_r($res);
	   
      $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $payload=[];
      $payload['lm_asin']=$payload['comp_asin']=$payload['fees_estimate']=$payload['referral_fee']=$payload['var_closing_fee']=$payload['per_item_fee']=$payload['fba_weight_handle']=$payload['fba_pick_pack']=$payload['fba_order_handle']='';
      $payload['asin_counts']=-3;
      if($httpcode != 200)
      {
        if(preg_match('/throttled/',(string)$res->GetMyFeesEstimateResult->FeesEstimateResultList->FeesEstimateResult->Error->Message))
        {
          sleep(1);
          echo "throttling occured;\n";
          $this->fetch_product_details($user_id,$asin);
        }
        
        
      }
      if(preg_match('/Invalid ASIN/',(string)$res->GetMyFeesEstimateResult->FeesEstimateResultList->FeesEstimateResult->Error->Message))
      {
        
          echo "ERROR ".(string)$res->GetMyFeesEstimateResult->FeesEstimateResultList->FeesEstimateResult->Error->Message;
          $data['status_code']=3;
          $data['status_text']="No Data";
          $payload['lm_asin']=$asin;
          $payload['asin_counts']=-3;
          $data['payload']=$payload;  
          return $data;
        //throw new Exception($res->GetMatchingProductForIdResult->Error->Message);   
      }
      
       if(isset($res->GetMyFeesEstimateResult))
      {
            $payload['lm_asin']=$asin;
	        $payload['comp_asin']= (string)$res->GetMyFeesEstimateResult->FeesEstimateResultList->FeesEstimateResult->FeesEstimateIdentifier->IdValue;
			//$payload['fees_estimate']= (string)$res->GetMyFeesEstimateResult->FeesEstimateResultList->FeesEstimateResult->FeesEstimate->TotalFeesEstimate->Amount;
	 }
		
if(preg_match_all('/<FeeDetail>\s*<FeeAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<Amount>([^>]*?)<\/Amount>\s*<\/FeeAmount>\s*<FinalFee>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<Amount>([^>]*?)<\/Amount>\s*<\/FinalFee>\s*<FeePromotion>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<Amount>[^>]*?<\/Amount>\s*<\/FeePromotion>\s*<FeeType>FBAFees<\/FeeType>/',$response,$matches)){
	   $res=$matches[1];
	   $payload['fees_estimate']=$res[0];
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
 
 public function fetch_monitor_product_details($user_id,$asin)
  { 
    try
    {
      $httpHeader=array();
      $httpHeader[]='Transfer-Encoding: chunked';
      $httpHeader[]='Content-Type: text/xml';
      $httpHeader[]='Expect:';
      $httpHeader[]='Accept:';
      $param['Action']=urlencode("GetMyFeesEstimate");
      $param['FeesEstimateRequestList.FeesEstimateRequest.1.MarketplaceId']=$this->market_id;
	  $param['FeesEstimateRequestList.FeesEstimateRequest.1.IdType']='ASIN';
	  $param['FeesEstimateRequestList.FeesEstimateRequest.1.IdValue']=$asin;
	  $param['FeesEstimateRequestList.FeesEstimateRequest.1.IsAmazonFulfilled']='true';
	  $param['FeesEstimateRequestList.FeesEstimateRequest.1.Identifier']='request1';
	  $param['FeesEstimateRequestList.FeesEstimateRequest.1.PriceToEstimateFees.ListingPrice.Amount']='0.00';
	  $param['FeesEstimateRequestList.FeesEstimateRequest.1.PriceToEstimateFees.ListingPrice.CurrencyCode']='USD';
	  $param['FeesEstimateRequestList.FeesEstimateRequest.1.PriceToEstimateFees.Shipping.Amount']='0.00';
	  $param['FeesEstimateRequestList.FeesEstimateRequest.1.PriceToEstimateFees.Shipping.CurrencyCode']='USD';
	  $param['FeesEstimateRequestList.FeesEstimateRequest.1.PriceToEstimateFees.Points.PointsNumber']='0';
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $this->built_query_string($param));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_TIMEOUT, 15);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
      curl_setopt($ch, CURLOPT_POST, true);
      $response = curl_exec($ch);
	  //echo"$response";
      $res = simplexml_load_string($response);
      //print_r($res);
	   
      $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $payload=[];
      $payload['lm_asin']=$payload['comp_asin']=$payload['fees_estimate']=$payload['referral_fee']=$payload['var_closing_fee']=$payload['per_item_fee']=$payload['fba_weight_handle']=$payload['fba_pick_pack']=$payload['fba_order_handle']='';
      $payload['asin_counts']=-3;
      if($httpcode != 200)
      {
        if(preg_match('/throttled/',(string)$res->GetMyFeesEstimateResult->FeesEstimateResultList->FeesEstimateResult->Error->Message))
        {
          sleep(1);
          echo "throttling occured;\n";
          $this->fetch_product_details_monitor($user_id,$asin);
        }
        
        
      }
      if(preg_match('/Invalid ASIN/',(string)$res->GetMyFeesEstimateResult->FeesEstimateResultList->FeesEstimateResult->Error->Message))
      {
        
          echo "ERROR ".(string)$res->GetMyFeesEstimateResult->FeesEstimateResultList->FeesEstimateResult->Error->Message;
          $data['status_code']=3;
          $data['status_text']="No Data";
          $payload['lm_asin']=$asin;
          $payload['asin_counts']=-3;
          $data['payload']=$payload;  
          return $data;
        //throw new Exception($res->GetMatchingProductForIdResult->Error->Message);   
      }
      
       if(isset($res->GetMyFeesEstimateResult))
      {
            $payload['lm_asin']=$asin;
	        $payload['comp_asin']= (string)$res->GetMyFeesEstimateResult->FeesEstimateResultList->FeesEstimateResult->FeesEstimateIdentifier->IdValue;
			//$payload['fees_estimate']= (string)$res->GetMyFeesEstimateResult->FeesEstimateResultList->FeesEstimateResult->FeesEstimate->TotalFeesEstimate->Amount;
	 }
	 
if(preg_match_all('/<FeeDetail>\s*<FeeAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<Amount>([^>]*?)<\/Amount>\s*<\/FeeAmount>\s*<FinalFee>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<Amount>([^>]*?)<\/Amount>\s*<\/FinalFee>\s*<FeePromotion>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<Amount>[^>]*?<\/Amount>\s*<\/FeePromotion>\s*<FeeType>FBAFees<\/FeeType>/',$response,$matches)){
	   $res=$matches[1];
	   $payload['fees_estimate']=$res[0];
	   print_r($payload['fees_estimate']); 
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
                  'SellerId'=> urlencode($this->seller_id),
				  'MWSAuthToken'=>urlencode($this->auth_token),
                  'SignatureMethod' => urlencode("HmacSHA256"),
                  'SignatureVersion'=> urlencode("2"),
                  'Timestamp'=>gmdate("Y-m-d\TH:i:s.\\0\\0\\0\\Z", time()),
                  'Version' => urlencode("2011-10-01"),
                  'MarketplaceId'=>$this->market_id

                 );
  
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
            return $url; 
 }
}