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

    private $mails;

    private $name;

    private function __construct(String $name) {
        $this->name = $name;
    }

    public function getSentMails(int $uniqueId, int $start, int $end) {
        $count = $end - $start;
        $sql = "SELECT * FROM mail WHERE name = ? order by MailId limit ? , ?";

        // 未実装だよっ ( > < )
        // メールを取得する処理を書く予定
    }
	
    



}