<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Process_finance_api extends CI_Model
{
  private $seller_id='';
  private $auth_token='';
  private $access_key='';
  private $secret_key='';
  private $market_id='';  
  private $ch = '';
  public function  __construct()
  {
      parent::__construct();
  }

  public function get_seller_for_process($user_id='')
  {
    $sql="SELECT profile_id,prf.seller_id,auth_token,access_key,secret_key,amz_code,cnt.country_code,country_name,mws_url,amz_code FROM amazon_profile AS prf
          INNER JOIN seller_country_mapping AS cnt ON ";
            if(!empty($user_id))
            {
              $sql.=" profile_id=".$this->db->escape($user_id)." AND ";  
            }
    $sql.=" prf.is_active=1 AND cnt.seller_id=profile_id
    INNER JOIN supported_country AS spt ON spt.country_code=cnt.country_code AND spt.is_active=1 ORDER BY profile_id ASC";
    $query=$this->db->query($sql);
    return $query->result_array();
  }

  public function set_credentials($usr)
  {
        $this->seller_id=$usr['seller_id'];
        $this->auth_token=$usr['auth_token'];
        $this->access_key=$usr['access_key'];
        $this->secret_key=$usr['secret_key'];
        $this->market_id=$usr['amz_code'];  
        $this->mws_site=$usr['mws_url'];
        $this->ch = curl_init();
        return TRUE;
  }
  public function get_product_to_match($user_id,$country_code)
  {
    $sql="SELECT prod_id,order_id,sales_channel FROM rep_orders_data_order_date_list where user_id=".$user_id." AND ord_status='Shipped' and sales_channel=".$this->db->escape($country_code)." LIMIT 0,2000";
    $query=$this->db->query($sql);
    return $query->result_array();
  }
  
  public function fetch_product_details($user_id,$order_id,$amz_country_code,$country_code)
  { 
    try
    {
      $param['Action']=urlencode("ListFinancialEvents");
      //$param['IdType']='ISBN';
      $param['AmazonOrderId']=$order_id;
      //$param['MarketplaceId']=$amz_country_code;

      $curl_res=$this->create_curl_request($param);
      if($curl_res['status_code']==0)
      {
        throw new Exception($curl_res['status_text']);   
      }
      
        $response=$curl_res['payload'];
		//print_r($response);
      $res = simplexml_load_string($curl_res['payload']);
    
      $payload=[];

      $payload['order_id']=$payload['principal']=$payload['tax']=$payload['giftwrap']=$payload['giftwraptax']=$payload['shippingcharge']=$payload['shippingtax']=$payload['fbafee']=$payload['commission']=$payload['fixedclosingfee']=$payload['giftwrapchargeback']=
	  $payload['shippingchargeback']=$payload['variableclosingfee']=$payload['sku']=$payload['itemid']=$payload['marketplace']=$payload['qty']=$payload['posted_date']='';      
	  $payload['asin_counts']=-3;
      
      $namespaces = $res->getNamespaces(true);
      $httpcode = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
      if($httpcode != 200)
      {
        if(preg_match('/throttled/',(string)$res->Error->Message))
        {
          sleep(10);
          echo "throttling occured;\n";
          $this->fetch_product_details($user_id,$order_id,$amz_country_code,$country_code);
        }
      }
      if(preg_match('/Invalid/',(string)$res->Error->Message))
      {
        
          echo "ERROR ".(string)$res->ListFinancialEventsResult->Error->Message;
          $data['status_code']=3;
          $data['status_text']="No Data";
          $payload['lm_ean']=$order_id;
          $payload['asin_counts']=-3;
          $payload['lm_asin']='';
          $data['payload']=$payload;  
          return $data;
        //throw new Exception($res->GetMatchingProductForIdResult->Error->Message);   
      }
	  
	   if(preg_match_all('/<ChargeComponent>\s*<ChargeType>Principal<\/ChargeType>\s*<ChargeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/ChargeAmount>\s*<\/ChargeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
		//print_r($res);
	    $payload['principal']=$res[0];
		}
		else
		{
		$payload['principal']='0.00';	
		}
		if(preg_match_all('/<ChargeComponent>\s*<ChargeType>Tax<\/ChargeType>\s*<ChargeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/ChargeAmount>\s*<\/ChargeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
		//print_r($res);
	    $payload['tax']=$res[0];
		}
		else
		{
		$payload['tax']='0.00';	
		}
		if(preg_match_all('/<ChargeComponent>\s*<ChargeType>GiftWrap<\/ChargeType>\s*<ChargeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/ChargeAmount>\s*<\/ChargeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
		//print_r($res);
	    $payload['giftwrap']=$res[0];
		}
		else
		{
		$payload['giftwrap']='0.00';	
		}
		if(preg_match_all('/<ChargeComponent>\s*<ChargeType>GiftWrapTax<\/ChargeType>\s*<ChargeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/ChargeAmount>\s*<\/ChargeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
		//print_r($res);
	    $payload['giftwraptax']=$res[0];
		}
		else
		{
		$payload['giftwraptax']='0.00';	
		}
		if(preg_match_all('/<ChargeComponent>\s*<ChargeType>ShippingCharge<\/ChargeType>\s*<ChargeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/ChargeAmount>\s*<\/ChargeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
		//print_r($res);
	    $payload['shippingcharge']=$res[0];
		}
		else
		{
		$payload['shippingcharge']='0.00';	
		}
		if(preg_match_all('/<ChargeComponent>\s*<ChargeType>ShippingTax<\/ChargeType>\s*<ChargeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/ChargeAmount>\s*<\/ChargeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
		//print_r($res);
	    $payload['shippingtax']=$res[0];
		}
		else
		{
		$payload['shippingtax']='0.00';	
		}
		
		if(preg_match_all('/<ChargeComponent>\s*<ChargeType>MarketplaceFacilitatorTax-Principal<\/ChargeType>\s*<ChargeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/ChargeAmount>\s*<\/ChargeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
		//print_r($res);
	    $payload['market_facilatortax_principal']=$res[0];
		}
		else
		{
		$payload['market_facilatortax_principal']='0.00';	
		}
		
		if(preg_match_all('/<ChargeComponent>\s*<ChargeType>MarketplaceFacilitatorTax-Shipping<\/ChargeType>\s*<ChargeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/ChargeAmount>\s*<\/ChargeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
		//print_r($res);
	    $payload['market_facilatortax_shipping']=$res[0];
		}
		else
		{
		$payload['market_facilatortax_shipping']='0.00';	
		}
		
		if(preg_match_all('/<ChargeComponent>\s*<ChargeType>MarketplaceFacilitatorTax-Giftwrap<\/ChargeType>\s*<ChargeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/ChargeAmount>\s*<\/ChargeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
		//print_r($res);
	    $payload['market_facilatortax_giftwrap']=$res[0];
		}
		else
		{
		$payload['market_facilatortax_giftwrap']='0.00';	
		}
		if(preg_match_all('/<ChargeComponent>\s*<ChargeType>MarketplaceFacilitatorTax-Other<\/ChargeType>\s*<ChargeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/ChargeAmount>\s*<\/ChargeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
		//print_r($res);
	    $payload['market_facilatortax_other']=$res[0];
		}
		else
		{
		$payload['market_facilatortax_other']='0.00';	
		}
		if(preg_match_all('/<ChargeComponent>\s*<ChargeType>TaxDiscount<\/ChargeType>\s*<ChargeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/ChargeAmount>\s*<\/ChargeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
		//print_r($res);
	    $payload['taxdiscount']=$res[0];
		}
		else
		{
		$payload['taxdiscount']='0.00';	
		}
		if(preg_match_all('/<ChargeComponent>\s*<ChargeType>CODItemCharge<\/ChargeType>\s*<ChargeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/ChargeAmount>\s*<\/ChargeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
		//print_r($res);
	    $payload['cod_item_charge']=$res[0];
		}
		else
		{
		$payload['cod_item_charge']='0.00';	
		}
		if(preg_match_all('/<ChargeComponent>\s*<ChargeType>CODItemTaxCharge<\/ChargeType>\s*<ChargeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/ChargeAmount>\s*<\/ChargeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
		//print_r($res);
	    $payload['cod_item_tax_charge']=$res[0];
		}
		else
		{
		$payload['cod_item_tax_charge']='0.00';	
		}
		if(preg_match_all('/<ChargeComponent>\s*<ChargeType>CODOrderCharge<\/ChargeType>\s*<ChargeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/ChargeAmount>\s*<\/ChargeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
		//print_r($res);
	    $payload['cod_order_charge']=$res[0];
		}
		else
		{
		$payload['cod_order_charge']='0.00';	
		}
		
		if(preg_match_all('/<ChargeComponent>\s*<ChargeType>CODOrderTaxCharge<\/ChargeType>\s*<ChargeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/ChargeAmount>\s*<\/ChargeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
		//print_r($res);
	    $payload['cod_order_tax_charge']=$res[0];
		}
		else
		{
		$payload['cod_order_tax_charge']='0.00';	
		}
		
	  if(preg_match_all('/<ChargeComponent>\s*<ChargeType>CODShippingCharge<\/ChargeType>\s*<ChargeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/ChargeAmount>\s*<\/ChargeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
		//print_r($res);
	    $payload['cod_shipping_charge']=$res[0];
		}
		else
		{
		$payload['cod_shipping_charge']='0.00';	
		}
		if(preg_match_all('/<ChargeComponent>\s*<ChargeType>CODShippingTaxCharge<\/ChargeType>\s*<ChargeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/ChargeAmount>\s*<\/ChargeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
		//print_r($res);
	    $payload['cod_shipping_tax_charge']=$res[0];
		}
		else
		{
		$payload['cod_shipping_tax_charge']='0.00';	
		}
		
		if(preg_match_all('/<ChargeComponent>\s*<ChargeType>Goodwill<\/ChargeType>\s*<ChargeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/ChargeAmount>\s*<\/ChargeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
		//print_r($res);
	    $payload['good_will']=$res[0];
		}
		else
		{
		$payload['good_will']='0.00';	
		}
		if(preg_match_all('/<ChargeComponent>\s*<ChargeType>RestockingFee<\/ChargeType>\s*<ChargeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/ChargeAmount>\s*<\/ChargeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
		//print_r($res);
	    $payload['restocking_fee']=$res[0];
		}
		else
		{
		$payload['restocking_fee']='0.00';	
		}
		if(preg_match_all('/<ChargeComponent>\s*<ChargeType>ReturnShipping<\/ChargeType>\s*<ChargeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/ChargeAmount>\s*<\/ChargeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
		//print_r($res);
	    $payload['return_shipping']=$res[0];
		}
		else
		{
		$payload['return_shipping']='0.00';	
		}
		
		if(preg_match_all('/<ChargeComponent>\s*<ChargeType>PointsFee<\/ChargeType>\s*<ChargeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/ChargeAmount>\s*<\/ChargeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
		//print_r($res);
	    $payload['points_fee']=$res[0];
		}
		else
		{
		$payload['points_fee']='0.00';	
		}
		
		if(preg_match_all('/<ChargeComponent>\s*<ChargeType>GenericDeduction<\/ChargeType>\s*<ChargeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/ChargeAmount>\s*<\/ChargeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
		//print_r($res);
	    $payload['generic_deduction']=$res[0];
		}
		else
		{
		$payload['generic_deduction']='0.00';	
		}
		if(preg_match_all('/<ChargeComponent>\s*<ChargeType>FreeReplacementReturnShipping<\/ChargeType>\s*<ChargeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/ChargeAmount>\s*<\/ChargeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
		//print_r($res);
	    $payload['free_replace_ret_shipping']=$res[0];
		}
		else
		{
		$payload['free_replace_ret_shipping']='0.00';	
		}
		if(preg_match_all('/<ChargeComponent>\s*<ChargeType>PaymentMethodFee<\/ChargeType>\s*<ChargeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/ChargeAmount>\s*<\/ChargeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
		//print_r($res);
	    $payload['payment_method_fee']=$res[0];
		}
		else
		{
		$payload['payment_method_fee']='0.00';	
		}
		if(preg_match_all('/<ChargeComponent>\s*<ChargeType>ExportCharge<\/ChargeType>\s*<ChargeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/ChargeAmount>\s*<\/ChargeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
		//print_r($res);
	    $payload['export_charge']=$res[0];
		}
		else
		{
		$payload['export_charge']='0.00';	
		}
		if(preg_match_all('/<ChargeComponent>\s*<ChargeType>SAFE-TReimbursement<\/ChargeType>\s*<ChargeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/ChargeAmount>\s*<\/ChargeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
		//print_r($res);
	    $payload['safe_t_claim']=$res[0];
		}
		else
		{
		$payload['safe_t_claim']='0.00';	
		}
		if(preg_match_all('/<ChargeComponent>\s*<ChargeType>TCS-CGST<\/ChargeType>\s*<ChargeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/ChargeAmount>\s*<\/ChargeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
		//print_r($res);
	    $payload['tcs_cgst']=$res[0];
		}
		else
		{
		$payload['tcs_cgst']='0.00';	
		}
		if(preg_match_all('/<ChargeComponent>\s*<ChargeType>TCS-SGST<\/ChargeType>\s*<ChargeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/ChargeAmount>\s*<\/ChargeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
		//print_r($res);
	    $payload['tcs_sgst']=$res[0];
		}
		else
		{
		$payload['tcs_sgst']='0.00';	
		}
		if(preg_match_all('/<ChargeComponent>\s*<ChargeType>TCS-IGST<\/ChargeType>\s*<ChargeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/ChargeAmount>\s*<\/ChargeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
		//print_r($res);
	    $payload['tcs_igst']=$res[0];
		}
		else
		{
		$payload['tcs_igst']='0.00';	
		}
		if(preg_match_all('/<ChargeComponent>\s*<ChargeType>TCS-UTGST<\/ChargeType>\s*<ChargeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/ChargeAmount>\s*<\/ChargeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
		//print_r($res);
	    $payload['tcs_utgst']=$res[0];
		}
		else
		{
		$payload['tcs_utgst']='0.00';	
		}
		
		
        if(preg_match_all('/<FeeComponent>\s*<FeeType>FBAPerUnitFulfillmentFee<\/FeeType>\s*<FeeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/FeeAmount>\s*<\/FeeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
		//print_r($res);
	    $payload['fbafee']=$res[0];
		
		}
		else
		{
		$payload['fbafee']='0.00';
		}
		if(preg_match_all('/<FeeComponent>\s*<FeeType>Commission<\/FeeType>\s*<FeeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/FeeAmount>\s*<\/FeeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
		//print_r($res);
	    $payload['commission']=$res[0];
		
		}
		else
		{
		$payload['commission']='0.00';
		}
		if(preg_match_all('/<FeeComponent>\s*<FeeType>FixedClosingFee<\/FeeType>\s*<FeeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/FeeAmount>\s*<\/FeeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
		//print_r($res);
	    $payload['fixedclosingfee']=$res[0];
		
		}
		else
		{
		$payload['fixedclosingfee']='0.00';
		}
		if(preg_match_all('/<FeeComponent>\s*<FeeType>GiftwrapChargeback<\/FeeType>\s*<FeeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/FeeAmount>\s*<\/FeeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
		//print_r($res);
	    $payload['giftwrapchargeback']=$res[0];
		
		}
		else
		{
		$payload['giftwrapchargeback']='0.00';
		}
		if(preg_match_all('/<FeeComponent>\s*<FeeType>ShippingChargeback<\/FeeType>\s*<FeeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/FeeAmount>\s*<\/FeeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
	    $payload['shippingchargeback']=$res[0];
		}
		else
		{
		$payload['shippingchargeback']='0.00';
		}
		if(preg_match_all('/<FeeComponent>\s*<FeeType>VariableClosingFee<\/FeeType>\s*<FeeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/FeeAmount>\s*<\/FeeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
	    $payload['variableclosingfee']=$res[0];
		}
		else
		{
		$payload['variableclosingfee']='0.00';
		}
		if(preg_match_all('/<FeeComponent>\s*<FeeType>BubblewrapFee<\/FeeType>\s*<FeeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/FeeAmount>\s*<\/FeeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
	    $payload['bubble_wrap_fee']=$res[0];
	    }
		else
		{
		$payload['bubble_wrap_fee']='0.00';
		}
		if(preg_match_all('/<FeeComponent>\s*<FeeType>FBACustomerReturnPerOrderFee<\/FeeType>\s*<FeeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/FeeAmount>\s*<\/FeeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
	    $payload['fba_cus_ret_per_order_fee']=$res[0];
	    }
		else
		{
		$payload['fba_cus_ret_per_order_fee']='0.00';
		}
		if(preg_match_all('/<FeeComponent>\s*<FeeType>FBACustomerReturnPerUnitFee<\/FeeType>\s*<FeeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/FeeAmount>\s*<\/FeeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
	    $payload['fba_cus_ret_per_unit_fee']=$res[0];
	    }
		else
		{
		$payload['fba_cus_ret_per_unit_fee']='0.00';
		}
		if(preg_match_all('/<FeeComponent>\s*<FeeType>FBACustomerReturnWeightBasedFee<\/FeeType>\s*<FeeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/FeeAmount>\s*<\/FeeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
	    $payload['fba_cus_ret_weightbased_fee']=$res[0];
	    }
		else
		{
		$payload['fba_cus_ret_weightbased_fee']='0.00';
		}
		if(preg_match_all('/<FeeComponent>\s*<FeeType>FBADisposalFee<\/FeeType>\s*<FeeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/FeeAmount>\s*<\/FeeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
	    $payload['fba_disposal_fee']=$res[0];
	    }
		else
		{
		$payload['fba_disposal_fee']='0.00';
		}
		if(preg_match_all('/<FeeComponent>\s*<FeeType>FBAFulfillmentCODFee<\/FeeType>\s*<FeeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/FeeAmount>\s*<\/FeeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
	    $payload['fba_fulfil_cod_fee']=$res[0];
	    }
		else
		{
		$payload['fba_fulfil_cod_fee']='0.00';
		}
		if(preg_match_all('/<FeeComponent>\s*<FeeType>FBAInboundConvenienceFee<\/FeeType>\s*<FeeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/FeeAmount>\s*<\/FeeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
	    $payload['fba_inb_con_fee']=$res[0];
	    }
		else
		{
		$payload['fba_inb_con_fee']='0.00';
		}
		if(preg_match_all('/<FeeComponent>\s*<FeeType>FBAInboundDefectFee<\/FeeType>\s*<FeeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/FeeAmount>\s*<\/FeeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
	    $payload['fba_inb_def_fee']=$res[0];
	    }
		else
		{
		$payload['fba_inb_def_fee']='0.00';
		}
		if(preg_match_all('/<FeeComponent>\s*<FeeType>FBAInboundTransportationFee<\/FeeType>\s*<FeeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/FeeAmount>\s*<\/FeeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
	    $payload['fba_inb_transport_fee']=$res[0];
	    }
		else
		{
		$payload['fba_inb_transport_fee']='0.00';
		}
		if(preg_match_all('/<FeeComponent>\s*<FeeType>FBAInboundTransportationProgramFee<\/FeeType>\s*<FeeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/FeeAmount>\s*<\/FeeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
	    $payload['fba_inb_transport_program_fee']=$res[0];
	    }
		else
		{
		$payload['fba_inb_transport_program_fee']='0.00';
		}
		if(preg_match_all('/<FeeComponent>\s*<FeeType>FBALongTermStorageFee<\/FeeType>\s*<FeeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/FeeAmount>\s*<\/FeeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
	    $payload['fba_longterm_storage_fee']=$res[0];
	    }
		else
		{
		$payload['fba_longterm_storage_fee']='0.00';
		}
		if(preg_match_all('/<FeeComponent>\s*<FeeType>FBAOverageFee<\/FeeType>\s*<FeeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/FeeAmount>\s*<\/FeeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
	    $payload['fba_overage_fee']=$res[0];
	    }
		else
		{
		$payload['fba_overage_fee']='0.00';
		}
		if(preg_match_all('/<FeeComponent>\s*<FeeType>FBAPerOrderFulfillmentFee<\/FeeType>\s*<FeeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/FeeAmount>\s*<\/FeeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
	    $payload['fba_perorder_fulfill_fee']=$res[0];
	    }
		else
		{
		$payload['fba_perorder_fulfill_fee']='0.00';
		}
		if(preg_match_all('/<FeeComponent>\s*<FeeType>FBARemovalFee<\/FeeType>\s*<FeeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/FeeAmount>\s*<\/FeeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
	    $payload['fba_removal_fee']=$res[0];
	    }
		else
		{
		$payload['fba_removal_fee']='0.00';
		}
		
		if(preg_match_all('/<FeeComponent>\s*<FeeType>FBAStorageFee<\/FeeType>\s*<FeeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/FeeAmount>\s*<\/FeeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
	    $payload['fba_storage_fee']=$res[0];
	    }
		else
		{
		$payload['fba_storage_fee']='0.00';
		}
		
		if(preg_match_all('/<FeeComponent>\s*<FeeType>FBATransportationFee<\/FeeType>\s*<FeeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/FeeAmount>\s*<\/FeeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
	    $payload['fba_transport_fee']=$res[0];
	    }
		else
		{
		$payload['fba_transport_fee']='0.00';
		}
		
		if(preg_match_all('/<FeeComponent>\s*<FeeType>FBAWeightBasedFee<\/FeeType>\s*<FeeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/FeeAmount>\s*<\/FeeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
	    $payload['fba_weightbased_fee']=$res[0];
	    }
		else
		{
		$payload['fba_weightbased_fee']='0.00';
		}
		
		if(preg_match_all('/<FeeComponent>\s*<FeeType>FulfillmentFee<\/FeeType>\s*<FeeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/FeeAmount>\s*<\/FeeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
	    $payload['fullfill_fee']=$res[0];
	    }
		else
		{
		$payload['fullfill_fee']='0.00';
		}
		if(preg_match_all('/<FeeComponent>\s*<FeeType>FulfillmentNetworkFee<\/FeeType>\s*<FeeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/FeeAmount>\s*<\/FeeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
	    $payload['fullfill_network_fee']=$res[0];
	    }
		else
		{
		$payload['fullfill_network_fee']='0.00';
		}
		
		if(preg_match_all('/<FeeComponent>\s*<FeeType>LabelingFee<\/FeeType>\s*<FeeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/FeeAmount>\s*<\/FeeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
	    $payload['lable_fee']=$res[0];
	    }
		else
		{
		$payload['lable_fee']='0.00';
		}
		
		if(preg_match_all('/<FeeComponent>\s*<FeeType>OpaqueBaggingFee<\/FeeType>\s*<FeeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/FeeAmount>\s*<\/FeeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
	    $payload['opa_bagging_fee']=$res[0];
	    }
		else
		{
		$payload['opa_bagging_fee']='0.00';
		}
		if(preg_match_all('/<FeeComponent>\s*<FeeType>PolybaggingFee<\/FeeType>\s*<FeeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/FeeAmount>\s*<\/FeeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
	    $payload['poly_bagging_fee']=$res[0];
	    }
		else
		{
		$payload['poly_bagging_fee']='0.00';
		}
		if(preg_match_all('/<FeeComponent>\s*<FeeType>SSOFFulfillmentFee<\/FeeType>\s*<FeeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/FeeAmount>\s*<\/FeeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
	    $payload['ssof_fullfill_fee']=$res[0];
	    }
		else
		{
		$payload['ssof_fullfill_fee']='0.00';
		}
		if(preg_match_all('/<FeeComponent>\s*<FeeType>TapingFee<\/FeeType>\s*<FeeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/FeeAmount>\s*<\/FeeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
	    $payload['taping_fee']=$res[0];
	    }
		else
		{
		$payload['taping_fee']='0.00';
		}
		
		if(preg_match_all('/<FeeComponent>\s*<FeeType>TransportationFee<\/FeeType>\s*<FeeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/FeeAmount>\s*<\/FeeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
	    $payload['transport_fee']=$res[0];
	    }
		else
		{
		$payload['transport_fee']='0.00';
		}
		
		if(preg_match_all('/<FeeComponent>\s*<FeeType>UnitFulfillmentFee<\/FeeType>\s*<FeeAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/FeeAmount>\s*<\/FeeComponent>/i',$response,$matches))
		{
	    $res=$matches[1];
	    $payload['unfullfill_fee']=$res[0];
	    }
		else
		{
		$payload['unfullfill_fee']='0.00';
		}
		
		if(preg_match_all('/<AmazonOrderId>([^>]*?)<\/AmazonOrderId>/i',$response,$matches))
		{
	    $res=$matches[1];
	    $payload['order_id']=$res[0];
		}
		if(preg_match_all('/<SellerSKU>([^>]*?)<\/SellerSKU>/i',$response,$matches))
		{
	    $res=$matches[1];
	    $payload['sku']=$res[0];
		}
		if(preg_match_all('/<OrderItemId>([^>]*?)<\/OrderItemId>/i',$response,$matches))
		{
	    $res=$matches[1];
	    $payload['itemid']=$res[0];
		}
		if(preg_match_all('/<MarketplaceName>([^>]*?)<\/MarketplaceName>/i',$response,$matches))
		{
	    $res=$matches[1];
	    $payload['marketplace']=$res[0];
		}
		if(preg_match_all('/<QuantityShipped>([^>]*?)<\/QuantityShipped>/i',$response,$matches))
		{
	    $res=$matches[1];
	    $payload['qty']=$res[0];
		}
		if(preg_match_all('/<PostedDate>([^>]*?)<\/PostedDate>/i',$response,$matches))
		{
	    $res=$matches[1];
	    $payload['posted_date']=$res[0];
		}
		if(preg_match_all('/<Promotion>\s*<PromotionType>[^>]*?<\/PromotionType>\s*<PromotionAmount>\s*<CurrencyAmount>([^>]*?)<\/CurrencyAmount>\s*<CurrencyCode>[^>]*?<\/CurrencyCode>\s*<\/PromotionAmount>\s*<PromotionId>[^>]*?<\/PromotionId>\s*<\/Promotion>/i',$response,$matches))
		{
	    $res=$matches[1];
		 //print_r($res);
	    $payload['promo_price1']=$res[0];
		$payload['promo_price2']=$res[1];
		$payload['promo_price3']=$res[2];
		$payload['promo_price4']=$res[3];
		$payload['promo_price5']=$res[4];
		$payload['promo_price6']=$res[5];
		}
		else
		{
		$payload['promo_price1']='0.0';
		$payload['promo_price2']='0.0';
		$payload['promo_price3']='0.0';
		$payload['promo_price4']='0.0';
		$payload['promo_price5']='0.0';
		$payload['promo_price6']='0.0';	
		}
     //die();
      
      if(count($payload) > 0 && !empty($payload['order_id']))
      {
        $data['status_code']=1;
        $data['status_text']="Success";
        $data['payload']=$payload;  
      }
      else
      {
          $data['status_code']=3;
          $data['status_text']="No Data";
          $payload['lm_ean']=$order_id;
          $payload['asin_counts']=-3;
          $payload['lm_asin']='';
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
 
  private function create_curl_request($param,$user_id=null,$store_to_file=0,$report_id='')
  {
      $httpHeader=array();
      $httpHeader[]='Transfer-Encoding: chunked';
      $httpHeader[]='Content-Type: text/xml';
      $httpHeader[]='Expect:';
      $httpHeader[]='Accept:';
      try
      {
        curl_setopt($this->ch, CURLOPT_URL, $this->built_query_string($param));
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $httpHeader);
        curl_setopt($this->ch, CURLOPT_POST, true);
        
        if($store_to_file==1 && $user_id != null && $report_id!='')
        {
          $rep_file=realpath('asset').DIRECTORY_SEPARATOR."amazon_report".DIRECTORY_SEPARATOR.$user_id."_".$report_id;
          global $file_handle; 
          $file_handle = fopen($rep_file, 'w+'); 
          curl_setopt($this->ch, CURLOPT_FILE, $file_handle);
          curl_setopt($this->ch, CURLOPT_WRITEFUNCTION, function ($cp, $data) {
            global $file_handle;
            $len = fwrite($file_handle, $data);
            return $len;
          });
          curl_exec($this->ch);
          fclose($file_handle);
          
        }  
        else
        {
          $response = curl_exec($this->ch);  
        }
        
        if(curl_errno($this->ch))
        {
            throw new Exception(curl_error($this->ch));
        }
        $data['status_code']=1;
        $data['status_text']='Success';
        if($store_to_file==1 && $user_id != null && $report_id!='')
        {
          $data['report_file']=$rep_file;
        }
        else
        {
          $data['payload']=$response;  
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
                  'SignatureMethod' => urlencode("HmacSHA256"),
                  'SignatureVersion'=> urlencode("2"),
                  'Timestamp'=>gmdate("Y-m-d\TH:i:s.\\0\\0\\0\\Z", time()),
                  'Version' => urlencode("2015-05-01"),
                  

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
            $string_to_sign = "POST\n".$this->mws_site."\n/Finances/2015-05-01\n" . $url_string;
            
            $signature = hash_hmac("sha256", $string_to_sign, $this->secret_key, TRUE);
            $signature = urlencode(base64_encode($signature));
            $url = "https://".$this->mws_site."/Finances/2015-05-01?". $url_string . "&Signature=" . $signature;
            return $url; 
 }
 
}
?>
  
