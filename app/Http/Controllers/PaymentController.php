<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Http\Controller\Midtrans\config;
use App\Http\Controllers\Midtrans\CoreApi as MidtransCoreApi;

class PaymentController extends Controller
{


    public function buatPermintaan(Request $req){
        try {

            //dibawah merupakan field baru yang perlu ditambahkan di request buat permintaan

            $result = null;
            $payment_method = $req->payment_method; //accepted value: bank_transfer, credit_card, bank_transfer_manual, bca_klikpay, bca_klikbca, bri_epay, cimb_clicks, danamon_online, qris, gopay, shopeepay
            $bank_name = $req->bank_name; //ini harus nullable, khusus untuk metode transfer bank
            $token_id = $req->token_id; //ini harus nullable, karena khusus untuk metode kartu kredit

            //generated random order id (hanya contoh)
            // $order_id = 'FS' . date('YmdHis');
            
            $order_id = 'orderpertama';


            $transaction = array(
                "transaction_details" => [
                    "gross_amount"=> 10000,
                    "order_id"=> date('Y-m-dHis')
                ],
                "customer_details" => [
                    "email" => "deris@Midtrans.com",
                    "first_name" => "Deris",
                    "last_name" => "Dev",
                    "phone" => "+628999049139"
                ],
    
                "item_details" => array(
                    [
                        "id" => "1388998298204",
                        "price" => 5000,
                        "quantity" => 1,
                        "name" => "Ayam Zozozo"
                     ],
                     [
                        "id" => "1388998298205",
                        "price" => 5000,
                        "quantity" => 1,
                        "name" => "Ayam Xoxoxo"
                     ]
                ),
            );

            switch($payment_method){
                case 'bank_transfer':
                    $result = self::chargeBankTransfer($order_id, $transaction, $bank_name);
                    break;
                case 'credit_card':
                    $result = self::chargeCreditCard($order_id, $token_id, $transaction);
                    break;

            }



            return $result;

        } catch (\Exception $e) {
            dd($e);

            //ini sesuaikan saja sama style masnya
            return ['code' => 0, 'message' => 'Terjadi Kesalahan'];

        }
    }

    static public function chargeBankTransfer($order_id, $transaction_object, $bank_name){
        
        try {
            
            $transaction = $transaction_object; 
            $transaction['payment_type'] = 'bank_transfer'; 
            $transaction['bank_transfer'] = [
                "bank" => $bank_name,
                "va_number" => "111111",
            ];

            $charge = MidtransCoreApi::charge($transaction);
            if(!$charge){
                return ['code' => 0, 'message' => 'Terjadi Kesalahan dalam charge'];
            }

            // $order = new Order();
            // $order->invoice = $order_id;
            // $order->transaction_id = $charge->transaction_id;
            // $order->status = "PENDING";

            // if(!$order->save())
            // return false;


            return ['code' => 1, 'message' => 'Success', 'data' => 'data yang mas return seperti di detail histori', 'result' => $charge];

        } catch (\Exception $e) {
            //ini sesuaikan saja sama style masnya
            return ['code' => 0, 'message' => 'Terjadi Kesalahan'];
        }

    }

    
    static public function chargeCreditCard($order_id, $token_id, $transaction_object){
        
        try {

            $credit_card = array(
                'token_id' =>  $token_id,
                'authentication' => true,
            );

            $transaction = $transaction_object;
            $transaction['payment_type'] = 'credit_card'; 
            $transaction['credit_card'] = $credit_card;
            $charge = MidtransCoreApi::charge($transaction);

            if(!$charge){
                return ['code' => 0, 'message' => 'Terjadi Kesalahan | Gagal Charge'];
            }

            // $order = new Order();
            // $order->invoice = $order_id;
            // $order->transaction_id = $charge->transaction_id;
            // $order->status = "PENDING";

            //tambahkan redirectUrl di history transaksi
            // $historyTransaksi->redirectUrl = $charge->redirect_url;

            // if(!$order->save())
            // return false;

            return ['code' => 1, 'message' => 'Berhasil', 'data' => 'data yang mas return seperti di detail histori', 'result' => $charge];

        } catch (\Exception $e) {
            //ini sesuaikan saja sama style masnya
            return ['code' => 0, 'message' => 'Terjadi Kesalahan'];
        }

    }

    public function getTokenCreditCard(Request $req){
        try {

            $cc_data = [
                'client_key' => $req->client_key,
                'card_number' => $req->card_number,
                'card_exp_month' => $req->card_exp_month,
                'card_exp_year' => $req->card_exp_year,
                'card_cvv' => $req->card_cvv,
            ];

            $data = http_build_query($cc_data);
            $token = MidtransCoreApi::token($data);

            if(!$token){
                return ['code' => 0, 'message' => 'Ada yang salah | Credit Card Tidak Valid / Didukung'];
            }

            $token_id = json_decode($token->original);
            return ['code' => 1, 'message' => 'Sukses', 'result' => $token_id];


        } catch (\Exception $e) {
            //ini sesuaikan saja sama style masnya
            return ['code' => 0, 'message' => 'Ada yang salah | Internal Get Token'];
        }   
    }

}
