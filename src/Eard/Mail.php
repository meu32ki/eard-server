<?php

namespace Eard;

use pocketmine\Player;


/****
*
*	メール送ったりするやつ - メールマネージャー?
*   Playerのオブジェクトは持たず, 結果だけを返す
*/

/** db table **
* MailId       : int(20)  auto_increment
* FromUniqueId : int(10)  送信者
* ToUniqueId   : int(10)  受信者
* Subject      : str(50)  見出し 256バイト
* Body         : str(300) 本文 65536バイト
* Date         :　timestamp
*/
class Mail {


    // Static Data
    public static $mailAccounts       = null;
    public static $companyMailAccount = null;

    public static function getMailAccount(Player $player) : Mail{
        $name = strtolower($player->getName());

        if(!isset(self::$mailAccounts[$name])) {
            $mail = new Mail($name, Mail::TYPE_PLAYER);
            $mail->setAccount(Account::get($player));
            self::$mailAccounts[$name] = $mail;
        }

        return self::$mailAccounts[$name];
    }

    public static function getCompanyMailAccount($id) : Mail {
        
    }

    public static function broadcastMail($subject, $body) {

        $db = DB::get();

        $sql = "INSERT INTO mail (FromUniqueId, ToUniqueId, Subject, Body, Date) VALUES (0, 0, ?, ?, now())";

        
        $db = DB::get();

        $stmt = $db->prepare($sql);
        $stmt->bind_param("ss", $subject, $body);

        $stmt->execute();

        

    }


    // 通知処理
    public static function noifyToPlayer($uniqueIds = []) {

        $accounts = Account::getOnlineUsers();

        foreach($uniqueIds as $uniqueId) {

            foreach($accounts as $account) {

                if($account->getUniqueNo() == $uniqueId) {
                    
                    $account->getPlayer()->sendTip("§ainfo: §fメールを受信しました!");
                }
            }

        }
        
    }

    // Main
    
    const MAIL_ID     = 0;
    const FROM_UNIQUE = 1, TO_UNIQUE = 2;
    const SUBJECT     = 3;
    const BODY        = 4;
    const DATE        = 5;
    const FROM        = 6;
    /*
        [
            MAIL_ID => id,
            FROM_UNIQUE => uniqueid,
            SUBJECT => 件名
            BODY    => 本文
        ]
    */

    const TYPE_PLAYER  = 0;
    const TYPE_COMPANY = 1;

    private $mails;
    private $type; // Player ? or Company ?
    private $account; 

    private $name;

    private function __construct(String $name, int $type) {
        $this->name = $name;
        $this->type = $type;
    }

    public function setAccount($account) {
        $this->account = $account;
    }

    public function getReceivedMails(int $uniqueId, int $start, int $end) : array { 
        $count = $end - $start;
        if($this->type === Mail::TYPE_PLAYER)
            $sql = 
            "SELECT 
                mail.MailId, mail.FromUniqueId, data.name, mail.Subject, mail.Body 
            FROM 
                mail, data 
            WHERE 
                (mail.ToUniqueId = ? OR mail.ToUniqueId = 0) AND mail.ToUniqueId = data.no
            order by 
                mail.MailId 
            limit 
                ? , ?"; // To UniqueId 0 is Broadcast
        else if ($this->type === Mail::TYPE_COMPANY)
            $sql = 
            "SELECT 
                mail.MailId, mail.FromUniqueId, data.name, mail.Subject, mail.Body 
            FROM 
                mail, data 
            WHERE 
                mail.ToUniqueId = ? AND mail.ToUniqueId = data.no
            order by 
                mail.MailId 
            limit 
                ? , ?";


        $db = DB::get();

        $stmt = $db->prepare($sql);
        $stmt->bind_param("sii", $uniqueId, $start, $end);

        // 初期化
        $mailId       = 0;
        $fromUniqueId = 0;
        $from         = "";
        $subject      = "";
        $body         = "";

        //えんど
        $stmt->bind_result(
            $mailId,
            $fromUniqueId,
            $from,
            $subject,
            $body
        );

        $stmt->execute();

        $results = [];
        while($stmt->fetch() === true) {
            $results[] = [
                self::MAIL_ID     => $mailId,
                self::FROM_UNIQUE => $fromUniqueId,
                self::SUBJECT     => $subject,
                self::BODY        => $body,
                self::FROM        => $from
            ];
        }

        $stmt->close();

        return $results;
        
    }

    public function getSentMails(int $start, int $end) : array {
        $count = $end - $start;

        $sql = "SELECT MailId, FromUniqueId, Subject, Body, Date FROM mail WHERE fromUniqueId = ? order by MailId limit ? , ?"; 

        $db = DB::get();

        $stmt = $db->prepare($sql);
        $stmt->bind_param("sii", $this->account->getUniqueNo(), $start, $end);

        // 初期化
        $mailId       = 0;
        $fromUniqueId = 0;
        $subject      = "";
        $body         = "";
        $date         = 0;

        //えんど
        $stmt->bind_result(
            $mailId,
            $fromUniqueId,
            $subject,
            $body,
            $date
        );

        $stmt->execute();

        $results = [];
        while($stmt->fetch() === true) {
            $results[] = [
                self::MAIL_ID     => $mailId,
                self::FROM_UNIQUE => $fromUniqueId,
                self::SUBJECT     => $subject,
                self::BODY        => $body,
                self::DATE        => $date
            ];
        }

        $stmt->close();

        return $results;
        
    }

    public function sendMail($toUniqueIds, $subject, $body) {
        
        $sql = "INSERT INTO mail (FromUniqueId, ToUniqueId, Subject, Body, Date) VALUES (?, ?, ?, ?, now())";

        $db = DB::get();
        
        $stmt = $db->prepare($sql);

        if($this->type === Mail::TYPE_PLAYER)
            $fromUniqueId = $this->account->getUniqueNo();


        $stmt->bind_param("iiss", $fromUniqueId, $toUniqueId, $subject, $body);

        foreach($toUniqueIds as $toUniqueId) {
            $stmt->execute();
        }

        $stmt->close();

        Mail::noifyToPlayer($toUniqueIds);

    }
}