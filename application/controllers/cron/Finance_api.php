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
        send_error_mail();
    }

    public function saveFinanceData($res,$usr)
    {
        if (isset($res['startDate'])) {
            $this->startDate = $res['startDate'];
        }
        if (!empty($res['payload']))
        {
            $date_now   = date('Y-m-d');
            $insert = [];
            if ($res['payload']['mwsNewDataLog'] && !empty($res['payload']['mwsNewDataLog'])) {
                foreach ($res['payload']['mwsNewDataLog'] as $mwsNewDataLog) {
                    $this->insertdata('mws_new_data_log',$mwsNewDataLog);
                }
            }
            if ($res['payload']['AdjustmentEventList'] && !empty($res['payload']['AdjustmentEventList'])) {
                foreach ($res['payload']['AdjustmentEventList'] as $adjustmentEventListValue) {
                    $adjustmentEventListValue['fin_country'] = $usr['country_code'];
                    $adjustmentEventListValue['added_by']    = $usr['profile_id'];
                    // $checkExits = $this->checkExits('finance_adjustment_event_list', array('adjustmenttype' => $adjustmentEventListValue['adjustmenttype'], 'added_by' => $usr['profile_id']));
                    // if ($checkExits > 0 ) {
                    //     $up = $this->updatedata('finance_adjustment_event_list', $adjustmentEventListValue, array('adjustmenttype' => $adjustmentEventListValue['adjustmenttype'], 'added_by' => $usr['profile_id']));
                    // } else {
                    $adjustmentEventListValue['createDate']  = date('Y-m-d H:i:s');
                    $this->insertdata('finance_adjustment_event_list',$adjustmentEventListValue);
                    // }
                }
            }
            if ($res['payload']['RefundEventList'] && !empty($res['payload']['RefundEventList'])) {
                foreach ($res['payload']['RefundEventList'] as $refundEventListValue) {
                    $refundEventListValue['fin_country'] = $usr['country_code'];
                    $refundEventListValue['added_by']    = $usr['profile_id'];
                    $checkExits = $this->checkExits('finance_refund_event_list', array('orderadjustmentitemid' => $refundEventListValue['orderadjustmentitemid'], 'added_by' => $usr['profile_id'], 'posteddate' => $refundEventListValue['posteddate']));
                    if ($checkExits > 0 ) {
                        $up = $this->updatedata('finance_refund_event_list', $refundEventListValue, array('orderadjustmentitemid' => $refundEventListValue['orderadjustmentitemid'], 'added_by' => $usr['profile_id'], 'posteddate' => $refundEventListValue['posteddate']));
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
                    $serviceFeeEventListValue['createDate']  = date('Y-m-d H:i:s');
                    $this->insertdata('finance_service_fee_event_list',$serviceFeeEventListValue);
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
                $this->finance_order_data_summary();
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
        $finance_order_data_update = array();
        $finance_order_data_update['finance_order_data_summary'] = 'y';
        $get_finance_order_data = $this->product_api->get_finance_order_data();
        if (!empty($get_finance_order_data)) {
            foreach ($get_finance_order_data as $finance_order_data) {
                $finance_order_data_summary_array = array();
                $finance_order_data_summary_array["posted_date"]     = $finance_order_data["posted_date"];
                $finance_order_data_summary_array["Date_in_GMT"]     = $finance_order_data["posted_date_gmt"];
                $finance_order_data_summary_array["Date_in_PST"]     = $finance_order_data["posted_date_pst"];
                $finance_order_data_summary_array["amazon_order_id"] = $finance_order_data["amazon_order_id"];
                $finance_order_data_summary_array["added_by"] = $finance_order_data["added_by"];
                $finance_order_data_summary_array["dev_date"] = $finance_order_data["dev_date"];
                $finance_order_data_summary_array["marketplace"] = $finance_order_data["market_place"];
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
                                if ($get_finance_order_item_promotion_list['promotion_type']=="PromotionMetaDataDefinitionValue") {
                                    $promotional_rebates_total += $get_finance_order_item_promotion_list["promotion_amount"];
                                }
                            }
                        }
                        $get_finance_order_item_tax_withheld_list_data = $this->product_api->get_finance_order_item_tax_withheld_list_data($get_finance_order_item);
                        if (!empty($get_finance_order_item_tax_withheld_list_data)) {
                            foreach ($get_finance_order_item_tax_withheld_list_data as $get_finance_order_item_tax_withheld_list) {
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
