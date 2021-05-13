<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Http\Controller\Midtrans\config;
use App\Http\Controllers\Midtrans\CoreApi as MidtransCoreApi;

class NotificationController extends Controller
{
    public function post(Request $req){
        try {
            $notification_body = json_decode($req->getContent(), true);
            $invoice = $notification_body['order_id'];
            $transaction_id = $notification_body['transaction_id'];
            $status_code = $notification_body['status_code'];
            $transaction_status = $notification_body['transaction_status'];

            //untuk ambil fcm_id user (hanya contoh)
            // $histori_transaksi = HistoriTransaksi::where('invoice', $invoice)->where('transaction_id', $transaction_id)->first();
            // $id_histori_transaksi = $histori_transaksi->id_histori_transaksi;
            $id_histori_transaksi = 1;

            // if(!$histori_transaksi)
            // return ['code' => 0, 'message' => "Terjadi kesalahan | histori_transaksi Tidak ditemukan"];

            switch($status_code){
                case '200':
                    
                    if($transaction_status=='cancel'){

                    //Notif Cancel
                    // $histori_transaksi->status = "Cancel";

                        self::sendPushNotification(
                            //fcm id belum dinamis
                            'fHaXx0MrTuiD4jDtVc0A32:APA91bGAyid7yxTjt5ljiz0Yk1aKXVZ742rIVMSSBN99bDQ-7qaLXmG8j1iHHHLUPZTT9t7egDAMY_HqBBKKkD508qbH46izb9pnp0VDYHZsj1vbU7o44fdLLevgyNNZbeE5vrgCqNM7', 
                            'Pembayaran Dibatalkan', 
                            'Pembayaran telah dibatalkan karena melebihi tenggat waktu.', 
                            '3', $id_histori_transaksi);
                    }
                    else {

                        //Notif Sukses
                    // $histori_transaksi->status = "Berhasil";

                        self::sendPushNotification(
                            //fcm id belum dinamis
                            'fHaXx0MrTuiD4jDtVc0A32:APA91bGAyid7yxTjt5ljiz0Yk1aKXVZ742rIVMSSBN99bDQ-7qaLXmG8j1iHHHLUPZTT9t7egDAMY_HqBBKKkD508qbH46izb9pnp0VDYHZsj1vbU7o44fdLLevgyNNZbeE5vrgCqNM7', 
                            'Pembayaran Berhasil', 
                            'Selamat! Pembayaran telah terkonfirmasi. Transaksimu sedang diproses.', 
                            '2', $id_histori_transaksi);
                    }
                    break;
                case '201':
                    //Notif Pending
                    // $histori_transaksi->status = "Pending";
                    self::sendPushNotification(
                        //fcm id belum dinamis
                        'fHaXx0MrTuiD4jDtVc0A32:APA91bGAyid7yxTjt5ljiz0Yk1aKXVZ742rIVMSSBN99bDQ-7qaLXmG8j1iHHHLUPZTT9t7egDAMY_HqBBKKkD508qbH46izb9pnp0VDYHZsj1vbU7o44fdLLevgyNNZbeE5vrgCqNM7', 
                        'Pembayaran Pending', 
                        'Pesanan telah terkonfirmasi. Lakukan pembayaran sebelum Selasa, 20:30 WIB.', 
                        '1', $id_histori_transaksi);

                    break;
                case '202' :
                    //Notif denied
                    // $histori_transaksi->status = "denied";
                    self::sendPushNotification(
                        //fcm id belum dinamis
                        'fHaXx0MrTuiD4jDtVc0A32:APA91bGAyid7yxTjt5ljiz0Yk1aKXVZ742rIVMSSBN99bDQ-7qaLXmG8j1iHHHLUPZTT9t7egDAMY_HqBBKKkD508qbH46izb9pnp0VDYHZsj1vbU7o44fdLLevgyNNZbeE5vrgCqNM7', 
                        'Pembayaran Ditolak', 
                        'Pesanan ditolak oleh provider', 
                        '5',$id_histori_transaksi);
                    break;
            }

            // $histori_transaksi->save();

            return response('Ok', 200)->header('Content-Type', 'text/plain');

        } catch (\Exception $e) {
            return response('Error', 404)->header('Content-Type', 'text/plain');
        }
    }

    static function sendPushNotification($fcm_token, $title, $message, $kode_status, $id_histori_transaksi , $id = null,$action = null) {  
     
        $url = "https://fcm.googleapis.com/fcm/send";            
        $header = [
            'authorization: key=AAAAfIemvrw:APA91bEqHB8ub4fYgb0AA_TtgZYIkAk0M65Bw0kdYnZsQ6WkmSjZ1H9A6VUDdPlHh7NqVe9QoownDrslpGh3JAPYRs4bu37qSbMaz-7Ob7kWmJVf8JW-LVulM2Tw4h8OLpirRi5xVdLQ',
            'content-type: application/json'
        ];    
     
        $notification = [
            'title' =>$title,
            'body' => $message
        ];

        $data = [
            "kode_status" => $kode_status,
            "id_histori_transaksi " => $id_histori_transaksi 
        ];

        $extraNotificationData = ["message" => $data,"id" =>$id,'action'=>$action];
     
        $fcmNotification = [
            'to'        => $fcm_token,
            'notification' => $notification,
            'data' => $extraNotificationData
        ];
     
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmNotification));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
     
        $result = curl_exec($ch);    
        curl_close($ch);
     
        return $result;
    }
}
