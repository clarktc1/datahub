<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Amazon_mws_myfeesestimate_api extends CI_Controller 
{
  public function  __construct()
  {
	     parent::__construct();
       	 $this->load->model('amazon_feed/amazon_myfeesestimate_api','amazon_api');
  }

  public function fee_match()
  {
    $query=$this->db->query("SELECT * FROM amazon_profile");
    $user=$query->result_array();
	//print_r($user);
	//die();
    if(count($user) > 0)
    {
      foreach($user as $usr)
      {
         $this->amazon_api->set_credentials($usr['profile_id'],$usr['seller_id'],$usr['auth_token'],$usr['access_key'],$usr['secret_key'],$usr['market_placeID']);
         $prod_list=$this->amazon_api->get_product_to_match(600000,$usr['profile_id']);
         if(!empty($prod_list))
         {
         	echo date('y-m-d h:i:s')."\n";
         	foreach($prod_list as $prd)
         	{
 			  if($prd['pro_asin'])
 			  {
 			   time_nanosleep(0, 250000000);
 			  	echo "Processing\t".$prd['pro_asin']."\n";
 			  	$res=$this->amazon_api->fetch_product_details($usr['profile_id'],$prd['pro_asin']);
 			  	if($res['status_code']==1)
 			  	{
 			  		$qi="UPDATE product_info SET fee_flag='1',pro_fees=".$this->db->escape($res['payload']['fees_estimate'])." WHERE pro_asin='".$prd['pro_asin']."' ";
					print_r($qi);
					
                    $this->db->query($qi);
					echo "\n INSERT MADED**********************\n";
 			  	}
 			  	elseif($res['status_code']==3)
 			  	{
 			  		//$product[]=$res['payload'];
 			  		$qi="UPDATE product_info SET fee_flag='0' WHERE pro_asin='".$prd['pro_asin']."' ";
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


  public function fee_match_monitor()
  {
    $query=$this->db->query("SELECT * FROM amazon_profile");
    $user=$query->result_array();
	//print_r($user);
	//die();
    if(count($user) > 0)
    {
      foreach($user as $usr)
      {
         $this->amazon_api->set_credentials($usr['profile_id'],$usr['seller_id'],$usr['auth_token'],$usr['access_key'],$usr['secret_key'],$usr['market_placeID']);
         $prod_list=$this->amazon_api->get_product_to_match_monitor(600000,$usr['profile_id']);
         if(!empty($prod_list))
         {
         	echo date('y-m-d h:i:s')."\n";
         	foreach($prod_list as $prd)
         	{
 			  if($prd['pro_asin'])
 			  {
 			   time_nanosleep(0, 250000000);
 			  	echo "Processing\t".$prd['pro_asin']."\n";
 			  	$res=$this->amazon_api->fetch_monitor_product_details($usr['profile_id'],$prd['pro_asin']);
 			  	if($res['status_code']==1)
 			  	{
 			  		$qi="UPDATE product_info SET fee_flag='1',pro_fees=".$this->db->escape($res['payload']['fees_estimate'])." WHERE pro_asin='".$prd['pro_asin']."' ";
					print_r($qi);
					
                    $this->db->query($qi);
					echo "\n INSERT MADED**********************\n";
 			  	}
 			  	elseif($res['status_code']==3)
 			  	{
 			  		//$product[]=$res['payload'];
 			  		$qi="UPDATE product_info SET added_on=now() WHERE pro_asin='".$prd['pro_asin']."' ";
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