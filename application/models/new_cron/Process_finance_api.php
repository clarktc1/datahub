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
        $this->db->select('amazon_profile.*,country_code');
        $this->db->from('amazon_profile');
        if ($user_id !='') {
            $this->db->where('profile_id',$this->db->escape($user_id));
        }
        $this->db->join('seller_country_mapping','seller_country_mapping.seller_id = amazon_profile.profile_id','inner');
        $this->db->where('seller_country_mapping.status',1);
        $this->db->where('profile_id != 1');
        $this->db->order_by('profile_id','ASC');
        $query = $this->db->get();
        return $query->result_array();
    }

    public function set_credentials($usr)
    {
        $this->seller_id=$usr['seller_id'];
        $this->auth_token=$usr['auth_token'];
        $this->access_key=$usr['access_key'];
        $this->secret_key=$usr['secret_key'];
        $this->market_id=$usr['market_placeID'];
        $this->mws_site=$usr['mws_endpoint'];
        $this->ch = curl_init();
        return TRUE;
    }
    public function get_product_to_match($user_id,$country_code)
    {
        $sql="SELECT prod_id,order_id,sales_channel FROM rep_orders_data_order_date_list where user_id=".$user_id." AND ord_status='Shipped' and sales_channel=".$this->db->escape($country_code)." LIMIT 0,2000";
        $query=$this->db->query($sql);
        return $query->result_array();
    }

    public function fetch_product_details($user_id =null,$order_id =null,$amz_country_code =null,$country_code=null, $token=null, $number = 1)
    {
        // echo $number;
        // echo "<br>";
        // if ($number > 9) {
        //     $number = $number;
        // } else {
        //     $number = "0".$number;
        // }
        // echo "2019-03-".$number."T18:30:00Z";
        // die();
        try
        {
            

            // Code for get 3 month before data Working Fine
            /*$createDate = date('Y-m-d');
            $dateCreatePostedBefore = date('Y-m-d', strtotime('-8 hours', strtotime($createDate)));
            $dateCreatePostedAfter  = date('Y-m-d', strtotime('-3 month', strtotime($createDate)));
            if (!empty($token) && !is_null($token)) {
                echo $token;
                echo "<br>";
                $param['NextToken'] = $token;
                $dateIn = date('Y-m-d', strtotime('+'.$number.' day', strtotime($dateCreatePostedAfter)));
                $param['PostedAfter']   = $dateIn."T00:01:00Z";
            } else {
                $param['PostedAfter']   = $dateCreatePostedAfter.'T00:01:00Z';
                $param['PostedBefore']  = $dateCreatePostedBefore.'T23:30:00Z';
            }*/
            // Code for get 3 month before data Working Fine


            $this->db->select('*');
            $this->db->from('finance_data_log');
            $this->db->where('user_id', $user_id);
            $query = $this->db->get();
            $userLastDate = $query->row();
            $createDate = $userLastDate->date;
            $data["createDate"] = $createDate;

            $date_now   = date('Y-m-d');
            // $date_check = date('Y-m-d', strtotime('3 month', strtotime($createDate)));
            $date_check = date('Y-m-d', strtotime('3 day', strtotime($createDate)));
            if (strtotime($date_check) >= strtotime($date_now) ) {
                $dateCreatePostedAfter  = $createDate;
                // $dateCreatePostedBefore = date('Y-m-d', strtotime('-8 hours', strtotime($date_now)));
                if (strtotime($date_now)>strtotime($createDate)) {
                    $dateCreatePostedBefore = date('Y-m-d', strtotime('1 day', strtotime($createDate)));
                    if (strtotime($dateCreatePostedBefore) == strtotime($date_now) ) {
                        $data['status_text']    = "Date Match and current to max";
                        return $data;
                    }    
                }
                if (strtotime($createDate) >= strtotime($date_now) ) {
                    $data['status_text']    = "Date Match and current to max";
                    return $data;
                }
            } else {
                $dateCreatePostedAfter   = $createDate;
                $dateCreatePostedBefore  = $date_check;
            }

            if (!empty($token) && !is_null($token)) {
                echo $token;
                echo "<br>";
                // $dateIn = date('Y-m-d', strtotime('+'.$number.' day', strtotime($dateCreatePostedAfter)));
                // $data["createDate"]     = $dateIn;
                // $data["createDate"]     = $dateCreatePostedBefore;
                $param['Action']        = urlencode("ListFinancialEventsByNextToken");
                $param['NextToken']     = $token;
                // $param['PostedAfter']   = $dateIn."T00:01:00Z";
            } else {
                $param['Action']        = urlencode("ListFinancialEvents");
                $param['PostedAfter']   = $dateCreatePostedAfter.'T00:01:00Z';
                $param['PostedBefore']  = $dateCreatePostedBefore.'T23:59:00Z';
                $data["startDate"]      = $dateCreatePostedAfter;
                $data["createDate"]     = $dateCreatePostedBefore;

                // $param['PostedAfter']   = '2019-03-22T00:01:00Z';
                // $param['PostedBefore']  = '2019-03-26T23:30:00Z';
            }
            // echo "<pre>";
            // print_r($data);
            // print_r($param);
            // die();

            //$param['IdType']='ISBN';
            // $param['AmazonOrderId'] = $order_id;
            // Multiple Items
            // $param['AmazonOrderId']='111-9603947-0971459';

            // single Items
            // $param['AmazonOrderId']='111-4382755-8488200';

            // AdjustmentEventList 3 time data
            // $param['AmazonOrderId']='114-3976110-8902610';

            // $param['AmazonOrderId']='S01-0203508-6429359';

            // No seller_order_id user 5 2018-03-01
            // $param['AmazonOrderId']='114-6167842-2317037';

            // $param['AmazonOrderId']='113-6819144-8652218';

            //$param['PostedAfter']   = date(DateTime::ISO8601, strtotime('-2 hours'));
            // $param['PostedAfter']   = date(DateTime::ISO8601, strtotime('2019-03-01'));
            // $param['PostedBefore']  = date(DateTime::ISO8601, time() - 120);
            
            //$param['MarketplaceId']=$amz_country_code;
            // echo "<pre>";
            // print_r($param);
            // echo "</pre>";
            // die('Process_finance_api 74');


            $curl_res=$this->create_curl_request($param);
            // echo "<pre>";
            // print_r($curl_res);
            if($curl_res['status_code']==0)
            {
                throw new Exception($curl_res['status_text']);
            }

            $response=$curl_res['payload'];
            $res = simplexml_load_string($curl_res['payload']);


            /*$getData = file_get_contents('http://localhost/test/abc.xml');
            $res = simplexml_load_string($getData);*/



            echo "<pre>";
            // echo "+++++++++++++++++++++++++++++++++++++";
            // print_r($res);
            // die();
            echo "=====================================";
            $payload=[];
            $newKeyLog = array();
            // $payload['order_id']=$payload['principal']=$payload['tax']=$payload['giftwrap']=$payload['giftwraptax']=$payload['shippingcharge']=$payload['shippingtax']=$payload['fbafee']=$payload['commission']=$payload['fixedclosingfee']=$payload['giftwrapchargeback']=
            // $payload['shippingchargeback']=$payload['variableclosingfee']=$payload['sku']=$payload['itemid']=$payload['marketplace']=$payload['qty']=$payload['posted_date']='';
            // $payload['asin_counts']=-3;
            // echo "<pre>";            
            // if (isset($res->ListFinancialEventsResult->NextToken) && !empty($res->ListFinancialEventsResult->NextToken)) {
            //     $tokens = $res->ListFinancialEventsResult->NextToken[0];
            // }
            // print_r($res->ListFinancialEventsResult->FinancialEvents->AdjustmentEventList);

            if (isset($res->Error) && !empty($res->Error)) {
                $responseError = array();
                $responseError['table_name'] = "Error";
                $responseError['user_id']    = $user_id;
                $responseError['data']       = json_encode($res);
                $responseError['api_date']   = $data["createDate"];
                $newKeyLog[] = $responseError;
            }

            $adjustmentEventListData = [];
            if (isset($res->ListFinancialEventsResult->FinancialEvents->AdjustmentEventList->AdjustmentEvent[0]) || isset($res->ListFinancialEventsByNextTokenResult->FinancialEvents->AdjustmentEventList->AdjustmentEvent[0])) {

                if (isset($res->ListFinancialEventsResult->FinancialEvents->AdjustmentEventList->AdjustmentEvent[0])) {
                    $getAdjustmentEventList = $res->ListFinancialEventsResult->FinancialEvents->AdjustmentEventList->AdjustmentEvent;
                }
                if (isset($res->ListFinancialEventsByNextTokenResult->FinancialEvents->AdjustmentEventList->AdjustmentEvent[0])) {
                    $getAdjustmentEventList = $res->ListFinancialEventsByNextTokenResult->FinancialEvents->AdjustmentEventList->AdjustmentEvent;
                }

                $adjustmentEventUseKey = array('AdjustmentItemList','AdjustmentAmount','AdjustmentType','PostedDate');
                $checkResponse = $this->checkMwsNewApiKey($getAdjustmentEventList, $adjustmentEventUseKey, 'finance_adjustment_event_list', $user_id, $data["createDate"]);
                if ($checkResponse['status'] == 1) {
                    unset($checkResponse['status']);
                    $newKeyLog[] = $checkResponse;
                }
                // print_r($newKeyLog);
                /* echo "<pre>";
                print_r($AdjustmentEventListKey);
                print_r($getAdjustmentEventList);*/
                $adjustmentEventI = 0;
                foreach ($getAdjustmentEventList as $getAdjustmentEvent) {
                    if ( isset($getAdjustmentEvent->AdjustmentItemList->AdjustmentItem->PerUnitAmount)) {
                        $adjustmentEventListData[$adjustmentEventI]['perunitamount'] = (string) $getAdjustmentEvent->AdjustmentItemList->AdjustmentItem->PerUnitAmount->CurrencyAmount;
                    } else {
                        $adjustmentEventListData[$adjustmentEventI]['perunitamount'] = isset($adjustmentEventListData[$adjustmentEventI]['perunitamount']) ? $adjustmentEventListData[$adjustmentEventI]['perunitamount'] : '0.00';
                    }
                    if ( isset($getAdjustmentEvent->AdjustmentItemList->AdjustmentItem->TotalAmount)) {
                        $adjustmentEventListData[$adjustmentEventI]['totalamount'] = (string) $getAdjustmentEvent->AdjustmentItemList->AdjustmentItem->TotalAmount->CurrencyAmount;
                    } else {
                        $adjustmentEventListData[$adjustmentEventI]['totalamount'] = isset($adjustmentEventListData[$adjustmentEventI]['totalamount']) ? $adjustmentEventListData[$adjustmentEventI]['totalamount'] : '0.00';
                    }
                    if ( isset($getAdjustmentEvent->AdjustmentItemList->AdjustmentItem->Quantity)) {
                        $adjustmentEventListData[$adjustmentEventI]['quantity'] = (string) $getAdjustmentEvent->AdjustmentItemList->AdjustmentItem->Quantity;
                    } else {
                        $adjustmentEventListData[$adjustmentEventI]['quantity'] = isset($adjustmentEventListData[$adjustmentEventI]['quantity']) ? $adjustmentEventListData[$adjustmentEventI]['quantity'] : '0';
                    }
                    if ( isset($getAdjustmentEvent->AdjustmentItemList->AdjustmentItem->SellerSKU)) {
                        $adjustmentEventListData[$adjustmentEventI]['sellersku'] = (string) $getAdjustmentEvent->AdjustmentItemList->AdjustmentItem->SellerSKU;
                    } else {
                        $adjustmentEventListData[$adjustmentEventI]['sellersku'] = isset($adjustmentEventListData[$adjustmentEventI]['sellersku']) ? $adjustmentEventListData[$adjustmentEventI]['sellersku'] : '0';
                    }
                    if ( isset($getAdjustmentEvent->AdjustmentItemList->AdjustmentItem->ProductDescription)) {
                        $adjustmentEventListData[$adjustmentEventI]['productdescription'] = (string) $getAdjustmentEvent->AdjustmentItemList->AdjustmentItem->ProductDescription;
                    } else {
                        $adjustmentEventListData[$adjustmentEventI]['productdescription'] = isset($adjustmentEventListData[$adjustmentEventI]['productdescription']) ? $adjustmentEventListData[$adjustmentEventI]['productdescription'] : '';
                    }
                    if (isset($getAdjustmentEvent->AdjustmentItemList->AdjustmentItem)) {
                        $adjustmentItemData = $getAdjustmentEvent->AdjustmentItemList->AdjustmentItem;
                        $adjustmentEventUseKey = array('TotalAmount','PerUnitAmount','Quantity','SellerSKU','ProductDescription');
                        $checkResponse = $this->checkMwsNewApiKey($adjustmentItemData, $adjustmentEventUseKey, 'finance_adjustment_event_list', $user_id, $data["createDate"]);
                        if ($checkResponse['status'] == 1) {
                            unset($checkResponse['status']);
                            $newKeyLog[] = $checkResponse;
                        }
                    }
                    if ( isset($getAdjustmentEvent->AdjustmentAmount)) {
                        $adjustmentEventListData[$adjustmentEventI]['adjustmentamount'] = (string) $getAdjustmentEvent->AdjustmentAmount->CurrencyAmount;
                    } else {
                        $adjustmentEventListData[$adjustmentEventI]['adjustmentamount'] = isset($adjustmentEventListData[$adjustmentEventI]['adjustmentamount']) ? $adjustmentEventListData[$adjustmentEventI]['adjustmentamount'] : '0.00';
                    }
                    if ( isset($getAdjustmentEvent->AdjustmentType)) {
                        $adjustmentEventListData[$adjustmentEventI]['adjustmenttype'] = (string) $getAdjustmentEvent->AdjustmentType;
                    } else {
                        $adjustmentEventListData[$adjustmentEventI]['adjustmenttype'] = isset($adjustmentEventListData[$adjustmentEventI]['adjustmenttype']) ? $adjustmentEventListData[$adjustmentEventI]['adjustmenttype'] : '';
                    }
                    if ( isset($getAdjustmentEvent->PostedDate)) {
                        $adjustmentEventListData[$adjustmentEventI]['posteddate'] = (string) $getAdjustmentEvent->PostedDate;
                    } else {
                        $adjustmentEventListData[$adjustmentEventI]['posteddate'] = isset($adjustmentEventListData[$adjustmentEventI]['posteddate']) ? $adjustmentEventListData[$adjustmentEventI]['posteddate'] : '';
                    }
                    $adjustmentEventI++;
                }
                $payload['AdjustmentEventList'] = $adjustmentEventListData;
            } else {
                if (isset($res->ListFinancialEventsResult->FinancialEvents->AdjustmentEventList->AdjustmentEvent) || isset($res->ListFinancialEventsByNextTokenResult->FinancialEvents->AdjustmentEventList->AdjustmentEvent)) {
                    
                    if (isset($res->ListFinancialEventsResult->FinancialEvents->AdjustmentEventList->AdjustmentEvent)) {
                        $getAdjustmentEventList = $res->ListFinancialEventsResult->FinancialEvents->AdjustmentEventList->AdjustmentEvent;
                    }
                    if (isset($res->ListFinancialEventsByNextTokenResult->FinancialEvents->AdjustmentEventList->AdjustmentEvent)) {
                        $getAdjustmentEventList = $res->ListFinancialEventsByNextTokenResult->FinancialEvents->AdjustmentEventList->AdjustmentEvent;
                    }

                    $adjustmentEventUseKey = array('AdjustmentItemList','AdjustmentAmount','AdjustmentType','PostedDate');
                    $checkResponse = $this->checkMwsNewApiKey($getAdjustmentEventList, $adjustmentEventUseKey, 'finance_adjustment_event_list', $user_id, $data["createDate"]);
                    if ($checkResponse['status'] == 1) {
                        unset($checkResponse['status']);
                        $newKeyLog[] = $checkResponse;
                    }

                    if ( isset($getAdjustmentEventList->AdjustmentItemList->AdjustmentItem->PerUnitAmount)) {
                        $adjustmentEventListData[0]['perunitamount'] = (string) $getAdjustmentEventList->AdjustmentItemList->AdjustmentItem->PerUnitAmount->CurrencyAmount;
                    } else {
                        $adjustmentEventListData[0]['perunitamount'] = isset($adjustmentEventListData[0]['perunitamount']) ? $adjustmentEventListData[0]['perunitamount'] : '0.00';
                    }
                    if ( isset($getAdjustmentEventList->AdjustmentItemList->AdjustmentItem->TotalAmount)) {
                        $adjustmentEventListData[0]['totalamount'] = (string) $getAdjustmentEventList->AdjustmentItemList->AdjustmentItem->TotalAmount->CurrencyAmount;
                    } else {
                        $adjustmentEventListData[0]['totalamount'] = isset($adjustmentEventListData[0]['totalamount']) ? $adjustmentEventListData[0]['totalamount'] : '0.00';
                    }
                    if ( isset($getAdjustmentEventList->AdjustmentItemList->AdjustmentItem->Quantity)) {
                        $adjustmentEventListData[0]['quantity'] = (string) $getAdjustmentEventList->AdjustmentItemList->AdjustmentItem->Quantity;
                    } else {
                        $adjustmentEventListData[0]['quantity'] = isset($adjustmentEventListData[0]['quantity']) ? $adjustmentEventListData[0]['quantity'] : '0';
                    }
                    if ( isset($getAdjustmentEventList->AdjustmentItemList->AdjustmentItem->SellerSKU)) {
                        $adjustmentEventListData[0]['sellersku'] = (string) $getAdjustmentEventList->AdjustmentItemList->AdjustmentItem->SellerSKU;
                    } else {
                        $adjustmentEventListData[0]['sellersku'] = isset($adjustmentEventListData[0]['sellersku']) ? $adjustmentEventListData[0]['sellersku'] : '0';
                    }
                    if ( isset($getAdjustmentEventList->AdjustmentItemList->AdjustmentItem->ProductDescription)) {
                        $adjustmentEventListData[0]['productdescription'] = (string) $getAdjustmentEventList->AdjustmentItemList->AdjustmentItem->ProductDescription;
                    } else {
                        $adjustmentEventListData[0]['productdescription'] = isset($adjustmentEventListData[0]['productdescription']) ? $adjustmentEventListData[0]['productdescription'] : '';
                    }

                    if (isset($getAdjustmentEventList->AdjustmentItemList->AdjustmentItem)) {
                        $adjustmentItemData = $getAdjustmentEventList->AdjustmentItemList->AdjustmentItem;
                        $adjustmentEventUseKey = array('TotalAmount','PerUnitAmount','Quantity','SellerSKU','ProductDescription');
                        $checkResponse = $this->checkMwsNewApiKey($adjustmentItemData, $adjustmentEventUseKey, 'finance_adjustment_event_list', $user_id, $data["createDate"]);
                        if ($checkResponse['status'] == 1) {
                            unset($checkResponse['status']);
                            $newKeyLog[] = $checkResponse;
                        }
                    }

                    if ( isset($getAdjustmentEventList->AdjustmentAmount)) {
                        $adjustmentEventListData[0]['adjustmentamount'] = (string) $getAdjustmentEventList->AdjustmentAmount->CurrencyAmount;
                    } else {
                        $adjustmentEventListData[0]['adjustmentamount'] = isset($adjustmentEventListData[0]['adjustmentamount']) ? $adjustmentEventListData[0]['adjustmentamount'] : '0.00';
                    }
                    if ( isset($getAdjustmentEventList->AdjustmentType)) {
                        $adjustmentEventListData[0]['adjustmenttype'] = (string) $getAdjustmentEvent->AdjustmentType;
                    } else {
                        $adjustmentEventListData[0]['adjustmenttype'] = isset($adjustmentEventListData[0]['adjustmenttype']) ? $adjustmentEventListData[0]['adjustmenttype'] : '';
                    }
                    if ( isset($getAdjustmentEventList->PostedDate)) {
                        $adjustmentEventListData[0]['posteddate'] = (string) $getAdjustmentEventList->PostedDate;
                    } else {
                        $adjustmentEventListData[0]['posteddate'] = isset($adjustmentEventListData[0]['posteddate']) ? $adjustmentEventListData[0]['posteddate'] : '';
                    }
                }
                $payload['AdjustmentEventList'] = $adjustmentEventListData;
            }
            // print_r($newKeyLog);
            // print_r($adjustmentEventListData);
            // die();
            // die('if');
            // checking if multiple shipment for one order {{foreach}}
            /*echo "<pre>";
            print_r($res->ListFinancialEventsResult->FinancialEvents->ShipmentEventList->ShipmentEvent);
            echo "<pre>";*/
            // echo "<pre>";
            // print_r($res->ListFinancialEventsResult->FinancialEvents->RefundEventList->ShipmentEvent);
            // print_r($res->ListFinancialEventsResult->FinancialEvents);

            /* Start ServiceFeeEventList Data */
            
            $serviceFeeEventListData = [];
            if (isset($res->ListFinancialEventsResult->FinancialEvents->ServiceFeeEventList->ServiceFeeEvent[0]) || isset($res->ListFinancialEventsByNextTokenResult->FinancialEvents->ServiceFeeEventList->ServiceFeeEvent[0])) {
                if (isset($res->ListFinancialEventsResult->FinancialEvents->ServiceFeeEventList->ServiceFeeEvent[0])) {
                    $getServiceFeeEventList = $res->ListFinancialEventsResult->FinancialEvents->ServiceFeeEventList->ServiceFeeEvent;
                }
                if (isset($res->ListFinancialEventsByNextTokenResult->FinancialEvents->ServiceFeeEventList->ServiceFeeEvent[0])) {
                    $getServiceFeeEventList = $res->ListFinancialEventsByNextTokenResult->FinancialEvents->ServiceFeeEventList->ServiceFeeEvent;
                }
                if (isset($res->ListFinancialEventsResult->FinancialEvents->ServiceFeeEventList)) {
                    $getServiceFeeEventListCheck = $res->ListFinancialEventsResult->FinancialEvents->ServiceFeeEventList;
                }
                if (isset($res->ListFinancialEventsByNextTokenResult->FinancialEvents->ServiceFeeEventList)) {
                    $getServiceFeeEventListCheck = $res->ListFinancialEventsByNextTokenResult->FinancialEvents->ServiceFeeEventList;
                }
                // print_r($getServiceFeeEventList);
                $serviceFeeEventUseKey = array('ServiceFeeEvent');
                $checkResponse = $this->checkMwsNewApiKey($getServiceFeeEventListCheck, $serviceFeeEventUseKey, 'finance_service_fee_event_list', $user_id, $data["createDate"]);
                if ($checkResponse['status'] == 1) {
                    unset($checkResponse['status']);
                    $newKeyLog[] = $checkResponse;
                }

                $serviceFeeEventI = 0;
                foreach ($getServiceFeeEventList as $getServiceFeeEvent) {
                    if (isset($getServiceFeeEvent->FeeList->FeeComponent->FeeType)) {
                        $serviceFeeEventListData[$serviceFeeEventI]['fee_type'] = (string) $getServiceFeeEvent->FeeList->FeeComponent->FeeType;
                    }
                    if (isset($getServiceFeeEvent->FeeList->FeeComponent->FeeAmount->CurrencyAmount)) {
                        $serviceFeeEventListData[$serviceFeeEventI]['fee_amount'] = (string) $getServiceFeeEvent->FeeList->FeeComponent->FeeAmount->CurrencyAmount;
                    }
                    $serviceFeeEventI++;
                }
                $payload['ServiceFeeEventList'] = $serviceFeeEventListData;
            } else {
                if (isset($res->ListFinancialEventsResult->FinancialEvents->ServiceFeeEventList->ServiceFeeEvent->FeeList) || isset($res->ListFinancialEventsByNextTokenResult->FinancialEvents->ServiceFeeEventList->ServiceFeeEvent->FeeList)) {
                    // $getServiceFeeEventList = $res->ListFinancialEventsResult->FinancialEvents->ServiceFeeEventList->ServiceFeeEvent;
                    if (isset($res->ListFinancialEventsResult->FinancialEvents->ServiceFeeEventList->ServiceFeeEvent)) {
                        $getServiceFeeEventList = $res->ListFinancialEventsResult->FinancialEvents->ServiceFeeEventList->ServiceFeeEvent;
                    }
                    if (isset($res->ListFinancialEventsByNextTokenResult->FinancialEvents->ServiceFeeEventList->ServiceFeeEvent)) {
                        $getServiceFeeEventList = $res->ListFinancialEventsByNextTokenResult->FinancialEvents->ServiceFeeEventList->ServiceFeeEvent;
                    }

                    if (isset($res->ListFinancialEventsResult->FinancialEvents->ServiceFeeEventList)) {
                        $getServiceFeeEventListCheck = $res->ListFinancialEventsResult->FinancialEvents->ServiceFeeEventList;
                    }
                    if (isset($res->ListFinancialEventsByNextTokenResult->FinancialEvents->ServiceFeeEventList)) {
                        $getServiceFeeEventListCheck = $res->ListFinancialEventsByNextTokenResult->FinancialEvents->ServiceFeeEventList;
                    }
                    $serviceFeeEventUseKey = array('ServiceFeeEvent');
                    $checkResponse = $this->checkMwsNewApiKey($getServiceFeeEventListCheck, $serviceFeeEventUseKey, 'finance_service_fee_event_list', $user_id, $data["createDate"]);
                    if ($checkResponse['status'] == 1) {
                        unset($checkResponse['status']);
                        $newKeyLog[] = $checkResponse;
                    }
                    if (isset($getServiceFeeEventList->FeeList->FeeComponent->FeeType)) {
                        $serviceFeeEventListData[0]['fee_type'] = (string) $getServiceFeeEventList->FeeList->FeeComponent->FeeType;
                    }
                    if (isset($getServiceFeeEventList->FeeList->FeeComponent->FeeAmount->CurrencyAmount)) {
                        $serviceFeeEventListData[0]['fee_amount'] = (string) $getServiceFeeEventList->FeeList->FeeComponent->FeeAmount->CurrencyAmount;
                    }
                }
                $payload['ServiceFeeEventList'] = $serviceFeeEventListData;
            }
            /* End ServiceFeeEventList Data */
            // print_r($newKeyLog);
            // print_r($serviceFeeEventListData);
            // die();

            /* Start Save RefundEventList Data Array */
            
            $refundEventListData = [];
            if (isset($res->ListFinancialEventsResult->FinancialEvents->RefundEventList->ShipmentEvent[0]) || isset($res->ListFinancialEventsByNextTokenResult->FinancialEvents->RefundEventList->ShipmentEvent[0])) {
                if (isset($res->ListFinancialEventsResult->FinancialEvents->RefundEventList->ShipmentEvent[0])) {
                    $getRefundEventList = $res->ListFinancialEventsResult->FinancialEvents->RefundEventList->ShipmentEvent;
                }
                if (isset($res->ListFinancialEventsByNextTokenResult->FinancialEvents->RefundEventList->ShipmentEvent[0])) {
                    $getRefundEventList = $res->ListFinancialEventsByNextTokenResult->FinancialEvents->RefundEventList->ShipmentEvent;
                }

                // print_r($getRefundEventList);
                $refundEventUseKey = array('AmazonOrderId','PostedDate','ShipmentItemAdjustmentList','MarketplaceName','SellerOrderId');
                $checkResponse = $this->checkMwsNewApiKey($getRefundEventList, $refundEventUseKey, 'finance_refund_event_list-1', $user_id, $data["createDate"]);
                if ($checkResponse['status'] == 1) {
                    unset($checkResponse['status']);
                    $newKeyLog[] = $checkResponse;
                }

                $refundI = 0;
                foreach ($getRefundEventList as $getRefundEvent) {
                    date_default_timezone_set('UTC');
                    $get_posted_date = (string) $getRefundEvent->PostedDate;
                    /*$searchData  = array('T','Z');
                    $replaceData = array(' ','');
                    $changeData  = str_replace($searchData, $replaceData,$get_posted_date);
                    $posted_date = date('Y-m-d H:i:s', strtotime('-8 hours', strtotime($changeData)));*/
                    $dateTime = new DateTime ($get_posted_date);
                    $dateTime->setTimezone(new DateTimeZone('America/Los_Angeles'));
                    $posted_date = $dateTime->format('Y-m-d H:i:s');
                    $refundEventListData[$refundI]['amazonorderid']   = (string) $getRefundEvent->AmazonOrderId;
                    $refundEventListData[$refundI]['posteddate']      = $posted_date;
                    $refundEventListData[$refundI]['marketplacename'] = (string) $getRefundEvent->MarketplaceName;
                    $refundEventListData[$refundI]['sellerorderid']   = (string) $getRefundEvent->SellerOrderId;
                    $refundEventListData[$refundI]['orderadjustmentitemid'] = (string) $getRefundEvent->ShipmentItemAdjustmentList->ShipmentItem->OrderAdjustmentItemId;
                    $refundEventListData[$refundI]['quantityshipped']       = (string) $getRefundEvent->ShipmentItemAdjustmentList->ShipmentItem->QuantityShipped;
                    $refundEventListData[$refundI]['sellersku']             = (string) $getRefundEvent->ShipmentItemAdjustmentList->ShipmentItem->SellerSKU;
                    
                    if (isset($getRefundEvent->ShipmentItemAdjustmentList->ShipmentItem->ItemTaxWithheldList->TaxWithheldComponent->TaxesWithheld->ChargeComponent[0])) {
                        $MarketplaceFacilitators = $getRefundEvent->ShipmentItemAdjustmentList->ShipmentItem->ItemTaxWithheldList->TaxWithheldComponent->TaxesWithheld->ChargeComponent;
                        foreach ($MarketplaceFacilitators as $MarketplaceFacilitator) {
                            if ( (string) $MarketplaceFacilitator->ChargeType == "MarketplaceFacilitatorTax-Shipping") {
                                $refundEventListData[$refundI]['marketplacefacilitatortaxshipping'] = (string) $MarketplaceFacilitator->ChargeAmount->CurrencyAmount;
                            }
                            if ( (string) $MarketplaceFacilitator->ChargeType == "MarketplaceFacilitatorTax-Principal") {
                                $refundEventListData[$refundI]['marketplacefacilitatortaxprincipal'] = (string) $MarketplaceFacilitator->ChargeAmount->CurrencyAmount;
                            }
                        }
                    }

                    $refundEventAmazonOrderId = (string) $getRefundEvent->AmazonOrderId;
                    if (isset($getRefundEvent->ShipmentItemAdjustmentList)) {
                        $shipmentItemUseKey = array('ShipmentItem');
                        $checkResponse = $this->checkMwsNewApiKey($getRefundEvent->ShipmentItemAdjustmentList, $shipmentItemUseKey, 'finance_refund_event_list-2', $user_id, $data["createDate"],$refundEventAmazonOrderId);
                        if ($checkResponse['status'] == 1) {
                            unset($checkResponse['status']);
                            $newKeyLog[] = $checkResponse;
                        }
                    }

                    if (isset($getRefundEvent->ShipmentItemAdjustmentList->ShipmentItem)) {
                        $shipmentItemUseKey = array('ItemFeeAdjustmentList','OrderAdjustmentItemId','QuantityShipped','ItemChargeAdjustmentList','SellerSKU','PromotionAdjustmentList','ItemTaxWithheldList');
                        $checkResponse = $this->checkMwsNewApiKey($getRefundEvent->ShipmentItemAdjustmentList->ShipmentItem, $shipmentItemUseKey, 'finance_refund_event_list-3', $user_id, $data["createDate"],$refundEventAmazonOrderId);
                        if ($checkResponse['status'] == 1) {
                            unset($checkResponse['status']);
                            $newKeyLog[] = $checkResponse;
                        }
                    }

                    if (isset($getRefundEvent->ShipmentItemAdjustmentList->ShipmentItem->ItemTaxWithheldList->TaxWithheldComponent->TaxesWithheld->ChargeComponent->ChargeType)) {
                        $MarketplaceFacilitators = $getRefundEvent->ShipmentItemAdjustmentList->ShipmentItem->ItemTaxWithheldList->TaxWithheldComponent->TaxesWithheld->ChargeComponent->ChargeType;
                        if ( (string) $MarketplaceFacilitators->ChargeType == "MarketplaceFacilitatorTax-Shipping") {
                            $refundEventListData[$refundI]['marketplacefacilitatortaxshipping'] = (string) $MarketplaceFacilitators->ChargeAmount->CurrencyAmount;
                        }
                        if ( (string) $MarketplaceFacilitators->ChargeType == "MarketplaceFacilitatorTax-Principal") {
                            $refundEventListData[$refundI]['marketplacefacilitatortaxprincipal'] = (string) $MarketplaceFacilitators->ChargeAmount->CurrencyAmount;
                        }
                    }

                    if (isset($getRefundEvent->ShipmentItemAdjustmentList->ShipmentItem->PromotionAdjustmentList->Promotion)) {
                        $refundShipmentItems = $getRefundEvent->ShipmentItemAdjustmentList->ShipmentItem->PromotionAdjustmentList->Promotion;
                        $PromotionAdjustmentList = array();
                        $PromotionAdjustmentListId = array();
                        foreach ($refundShipmentItems as $refundShipmentItem) {
                            if ($refundShipmentItem->PromotionType=="PromotionMetaDataDefinitionValue") {
                                $PromotionAdjustmentList[] = (string) $refundShipmentItem->PromotionAmount->CurrencyAmount;
                                $PromotionAdjustmentListId[] = (string) $refundShipmentItem->PromotionId;
                            }
                        }
                        if (!empty($PromotionAdjustmentList)) {
                            $refundEventListData[$refundI]['promotionmetadatadefinitionvalue'] = implode(",", $PromotionAdjustmentList);
                            $refundEventListData[$refundI]['promotionid'] = implode(",", $PromotionAdjustmentListId);
                        }
                    }

                    if (isset($getRefundEvent->ShipmentItemAdjustmentList->ShipmentItem->ItemFeeAdjustmentList->FeeComponent)) {
                        $refundShipmentItems = $getRefundEvent->ShipmentItemAdjustmentList->ShipmentItem->ItemFeeAdjustmentList->FeeComponent;
                        foreach ($refundShipmentItems as $refundShipmentItem) {
                            if ( (string) $refundShipmentItem->FeeType == "Commission") {
                                $refundEventListData[$refundI]['commission'] = (string) $refundShipmentItem->FeeAmount->CurrencyAmount;
                            }
                            if ( (string) $refundShipmentItem->FeeType == "RefundCommission") {
                                $refundEventListData[$refundI]['refundcommission'] = (string) $refundShipmentItem->FeeAmount->CurrencyAmount;
                            }
                        }
                    }

                    if (isset($getRefundEvent->ShipmentItemAdjustmentList->ShipmentItem->ItemChargeAdjustmentList->ChargeComponent)) {
                        $refundChargeComponents = $getRefundEvent->ShipmentItemAdjustmentList->ShipmentItem->ItemChargeAdjustmentList->ChargeComponent;
                        foreach ($refundChargeComponents as $refundChargeComponent) {
                            if ( (string) $refundChargeComponent->ChargeType == "Tax") {
                                $refundEventListData[$refundI]['tax'] = (string) $refundChargeComponent->ChargeAmount->CurrencyAmount;
                            }
                            if ( (string) $refundChargeComponent->ChargeType == "Principal") {
                                $refundEventListData[$refundI]['principal'] = (string) $refundChargeComponent->ChargeAmount->CurrencyAmount;
                            }
                            if ( (string) $refundChargeComponent->ChargeType == "ShippingTax") {
                                $refundEventListData[$refundI]['shippingtax'] = (string) $refundChargeComponent->ChargeAmount->CurrencyAmount;
                            }
                            if ( (string) $refundChargeComponent->ChargeType == "ShippingCharge") {
                                $refundEventListData[$refundI]['shippingcharge'] = (string) $refundChargeComponent->ChargeAmount->CurrencyAmount;
                            }
                        }
                    }
                    $refundI++;
                }
                $payload['RefundEventList'] = $refundEventListData;
            } else {
                if ((isset($res->ListFinancialEventsResult->FinancialEvents->RefundEventList->ShipmentEvent) && isset($res->ListFinancialEventsResult->FinancialEvents->RefundEventList->ShipmentEvent->AmazonOrderId)) || (isset($res->ListFinancialEventsByNextTokenResult->FinancialEvents->RefundEventList->ShipmentEvent) && isset($res->ListFinancialEventsByNextTokenResult->FinancialEvents->RefundEventList->ShipmentEvent->AmazonOrderId))) {
                    if (isset($res->ListFinancialEventsResult->FinancialEvents->RefundEventList->ShipmentEvent) && isset($res->ListFinancialEventsResult->FinancialEvents->RefundEventList->ShipmentEvent->AmazonOrderId)) {
                        $getRefundEventList = $res->ListFinancialEventsResult->FinancialEvents->RefundEventList->ShipmentEvent;
                    }
                    if (isset($res->ListFinancialEventsByNextTokenResult->FinancialEvents->RefundEventList->ShipmentEvent) && isset($res->ListFinancialEventsByNextTokenResult->FinancialEvents->RefundEventList->ShipmentEvent->AmazonOrderId)) {
                        $getRefundEventList = $res->ListFinancialEventsByNextTokenResult->FinancialEvents->RefundEventList->ShipmentEvent;
                    }

                    $refundEventAmazonOrderId = (string) $getRefundEventList->AmazonOrderId;

                    $refundEventUseKey = array('AmazonOrderId','PostedDate','ShipmentItemAdjustmentList','MarketplaceName','SellerOrderId');
                    $checkResponse = $this->checkMwsNewApiKey($getRefundEventList, $refundEventUseKey, 'finance_refund_event_list-1', $user_id, $data["createDate"],$refundEventAmazonOrderId);
                    if ($checkResponse['status'] == 1) {
                        unset($checkResponse['status']);
                        $newKeyLog[] = $checkResponse;
                    }

                    $get_posted_date = (string) $getRefundEventList->PostedDate;
                    date_default_timezone_set('UTC');
                    /*$searchData  = array('T','Z');
                    $replaceData = array(' ','');
                    $changeData  = str_replace($searchData, $replaceData,$get_posted_date);
                    $posted_date = date('Y-m-d H:i:s', strtotime('-8 hours', strtotime($changeData)));*/
                    $dateTime = new DateTime ($get_posted_date);
                    $dateTime->setTimezone(new DateTimeZone('America/Los_Angeles'));
                    $posted_date = $dateTime->format('Y-m-d H:i:s');
                    $refundEventListData[0]['amazonorderid']   = (string) $getRefundEventList->AmazonOrderId;
                    $refundEventListData[0]['posteddate']      = $posted_date;
                    $refundEventListData[0]['marketplacename'] = (string) $getRefundEventList->MarketplaceName;
                    $refundEventListData[0]['sellerorderid']   = (string) $getRefundEventList->SellerOrderId;
                    $refundEventListData[0]['orderadjustmentitemid'] = (string) $getRefundEventList->ShipmentItemAdjustmentList->ShipmentItem->OrderAdjustmentItemId;
                    $refundEventListData[0]['quantityshipped']       = (string) $getRefundEventList->ShipmentItemAdjustmentList->ShipmentItem->QuantityShipped;
                    $refundEventListData[0]['sellersku']             = (string) $getRefundEventList->ShipmentItemAdjustmentList->ShipmentItem->SellerSKU;
                    
                    if (isset($getRefundEventList->ShipmentItemAdjustmentList)) {
                        $shipmentItemUseKey = array('ShipmentItem');
                        $checkResponse = $this->checkMwsNewApiKey($getRefundEventList->ShipmentItemAdjustmentList, $shipmentItemUseKey, 'finance_refund_event_list-2', $user_id, $data["createDate"],$refundEventAmazonOrderId);
                        if ($checkResponse['status'] == 1) {
                            unset($checkResponse['status']);
                            $newKeyLog[] = $checkResponse;
                        }
                    }

                    if (isset($getRefundEventList->ShipmentItemAdjustmentList->ShipmentItem)) {
                        $shipmentItemUseKey = array('ItemFeeAdjustmentList','OrderAdjustmentItemId','QuantityShipped','ItemChargeAdjustmentList','SellerSKU','PromotionAdjustmentList','ItemTaxWithheldList');
                        $checkResponse = $this->checkMwsNewApiKey($getRefundEventList->ShipmentItemAdjustmentList->ShipmentItem, $shipmentItemUseKey, 'finance_refund_event_list-3', $user_id, $data["createDate"],$refundEventAmazonOrderId);
                        if ($checkResponse['status'] == 1) {
                            unset($checkResponse['status']);
                            $newKeyLog[] = $checkResponse;
                        }
                    }

                    if (isset($getRefundEventList->ShipmentItemAdjustmentList->ShipmentItem->ItemTaxWithheldList->TaxWithheldComponent->TaxesWithheld->ChargeComponent[0])) {
                        $MarketplaceFacilitators = $getRefundEventList->ShipmentItemAdjustmentList->ShipmentItem->ItemTaxWithheldList->TaxWithheldComponent->TaxesWithheld->ChargeComponent;
                        foreach ($MarketplaceFacilitators as $MarketplaceFacilitator) {
                            if ( (string) $MarketplaceFacilitator->ChargeType == "MarketplaceFacilitatorTax-Shipping") {
                                $refundEventListData[0]['marketplacefacilitatortaxshipping'] = (string) $MarketplaceFacilitator->ChargeAmount->CurrencyAmount;
                            }
                            if ( (string) $MarketplaceFacilitator->ChargeType == "MarketplaceFacilitatorTax-Principal") {
                                $refundEventListData[0]['marketplacefacilitatortaxprincipal'] = (string) $MarketplaceFacilitator->ChargeAmount->CurrencyAmount;
                            }
                        }
                    }

                    if (isset($getRefundEventList->ShipmentItemAdjustmentList->ShipmentItem->ItemTaxWithheldList->TaxWithheldComponent->TaxesWithheld->ChargeComponent->ChargeType)) {
                        $MarketplaceFacilitators = $getRefundEventList->ShipmentItemAdjustmentList->ShipmentItem->ItemTaxWithheldList->TaxWithheldComponent->TaxesWithheld->ChargeComponent->ChargeType;
                        if ( (string) $MarketplaceFacilitators->ChargeType == "MarketplaceFacilitatorTax-Shipping") {
                            $refundEventListData[0]['marketplacefacilitatortaxshipping'] = (string) $MarketplaceFacilitators->ChargeAmount->CurrencyAmount;
                        }
                        if ( (string) $MarketplaceFacilitators->ChargeType == "MarketplaceFacilitatorTax-Principal") {
                            $refundEventListData[0]['marketplacefacilitatortaxprincipal'] = (string) $MarketplaceFacilitators->ChargeAmount->CurrencyAmount;
                        }
                    }

                    if (isset($getRefundEventList->ShipmentItemAdjustmentList->ShipmentItem->ItemFeeAdjustmentList->FeeComponent)) {
                        $refundShipmentItems = $getRefundEventList->ShipmentItemAdjustmentList->ShipmentItem->ItemFeeAdjustmentList->FeeComponent;
                        foreach ($refundShipmentItems as $refundShipmentItem) {
                            if ( (string) $refundShipmentItem->FeeType == "Commission") {
                                $refundEventListData[0]['commission'] = (string) $refundShipmentItem->FeeAmount->CurrencyAmount;
                            }
                            if ( (string) $refundShipmentItem->FeeType == "RefundCommission") {
                                $refundEventListData[0]['refundcommission'] = (string) $refundShipmentItem->FeeAmount->CurrencyAmount;
                            }
                        }
                    }

                    if (isset($getRefundEventList->ShipmentItemAdjustmentList->ShipmentItem->PromotionAdjustmentList->Promotion)) {
                        $refundShipmentItems = $getRefundEventList->ShipmentItemAdjustmentList->ShipmentItem->PromotionAdjustmentList->Promotion;
                        $PromotionAdjustmentList = array();
                        $PromotionAdjustmentListId = array();
                        foreach ($refundShipmentItems as $refundShipmentItem) {
                            if ($refundShipmentItem->PromotionType=="PromotionMetaDataDefinitionValue") {
                                $PromotionAdjustmentList[] = (string) $refundShipmentItem->PromotionAmount->CurrencyAmount;
                                $PromotionAdjustmentListId[] = (string) $refundShipmentItem->PromotionId;
                            }
                        }
                        if (!empty($PromotionAdjustmentList)) {
                            $refundEventListData[$refundI]['promotionmetadatadefinitionvalue'] = implode(",", $PromotionAdjustmentList);
                            $refundEventListData[$refundI]['promotionid'] = implode(",", $PromotionAdjustmentListId);
                        }
                    }

                    if (isset($getRefundEventList->ShipmentItemAdjustmentList->ShipmentItem->ItemChargeAdjustmentList->ChargeComponent)) {
                        $refundChargeComponents = $getRefundEventList->ShipmentItemAdjustmentList->ShipmentItem->ItemChargeAdjustmentList->ChargeComponent;
                        foreach ($refundChargeComponents as $refundChargeComponent) {
                            if ( (string) $refundChargeComponent->ChargeType == "Tax") {
                                $refundEventListData[0]['tax'] = (string) $refundChargeComponent->ChargeAmount->CurrencyAmount;
                            }
                            if ( (string) $refundChargeComponent->ChargeType == "Principal") {
                                $refundEventListData[0]['principal'] = (string) $refundChargeComponent->ChargeAmount->CurrencyAmount;
                            }
                            if ( (string) $refundChargeComponent->ChargeType == "ShippingTax") {
                                $refundEventListData[0]['shippingtax'] = (string) $refundChargeComponent->ChargeAmount->CurrencyAmount;
                            }
                            if ( (string) $refundChargeComponent->ChargeType == "ShippingCharge") {
                                $refundEventListData[0]['shippingcharge'] = (string) $refundChargeComponent->ChargeAmount->CurrencyAmount;
                            }
                        }
                    }                    
                }
                $payload['RefundEventList'] = $refundEventListData;
            }
            /* End Save RefundEventList Data Array */
            // print_r($newKeyLog);
            // print_r($refundEventListData);
            // die();


            if (isset($res->ListFinancialEventsResult->FinancialEvents->ShipmentEventList->ShipmentEvent[0]) || isset($res->ListFinancialEventsByNextTokenResult->FinancialEvents->ShipmentEventList->ShipmentEvent[0]))
            {
                if (isset($res->ListFinancialEventsResult->NextToken) && !empty($res->ListFinancialEventsResult->NextToken)) {
                    $tokens = (string) $res->ListFinancialEventsResult->NextToken;
                    $data['tokens'] = $tokens;
                    $data['numberCheck'] = $number;
                    // $this->fetch_product_details($user_id,$order_id,$amz_country_code,$country_code, $tokens);
                }
                if (isset($res->ListFinancialEventsByNextTokenResult->NextToken) && !empty($res->ListFinancialEventsByNextTokenResult->NextToken)) {
                    $tokens = (string) $res->ListFinancialEventsByNextTokenResult->NextToken;
                    $data['tokens'] = $tokens;
                    $data['numberCheck'] = $number;
                    // $this->fetch_product_details($user_id,$order_id,$amz_country_code,$country_code, $tokens);
                }
                if (isset($res->ListFinancialEventsResult->FinancialEvents->ShipmentEventList->ShipmentEvent[0]))
                {
                    $getShipmentEventList = $res->ListFinancialEventsResult->FinancialEvents->ShipmentEventList->ShipmentEvent;
                }
                if (isset($res->ListFinancialEventsByNextTokenResult->FinancialEvents->ShipmentEventList->ShipmentEvent[0]))
                {
                    $getShipmentEventList = $res->ListFinancialEventsByNextTokenResult->FinancialEvents->ShipmentEventList->ShipmentEvent;
                }

                // print_r($getShipmentEventList->ShipmentItemList->ShipmentItem);

                $i = 0;
                // start for each
                foreach ($getShipmentEventList as $key => $ShipmentEvent)
                {
                    date_default_timezone_set('UTC');
                    $dev_ref = $this->generate_random_strings();
                    $dev_ref = $dev_ref.time();
                    $get_posted_date = (string) $ShipmentEvent->PostedDate;
                    /*$searchData  = array('T','Z');
                    $replaceData = array(' ','');
                    $changeData  = str_replace($searchData, $replaceData,$get_posted_date);
                    $posted_date = date('Y-m-d H:i:s', strtotime('-8 hours', strtotime($changeData)));*/
                    $dateTime = new DateTime ($get_posted_date);
                    $dateTime->setTimezone(new DateTimeZone('America/Los_Angeles'));
                    $posted_date = $dateTime->format('Y-m-d H:i:s');

                    $dateTimeGmt = new DateTime ($get_posted_date);
                    $dateTimeGmt->setTimezone(new DateTimeZone('GMT'));
                    $posted_date_gmt = $dateTimeGmt->format('Y-m-d H:i:s');

                    $dateTimePst = new DateTime ($get_posted_date);
                    $dateTimePst->setTimezone(new DateTimeZone('PST'));
                    $posted_date_pst = $dateTimePst->format('Y-m-d H:i:s');

                    $amazonOrderIdForLog            = (string) $ShipmentEvent->AmazonOrderId;
                    $payload[$i]['amazon_order_id'] = $amazonOrderIdForLog;
                    $payload[$i]['posted_date']     = $posted_date;
                    $payload[$i]['posted_date_gmt'] = $posted_date_gmt;
                    $payload[$i]['posted_date_pst'] = $posted_date_pst;
                    $payload[$i]['market_place']    = (string) $ShipmentEvent->MarketplaceName;
                    $payload[$i]['seller_order_id'] = (string) $ShipmentEvent->SellerOrderId;
                    $payload[$i]['dev_ref']         = $dev_ref;
                    $payload[$i]['dev_date']        = $get_posted_date;

                    $serviceFeeEventUseKey = array('ShipmentItemList','AmazonOrderId','MarketplaceName','PostedDate','SellerOrderId','ShipmentFeeList');
                    $checkResponse = $this->checkMwsNewApiKey($ShipmentEvent, $serviceFeeEventUseKey, 'finance_data-1', $user_id, $data["createDate"],$amazonOrderIdForLog);
                    if ($checkResponse['status'] == 1) {
                        unset($checkResponse['status']);
                        $newKeyLog[] = $checkResponse;
                    }
                    if (isset($ShipmentEvent->ShipmentItemList->ShipmentItem->ItemFeeList)) {
                        $serviceFeeEventUseKey = array('FeeComponent');
                        $checkResponse = $this->checkMwsNewApiKey($ShipmentEvent->ShipmentItemList->ShipmentItem->ItemFeeList, $serviceFeeEventUseKey, 'finance_data-4', $user_id, $data["createDate"],$amazonOrderIdForLog);
                        if ($checkResponse['status'] == 1) {
                            unset($checkResponse['status']);
                            $newKeyLog[] = $checkResponse;
                        }
                    }

                    if (isset($ShipmentEvent->ShipmentItemList->ShipmentItem->ItemChargeList)) {
                        $serviceFeeEventUseKey = array('ChargeComponent');
                        $checkResponse = $this->checkMwsNewApiKey($ShipmentEvent->ShipmentItemList->ShipmentItem->ItemChargeList, $serviceFeeEventUseKey, 'finance_data-3', $user_id, $data["createDate"],$amazonOrderIdForLog);
                        if ($checkResponse['status'] == 1) {
                            unset($checkResponse['status']);
                            $newKeyLog[] = $checkResponse;
                        }
                    }

                    if (isset($ShipmentEvent->ShipmentFeeList)) {
                        $shipmentFeeListKey = array('FeeComponent');
                        $checkResponse = $this->checkMwsNewApiKey($ShipmentEvent->ShipmentFeeList, $shipmentFeeListKey, 'finance_data-10', $user_id, $data["createDate"],$amazonOrderIdForLog);
                        if ($checkResponse['status'] == 1) {
                            unset($checkResponse['status']);
                            $newKeyLog[] = $checkResponse;
                        }
                        if (isset($ShipmentEvent->ShipmentFeeList->FeeComponent)) {
                            $shipmentFeeListDataI = 0;
                            $shipmentFeeListDataArray = array();
                            foreach ($ShipmentEvent->ShipmentFeeList->FeeComponent as $shipmentFeeListData) {
                                $shipment_fee_list_dev_ref = $this->generate_random_strings();
                                $shipment_fee_list_dev_ref = $shipment_fee_list_dev_ref.time();
                                $shipmentFeeListDataArray[$shipmentFeeListDataI]['fee_type'] = (string) $shipmentFeeListData->FeeType;
                                $shipmentFeeListDataArray[$shipmentFeeListDataI]['fee_amount'] = (string) $shipmentFeeListData->FeeAmount->CurrencyAmount;
                                $shipmentFeeListDataArray[$shipmentFeeListDataI]['currency_code'] = (string) $shipmentFeeListData->FeeAmount->CurrencyCode;
                                $shipmentFeeListDataArray[$shipmentFeeListDataI]['amazon_order_id'] = $amazonOrderIdForLog;
                                $shipmentFeeListDataArray[$shipmentFeeListDataI]['order_dev_ref'] = $dev_ref;
                                $shipmentFeeListDataArray[$shipmentFeeListDataI]['dev_date']     = $get_posted_date;
                                $shipmentFeeListDataArray[$shipmentFeeListDataI]['shipment_fee_list_dev_ref'] = $shipment_fee_list_dev_ref;
                                $shipmentFeeListDataI++;
                            }
                            if (!empty($shipmentFeeListDataArray)) {
                                $payload[$i]['ShipmentFeeList'] = $shipmentFeeListDataArray;
                            }
                        }
                    }

                    if (isset($ShipmentEvent->ShipmentItemList->ShipmentItem)) {
                        $shipmentItemListShipmentItem = $ShipmentEvent->ShipmentItemList->ShipmentItem;
                        $serviceFeeEventUseKey = array('ItemChargeList','PromotionList','ItemFeeList','SellerSKU','OrderItemId','QuantityShipped','ItemTaxWithheldList');
                        $checkResponse = $this->checkMwsNewApiKey($shipmentItemListShipmentItem, $serviceFeeEventUseKey, 'finance_data-2', $user_id, $data["createDate"],$amazonOrderIdForLog);
                        if ($checkResponse['status'] == 1) {
                            unset($checkResponse['status']);
                            $newKeyLog[] = $checkResponse;
                        }
                        if (isset($shipmentItemListShipmentItem[0])) {
                            $shipmentItemListsI = 0;
                            $shipmentItemListsArray = array();
                            foreach ($shipmentItemListShipmentItem as $shipmentItemLists) {
                                // Item add code columns
                                $shipmentItemDevRef = $this->generate_random_strings();
                                $shipmentItemDevRef = $shipmentItemDevRef.time();
                                $shipmentItemListsArray[$shipmentItemListsI]['order_item_id'] = (string) $shipmentItemLists->OrderItemId;
                                $shipmentItemListsArray[$shipmentItemListsI]['quantity_shipped'] = (string) $shipmentItemLists->QuantityShipped;
                                $shipmentItemListsArray[$shipmentItemListsI]['seller_sku'] = (string) $shipmentItemLists->SellerSKU;
                                $shipmentItemListsArray[$shipmentItemListsI]['amazon_order_id'] = $amazonOrderIdForLog;
                                $shipmentItemListsArray[$shipmentItemListsI]['order_dev_ref'] = $dev_ref;
                                $shipmentItemListsArray[$shipmentItemListsI]['item_dev_ref'] = $shipmentItemDevRef;
                                $shipmentItemListsArray[$shipmentItemListsI]['dev_date']     = $get_posted_date;
                                // End Item add code columns

                                // Add item fee list columns
                                if (isset($shipmentItemLists->ItemFeeList) && !empty($shipmentItemLists->ItemFeeList)) {
                                    if (isset($shipmentItemLists->ItemFeeList->FeeComponent)) {
                                        $serviceFeeEventUseKey = array('FBAPerUnitFulfillmentFee','Commission','FixedClosingFee','GiftwrapChargeback','ShippingChargeback','VariableClosingFee','BubblewrapFee','FBACustomerReturnPerOrderFee','FBACustomerReturnPerUnitFee','FBACustomerReturnWeightBasedFee','FBADisposalFee','FBAFulfillmentCODFee','FBAInboundConvenienceFee','FBAInboundDefectFee','FBAInboundTransportationFee','FBAInboundTransportationProgramFee','FBALongTermStorageFee','FBAOverageFee','FBAPerOrderFulfillmentFee','FBARemovalFee','FBAStorageFee','FBATransportationFee','FBAWeightBasedFee','FulfillmentFee','FulfillmentNetworkFee','LabelingFee','OpaqueBaggingFee','PolybaggingFee','SSOFFulfillmentFee','TapingFee','TransportationFee','UnitFulfillmentFee','SalesTaxCollectionFee','ShippingHB','GiftwrapCommission','GetPaidFasterFee');
                                        $checkResponse = $this->checkMwsApiMissingTypeKey('FeeType', $shipmentItemLists->ItemFeeList->FeeComponent, $serviceFeeEventUseKey, 'finance_data-7', $user_id, $data["createDate"],$amazonOrderIdForLog);
                                        if ($checkResponse['status'] == 1) {
                                            unset($checkResponse['status']);
                                            $newKeyLog[] = $checkResponse;
                                        }
                                        $ItemFeeListI = 0;
                                        $ItemFeeListArray = array();
                                        foreach ($shipmentItemLists->ItemFeeList->FeeComponent as $ItemFeeListData) {
                                            $ItemFeeListArray[$ItemFeeListI]['fee_type'] = (string) $ItemFeeListData->FeeType;
                                            $ItemFeeListArray[$ItemFeeListI]['fee_amount'] = (string) $ItemFeeListData->FeeAmount->CurrencyAmount;
                                            $ItemFeeListArray[$ItemFeeListI]['currency_code'] = (string) $ItemFeeListData->FeeAmount->CurrencyCode;
                                            $ItemFeeListArray[$ItemFeeListI]['amazon_order_id'] = $amazonOrderIdForLog;
                                            $ItemFeeListArray[$ItemFeeListI]['order_dev_ref'] = $dev_ref;
                                            $ItemFeeListArray[$ItemFeeListI]['item_dev_ref'] = $shipmentItemDevRef;
                                            $ItemFeeListArray[$ItemFeeListI]['dev_date']     = $get_posted_date;
                                            $ItemFeeListI++;
                                        }
                                        if (!empty($ItemFeeListArray)) {
                                            $shipmentItemListsArray[$shipmentItemListsI]['ItemFeeList'] = $ItemFeeListArray;
                                        }
                                    }

                                }
                                // End Add item fee list columns

                                // Add item Charge list columns
                                if (isset($shipmentItemLists->ItemChargeList) && !empty($shipmentItemLists->ItemChargeList)) {
                                    if (isset($shipmentItemLists->ItemChargeList->ChargeComponent)) {
                                        $serviceFeeEventUseKey = array('Principal','Tax','GiftWrap','GiftWrapTax','ShippingCharge','ShippingTax','MarketplaceFacilitatorTax-Principal','MarketplaceFacilitatorTax-Shipping','MarketplaceFacilitatorTax-Giftwrap','MarketplaceFacilitatorTax-Other','TaxDiscount','CODItemCharge','CODItemTaxCharge','CODOrderCharge','CODOrderTaxCharge','CODShippingCharge','CODShippingTaxCharge','Goodwill','RestockingFee','ReturnShipping','PointsFee','GenericDeduction','FreeReplacementReturnShipping','PaymentMethodFee','ExportCharge','SAFE-TReimbursement','TCS-CGST','TCS-SGST','TCS-IGST','TCS-UTGST');
                                        $checkResponse = $this->checkMwsApiMissingTypeKey('ChargeType', $shipmentItemLists->ItemChargeList->ChargeComponent, $serviceFeeEventUseKey, 'finance_data-6', $user_id, $data["createDate"],$amazonOrderIdForLog);
                                        if ($checkResponse['status'] == 1) {
                                            unset($checkResponse['status']);
                                            $newKeyLog[] = $checkResponse;
                                        }

                                        $ItemChargeListI = 0;
                                        $ItemChargeListArray = array();
                                        foreach ($shipmentItemLists->ItemChargeList->ChargeComponent as $itemChargeListData) {
                                            $ItemChargeListArray[$ItemChargeListI]['charge_type'] = (string) $itemChargeListData->ChargeType;
                                            $ItemChargeListArray[$ItemChargeListI]['charge_amount'] = (string) $itemChargeListData->ChargeAmount->CurrencyAmount;
                                            $ItemChargeListArray[$ItemChargeListI]['currency_code'] = (string) $itemChargeListData->ChargeAmount->CurrencyCode;
                                            $ItemChargeListArray[$ItemChargeListI]['amazon_order_id'] = $amazonOrderIdForLog;
                                            $ItemChargeListArray[$ItemChargeListI]['order_dev_ref'] = $dev_ref;
                                            $ItemChargeListArray[$ItemChargeListI]['item_dev_ref'] = $shipmentItemDevRef;
                                            $ItemChargeListArray[$ItemChargeListI]['dev_date']     = $get_posted_date;
                                            $ItemChargeListI++;
                                        }
                                        if (!empty($ItemChargeListArray)) {
                                            $shipmentItemListsArray[$shipmentItemListsI]['ItemChargeList'] = $ItemChargeListArray;
                                        }
                                    }
                                }
                                // End Add item Charge list columns

                                // Add Promotion List columns
                                if (isset($shipmentItemLists->PromotionList) && !empty($shipmentItemLists->PromotionList)) {
                                    $serviceFeeEventUseKey = array('Promotion');
                                    $checkResponse = $this->checkMwsNewApiKey($shipmentItemLists->PromotionList, $serviceFeeEventUseKey, 'finance_data-5', $user_id, $data["createDate"],$amazonOrderIdForLog);
                                    if ($checkResponse['status'] == 1) {
                                        unset($checkResponse['status']);
                                        $newKeyLog[] = $checkResponse;
                                    }

                                    if (isset($shipmentItemLists->PromotionList->Promotion)) {
                                        $promotionListI = 0;
                                        $promotionListArray = array();
                                        foreach ($shipmentItemLists->PromotionList->Promotion as $promotionListData) {
                                            $promotionListArray[$promotionListI]['promotion_type'] = (string) $promotionListData->PromotionType;
                                            $promotionListArray[$promotionListI]['promotion_amount'] = (string) $promotionListData->PromotionAmount->CurrencyAmount;
                                            $promotionListArray[$promotionListI]['currency_code'] = (string) $promotionListData->PromotionAmount->CurrencyCode;
                                            $promotionListArray[$promotionListI]['promotion_id'] = (string) $promotionListData->PromotionId;
                                            $promotionListArray[$promotionListI]['amazon_order_id'] = $amazonOrderIdForLog;
                                            $promotionListArray[$promotionListI]['order_dev_ref'] = $dev_ref;
                                            $promotionListArray[$promotionListI]['item_dev_ref'] = $shipmentItemDevRef;
                                            $promotionListArray[$promotionListI]['dev_date']     = $get_posted_date;
                                            $promotionListI++;
                                        }
                                        if (!empty($promotionListArray)) {
                                            $shipmentItemListsArray[$shipmentItemListsI]['PromotionList'] = $promotionListArray;
                                        }
                                    }
                                }
                                // End Add Promotion List columns


                                // echo "<br>Start ItemFeeList<br>";
                                // print_r($shipmentItemLists->ItemTaxWithheldList);
                                // echo "<br>End ItemFeeList<br><br>";


                                // Add Promotion List columns

                                if (isset($shipmentItemLists->ItemTaxWithheldList) && !empty($shipmentItemLists->ItemTaxWithheldList)) {
                                    $itemTaxWithheldListKey = array('TaxWithheldComponent');
                                    $checkResponse = $this->checkMwsNewApiKey($shipmentItemLists->ItemTaxWithheldList, $itemTaxWithheldListKey, 'finance_data-8', $user_id, $data["createDate"],$amazonOrderIdForLog);
                                    if ($checkResponse['status'] == 1) {
                                        unset($checkResponse['status']);
                                        $newKeyLog[] = $checkResponse;
                                    }
                                    if (isset($shipmentItemLists->ItemTaxWithheldList->TaxWithheldComponent)) {
                                        $taxWithheldComponent = $shipmentItemLists->ItemTaxWithheldList->TaxWithheldComponent;
                                        $taxWithheldComponentKey = array('TaxCollectionModel','TaxesWithheld');
                                        $checkResponse = $this->checkMwsNewApiKey($taxWithheldComponent, $taxWithheldComponentKey, 'finance_data-9', $user_id, $data["createDate"],$amazonOrderIdForLog);
                                        if ($checkResponse['status'] == 1) {
                                            unset($checkResponse['status']);
                                            $newKeyLog[] = $checkResponse;
                                        }

                                        if (isset($taxWithheldComponent->TaxesWithheld->ChargeComponent) && !empty($taxWithheldComponent->TaxesWithheld->ChargeComponent)) {
                                            $taxesWithheldChargeComponentData = $taxWithheldComponent->TaxesWithheld->ChargeComponent;
                                            $taxesWithheldChargeComponentI = 0;
                                            $taxesWithheldChargeComponentArray = array();
                                            foreach ($taxesWithheldChargeComponentData as $taxesWithheldChargeComponent) {
                                                $taxesWithheldChargeComponentArray[$taxesWithheldChargeComponentI]['charge_type']     = (string) $taxesWithheldChargeComponent->ChargeType;
                                                $taxesWithheldChargeComponentArray[$taxesWithheldChargeComponentI]['charge_amount']   = (string) $taxesWithheldChargeComponent->ChargeAmount->CurrencyAmount;
                                                $taxesWithheldChargeComponentArray[$taxesWithheldChargeComponentI]['currency_code']   = (string) $taxesWithheldChargeComponent->ChargeAmount->CurrencyCode;
                                                $taxesWithheldChargeComponentArray[$taxesWithheldChargeComponentI]['amazon_order_id'] = $amazonOrderIdForLog;
                                                $taxesWithheldChargeComponentArray[$taxesWithheldChargeComponentI]['order_dev_ref']   = $dev_ref;
                                                $taxesWithheldChargeComponentArray[$taxesWithheldChargeComponentI]['item_dev_ref']    = $shipmentItemDevRef;
                                                $taxesWithheldChargeComponentArray[$taxesWithheldChargeComponentI]['dev_date']        = $get_posted_date;
                                                $taxesWithheldChargeComponentI++;
                                            }
                                            if (!empty($taxesWithheldChargeComponentArray)) {
                                                $shipmentItemListsArray[$shipmentItemListsI]['ItemTaxWithheldList'] = $taxesWithheldChargeComponentArray;
                                            }
                                        }
                                    }
                                }
                                // End Add Promotion List columns

                                $shipmentItemListsI++;
                            }
                            if (!empty($shipmentItemListsArray)) {
                                $payload[$i]['shipmentItemList'] = $shipmentItemListsArray;
                            }
                        }
                    }                    
                    $i++;
                }
            }
            // print_r($newKeyLog);
            // echo "<prE>";print_r($payload);
            // die('Process_finance_api 1717');

            $payload['mwsNewDataLog'] = $newKeyLog;
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

            //echo "<prE>";print_r($payload);die('Process_finance_api 2412');

            if(count($payload) > 0 && !empty($payload['order_id']))
            {
                $data['status_code']  =   1;
                $data['status_text']  =   "Success";
                $data['payload']      =   $payload;
            }
            else
            {
                $data['status_code']    = 3;
                $data['status_text']    = "No Data";
                $payload['lm_ean']      = $order_id;
                $payload['asin_counts'] = -3;
                $payload['lm_asin']     = '';
                $data['payload']        = $payload;
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

    public function checkMwsNewApiKey($apiData, $useKey, $tableName, $userId, $date, $amazonOrderIdForLog = null)
    {
        $responseStatus = array();
        $responseStatus['status'] = 0;
        $getApiKeys = array_keys((array) $apiData);
        $getNewKeys = array_diff($getApiKeys,$useKey);
        if (!empty($getNewKeys)) {
            $notAddData = array();
            foreach ($getNewKeys as $getNewKey) {
                $notAddData[$getNewKey] = $apiData->$getNewKey;
            }
            if (!empty($amazonOrderIdForLog) && !is_null($amazonOrderIdForLog)) {
                $responseStatus['amazon_order_id'] = $amazonOrderIdForLog;
            }
            $responseStatus['status']     = 1;
            $responseStatus['table_name'] = $tableName;
            $responseStatus['user_id']    = $userId;
            $responseStatus['data']       = json_encode($notAddData);
            $responseStatus['api_date']   = $date;
        }
        return $responseStatus;
    }

    public function checkMwsApiMissingTypeKey($matchArrayKey,$apiData, $useKey, $tableName, $userId, $date, $amazonOrderIdForLog = null)
    {
        $responseStatus = array();
        $responseStatus['status'] = 0;
        $notAddData = array();
        foreach ($apiData as $data) {
            if (!in_array($data->$matchArrayKey, $useKey)) {
                $notAddData[] = $data;
            }
        }
        if (!empty($notAddData)) {
            if (!empty($amazonOrderIdForLog) && !is_null($amazonOrderIdForLog)) {
                $responseStatus['amazon_order_id'] = $amazonOrderIdForLog;
            }
            $responseStatus['status']     = 1;
            $responseStatus['table_name'] = $tableName;
            $responseStatus['user_id']    = $userId;
            $responseStatus['data']       = json_encode($notAddData);
            $responseStatus['api_date']   = $date;
        }
        return $responseStatus;
    }

    function generate_random_strings($length_of_string = 20) 
    { 
        $str_result = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        return substr(str_shuffle($str_result), 0, $length_of_string); 
    }

    function get_finance_order_data()
    {
        $this->db->select('*');
        $this->db->from('finance_order_data');
        $this->db->where('finance_order_data_summary','n');
        $query = $this->db->get();
        return $query->result_array();

    }

    function get_finance_order_item_data($data)
    {
        $this->db->select('*');
        $this->db->from('finance_order_item_data');
        $this->db->where('amazon_order_id',$data['amazon_order_id']);
        $this->db->where('order_dev_ref',$data['dev_ref']);
        $this->db->where('dev_date',$data['dev_date']);
        $this->db->where('added_by',$data['added_by']);
        $query = $this->db->get();
        return $query->result_array();

    }

    function get_finance_order_item_charge_list_data($data)
    {
        $this->db->select('*');
        $this->db->from('finance_order_item_charge_list_data');
        $this->db->where('amazon_order_id',$data['amazon_order_id']);
        $this->db->where('order_dev_ref',$data['order_dev_ref']);
        $this->db->where('item_dev_ref',$data['item_dev_ref']);
        $this->db->where('dev_date',$data['dev_date']);
        $query = $this->db->get();
        return $query->result_array();

    }

    function get_finance_order_item_fee_list_data($data)
    {
        $this->db->select('*');
        $this->db->from('finance_order_item_fee_list_data');
        $this->db->where('amazon_order_id',$data['amazon_order_id']);
        $this->db->where('order_dev_ref',$data['order_dev_ref']);
        $this->db->where('item_dev_ref',$data['item_dev_ref']);
        $this->db->where('dev_date',$data['dev_date']);
        $query = $this->db->get();
        return $query->result_array();

    }

    function get_finance_order_item_promotion_list_data($data)
    {
        $this->db->select('*');
        $this->db->from('finance_order_item_promotion_list_data');
        $this->db->where('amazon_order_id',$data['amazon_order_id']);
        $this->db->where('order_dev_ref',$data['order_dev_ref']);
        $this->db->where('item_dev_ref',$data['item_dev_ref']);
        $this->db->where('dev_date',$data['dev_date']);
        $query = $this->db->get();
        return $query->result_array();

    }

    function get_finance_order_item_tax_withheld_list_data($data)
    {
        $this->db->select('*');
        $this->db->from('finance_order_item_tax_withheld_list_data');
        $this->db->where('amazon_order_id',$data['amazon_order_id']);
        $this->db->where('order_dev_ref',$data['order_dev_ref']);
        $this->db->where('item_dev_ref',$data['item_dev_ref']);
        $this->db->where('dev_date',$data['dev_date']);
        $query = $this->db->get();
        return $query->result_array();

    }

    function get_finance_order_data_summary($data,$getRespose = null)
    {
        $this->db->select('*');
        $this->db->from('finance_order_data_summary');
        $this->db->where('amazon_order_id',$data['amazon_order_id']);
        $this->db->where('added_by',$data['added_by']);
        $this->db->where('dev_date',$data['dev_date']);
        $query = $this->db->get();
        if ($getRespose=="All")
            return $query->result_array();
        else
            return $query->row();
    }
}
?>
