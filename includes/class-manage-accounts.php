<?php

namespace MOMO\MPay;

use eftec\bladeone\BladeOne;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Manage_Accounts
{
    
    public function download_clients_list()
    {
        $upload_path = wp_upload_dir();
        $file_name = 'data-upload-template.xlsx';
        $path = $upload_path['path'] . '/' . $file_name;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', '#');
        $sheet->setCellValue('B1', 'Full Name');
        $sheet->setCellValue('C1', 'Registered Mobile Number');
        $sheet->setCellValue('D1', 'Amount');

        //Populate student details.
        $sheet->setCellValue('A2', 1);
        $sheet->setCellValue('B2', 'John Doe');
        $sheet->setCellValue('C2', '256785 xxx xxx');
        $sheet->setCellValue('D2', 20000);

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        wp_send_json_success($upload_path['url'] . '/' . $file_name);
    }

    public function upload_clients_list()
    {
        $user_id = get_current_user_id();
        $upload_path = wp_upload_dir();
        $file_location = $upload_path['path'] . '/' . md5(microtime()) . '.xlsx';
        move_uploaded_file($_FILES['file']['tmp_name'], $file_location);
        $data_sheet   = IOFactory::load($file_location);

        $contacts = [];
        //Read contacts sheet
        $sheet = $data_sheet->getSheet(0);
        for ($i = 2; $i <= $sheet->getHighestDataRow(); $i++) {
            $telephone = $sheet->getCellByColumnAndRow(3, $i)->getValue();
            $amount = $sheet->getCellByColumnAndRow(4, $i)->getValue();
            $contacts[] = [
                'full_name' => $sheet->getCellByColumnAndRow(2, $i)->getValue(),
                'telephone' => '+256' . substr(str_replace(' ', '', $telephone), -9),
                'amount' => str_replace(',', '', $amount),
                //@todo  Add check for registrtaion
                'registered' => 'yes'
            ];

        }

        update_user_meta($user_id, '_momo_mpay_contact_list', $contacts);
        //Delete file after upload
        unlink($file_location);

        wp_send_json_success();
    }
    function get_client_contacts($user_id)
    {
        $contacts = get_user_meta($user_id, '_momo_mpay_contact_list', true);
        if ($contacts) {
            return $contacts;
        }
    
        return [];
    }
    
    function get_disbursement_total($user_id)
    {
        $amount = 0;
        $client_contacts = get_client_contacts($user_id);
        foreach ($client_contacts as $contact) {
            $amount = $amount + $contact['amount'];
        }
    
        return $amount;
    }
    
    function get_client_telephone($user_id)
    {
        $telephone = get_user_meta($user_id, '_mpay_clinet_telephone', true);
    
        return '0' . substr($telephone, -9);
    }
    
    function get_transactions($user_id)
    {
        $transactions = get_posts(
            [
                'post_type' => 'mpay_transaction',
                'post_author' => $user_id,
                'numberposts' => -1
            ]
        );
    
        return $transactions;
    }
    
    function get_wallet_balance($user_id)
    {
        $balance = 0;
        $transactions = get_transactions($user_id);
        foreach ($transactions as $transaction) {
            $type = get_post_meta($transaction->ID, '_transaction_type', true);
            $amount = get_post_meta($transaction->ID, '_transaction_amount', true);
            if ($type === 'charge' || $type === 'disbursement') {
                $balance = $balance - $amount;
            } else {
                $balance = $balance + $amount;
            }
        }
    
        return $balance;
    }
    
    function last_transaction($user_id){
        $amount = 0;
        $transactions = get_transactions($user_id);
        if(count($transactions) > 0) {
            $amount = get_post_meta($transactions[0]->ID, '_transaction_amount', true);
        }
    
        return $amount;
    }
    
    function mpay_fund_wallet_now()
        {
           if (isset($_POST['amount']) && isset($_POST['number'])) {
            
            $token = json_decode( request_auth() );
    
            $deposit = rquest_to_pay($_POST['amount'], $_POST['number'],  $token->access_token);
            
            $post_id = wp_insert_post(
                array(
                    'post_title'  => __( 'Fund Wallet' ),
                    'post_type'   => 'mpay_transaction',
                    'post_status' => 'publish',
                )
            );
    
            if (!is_wp_error($post_id)) {
                update_post_meta($post_id, '_transaction_amount', $_POST['amount'] );
                update_post_meta($post_id, '_transaction_type', 'deposit' );
            }
            
            wp_send_json_success();
           }
        }
    
        // mtn auth
    function request_auth()
        {
            try{
                $args = array(
                    'body'        => [],
                    'headers' => array(
                        'Authorization' => 'Basic ' . base64_encode( '97d51d78-9115-4c19-87d5-54661f430542' . ':' . '51d79d0e8a8c4ea0aa1e5f230cc9e4fb' ),
                        'Content-Type' => 'application/json',
                        'Ocp-Apim-Subscription-Key' => '6afe5756a14c40b7a9fc9bf843f97ea8',
                    )
                );
                $response = wp_remote_post( 'https://sandbox.momodeveloper.mtn.com/collection/token/', $args );
                $body     = wp_remote_retrieve_body( $response );
                
                if ($body) {
                    return $body;
                }
                
            }
            catch (e $ex)
            {
                return $ex;
            }
    
            
        }
    
        // collections
    function rquest_to_pay($amount, $number, $token)
    {
        try{
            $args = array(
                'body'        => [
                'amount' => strval($amount),
                'currency' => 'EUR',
                'externalId' => "6353449",
                'payer' => [
                    'partyIdType' => "MSISDN",
                    'partyId'=> $number
                ],
                'payerMessage' => "Deposit",
                'payeeNote' => "Deposit"
                ],
                'headers' => array(
                    'X-Reference-Id' => wp_generate_uuid4(),
                    'X-Target-Environment' => 'sandbox',
                    'Ocp-Apim-Subscription-Key' => '6afe5756a14c40b7a9fc9bf843f97ea8',
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json'
                )
            );
    
            
            $response = wp_remote_post( 'https://sandbox.momodeveloper.mtn.com/collection/v1_0/requesttopay', $args);
            $status_code     = wp_remote_retrieve_response_code( $response );
            
            if ($status_code) {
                return $status_code;
            }
            
        }
        catch (e $ex)
        {
            return $ex;
        }
    }
}