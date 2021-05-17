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

            $this->validate($req, [
                'order_id' => 'nullable',
                'payment_type' => 'nullable',
                'bank_name' => 'nullable',
                'token_id' => 'nullable',
                'store' => 'nullable',
                'internet_banking_bank' => 'nullable',
                'klik_bca_user_id' => 'nullable',
                'e_wallet_type' => 'nullable',
                'acquirer_type' => 'nullable',
                'cardless_type' => 'nullable',
             ]);

            $result = null;
            $order_id = $req->input('order_id');
            $payment_type = $req->input('payment_type'); //accepted value: bank_transfer, credit_card, counter, internet_banking, e_wallet, cardless_credit, bank_transfer_manual
            $bank_name = $req->input('bank_name'); //harus nullable, khusus untuk metode transfer bank accepted value: permata, bca, echannel, bni, bri
            $token_id = $req->input('token_id'); //harus nullable, khusus untuk metode kartu kredit
            $store = $req->input('store'); //harus nullable, khusus untuk metode Over the Counter. accepted value: alfamart, Indomaret
            $internet_banking_type = $req->input('internet_banking_bank'); //harus nullable, khusus untuk metode Internet Banking. accepted value: bca_klikpay, bca_klikbca, bri_epay, cimb_clicks, danamon_online
            $klik_bca_user_id = $req->input('klik_bca_user_id'); //harus nullable, khusus untuk metode Internet Banking Klik BCA. 
            $e_wallet_type = $req->input('e_wallet_type'); //harus nullable, khusus untuk metode E-Wallet. accepted value: qris, gopay, shopeepay
            $acquirer_type = $req->input('acquirer_type'); //harus nullable, khusus untuk metode E-Wallet tipe qris. accepted value: airpay shopee, gopay
            $cardless_type = $req->input('cardless_type'); //harus nullable, khusus untuk metode Cardless Credit. accepted value: akulaku

            $this->validate($req, [
                'order_id' => 'nullable',
                'payment_type' => 'nullable',
                'bank_name' => 'nullable',
                'token_id' => 'nullable',
                'store' => 'nullable',
                'internet_banking_bank' => 'nullable',
                'klik_bca_user_id' => 'nullable',
                'e_wallet_type' => 'nullable',
                'acquirer_type' => 'nullable',
             ]);
            

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
                    $result = self::chargeInternetBanking($order_id, $transaction, $internet_banking_type, $klik_bca_user_id);
                    break;
                case 'e_wallet':
                    $result = self::chargeEwallet($order_id, $transaction, $e_wallet_type, $acquirer_type);
                    break;
                case 'cardless_credit':
                    $result = self::chargeCardlessCredit($order_id, $transaction, $cardless_type);
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
            

            if($bank_name=='echannel'){
                $transaction['payment_type'] = 'echannel'; 
                $transaction['echannel'] = [
                    "bill_info1" => "Payment For:",
                    "bill_info2" => "debt"
                ];
            }
            else {
                $transaction['payment_type'] = 'bank_transfer'; 
                $transaction['bank_transfer'] = [
                    "bank" => $bank_name,
                ];
            }

           

            $charge = MidtransCoreApi::charge($transaction);
            if(!$charge){
                return ['code' => 0, 'message' => 'Terjadi Kesalahan dalam charge'];
            }


            // $histori_transaksi = new HistoriTransaksi();
            // $histori_transaksi->order_id = $charge->$order_id;
            // $histori_transaksi->transaction_id = $charge->transaction_id;
            // $histori_transaksi->status = "PENDING";

            //tambahkan payment_type, va_number, bill_info (khusus mandiri/echannel), bank, tenggat waktu di detail history transaksi
            // $histori_transaksi->payment_type = $charge->payment_type;
            
            if($bank_name=='permata'){
            // $histori_transaksi->va_number = $charge->permata_va_number;
            // $histori_transaksi->bank = 'permata';
            }
            else if($bank_name=='echannel'){
            // $histori_transaksi->bill_key = $charge->bill_key;
            // $histori_transaksi->biller_code = $charge->biller_code;
            // $histori_transaksi->bank = 'echannel';
            }
            else {
            // $histori_transaksi->va_number = $charge->va_numbers[0]->va_number;
            // $histori_transaksi->bank =  $charge->va_numbers[0]->bank;
            }
            // $histori_transaksi->tenggat_waktu = masukkan tenggat waktu;

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
            dd($e);
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
            // $histori_transaksi->order_id = $histori_transaksi_id;
            // $histori_transaksi->transaction_id = $charge->transaction_id;
            // $histori_transaksi->status = "PENDING";

            // perlu redirect_url

            //tambahkan redirect_url, payment_type di detail history transaksi
            // $histori_transaksi->redirect_url = $charge->redirect_url;
            // $histori_transaksi->payment_type = $charge->payment_type;
            // $histori_transaksi->tenggat_waktu = masukkan tenggat waktu;

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
            dd($e);
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
            // $histori_transaksi->order_id = $charge->$order_id;
            // $histori_transaksi->transaction_id = $charge->transaction_id;
            // $histori_transaksi->status = "PENDING";

            //tambahkan payment_type, payment_code, store, tenggat waktu, di detail history transaksi
            // $historyTransaksi->payment_type = $charge->payment_type;
            // $historyTransaksi->payment_code = $charge->payment_code;
            if($store=='alfamart'){
                // $historyTransaksi->store = 'alfamart';
            }
            else {
                // $historyTransaksi->store = 'indomaret';

            }
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
            dd($e);
            //ini sesuaikan saja sama style masnya
            return ['code' => 0, 'message' => 'Terjadi Kesalahan'];
        }

    }

    static public function chargeInternetBanking($order_id, $transaction_object, $internet_banking_type, $klikbcaUserID){
        
        try {
            
            $transaction = $transaction_object; 
            $transaction['payment_type'] = $internet_banking_type; 

            if($internet_banking_type=='bca_klikpay' || $internet_banking_type=='cimb_clicks'){
                $transaction[$internet_banking_type] = [
                    "description" => 'Pembelian barang', // required
                ];
            }
            else if($internet_banking_type=='bca_klikbca'){
                $transaction[$internet_banking_type] = [
                    "description" => 'Pembelian barang', // required
                    "user_id" => $klikbcaUserID // required
                ];

            }

           

            $charge = MidtransCoreApi::charge($transaction);
            if(!$charge){
                return ['code' => 0, 'message' => 'Terjadi Kesalahan dalam charge'];
            }


            // $histori_transaksi = new HistoriTransaksi();
            // $histori_transaksi->order_id = $charge->$order_id;
            // $histori_transaksi->transaction_id = $charge->transaction_id;
            // $histori_transaksi->status = "PENDING";

            
            // perlu redirect_url

            //tambahkan redirect_url, payment_type, tenggat waktu di detail history transaksi
            // $historyTransaksi->redirect_url = $charge->redirect_url;
            // $historyTransaksi->payment_type = 'internet_banking';
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
            dd($e);
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
                    // "callback_url" => "someapps://callback" 
                ];

            }

            else if($e_wallet_type=='shopeepay'){
                $transaction[$e_wallet_type] = [
                    //callback ketika user telah menyelesaikan pembayaran di aplikasi gojek, bisa berupa http atau deeplink, default nya ada dashboard midtrans bagian finish
                    // "callback_url" => "someapps://callback" 
                ];

            }

           

            $charge = MidtransCoreApi::charge($transaction);
            if(!$charge){
                return ['code' => 0, 'message' => 'Terjadi Kesalahan dalam charge'];
            }


            // $histori_transaksi = new HistoriTransaksi();
            // $histori_transaksi->order_id = $charge->$order_id;
            // $histori_transaksi->transaction_id = $charge->transaction_id;
            // $histori_transaksi->status = "PENDING";

            
            // perlu redirect_url

            //tambahkan redirect_url, payment_type, tenggat waktu di detail history transaksi
            if($e_wallet_type=='gopay'){
            // $history_transaksi->redirect_url = $charge->actions[1]->url;
            }
            else {
                // $history_transaksi->redirect_url = $charge->actions[0]->url;
            }   
                
            // $history_transaksi->payment_type = 'e_wallet';

            // $history_transaksi->tenggat_waktu = masukkan tenggat waktu;

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
            dd($e);
            //ini sesuaikan saja sama style masnya
            return ['code' => 0, 'message' => 'Terjadi Kesalahan'];
        }

    }

    static public function chargeCardlessCredit($order_id, $transaction_object, $cardless_type){
        
        try {
            
            $transaction = $transaction_object; 
            $transaction['payment_type'] = $cardless_type; 


            $charge = MidtransCoreApi::charge($transaction);
            if(!$charge){
                return ['code' => 0, 'message' => 'Terjadi Kesalahan dalam charge'];
            }


            // $histori_transaksi = new HistoriTransaksi();
            // $histori_transaksi->order_id = $charge->$order_id;
            // $histori_transaksi->transaction_id = $charge->transaction_id;
            // $histori_transaksi->status = "PENDING";

            
            // perlu redirect_url

            //tambahkan redirect_url, payment_type, tenggat waktu, di detail history transaksi
            // $history_transaksi->redirect_url = $charge->redirect_url;
            // $history_transaksi->payment_type = 'cardless_credit';
            // $history_transaksi->tenggat_waktu = masukkan tenggat waktu;

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
            dd($e);
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
