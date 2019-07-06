<?php
public function finance_order_data_summary()
{
    // Code Start For add finance order data to summary table
    $finance_order_data_update = array();
    $finance_order_data_update['finance_order_data_summary'] = 'y';
    $get_finance_order_data = $this->product_api->get_finance_order_data();
    // echo "<pre>";
    // print_r($get_finance_order_data);
    // die();
    if (!empty($get_finance_order_data)) {
        $fod_bulk_query = array();
        foreach ($get_finance_order_data as $finance_order_data) {
            $financeCurrency = "";
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
                $seller_sku = "";
                foreach ($get_finance_order_item_data as $get_finance_order_item) {
                    // $finance_order_data_summary_array["seller_sku"] = $get_finance_order_item["seller_sku"];
                    $seller_sku = $get_finance_order_item["seller_sku"];
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

                // $finance_order_data_summary_array = array();
                // $finance_order_data_summary_array["posted_date"]     = $finance_order_data["posted_date"];
                // $finance_order_data_summary_array["Date_in_GMT"]     = $finance_order_data["posted_date_gmt"];
                // $finance_order_data_summary_array["Date_in_PST"]     = $finance_order_data["posted_date_pst"];
                // $finance_order_data_summary_array["amazon_order_id"] = $finance_order_data["amazon_order_id"];
                // $finance_order_data_summary_array["added_by"] = $finance_order_data["added_by"];
                // $finance_order_data_summary_array["dev_date"] = $finance_order_data["dev_date"];
                // $finance_order_data_summary_array["marketplace"] = $finance_order_data["market_place"];
                // $finance_order_data_summary_array["type"]        = 'order';
                //
                //
                //
                //
                // $finance_order_data_summary_array["quantity"] = $quantity;
                // $finance_order_data_summary_array["sales_tax_collected"] = $sales_tax_collected;
                // $finance_order_data_summary_array["product_sales"] = $product_sales;
                // $finance_order_data_summary_array["shipping_credits"] = $shipping_credits;
                // $finance_order_data_summary_array["gift_wrap_credits"] = $gift_wrap_credits;
                // $finance_order_data_summary_array["fba_fees"] = $fba_fees;
                // $finance_order_data_summary_array["selling_fees"] = $selling_fees;
                // $finance_order_data_summary_array["other_transaction_fees"] = $other_transaction_fees;
                // $finance_order_data_summary_array["promotional_rebates"] = $promotional_rebates_total;
                // $finance_order_data_summary_array["marketplace_facilitator_tax"] = $total_charge_amount;
                // $finance_order_data_summary_array["currency"] = $financeCurrency;


                $fod_posted_date                 =  $this->db->escape($finance_order_data["posted_date"]);
                $fod_Date_in_GMT                 =  $this->db->escape($finance_order_data["posted_date_gmt"]);
                $fod_Date_in_PST                 =  $this->db->escape($finance_order_data["posted_date_pst"]);
                $fod_amazon_order_id             =  $this->db->escape($finance_order_data["amazon_order_id"]);
                $fod_seller_sku                  =  $this->db->escape($seller_sku);
                $fod_quantity                    =  $this->db->escape($quantity);
                $fod_marketplace                 =  $this->db->escape($finance_order_data["market_place"]);
                $fod_product_sales               =  $this->db->escape($product_sales);
                $fod_shipping_credits            =  $this->db->escape($shipping_credits);
                $fod_gift_wrap_credits           =  $this->db->escape($gift_wrap_credits);
                $fod_promotional_rebates         =  $this->db->escape($promotional_rebates_total);
                $fod_sales_tax_collected         =  $this->db->escape($sales_tax_collected);
                $fod_marketplace_facilitator_tax =  $this->db->escape($total_charge_amount);
                $fod_selling_fees                =  $this->db->escape($selling_fees);
                $fod_fba_fees                    =  $this->db->escape($fba_fees);
                $fod_other_transaction_fees      =  $this->db->escape($other_transaction_fees);
                $fod_currency                    =  $this->db->escape($financeCurrency);
                $fod_added_by                    =  $this->db->escape($finance_order_data["added_by"]);
                $fod_type                        =  $this->db->escape('order');
                $fod_dev_date                    =  $this->db->escape($finance_order_data["dev_date"]);
                // $fod_adjustment_id               =  $this->db->escape();
                // $fod_refund_id                   =  $this->db->escape();
                // $fod_service_fee_id              =  $this->db->escape();
                // $fod_fee_type                    =  $this->db->escape();

                $fod_bulk_query[] = "({$fod_posted_date},{$fod_Date_in_GMT},{$fod_Date_in_PST},{$fod_amazon_order_id},{$fod_seller_sku},{$fod_quantity},{$fod_marketplace},{$fod_product_sales},{$fod_shipping_credits},{$fod_gift_wrap_credits},{$fod_promotional_rebates},{$fod_sales_tax_collected},{$fod_marketplace_facilitator_tax},{$fod_selling_fees},{$fod_fba_fees},{$fod_other_transaction_fees},{$fod_currency},{$fod_added_by},{$fod_type},{$fod_dev_date})";

                // echo "<pre>";
                // print_r($finance_order_data_summary_array);
                // die();

                // $get_finance_order_data_summary = $this->product_api->get_finance_order_data_summary($finance_order_data);
                // if (!empty($get_finance_order_data_summary)) {
                //     $checkUpdate = $this->updatedata('finance_order_data_summary', $finance_order_data_summary_array, array('f_oid' => $get_finance_order_data_summary->f_oid));
                //     if ($checkUpdate==1) {
                //         $this->updatedata('finance_order_data', $finance_order_data_update, array('id' => $finance_order_data["id"]));
                //     }
                // } else {
                //     if (!$get_finance_order_data_summary) {
                //         $checkInsert = $this->insertdata('finance_order_data_summary',$finance_order_data_summary_array);
                //         if ($checkInsert==1) {
                //             $this->updatedata('finance_order_data', $finance_order_data_update, array('id' => $finance_order_data["id"]));
                //         }
                //     }
                // }
            }
        }

        echo "<pre>";
        print_r($fod_bulk_query);
        die("eee");



        if (!empty($fod_bulk_query)) {
            $fod_bulk_query_data_implode = implode(',',$fod_bulk_query);
            $fods_sql_query = "INSERT INTO `finance_order_data_summary` (`posted_date`, `Date_in_GMT`, `Date_in_PST`, `amazon_order_id`, `seller_sku`, `quantity`, `marketplace`, `product_sales`, `shipping_credits`, `gift_wrap_credits`, `promotional_rebates`, `sales_tax_collected`, `marketplace_facilitator_tax`, `selling_fees`, `fba_fees`, `other_transaction_fees`, `currency`, `added_by`, `type`, `dev_date`)
                             VALUES
                             $fod_bulk_query_data_implode
                             ON DUPLICATE KEY
                             UPDATE
                             posted_date=VALUES(posted_date), Date_in_GMT=VALUES(Date_in_GMT), Date_in_PST=VALUES(Date_in_PST), amazon_order_id=VALUES(amazon_order_id), seller_sku=VALUES(seller_sku), quantity=VALUES(quantity), marketplace=VALUES(marketplace), product_sales=VALUES(product_sales), shipping_credits=VALUES(shipping_credits), gift_wrap_credits=VALUES(gift_wrap_credits), promotional_rebates=VALUES(promotional_rebates), sales_tax_collected=VALUES(sales_tax_collected), marketplace_facilitator_tax=VALUES(marketplace_facilitator_tax), selling_fees=VALUES(selling_fees), fba_fees=VALUES(fba_fees), other_transaction_fees=VALUES(other_transaction_fees), currency=VALUES(currency), added_by=VALUES(added_by), type=VALUES(type), dev_date=VALUES(dev_date)";

            $check_fods_sql_query = $this->db->query($fods_sql_query);
            if (!$check_fods_sql_query) {
                $saveErrorData = array();
                $saveErrorData['error'] = $this->db->error();
                $mwsNewDataLogEmpty = array();
                $mwsNewDataLogEmpty['table_name'] = "Error Found finance_order_data in finance_order_data_summary table Query";
                $mwsNewDataLogEmpty['data']       = json_encode($saveErrorData);
                $this->insertdata('mws_new_data_log',$mwsNewDataLogEmpty);
            }
        }


        echo "<pre>";
        print_r($fod_bulk_query);
        die("eee");
    }

    die("finance_order_data");
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
            $promotionmetadatadefinitionvalue_explode = explode(',',$get_finance_refund_event['promotionmetadatadefinitionvalue']);
            $promotionmetadatadefinitionvalue_sum     = array_sum($promotionmetadatadefinitionvalue_explode);
            $add_finance_refund_event_list['promotional_rebates'] = $promotionmetadatadefinitionvalue_sum;
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
            $add_finance_service_fee_event['fee_type']    = $get_finance_service_fee_event['fee_type'];
            $add_finance_service_fee_event['posted_date'] = $get_finance_service_fee_event['ref_date'];
            $checkExitServiceFeeEventArrayForSummary = array(
                                                                'other_transaction_fees' => $get_finance_service_fee_event['fee_amount'],
                                                                'added_by'   => $get_finance_service_fee_event['added_by'],
                                                                'type'       => "service-fee",
                                                                'fee_type'   => $get_finance_service_fee_event['fee_type']
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
