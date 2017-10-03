<?php
namespace Eard\Form;


#basic
use pocketmine\network\mcpe\protocol\ServerSettingsResponsePacket;
use pocketmine\Player;

class SettingForm {
    public static function sendSetting(Player $p,int $id){
        $data = [
            'type'    => 'custom_form',
            'title'   => 'EardServer',
            'icon'    => [
                'type' =>'url',
                'data'=>'http://eard.space/images/eardlogo.png'//アイコン画像
             ],
            'content' => [
                ]
        ];//Xを押したとき送られてきます([null,0,"Default"])
        $packet = new ServerSettingsResponsePacket();
        $packet->formId = $id;
        $packet->formData =json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING | JSON_UNESCAPED_UNICODE
        );
        $p->dataPacket($packet);
    }
}