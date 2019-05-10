<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Amazon_mws_competitive_asin_api extends CI_Controller 
{
  public function  __construct()
  {
	     parent::__construct();
       	 $this->load->model('amazon_feed/Amazon_competitive_asin_api','amazon_api');
  }

  public function competetive_pricing_match()
  {
    $query=$this->db->query("SELECT * FROM amazon_profile");
    $user=$query->result_array();
    if(count($user) > 0)
    {
      foreach($user as $usr)
      {
         $this->amazon_api->set_credentials($usr['profile_id'],$usr['seller_id'],$usr['auth_token'],$usr['access_key'],$usr['secret_key'],$usr['market_placeID']);
         $prod_list=$this->amazon_api->get_product_to_match(600000);
         if(!empty($prod_list))
         {
         	echo date('y-m-d h:i:s')."\n";
         	foreach($prod_list as $prd)
         	{
 			  if($prd['pro_asin'])
 			  {
 			//time_nanosleep(0, 100000000);
 			  	echo "Processing\t".$prd['pro_asin']."\n";
 			  	$res=$this->amazon_api->fetch_product_details($usr['profile_id'],$prd['pro_asin']);
 			  	if($res['status_code']==1)
 			  	{
 			  		$qi="UPDATE product_info SET pro_rank=".$this->db->escape($res['payload']['sales_rank']).",bb_flag=1 WHERE pro_asin='".$prd['pro_asin']."' ";
					//print_r($qi);
					
                    $this->db->query($qi);
					echo "\n INSERT MADED**********************\n";
 			  	}
 			  	elseif($res['status_code']==3)
 			  	{
 			  		//$product[]=$res['payload'];
 			  		$qi="UPDATE product_info SET bb_flag=1 WHERE pro_asin='".$prd['pro_asin']."' ";
					//print_r($qi);
					
                    $this->db->query($qi);
					echo "\n INSERT MADED**********************\n";
 			  	}
 			 
         }
	     
	  }
	}
  }
 }
}


public function competetive_pricing_match_monitor()
  {
    $query=$this->db->query("SELECT * FROM amazon_profile");
    $user=$query->result_array();
    if(count($user) > 0)
    {
      foreach($user as $usr)
      {
         $this->amazon_api->set_credentials($usr['profile_id'],$usr['seller_id'],$usr['auth_token'],$usr['access_key'],$usr['secret_key'],$usr['market_placeID']);
         $prod_list=$this->amazon_api->get_product_to_match_monitor(600000);
         if(!empty($prod_list))
         {
         	echo date('y-m-d h:i:s')."\n";
         	foreach($prod_list as $prd)
         	{
 			  if($prd['pro_asin'])
 			  {
 			//time_nanosleep(0, 100000000);
 			  	echo "Processing\t".$prd['pro_asin']."\n";
 			  	$res=$this->amazon_api->fetch_product_details_monitor($usr['profile_id'],$prd['pro_asin']);
 			  	if($res['status_code']==1)
 			  	{
 			  		$qi="UPDATE product_info SET pro_rank=".$this->db->escape($res['payload']['sales_rank']).",bb_flag=1 WHERE pro_asin='".$prd['pro_asin']."' ";
					//print_r($qi);
					
                    $this->db->query($qi);
					echo "\n INSERT MADED**********************\n";
 			  	}
 			  	elseif($res['status_code']==3)
 			  	{
 			  		//$product[]=$res['payload'];
 			  		$qi="UPDATE product_info SET bb_flag=1 WHERE pro_asin='".$prd['pro_asin']."' ";
					//print_r($qi);
					
                    $this->db->query($qi);
					echo "\n INSERT MADED**********************\n";
 			  	}
 			 
         }
	     
	  }
	}
  }
 }
}
}