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
            $order_id = $req->order_id;
            $payment_type = $req->payment_type; //accepted value: bank_transfer, credit_card, counter, internet_banking, e_wallet, bank_transfer_manual
            $bank_name = $req->bank_name; //harus nullable, khusus untuk metode transfer bank
            $token_id = $req->token_id; //harus nullable, khusus untuk metode kartu kredit
            $store = $req->store; //harus nullable, khusus untuk metode Over the Counter (indomart, alfamart)
            $internet_banking_bank = $req->internet_banking_bank; //harus nullable, khusus untuk metode Internet Banking
            $klik_bca_user_id = $req->klik_bca_user_id; //harus nullable, khusus untuk metode Internet Banking Klik BCA
            $e_wallet_type = $req->e_wallet_type; //harus nullable, khusus untuk metode E-Wallet
            $acquirer_type = $req->acquirer_type; //harus nullable, khusus untuk metode E-Wallet tipe qris
            

            $transaction = array(
                "transaction_details" => [
                    "gross_amount"=> 10000,
                    "order_id"=> $order_id
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

            switch($payment_type){
                case 'bank_transfer':
                    $result = self::chargeBankTransfer($order_id, $transaction, $bank_name);
                    break;
                case 'credit_card':
                    $result = self::chargeCreditCard($order_id, $token_id, $transaction);
                    break;
                case 'counter':
                    $result = self::chargeOverCounter($order_id,$transaction, $store);
                    break;
                case 'internet_banking':
                    $result = self::chargeInternetBanking($order_id,$transaction, $internet_banking_bank, $klik_bca_user_id);
                    break;
                case 'e_wallet':
                    $result = self::chargeEwallet($order_id,$transaction, $e_wallet_type, $acquirer_type);
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
            ];

            $charge = MidtransCoreApi::charge($transaction);
            if(!$charge){
                return ['code' => 0, 'message' => 'Terjadi Kesalahan dalam charge'];
            }


            // $histori_transaksi = new HistoriTransaksi();
            // $histori_transaksi->invoice = $charge->$order_id;
            // $histori_transaksi->transaction_id = $charge->transaction_id;
            // $histori_transaksi->status = "PENDING";

            //tambahkan payment_type, order_id tenggat waktu, di detail history transaksi
            // $historyTransaksi->payment_type = $charge->payment_type;
            // $historyTransaksi->tenggat_waktu = masukkan tenggat waktu;

            //waktu expire bisa dicek di link berikut:
            //https://api-docs.midtrans.com/#code-2xx
            
            //jika ingin custom tenggat waktu tambahkan field berikut, credit card method tidak bisa custom, dan qris & shopeepay max 60 menit
            // "custom_expiry": {
            //     "order_time": "2016-12-07 11:54:12 +0700",
            //     "expiry_duration": 60,
            //     "unit": "minute"
            // }

            // if(!$histori_transaksi->save())
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

            // $histori_transaksi = new HistoriTransaksi();
            // $histori_transaksi->invoice = $histori_transaksi_id;
            // $histori_transaksi->transaction_id = $charge->transaction_id;
            // $histori_transaksi->status = "PENDING";

            // perlu redirectURL

            //tambahkan redirectUrl, payment_type, order_id di detail history transaksi
            // $historyTransaksi->redirectUrl = $charge->redirect_url;
            // $historyTransaksi->payment_type = $charge->payment_type;
            // $historyTransaksi->tenggat_waktu = masukkan tenggat waktu;

            //waktu expire bisa dicek di link berikut:
            //https://api-docs.midtrans.com/#code-2xx
            
            //jika ingin custom tenggat waktu tambahkan field berikut, credit card method tidak bisa custom, dan qris & shopeepay max 60 menit
            // "custom_expiry": {
            //     "order_time": "2016-12-07 11:54:12 +0700",
            //     "expiry_duration": 60,
            //     "unit": "minute"
            // }

            // if(!$histori_transaksi->save())
            // return false;

            return ['code' => 1, 'message' => 'Berhasil', 'data' => 'data yang mas return seperti di detail histori', 'result' => $charge];

        } catch (\Exception $e) {
            //ini sesuaikan saja sama style masnya
            return ['code' => 0, 'message' => 'Terjadi Kesalahan'];
        }

    }

    static public function chargeOverCounter($order_id, $transaction_object, $store){
        
        try {
            
            $transaction = $transaction_object; 
            $transaction['payment_type'] = 'cstore'; 
            $transaction['cstore'] = [
                "store" => $store,
            ];

            $charge = MidtransCoreApi::charge($transaction);
            if(!$charge){
                return ['code' => 0, 'message' => 'Terjadi Kesalahan dalam charge'];
            }


            // $histori_transaksi = new HistoriTransaksi();
            // $histori_transaksi->invoice = $charge->$order_id;
            // $histori_transaksi->transaction_id = $charge->transaction_id;
            // $histori_transaksi->status = "PENDING";

            //tambahkan payment_type, order_id tenggat waktu, di detail history transaksi
            // $historyTransaksi->payment_type = $charge->payment_type;
            // $historyTransaksi->tenggat_waktu = masukkan tenggat waktu;

            //waktu expire bisa dicek di link berikut:
            //https://api-docs.midtrans.com/#code-2xx
            
            //jika ingin custom tenggat waktu tambahkan field berikut, credit card method tidak bisa custom, dan qris & shopeepay max 60 menit
            // "custom_expiry": {
            //     "order_time": "2016-12-07 11:54:12 +0700",
            //     "expiry_duration": 60,
            //     "unit": "minute"
            // }

            // if(!$histori_transaksi->save())
            // return false;


            return ['code' => 1, 'message' => 'Success', 'data' => 'data yang mas return seperti di detail histori', 'result' => $charge];

        } catch (\Exception $e) {
            //ini sesuaikan saja sama style masnya
            return ['code' => 0, 'message' => 'Terjadi Kesalahan'];
        }

    }

    static public function chargeInternetBanking($order_id, $transaction_object, $internet_banking_bank, $klikbcaUserID){
        
        try {
            
            $transaction = $transaction_object; 
            $transaction['payment_type'] = $internet_banking_bank; 

            if($internet_banking_bank=='bca_klikpay' || $internet_banking_bank=='cimb_clicks'){
                $transaction[$internet_banking_bank] = [
                    "description" => 'Pembelian barang', // required
                ];
            }
            else if($internet_banking_bank=='bca_klikbca'){
                $transaction[$internet_banking_bank] = [
                    "description" => 'Pembelian barang', // required
                    "user_id" => $klikbcaUserID // required
                ];

            }

           

            $charge = MidtransCoreApi::charge($transaction);
            if(!$charge){
                return ['code' => 0, 'message' => 'Terjadi Kesalahan dalam charge'];
            }


            // $histori_transaksi = new HistoriTransaksi();
            // $histori_transaksi->invoice = $charge->$order_id;
            // $histori_transaksi->transaction_id = $charge->transaction_id;
            // $histori_transaksi->status = "PENDING";

            
            // perlu redirectURL

            //tambahkan redirectUrl, payment_type, order_id di detail history transaksi
            // $historyTransaksi->redirectUrl = $charge->redirect_url;
            // $historyTransaksi->payment_type = $charge->payment_type;
            // $historyTransaksi->tenggat_waktu = masukkan tenggat waktu;

            //waktu expire bisa dicek di link berikut:
            //https://api-docs.midtrans.com/#code-2xx
            
            //jika ingin custom tenggat waktu tambahkan field berikut, credit card method tidak bisa custom, dan qris & shopeepay max 60 menit
            // "custom_expiry": {
            //     "order_time": "2016-12-07 11:54:12 +0700",
            //     "expiry_duration": 60,
            //     "unit": "minute"
            // }

            // if(!$histori_transaksi->save())
            // return false;


            return ['code' => 1, 'message' => 'Success', 'data' => 'data yang mas return seperti di detail histori', 'result' => $charge];

        } catch (\Exception $e) {
            //ini sesuaikan saja sama style masnya
            return ['code' => 0, 'message' => 'Terjadi Kesalahan'];
        }

    }

    
    static public function chargeEwallet($order_id, $transaction_object, $e_wallet_type, $acquirer_type){
        
        try {
            
            $transaction = $transaction_object; 
            $transaction['payment_type'] = $e_wallet_type; 

            if($e_wallet_type=='qris'){
                $transaction[$e_wallet_type] = [
                    "acquirer" => $acquirer_type, // bisa gopay atau shopeepay
                ];
            }
            else if($e_wallet_type=='gopay'){
                $transaction[$e_wallet_type] = [
                    "enable_callback" => true, 

                    //callback ketika user telah menyelesaikan pembayaran di aplikasi gojek, bisa berupa http atau deeplink, default nya ada dashboard midtrans bagian finish
                    "callback_url" => "someapps://callback" 
                ];

            }

            else if($e_wallet_type=='shopeepay'){
                $transaction[$e_wallet_type] = [
                    //callback ketika user telah menyelesaikan pembayaran di aplikasi gojek, bisa berupa http atau deeplink, default nya ada dashboard midtrans bagian finish
                    "callback_url" => "someapps://callback" 
                ];

            }

           

            $charge = MidtransCoreApi::charge($transaction);
            if(!$charge){
                return ['code' => 0, 'message' => 'Terjadi Kesalahan dalam charge'];
            }


            // $histori_transaksi = new HistoriTransaksi();
            // $histori_transaksi->invoice = $charge->$order_id;
            // $histori_transaksi->transaction_id = $charge->transaction_id;
            // $histori_transaksi->status = "PENDING";

            
            // perlu redirectURL

            //tambahkan redirectUrl, payment_type, order_id di detail history transaksi
            // $historyTransaksi->redirectUrl = $charge->redirect_url;
            // $historyTransaksi->payment_type = $charge->payment_type;
            // $historyTransaksi->tenggat_waktu = masukkan tenggat waktu;

            //waktu expire bisa dicek di link berikut:
            //https://api-docs.midtrans.com/#code-2xx
            
            //jika ingin custom tenggat waktu tambahkan field berikut, credit card method tidak bisa custom, dan qris & shopeepay max 60 menit
            // "custom_expiry": {
            //     "order_time": "2016-12-07 11:54:12 +0700",
            //     "expiry_duration": 60,
            //     "unit": "minute"
            // }

            // if(!$histori_transaksi->save())
            // return false;


            return ['code' => 1, 'message' => 'Success', 'data' => 'data yang mas return seperti di detail histori', 'result' => $charge];

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
