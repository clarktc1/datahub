<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Finance_api extends CI_Controller
{
    private $startDate;
    public function  __construct()
    {
        parent::__construct();
        $this->load->model('new_cron/process_finance_api','product_api');
    }

    public function checkExits($table,$where)
    {
        $this->db->select('*');
        $this->db->where($where);
        $query = $this->db->get($table);
        $num = $query->num_rows();
        $result = 0;
        if ($num > 0) {
            $result = 1;
        }
        return $result;
    }

    public function updatedata($tbname, $data, $parm)
    {
        return $this->db->update($tbname, $data, $parm);
    }

    public function insertdata($tbname, $data)
    {
        return $this->db->insert($tbname, $data);
    }

    public function product_match($user_id='')
    {
        // die("hello dsfsdfds dsfds");
        $users=$this->product_api->get_seller_for_process();
        // echo "<pre>";
        // print_r($users);
        // die();
        if(count($users) > 0)
        {
            foreach($users as $usr)
            {
                $this->product_api->set_credentials($usr);
                $prod_list=$this->product_api->get_product_to_match($usr['profile_id'],$usr['country_code']);
                $res = $this->product_api->fetch_product_details($usr['profile_id'],null,null,$usr['country_code']);
                if ($res['status_code']==0) {
                    if (isset($res['mwsNewDataLog']) && !empty($res['mwsNewDataLog'])) {
                        foreach ($res['mwsNewDataLog'] as $mwsNewDataLog) {
                            insertdata('mws_new_data_log',$mwsNewDataLog);
                        }
                    }
                }
                // echo "<pre>"; print_r($res);
                // die;
                // $this->saveFinanceData($res, $usr);
                // $responseArray = array();
                // $responseArray['api'] = "Finances / ListFinancialEvent";
                // $responseArray['status'] = "Successfully Fetched";
                // echo "<prE>"; print_r($responseArray); die();
            }
        }
    }

    public function saveFinanceDataByDatabase()
    {
        $get_finance_api_data_match_key = array('save_data' => 'n');
        $get_finance_api_data = checkExitData('finance_data_api', $get_finance_api_data_match_key);
        if (!empty($get_finance_api_data)) {
            foreach ($get_finance_api_data as $get_finance_api) {
                $resDataArray = $this->product_api->fetch_product_details_merge_data($get_finance_api,$get_finance_api['user_id']);
                $this->saveFinanceDataByDatabaseTable($resDataArray,$get_finance_api);
            }
        }
        $this->rerun_finace_empty();
        $this->finance_order_data_summary();
        // send_error_mail();
    }

    public function saveFinanceDataByDatabaseTable($res,$usr)
    {
        if (!empty($res['payload']))
        {
            $apiLastDate     = $usr['end_date'];
            $apiStartDate    = $usr['start_date'];
            $user_profile_id = $usr['user_id'];
            $country_code    = $usr['fin_country'];
            $start_date      = $usr['start_date'];
            $end_date        = $usr['end_date'];
            $apiTableId      = $usr['id'];
            $date_now = date('Y-m-d');
            $insert   = [];
            if ($res['payload']['mwsNewDataLog'] && !empty($res['payload']['mwsNewDataLog'])) {
                foreach ($res['payload']['mwsNewDataLog'] as $mwsNewDataLog) {
                    $this->insertdata('mws_new_data_log',$mwsNewDataLog);
                    $this->updatedata('finance_data_log',['date' => $apiStartDate ], ['user_id' => $user_profile_id ] );
                    updatedata('finance_data_api',['save_data' => 'y' ], ['id' => $apiTableId ] );
                }
            }
            if ($res['payload']['AdjustmentEventList'] && !empty($res['payload']['AdjustmentEventList'])) {
                foreach ($res['payload']['AdjustmentEventList'] as $adjustmentEventListValue) {
                    $adjustmentEventListValue['fin_country'] = $country_code;
                    $adjustmentEventListValue['added_by']    = $user_profile_id;
                    $checkExitsAdjustmentEventArray = array(
                                                                'totalamount' => $adjustmentEventListValue['totalamount'],
                                                                'adjustmenttype' => $adjustmentEventListValue['adjustmenttype'],
                                                                'sellersku' => $adjustmentEventListValue['sellersku'],
                                                                'dev_date'  => $adjustmentEventListValue['dev_date'],
                                                                'added_by'  => $user_profile_id
                                                            );
                    $checkExits = $this->checkExits('finance_adjustment_event_list', $checkExitsAdjustmentEventArray);
                    if ($checkExits > 0 ) {
                        $this->updatedata('finance_adjustment_event_list', $adjustmentEventListValue, $checkExitsAdjustmentEventArray);
                    } else {
                        $adjustmentEventListValue['createDate']  = date('Y-m-d H:i:s');
                        $this->insertdata('finance_adjustment_event_list',$adjustmentEventListValue);
                    }
                }
            }
            if ($res['payload']['RefundEventList'] && !empty($res['payload']['RefundEventList'])) {
                foreach ($res['payload']['RefundEventList'] as $refundEventListValue) {
                    $refundEventListValue['fin_country'] = $country_code;
                    $refundEventListValue['added_by']    = $user_profile_id;
                    $checkExitsRefundEventArray = array(
                                                            'orderadjustmentitemid' => $refundEventListValue['orderadjustmentitemid'],
                                                            'sellersku'             => $refundEventListValue['sellersku'],
                                                            'added_by'              => $user_profile_id,
                                                            'amazonorderid'         => $refundEventListValue['amazonorderid'],
                                                            'dev_date'              => $refundEventListValue['dev_date']
                                                        );
                    $checkExits = $this->checkExits('finance_refund_event_list', $checkExitsRefundEventArray);
                    if ($checkExits > 0 ) {
                        $this->updatedata('finance_refund_event_list', $refundEventListValue, $checkExitsRefundEventArray);
                    } else {
                        $refundEventListValue['createDate']  = date('Y-m-d H:i:s');
                        $this->insertdata('finance_refund_event_list',$refundEventListValue);
                    }
                }
            }
            if ($res['payload']['ServiceFeeEventList'] && !empty($res['payload']['ServiceFeeEventList'])) {
                foreach ($res['payload']['ServiceFeeEventList'] as $serviceFeeEventListValue) {
                    $serviceFeeEventListValue['fin_country'] = $country_code;
                    $serviceFeeEventListValue['added_by']    = $user_profile_id;
                    $checkExitServiceFeeEventArray = array(
                                                                'fee_amount' => $serviceFeeEventListValue['fee_amount'],
                                                                'fee_type'   => $serviceFeeEventListValue['fee_type'],
                                                                'added_by'   => $user_profile_id
                                                            );
                    $checkExits = $this->checkExits('finance_service_fee_event_list', $checkExitServiceFeeEventArray);
                    if ($checkExits > 0 ) {
                        $this->updatedata('finance_service_fee_event_list', $serviceFeeEventListValue, $checkExitServiceFeeEventArray);
                    } else {
                        $serviceFeeEventListValue['createDate']  = date('Y-m-d H:i:s');
                        $this->insertdata('finance_service_fee_event_list',$serviceFeeEventListValue);
                    }
                }
            }
            $checkGetErrorLog = $res['payload']['mwsNewDataLog'];
            unset($res['payload']['mwsNewDataLog']);
            unset($res['payload']['ServiceFeeEventList']);
            unset($res['payload']['RefundEventList']);
            unset($res['payload']['AdjustmentEventList']);
            $apiGetDateToData = $start_date." to ".$end_date;
            if (isset($res['payload'][0]) && !empty($res['payload'][0])) {
                foreach ($res['payload'] as $key => $value) {
                    // if ( (isset($value['amazon_order_id']) && trim($value['amazon_order_id']) !='' ) && (isset($value['seller_order_id']) && trim($value['seller_order_id']) !='' ) ) {
                    if ( isset($value['amazon_order_id']) && trim($value['amazon_order_id']) !='' ) {
                        $amazon_order_id = $value['amazon_order_id'];
                        $this->db->delete('finance_order_item_fee_list_data', array('amazon_order_id' => $amazon_order_id, 'dev_date' => $value['dev_date']));
                        $this->db->delete('finance_order_item_charge_list_data', array('amazon_order_id' => $amazon_order_id, 'dev_date' => $value['dev_date']));
                        $this->db->delete('finance_order_item_promotion_list_data', array('amazon_order_id' => $amazon_order_id, 'dev_date' => $value['dev_date']));
                        $this->db->delete('finance_order_item_tax_withheld_list_data', array('amazon_order_id' => $amazon_order_id, 'dev_date' => $value['dev_date']));
                        $this->db->delete('finance_order_shipment_fee_list', array('amazon_order_id' => $amazon_order_id, 'dev_date' => $value['dev_date']));
                        $this->db->delete('finance_order_item_data', array('amazon_order_id' => $amazon_order_id, 'dev_date' => $value['dev_date']));
                        if (isset($value['shipmentItemList']) && !empty($value['shipmentItemList'])) {
                            foreach ($value['shipmentItemList'] as $shipmentItemList) {
                                if (isset($shipmentItemList['ItemFeeList']) && !empty($shipmentItemList['ItemFeeList'])) {
                                    $this->db->insert_batch('finance_order_item_fee_list_data', $shipmentItemList['ItemFeeList']);
                                }
                                if (isset($shipmentItemList['ItemChargeList']) && !empty($shipmentItemList['ItemChargeList'])) {
                                    $this->db->insert_batch('finance_order_item_charge_list_data', $shipmentItemList['ItemChargeList']);
                                }
                                if (isset($shipmentItemList['PromotionList']) && !empty($shipmentItemList['PromotionList'])) {
                                    $this->db->insert_batch('finance_order_item_promotion_list_data', $shipmentItemList['PromotionList']);
                                }
                                if (isset($shipmentItemList['ItemTaxWithheldList']) && !empty($shipmentItemList['ItemTaxWithheldList'])) {
                                    $this->db->insert_batch('finance_order_item_tax_withheld_list_data', $shipmentItemList['ItemTaxWithheldList']);
                                }
                                unset($shipmentItemList['ItemFeeList']);
                                unset($shipmentItemList['ItemChargeList']);
                                unset($shipmentItemList['PromotionList']);
                                unset($shipmentItemList['ItemTaxWithheldList']);
                                $shipmentItemList['fin_country'] = $country_code;
                                $shipmentItemList['added_by']    = $user_profile_id;
                                $this->insertdata('finance_order_item_data',$shipmentItemList);
                            }
                        }

                        if (isset($value['ShipmentFeeList']) && !empty($value['ShipmentFeeList'])) {
                            $this->db->insert_batch('finance_order_shipment_fee_list', $value['ShipmentFeeList']);
                        }
                        unset($value['ShipmentFeeList']);
                        unset($value['shipmentItemList']);
                        // sleep(2);
                        $value['fin_country'] = $country_code;
                        $value['added_by']    = $user_profile_id;

                        // $value['extra']    = $user_profile_id;

                        $checkExits = $this->checkExits('finance_order_data',array('amazon_order_id' => $value['amazon_order_id'], 'dev_date' => $value['dev_date']));
                        if ($checkExits > 0 ) {
                            $value['finance_order_data_summary'] = 'n';
                            $checkUpDateFinData = $this->updatedata('finance_order_data', $value, array('amazon_order_id' => $value['amazon_order_id'], 'dev_date' => $value['dev_date']));
                            if ($checkUpDateFinData) {
                                $this->updatedata('rep_orders_data_order_date_list',['fee_flag' => 1 ], ['order_id' => $value['amazon_order_id'] ] );
                                $this->updatedata('finance_data_log',['date' => $apiLastDate ], ['user_id' => $user_profile_id ] );
                                updatedata('finance_data_api',['save_data' => 'y' ], ['id' => $apiTableId ] );
                            } else {
                                $saveErrorData = array();
                                $saveErrorData['error'] = $this->db->error();
                                $saveErrorData['error_dates'] = $apiGetDateToData;
                                $mwsNewDataLogEmpty = array();
                                $mwsNewDataLogEmpty['table_name'] = "Error Found finance_order_data update Query";
                                $mwsNewDataLogEmpty['user_id']    = $user_profile_id;
                                $mwsNewDataLogEmpty['data']       = json_encode($saveErrorData);
                                $mwsNewDataLogEmpty['api_date']   = $apiLastDate;
                                $this->insertdata('mws_new_data_log',$mwsNewDataLogEmpty);
                                // break;
                            }
                        } else {
                            $checkInsertFinData = $this->insertdata('finance_order_data',$value);
                            if ($checkInsertFinData) {
                                $this->updatedata('rep_orders_data_order_date_list',['fee_flag' => 1 ], ['order_id' => $value['amazon_order_id'] ] );
                                $this->updatedata('finance_data_log',['date' => $apiLastDate ], ['user_id' => $user_profile_id ] );
                                updatedata('finance_data_api',['save_data' => 'y' ], ['id' => $apiTableId ] );
                            } else {
                                $saveErrorData = array();
                                $saveErrorData['error'] = $this->db->error();
                                $saveErrorData['error_dates'] = $apiGetDateToData;
                                $mwsNewDataLogEmpty = array();
                                $mwsNewDataLogEmpty['table_name'] = "Error Found finance_order_data insert Query";
                                $mwsNewDataLogEmpty['user_id']    = $user_profile_id;
                                $mwsNewDataLogEmpty['data']       = json_encode($saveErrorData);
                                $mwsNewDataLogEmpty['api_date']   = $apiLastDate;
                                $this->insertdata('mws_new_data_log',$mwsNewDataLogEmpty);
                                // break;
                            }
                            // $insert[] = $value;
                        }
                    } else {
                        /*if (strtotime($apiLastDate) < strtotime($date_now) ) {
                            $createSubmitDate = date('Y-m-d', strtotime('+1 day', strtotime($apiLastDate)));
                            $this->updatedata('finance_data_log',['date' => $createSubmitDate ], ['user_id' => $user_profile_id ] );
                        }*/
                        $this->updatedata('finance_data_log',['date' => $apiLastDate ], ['user_id' => $user_profile_id ] );
                        updatedata('finance_data_api',['save_data' => 'y' ], ['id' => $apiTableId ] );
                    }
                }
            } else {
                if (isset($res['payload']) && empty($res['payload'][0])) {
                    if (empty($checkGetErrorLog)) {
                        $mwsNewDataLogEmpty = array();
                        $mwsNewDataLogEmpty['table_name'] = "No Data Found finance_order_data";
                        $mwsNewDataLogEmpty['user_id']    = $user_profile_id;
                        $mwsNewDataLogEmpty['data']       = $apiGetDateToData;
                        $mwsNewDataLogEmpty['api_date']   = $apiLastDate;
                        $this->insertdata('mws_new_data_log',$mwsNewDataLogEmpty);
                        $this->updatedata('finance_data_log',['date' => $apiLastDate ], ['user_id' => $user_profile_id ] );
                        updatedata('finance_data_api',['save_data' => 'y' ], ['id' => $apiTableId ] );
                    }
                }
            }
        }
    }


    public function product_match_sb_bk($user_id='')
    {
        // die("hello dsfsdfds dsfds");
        $users=$this->product_api->get_seller_for_process();
        // echo "<pre>";
        // print_r($users);
        // die();
        if(count($users) > 0)
        {
            foreach($users as $usr)
            {
                $this->product_api->set_credentials($usr);
                $prod_list=$this->product_api->get_product_to_match($usr['profile_id'],$usr['country_code']);
                $res=$this->product_api->fetch_product_details($usr['profile_id']);
                // echo "<pre>"; print_r($res);
                // die;
                $this->saveFinanceData($res, $usr);
                $responseArray = array();
                $responseArray['api'] = "Finances / ListFinancialEvent";
                $responseArray['status'] = "Successfully Fetched";
                // echo "<prE>"; print_r($responseArray); die();
            }
        }
        $this->rerun_finace_empty();
        $this->finance_order_data_summary();
        send_error_mail();
    }

    public function saveFinanceData_sb_bk($res,$usr)
    {
        // echo "<pre>"; print_r($res);
        // die();
        if (isset($res['startDate'])) {
            $this->startDate = $res['startDate'];
        }
        if (!empty($res['payload']))
        {
            $date_now = date('Y-m-d');
            $insert   = [];
            if ($res['payload']['mwsNewDataLog'] && !empty($res['payload']['mwsNewDataLog'])) {
                foreach ($res['payload']['mwsNewDataLog'] as $mwsNewDataLog) {
                    $this->insertdata('mws_new_data_log',$mwsNewDataLog);
                }
            }
            if ($res['payload']['AdjustmentEventList'] && !empty($res['payload']['AdjustmentEventList'])) {
                foreach ($res['payload']['AdjustmentEventList'] as $adjustmentEventListValue) {
                    $adjustmentEventListValue['fin_country'] = $usr['country_code'];
                    $adjustmentEventListValue['added_by']    = $usr['profile_id'];
                    $checkExitsAdjustmentEventArray = array(
                                                                'totalamount' => $adjustmentEventListValue['totalamount'],
                                                                'adjustmenttype' => $adjustmentEventListValue['adjustmenttype'],
                                                                'sellersku' => $adjustmentEventListValue['sellersku'],
                                                                'dev_date'  => $adjustmentEventListValue['dev_date'],
                                                                'added_by'  => $usr['profile_id']
                                                            );
                    $checkExits = $this->checkExits('finance_adjustment_event_list', $checkExitsAdjustmentEventArray);
                    if ($checkExits > 0 ) {
                        $this->updatedata('finance_adjustment_event_list', $adjustmentEventListValue, $checkExitsAdjustmentEventArray);
                    } else {
                        $adjustmentEventListValue['createDate']  = date('Y-m-d H:i:s');
                        $this->insertdata('finance_adjustment_event_list',$adjustmentEventListValue);
                    }
                }
            }
            if ($res['payload']['RefundEventList'] && !empty($res['payload']['RefundEventList'])) {
                foreach ($res['payload']['RefundEventList'] as $refundEventListValue) {
                    $refundEventListValue['fin_country'] = $usr['country_code'];
                    $refundEventListValue['added_by']    = $usr['profile_id'];
                    $checkExitsRefundEventArray = array(
                                                            'orderadjustmentitemid' => $refundEventListValue['orderadjustmentitemid'],
                                                            'sellersku'             => $refundEventListValue['sellersku'],
                                                            'added_by'              => $usr['profile_id'],
                                                            'amazonorderid'         => $refundEventListValue['amazonorderid'],
                                                            'dev_date'              => $refundEventListValue['dev_date']
                                                        );
                    $checkExits = $this->checkExits('finance_refund_event_list', $checkExitsRefundEventArray);
                    if ($checkExits > 0 ) {
                        $this->updatedata('finance_refund_event_list', $refundEventListValue, $checkExitsRefundEventArray);
                    } else {
                        $refundEventListValue['createDate']  = date('Y-m-d H:i:s');
                        $this->insertdata('finance_refund_event_list',$refundEventListValue);
                    }
                }
            }
            if ($res['payload']['ServiceFeeEventList'] && !empty($res['payload']['ServiceFeeEventList'])) {
                foreach ($res['payload']['ServiceFeeEventList'] as $serviceFeeEventListValue) {
                    $serviceFeeEventListValue['fin_country'] = $usr['country_code'];
                    $serviceFeeEventListValue['added_by']    = $usr['profile_id'];
                    $checkExitServiceFeeEventArray = array(
                                                                'fee_amount' => $serviceFeeEventListValue['fee_amount'],
                                                                'fee_type'   => $serviceFeeEventListValue['fee_type'],
                                                                'added_by'   => $usr['profile_id']
                                                            );
                    $checkExits = $this->checkExits('finance_service_fee_event_list', $checkExitServiceFeeEventArray);
                    if ($checkExits > 0 ) {
                        $this->updatedata('finance_service_fee_event_list', $serviceFeeEventListValue, $checkExitServiceFeeEventArray);
                    } else {
                        $serviceFeeEventListValue['createDate']  = date('Y-m-d H:i:s');
                        $this->insertdata('finance_service_fee_event_list',$serviceFeeEventListValue);
                    }
                }
            }
            $checkGetErrorLog = $res['payload']['mwsNewDataLog'];
            unset($res['payload']['mwsNewDataLog']);
            unset($res['payload']['ServiceFeeEventList']);
            unset($res['payload']['RefundEventList']);
            unset($res['payload']['AdjustmentEventList']);
            $apiGetDateToData = $this->startDate." to ".$res['createDate'];
            if (isset($res['payload'][0]) && !empty($res['payload'][0])) {
                foreach ($res['payload'] as $key => $value) {
                    // if ( (isset($value['amazon_order_id']) && trim($value['amazon_order_id']) !='' ) && (isset($value['seller_order_id']) && trim($value['seller_order_id']) !='' ) ) {
                    if ( isset($value['amazon_order_id']) && trim($value['amazon_order_id']) !='' ) {
                        $amazon_order_id = $value['amazon_order_id'];
                        $this->db->delete('finance_order_item_fee_list_data', array('amazon_order_id' => $amazon_order_id, 'dev_date' => $value['dev_date']));
                        $this->db->delete('finance_order_item_charge_list_data', array('amazon_order_id' => $amazon_order_id, 'dev_date' => $value['dev_date']));
                        $this->db->delete('finance_order_item_promotion_list_data', array('amazon_order_id' => $amazon_order_id, 'dev_date' => $value['dev_date']));
                        $this->db->delete('finance_order_item_tax_withheld_list_data', array('amazon_order_id' => $amazon_order_id, 'dev_date' => $value['dev_date']));
                        $this->db->delete('finance_order_shipment_fee_list', array('amazon_order_id' => $amazon_order_id, 'dev_date' => $value['dev_date']));
                        $this->db->delete('finance_order_item_data', array('amazon_order_id' => $amazon_order_id, 'dev_date' => $value['dev_date']));
                        if (isset($value['shipmentItemList']) && !empty($value['shipmentItemList'])) {
                            foreach ($value['shipmentItemList'] as $shipmentItemList) {
                                if (isset($shipmentItemList['ItemFeeList']) && !empty($shipmentItemList['ItemFeeList'])) {
                                    $this->db->insert_batch('finance_order_item_fee_list_data', $shipmentItemList['ItemFeeList']);
                                }
                                if (isset($shipmentItemList['ItemChargeList']) && !empty($shipmentItemList['ItemChargeList'])) {
                                    $this->db->insert_batch('finance_order_item_charge_list_data', $shipmentItemList['ItemChargeList']);
                                }
                                if (isset($shipmentItemList['PromotionList']) && !empty($shipmentItemList['PromotionList'])) {
                                    $this->db->insert_batch('finance_order_item_promotion_list_data', $shipmentItemList['PromotionList']);
                                }
                                if (isset($shipmentItemList['ItemTaxWithheldList']) && !empty($shipmentItemList['ItemTaxWithheldList'])) {
                                    $this->db->insert_batch('finance_order_item_tax_withheld_list_data', $shipmentItemList['ItemTaxWithheldList']);
                                }
                                unset($shipmentItemList['ItemFeeList']);
                                unset($shipmentItemList['ItemChargeList']);
                                unset($shipmentItemList['PromotionList']);
                                unset($shipmentItemList['ItemTaxWithheldList']);
                                $shipmentItemList['fin_country'] = $usr['country_code'];
                                $shipmentItemList['added_by']    = $usr['profile_id'];
                                $this->insertdata('finance_order_item_data',$shipmentItemList);
                            }
                        }

                        if (isset($value['ShipmentFeeList']) && !empty($value['ShipmentFeeList'])) {
                            $this->db->insert_batch('finance_order_shipment_fee_list', $value['ShipmentFeeList']);
                        }
                        unset($value['ShipmentFeeList']);
                        unset($value['shipmentItemList']);
                        // sleep(2);
                        $value['fin_country'] = $usr['country_code'];
                        $value['added_by']    = $usr['profile_id'];

                        // $value['extra']    = $usr['profile_id'];

                        $checkExits = $this->checkExits('finance_order_data',array('amazon_order_id' => $value['amazon_order_id'], 'dev_date' => $value['dev_date']));
                        if ($checkExits > 0 ) {
                            $value['finance_order_data_summary'] = 'n';
                            $checkUpDateFinData = $this->updatedata('finance_order_data', $value, array('amazon_order_id' => $value['amazon_order_id'], 'dev_date' => $value['dev_date']));
                            if ($checkUpDateFinData) {
                                $this->updatedata('rep_orders_data_order_date_list',['fee_flag' => 1 ], ['order_id' => $value['amazon_order_id'] ] );
                                $this->updatedata('finance_data_log',['date' => $res['createDate'] ], ['user_id' => $usr['profile_id'] ] );
                            } else {
                                $saveErrorData = array();
                                $saveErrorData['error'] = $this->db->error();
                                $saveErrorData['error_dates'] = $apiGetDateToData;
                                $mwsNewDataLogEmpty = array();
                                $mwsNewDataLogEmpty['table_name'] = "Error Found finance_order_data update Query";
                                $mwsNewDataLogEmpty['user_id']    = $usr['profile_id'];
                                $mwsNewDataLogEmpty['data']       = json_encode($saveErrorData);
                                $mwsNewDataLogEmpty['api_date']   = $res['createDate'];
                                $this->insertdata('mws_new_data_log',$mwsNewDataLogEmpty);
                                // break;
                            }
                        } else {
                            $checkInsertFinData = $this->insertdata('finance_order_data',$value);
                            if ($checkInsertFinData) {
                                $this->updatedata('rep_orders_data_order_date_list',['fee_flag' => 1 ], ['order_id' => $value['amazon_order_id'] ] );
                                $this->updatedata('finance_data_log',['date' => $res['createDate'] ], ['user_id' => $usr['profile_id'] ] );
                            } else {
                                $saveErrorData = array();
                                $saveErrorData['error'] = $this->db->error();
                                $saveErrorData['error_dates'] = $apiGetDateToData;
                                $mwsNewDataLogEmpty = array();
                                $mwsNewDataLogEmpty['table_name'] = "Error Found finance_order_data insert Query";
                                $mwsNewDataLogEmpty['user_id']    = $usr['profile_id'];
                                $mwsNewDataLogEmpty['data']       = json_encode($saveErrorData);
                                $mwsNewDataLogEmpty['api_date']   = $res['createDate'];
                                $this->insertdata('mws_new_data_log',$mwsNewDataLogEmpty);
                                // break;
                            }
                            // $insert[] = $value;
                        }
                    } else {
                        /*if (strtotime($res['createDate']) < strtotime($date_now) ) {
                            $createSubmitDate = date('Y-m-d', strtotime('+1 day', strtotime($res['createDate'])));
                            $this->updatedata('finance_data_log',['date' => $createSubmitDate ], ['user_id' => $usr['profile_id'] ] );
                        }*/
                        $this->updatedata('finance_data_log',['date' => $res['createDate'] ], ['user_id' => $usr['profile_id'] ] );
                    }
                }
            } else {
                if (isset($res['payload']) && empty($res['payload'][0])) {
                    if (empty($checkGetErrorLog)) {
                        $mwsNewDataLogEmpty = array();
                        $mwsNewDataLogEmpty['table_name'] = "No Data Found finance_order_data";
                        $mwsNewDataLogEmpty['user_id']    = $usr['profile_id'];
                        $mwsNewDataLogEmpty['data']       = $apiGetDateToData;
                        $mwsNewDataLogEmpty['api_date']   = $res['createDate'];
                        $this->insertdata('mws_new_data_log',$mwsNewDataLogEmpty);
                        $this->updatedata('finance_data_log',['date' => $res['createDate'] ], ['user_id' => $usr['profile_id'] ] );
                    }
                }
            }
            if (isset($res['tokens'])) {
                $token = $res['tokens'];
                $number = $res['numberCheck']+1;
                // if ($number < 2) {
                    $ressss =$this->product_api->fetch_product_details($usr['profile_id'],null,null,null, $token,$number);
                    $this->saveFinanceData($ressss,$usr);
                // }
                // die("hello dsfsdfds dsfds");
            }
        }
    }

    public function rerun_finace_empty()
    {
        $sql="UPDATE rep_orders_data_order_date_list SET fee_flag='0' where prod_id in(SELECT fin_id from finance_data where sku='')";
        //print_r($sql);
        $this->db->query($sql);
    }

    public function finance_order_data_summary()
    {
        // Code Start For add finance order data to summary table
        $finance_order_data_update = array();
        $finance_order_data_update['finance_order_data_summary'] = 'y';
        $get_finance_order_data = $this->product_api->get_finance_order_data();
        if (!empty($get_finance_order_data)) {
            foreach ($get_finance_order_data as $finance_order_data) {
                $financeCurrency = "";
                $finance_order_data_summary_array = array();
                $finance_order_data_summary_array["posted_date"]     = $finance_order_data["posted_date"];
                $finance_order_data_summary_array["Date_in_GMT"]     = $finance_order_data["posted_date_gmt"];
                $finance_order_data_summary_array["Date_in_PST"]     = $finance_order_data["posted_date_pst"];
                $finance_order_data_summary_array["amazon_order_id"] = $finance_order_data["amazon_order_id"];
                $finance_order_data_summary_array["added_by"] = $finance_order_data["added_by"];
                $finance_order_data_summary_array["dev_date"] = $finance_order_data["dev_date"];
                $finance_order_data_summary_array["marketplace"] = $finance_order_data["market_place"];
                $finance_order_data_summary_array["type"]        = 'order';
                $get_finance_order_item_data = $this->product_api->get_finance_order_item_data($finance_order_data);
                if (!empty($get_finance_order_item_data)) {
                    $quantity            = 0;
                    $sales_tax_collected = 0;
                    $product_sales       = 0;
                    $shipping_credits    = 0;
                    $gift_wrap_credits   = 0;
                    $fba_fees            = 0;
                    $selling_fees        = 0;
                    $other_transaction_fees    = 0;
                    $promotional_rebates_total = 0;
                    $total_charge_amount = 0;
                    foreach ($get_finance_order_item_data as $get_finance_order_item) {
                        $finance_order_data_summary_array["seller_sku"] = $get_finance_order_item["seller_sku"];
                        $quantity += $get_finance_order_item["quantity_shipped"];
                        $get_finance_order_item_charge_list_data = $this->product_api->get_finance_order_item_charge_list_data($get_finance_order_item);
                        if (!empty($get_finance_order_item_charge_list_data)) {
                            foreach ($get_finance_order_item_charge_list_data as $get_finance_order_item_charge_list) {
                                $financeCurrency = $get_finance_order_item_charge_list["currency_code"];
                                if ($get_finance_order_item_charge_list['charge_type'] == "Principal") {
                                    $product_sales += $get_finance_order_item_charge_list["charge_amount"];
                                }
                                if (strpos($get_finance_order_item_charge_list['charge_type'], "Tax") !== false ) {
                                    $sales_tax_collected += $get_finance_order_item_charge_list["charge_amount"];
                                }
                                if ($get_finance_order_item_charge_list['charge_type'] == "ShippingCharge") {
                                    $shipping_credits += $get_finance_order_item_charge_list["charge_amount"];
                                }
                                if ($get_finance_order_item_charge_list['charge_type'] == "GiftWrap") {
                                    $gift_wrap_credits += $get_finance_order_item_charge_list["charge_amount"];
                                }
                            }
                        }
                        $get_finance_order_item_fee_list_data = $this->product_api->get_finance_order_item_fee_list_data($get_finance_order_item);
                        if (!empty($get_finance_order_item_fee_list_data)) {
                            foreach ($get_finance_order_item_fee_list_data as $get_finance_order_item_fee_list) {
                                $financeCurrency = $get_finance_order_item_fee_list["currency_code"];
                                if ($get_finance_order_item_fee_list["fee_type"]=="Commission") {
                                    $selling_fees += $get_finance_order_item_fee_list["fee_amount"];
                                }
                                if (strpos($get_finance_order_item_fee_list["fee_type"], 'FBA') !== false || $get_finance_order_item_fee_list["fee_type"]=="GiftwrapChargeback" || $get_finance_order_item_fee_list["fee_type"]=="ShippingChargeback" ) {
                                    $fba_fees += $get_finance_order_item_fee_list["fee_amount"];
                                }
                                if ($get_finance_order_item_fee_list["fee_type"]=="SalesTaxCollectionFee") {
                                    $other_transaction_fees += $get_finance_order_item_fee_list["fee_amount"];
                                }
                            }
                        }
                        $get_finance_order_item_promotion_list_data = $this->product_api->get_finance_order_item_promotion_list_data($get_finance_order_item);
                        if (!empty($get_finance_order_item_promotion_list_data)) {
                            foreach ($get_finance_order_item_promotion_list_data as $get_finance_order_item_promotion_list) {
                                $financeCurrency = $get_finance_order_item_promotion_list["currency_code"];
                                if ($get_finance_order_item_promotion_list['promotion_type']=="PromotionMetaDataDefinitionValue") {
                                    $promotional_rebates_total += $get_finance_order_item_promotion_list["promotion_amount"];
                                }
                            }
                        }
                        $get_finance_order_item_tax_withheld_list_data = $this->product_api->get_finance_order_item_tax_withheld_list_data($get_finance_order_item);
                        if (!empty($get_finance_order_item_tax_withheld_list_data)) {
                            foreach ($get_finance_order_item_tax_withheld_list_data as $get_finance_order_item_tax_withheld_list) {
                                $financeCurrency = $get_finance_order_item_tax_withheld_list['currency_code'];
                                $total_charge_amount += $get_finance_order_item_tax_withheld_list['charge_amount'];
                            }
                        }
                    }
                    $finance_order_data_summary_array["quantity"] = $quantity;
                    $finance_order_data_summary_array["sales_tax_collected"] = $sales_tax_collected;
                    $finance_order_data_summary_array["product_sales"] = $product_sales;
                    $finance_order_data_summary_array["shipping_credits"] = $shipping_credits;
                    $finance_order_data_summary_array["gift_wrap_credits"] = $gift_wrap_credits;
                    $finance_order_data_summary_array["fba_fees"] = $fba_fees;
                    $finance_order_data_summary_array["selling_fees"] = $selling_fees;
                    $finance_order_data_summary_array["other_transaction_fees"] = $other_transaction_fees;
                    $finance_order_data_summary_array["promotional_rebates"] = $promotional_rebates_total;
                    $finance_order_data_summary_array["marketplace_facilitator_tax"] = $total_charge_amount;
                    $finance_order_data_summary_array["currency"] = $financeCurrency;
                    $get_finance_order_data_summary = $this->product_api->get_finance_order_data_summary($finance_order_data);
                    if (!empty($get_finance_order_data_summary)) {
                        $checkUpdate = $this->updatedata('finance_order_data_summary', $finance_order_data_summary_array, array('f_oid' => $get_finance_order_data_summary->f_oid));
                        if ($checkUpdate==1) {
                            $this->updatedata('finance_order_data', $finance_order_data_update, array('id' => $finance_order_data["id"]));
                        }
                    } else {
                        if (!$get_finance_order_data_summary) {
                            $checkInsert = $this->insertdata('finance_order_data_summary',$finance_order_data_summary_array);
                            if ($checkInsert==1) {
                                $this->updatedata('finance_order_data', $finance_order_data_update, array('id' => $finance_order_data["id"]));
                            }
                        }
                    }
                }
            }
        }
        // Code End For add finance order data to summary table

        $changeStatus = array();
        $changeStatus['finance_order_data_summary'] = 'y';

        // Code Start To adjustment to summary table
        $get_finance_adjustment_event_list = $this->product_api->get_finance_adjustment_event_list();
        if (!empty($get_finance_adjustment_event_list)) {
            foreach ($get_finance_adjustment_event_list as $get_finance_adjustment_event) {
                $add_finance_adjustment_event_list = array();
                $add_finance_adjustment_event_list['posted_date']   = $get_finance_adjustment_event['posteddate'];
                $add_finance_adjustment_event_list['seller_sku']    = $get_finance_adjustment_event['sellersku'];
                $add_finance_adjustment_event_list['quantity']      = $get_finance_adjustment_event['quantity'];
                $add_finance_adjustment_event_list['other_transaction_fees'] = $get_finance_adjustment_event['totalamount'];
                $add_finance_adjustment_event_list['added_by']      = $get_finance_adjustment_event['added_by'];
                $add_finance_adjustment_event_list['type']          = "adjustment";
                $add_finance_adjustment_event_list['adjustment_id'] = $get_finance_adjustment_event['id'];
                $add_finance_adjustment_event_list['currency']      = $get_finance_adjustment_event['currency'];
                $add_finance_adjustment_event_list['dev_date']      = $get_finance_adjustment_event['dev_date'];
                $checkExitsAdjustmentEventArrayForSummary = array(
                                                                    'other_transaction_fees' => $get_finance_adjustment_event['totalamount'],
                                                                    'seller_sku' => $get_finance_adjustment_event['sellersku'],
                                                                    'added_by'   => $get_finance_adjustment_event['added_by'],
                                                                    'type'       => "adjustment",
                                                                    'dev_date'   => $get_finance_adjustment_event['dev_date'],
                                                                );
                $checkExitAdjustmentEvent = checkExitData('finance_order_data_summary', $checkExitsAdjustmentEventArrayForSummary);
                if (!empty($checkExitAdjustmentEvent)) {
                    $checkExitAdjustmentEvent = current($checkExitAdjustmentEvent);
                    $checkUpdate = updatedata('finance_order_data_summary', $add_finance_adjustment_event_list, array('f_oid' => $checkExitAdjustmentEvent['f_oid']));
                    if ($checkUpdate==1) {
                        $changeStatus['finance_order_data_summary_id'] = $checkExitAdjustmentEvent['f_oid'];
                        updatedata('finance_adjustment_event_list', $changeStatus, array('id' => $get_finance_adjustment_event['id']));
                    }
                } else {
                    $checkInsert = insertdata('finance_order_data_summary',$add_finance_adjustment_event_list);
                    if ($checkInsert==1) {
                        $insertId = $this->db->insert_id();
                        $changeStatus['finance_order_data_summary_id'] = $insertId;
                        updatedata('finance_adjustment_event_list', $changeStatus, array('id' => $get_finance_adjustment_event['id']));
                    }
                }
            }
        }
        // Code End To adjustment to summary table

        // Code Start To refund to summary table
        $get_finance_refund_event_list = $this->product_api->get_finance_refund_event_list();
        if (!empty($get_finance_refund_event_list)) {
            foreach ($get_finance_refund_event_list as $get_finance_refund_event) {
                // echo "<pre>";
                // print_r($get_finance_refund_event);
                // die();
                $add_finance_refund_event_list = array();
                $add_finance_refund_event_list['posted_date'] = $get_finance_refund_event['posteddate'];
                $add_finance_refund_event_list['amazon_order_id'] = $get_finance_refund_event['amazonorderid'];
                $add_finance_refund_event_list['seller_sku']    = $get_finance_refund_event['sellersku'];
                $add_finance_refund_event_list['quantity']      = $get_finance_refund_event['quantityshipped'];
                $add_finance_refund_event_list['marketplace']   = $get_finance_refund_event['marketplacename'];
                $add_finance_refund_event_list['product_sales'] = $get_finance_refund_event['principal'];
                $add_finance_refund_event_list['shipping_credits']    = $get_finance_refund_event['shippingcharge'];
                $add_finance_refund_event_list['promotional_rebates'] = $get_finance_refund_event['promotionmetadatadefinitionvalue'];
                $add_finance_refund_event_list['sales_tax_collected'] = $get_finance_refund_event['tax'];
                $marketplacefacilitatortaxprincipal = $get_finance_refund_event['marketplacefacilitatortaxprincipal'];
                if (!is_numeric(trim($get_finance_refund_event['marketplacefacilitatortaxprincipal']))) {
                    $marketplacefacilitatortaxprincipal = 0;
                }
                $marketplacefacilitatortaxshipping = $get_finance_refund_event['marketplacefacilitatortaxshipping'];
                if (!is_numeric(trim($get_finance_refund_event['marketplacefacilitatortaxshipping']))) {
                    $marketplacefacilitatortaxshipping = 0;
                }
                $add_finance_refund_event_list['marketplace_facilitator_tax'] = ($marketplacefacilitatortaxprincipal+$marketplacefacilitatortaxshipping);
                $add_finance_refund_event_list['selling_fees'] = $get_finance_refund_event['commission'];
                $add_finance_refund_event_list['other_transaction_fees'] = $get_finance_refund_event['refundcommission'];
                $add_finance_refund_event_list['added_by']  = $get_finance_refund_event['added_by'];
                $add_finance_refund_event_list['type']      = "refund";
                $add_finance_refund_event_list['dev_date']  = $get_finance_refund_event['dev_date'];
                $add_finance_refund_event_list['currency']  = $get_finance_refund_event['currency'];
                $add_finance_refund_event_list['refund_id'] = $get_finance_refund_event['id'];
                $checkExitsRefundEventArrayForSummary = array(
                                                                    'amazon_order_id' => $get_finance_refund_event['amazonorderid'],
                                                                    'seller_sku' => $get_finance_refund_event['sellersku'],
                                                                    'added_by'   => $get_finance_refund_event['added_by'],
                                                                    'type'       => "refund",
                                                                    'dev_date'   => $get_finance_refund_event['dev_date'],
                                                                );
                $checkExitRefundEvent = checkExitData('finance_order_data_summary', $checkExitsRefundEventArrayForSummary);
                if (!empty($checkExitRefundEvent)) {
                    $checkExitRefundEvent = current($checkExitRefundEvent);
                    $checkUpdate = updatedata('finance_order_data_summary', $add_finance_refund_event_list, array('f_oid' => $checkExitRefundEvent['f_oid']));
                    if ($checkUpdate==1) {
                        $changeStatus['finance_order_data_summary_id'] = $checkExitRefundEvent['f_oid'];
                        updatedata('finance_refund_event_list', $changeStatus, array('id' => $get_finance_refund_event['id']));
                    }
                } else {
                    $checkInsert = insertdata('finance_order_data_summary',$add_finance_refund_event_list);
                    if ($checkInsert==1) {
                        $insertId = $this->db->insert_id();
                        $changeStatus['finance_order_data_summary_id'] = $insertId;
                        updatedata('finance_refund_event_list', $changeStatus, array('id' => $get_finance_refund_event['id']));
                    }
                }
            }
        }
        // Code End To refund to summary table

        // Code Start To service to summary table
        $get_finance_service_fee_event_list = $this->product_api->get_finance_service_fee_event_list();
        if (!empty($get_finance_service_fee_event_list)) {
            foreach ($get_finance_service_fee_event_list as $get_finance_service_fee_event) {
                $add_finance_service_fee_event = array();
                $add_finance_service_fee_event['other_transaction_fees'] = $get_finance_service_fee_event['fee_amount'];
                $add_finance_service_fee_event['added_by'] = $get_finance_service_fee_event['added_by'];
                $add_finance_service_fee_event['type']     = "service-fee";
                $add_finance_service_fee_event['service_fee_id'] = $get_finance_service_fee_event['id'];
                $add_finance_service_fee_event['currency']    = $get_finance_service_fee_event['currency'];
                $add_finance_service_fee_event['marketplace'] = $get_finance_service_fee_event['fee_type'];
                $checkExitServiceFeeEventArrayForSummary = array(
                                                                    'other_transaction_fees' => $get_finance_service_fee_event['fee_amount'],
                                                                    'added_by'   => $get_finance_service_fee_event['added_by'],
                                                                    'type'       => "service-fee",
                                                                    'marketplace' => $get_finance_service_fee_event['fee_type']
                                                                );
                $checkExitServiceFeeEvent = checkExitData('finance_order_data_summary', $checkExitServiceFeeEventArrayForSummary);
                if (!empty($checkExitServiceFeeEvent)) {
                    $checkExitServiceFeeEvent = current($checkExitServiceFeeEvent);
                    $checkUpdate = updatedata('finance_order_data_summary', $add_finance_service_fee_event, array('f_oid' => $checkExitServiceFeeEvent['f_oid']));
                    if ($checkUpdate==1) {
                        $changeStatus['finance_order_data_summary_id'] = $checkExitServiceFeeEvent['f_oid'];
                        updatedata('finance_service_fee_event_list', $changeStatus, array('id' => $get_finance_service_fee_event['id']));
                    }
                } else {
                    $checkInsert = insertdata('finance_order_data_summary',$add_finance_service_fee_event);
                    if ($checkInsert==1) {
                        $insertId = $this->db->insert_id();
                        $changeStatus['finance_order_data_summary_id'] = $insertId;
                        updatedata('finance_service_fee_event_list', $changeStatus, array('id' => $get_finance_service_fee_event['id']));
                    }
                }
            }
        }
        // Code End To service to summary table
    }

    /*public function send_error_mail()
    {
        $this->db->select('*');
        $this->db->from('mws_new_data_log');
        $this->db->where('sent_mail','n');
        $query = $this->db->get();
        $get_all_error = $query->result_array();
        if (!empty($get_all_error)) {
            $error_view_array = array();
            $error_msg_array = array();
            $error_status_array = array();
            foreach ($get_all_error as $get_error) {
                $error_msg_array[$get_error['user_id']][] = $get_error['data'];
                $error_status_array[] = $get_error['id'];
            }
            $error_msg_html = "";
            foreach ($error_msg_array as $error_key => $error_msgs) {
                $error_msg_html .= "User ID => ".$error_key."<br><br>";
                foreach ($error_msgs as $error_msg) {
                    $error_msg_html .= "Error Msg => ".$error_msg."<br><br>";
                }
                $error_msg_html .= "<br><br><br>";
            }
            $error_view_array['error_msg_html'] = $error_msg_html;
            $emailData =  array();
            $emailData['to'] = "support@magicdatamachine.com";
            $emailData['subject'] = "DataHub Error";
            $emailData['message'] = $this->load->view('email/email_template',$error_view_array,true);
            $checkEmail = sendEmails($emailData);
            if ($checkEmail) {
                $changeStatus = array();
                $changeStatus['sent_mail'] = "y";
                $this->db->where_in('id', $error_status_array);
                $this->db->update('mws_new_data_log',$changeStatus);
            }
        }
    }*/
}
