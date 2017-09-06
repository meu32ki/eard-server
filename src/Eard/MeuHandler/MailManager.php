<?php
namespace Eard\MeuHandler;


# Basic
use pocketmine\Player;
use pocketmine\Server;

# Eard
use Eard\DBCommunication\DB;
use Eard\MeuHandler\Account;
use Eard\Utils\Chat;


class MailManager {

    /*
        基本的には、メールは一回一回dbから取得する。
        ただし、今後すぐにそのメールが使われるだろうと思われる際には、self::$mails[$uniqueNo]に保存しておく
        ページ送りの場合に毎回connectが嫌なのでローカルに保存しておくほうがスマートかなと

        これは一部テーブルに過ぎない
    */

    /** db table **
    * MailId    : int(10)  auto_increment
    * From  : int(10)  送信者
    * To        : int(10)  受信者
    * State     : int(2)    1 = 削除, 2 = 既読, 3 = 未読
    * Key       : str(32) メールごとに割り当てられた一意なキー (複数一斉送信の場合にこれを見て確認する)
    * Subject      : str(50)  見出し 256バイト
    * Body         : str(300) 本文 65536バイト
    * Date         :　timestamp
    */



    /**
    *   メール送信
    */
    public static function sendMail(Mail $mail){
        if(!$mail->id && !$mail->key){
            // まだ送られていないので、送れる
            if($mail->to != null && $mail->from && $mail->subject && $mail->body){ // toは0になることがある(全員に送る場合)
                // キーを生成
                $mail->key = md5(uniqid(rand(), true)); // セキュリティとか大丈夫だよね！ね！
                $mail->date = date("Y-m-d H:i:s");
                $mail->state = Mail::STATE_UNREAD;

                // 送信
                $flag = MailManager::sendQueue($mail);
                return $flag;
            }else{
                return Mail::ERR_NO_INFO;
            }
        }else{
            // すでに送られている
            return Mail::ERR_ALREADY_SENT;
        }       
    }


    /**
    *   実際のmailオブジェクトを、メールとしてdbに記録する
    */
    public static function sendQueue(Mail $mail){
        $sql = "INSERT INTO mail ".
            "(FromUniqueId, ToUniqueId, State, Key, Subject, Body, Date) ".
            "VALUES (?, ?, ?, ?, ?, ?, ?);";
        $db = DB::get();
        $stmt = $db->prepare($sql);

        // 最初はtoにおくる
        $stmt->bind_param("iiissss", $mail->from, $mail->to, $mail->state, $mail->key, $mail->subject, $mail->body, $mail->date);
        $stmt->execute();

        // ccに送る処理
        foreach($mail->cc as $to){
            $stmt->bind_param("iiissss", $mail->from, $to, $mail->state, $mail->key, $mail->subject, $mail->body, $mail->date);
            $stmt->execute();
        }

        $stmt->close();
    }


    /**
    *   そのプレイヤーが受信できる(下書き含む)すべてのメールを取得しメモリ上に保存しておく
    *   @return Mail[]
    */
    public static function getAllMailsSentTo(Account $playerData){
        $uniqueNo = $playerData->getUniqueId();
        $sql = "SELECT * FROM mail WHERE ToUniqueId = ? OR ToUniqueId = 0;";

        $db = DB::get();
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $uniqueNo);
        $mails = self::handleGetQueue($stmt);

        // 保持しておく
        self::$mails[$uniqueNo] = $mails;
        return $mails;
    }


    /**
    *   未読のメールを取得しメモリ上に保存しておく
    *   @param Account このプレイヤーの受信できるメールを取得する
    *   @return Mail[]
    */
    public static function getUnreadMailsSentTo(Account $playerData){
        $uniqueNo = $playerData->getUniqueId();

        // 取得のためのsql
        $state = Mail::STATE_UNREAD;
        $sql = "SELECT * FROM mail WHERE (ToUniqueId = ? OR ToUniqueId = 0) AND State = {$state};";

        // 準備
        $db = DB::get();
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $uniqueNo);
        $mails = self::handleGetQueue($stmt);

        // 保持しておく
        self::$mails[$uniqueNo] = $mails;
        return $mails;
    }


    /**
    *   未読のメールを取得しメモリ上に保存しておく
    *   @param Account このプレイヤーの受信できるメールを取得する
    *   @return Mail[]
    */
    public static function getAllMailsSentFrom(Account $playerData){
        $uniqueNo = $playerData->getUniqueId();

        // 取得のためのsql
        $state = Mail::STATE_UNREAD;
        $sql = "SELECT * FROM mail WHERE FromUniqueId = ?;";

        // 準備
        $db = DB::get();
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $uniqueNo);
        $mails = self::handleGetQueue($stmt);

        // 保持しておく
        self::$mails[$uniqueNo] = $mails;
        return $mails;
    }

    /**
    *   向こう側で出したgetがわのstmtをここで処理する
    *   @return Mail[]
    */
    private static function handleGetQueue($stmt){

        $mailId = 0;
        $from = 0;
        $to = 0;
        $state = 0;
        $key = "";
        $subject = "";
        $body = "";
        $date = 0;

        $stmt->bind_result(
            $mailId, $from, $to, $state, $key, $subject, $body, $date;
        );
        $stmt->execute();

        // メールを格納
        $mails = [];
        while($stmt->fetch() === true) {
            $mail = new Mail;
            $mail->id = $mailId;
            $mail->from = $from;
            $mail->to = $to;
            $mail->state = $state;
            $mail->key = $key;
            $mail->subject = $subject;
            $mail->body = $body;
            $mail->date = $date;
            $mails[] = $mail;
        }
        $stmt->close();
        return $mails[];
    }


    /**
    *   メモリ上から、そのプレイヤーの受信できるメールをアンロードする(削除)
    *   @return true
    */
    public static function unloadMailsOf(Account $playerData){
        unset(self::$mails[$uniqueNo]);
        return true;
    }

    /**
    *   webからは使われるべきでない
    *   更新かける 新しいメール有れば取得して、通知する NotifyPlayerも兼ねている
    *
    *   今回取得したmailsの保存はしない
    *   なぜなら、通知用に一時的にmailsを取得するだけであって
    *   実際にプレイヤーが見るmailsは、get__MailsOfでプレイヤーごとに処理されるべきなので
    *   @return void
    */
    public static function reflesh(){

        // いま、PMMP側で保持している中でどこまでフェッチしたかを 
        $newestMailId = self::$newestMailId;
        $sql = "SELECT * FROM mail WHERE MailId <= {$newestMailId};";

        $db = DB::get();
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $uniqueNo);
        $mails = self::handleStmt($stmt);

        // $mailsの中身は新しく取得したメール 
        foreach($mails as $mail){
            $to = $mail->to;
            // 誰か個人にあてたもの
            if($to){    
                // おんらいんであれば
                $player = Account::getByUniqueNo($to)->getPlayer();
                if($player instanceof Player){
                    $name = Account::getByUniqueNo($mail->from)->getName();
                    $msg = Chat::Format("§8メール", "§6個人", "{$name}からメールが届きました！  [{$mail->subject}]");
                }

            // プレイヤー全員にあてたもの
            }else{
                $msg = Chat::Format("§8メール", "§bお知らせ", "政府からメールが届きました！ [{$mail->subject}]");
                $players = Server::getInstance()->getOnlinePlayers();
                foreach($players as $player){
                    $player->sendMessage($msg);
                }
            }
        }
    }


    private static $newestMailId;
}