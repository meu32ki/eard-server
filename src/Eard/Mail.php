<?php
namespace Eard;


/****
*
*	メール送ったりするやつ - メールマネージャー?
*   Playerのオブジェクトは持たず, 結果だけを返す
*/

/** db table **
* MailId       : int(20)  auto_increment
* FromUniqueId : int(10)  送信者
* ToUniqueId   : int(10)  受信者
* Subject      : str(50)  見出し
* Body         : str(300) 本文
*/
class Mail {

    // Static Data
    public static $mailAccounts = null;

    public static function getMailAccount(Player $Player) : Mail{
        $name = strtolower($player->getName());

        if(empty(self::$mailAccounts[$name])) {
            $mail = new Mail($name);
            self::$mailAccounts[$name] = $mail;
        }
    }

    // Main
    
    const MAIL_ID     = 0;
    const FROM_UNIQUE = 1, TO_UNIQUE = 2;
    const SUBJECT     = 3;
    const BODY        = 4;
    /*
        [
            MAIL_ID => id,
            FROM_UNIQUE => uniqueid,
            SUBJECT => 件名
            BODY    => 本文
        ]
    */
    private $mails;

    private $name;

    private function __construct(String $name) {
        $this->name = $name;
    }

    public function getSentMails(int $uniqueId, int $start, int $end) {
        $count = $end - $start;
        $sql = "SELECT MailId, FromUniqueId, Subject, Body FROM mail WHERE ToUniqueId = ? order by MailId limit ? , ?";
        
        /* DBが用意できるまでコメントアウト ( > < )
        $db = DB::get();

        $stmt = $db->prepare($sql);
        $stmt->bind_param("sii", $uniqueId, $start, $end);

        // 初期化
        $mailId       = 0;
        $fromUniqueId = 0;
        $subject      = "";
        $body         = "";

        //えんど
        $stmt->bind_result(
            $mailId,
            $fromUniqueId,
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
                self::BODY        => $body
            ];
        }

        return $results;
        */
    }
	
    



}