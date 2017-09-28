<?php
namespace Eard\Form;


# Basic
use pocketmine\Player;

# Eard
use Eard\MeuHandler\Account;


class Form {

	/*
		form送受信のための用意
		
		# 開始時
		new Form($playerData);
		Formの先でPlayerDataに、そのオブジェクトを登録
		1ページ目が送信される

		# 受け取り時
		Event->PlayerData(プレイヤー単位)->getFormObject();
		Receive()にフォームの情報が送られてくるので、情報によってsend()しろ

		# 最終時
		{Form}->close();
		closeで、そのオブジェクトに関連付けられたPlayerDataを削除

	*/

	public function __construct(Account $playerData){
		$this->playerData = $playerData;
		$this->playerData->setFormObject($this);
		$this->Send(1);
	}

	/**
	*	パケットを送りつける
	*	@param Account 送る対象のplayerData
	*	@param Int [$id] 正直なんでもいい
	*	@param Array [$data] フォーマットに沿ったかきかたをしたarray
	*/
	public function Show(Account $playerData, $id, $data){

		/*
		$player = $playerData->getPlayer();
		$pk = new ShowModalFormPacket();
		$pk->formId = $id;
		$pk->data = json_encode( $data, JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING | JSON_UNESCAPED_UNICODE );
		$player->dataPacket($pk);
		return true;
		*/
		echo json_encode( $data, JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING | JSON_UNESCAPED_UNICODE );
		echo " Formid: {$id}\n";
	}

	/**
	*	フォーム入力モードを終わる、そしてオブジェクトを破棄する
	*/
	public function close(){
		$this->playerData->removeFormObject();
		$this->playerData = [];
	}

	/**
	*	プレイヤーに送るFormの用意をする
	*	1000番は予約済みなので使うな
	*	@param Int 送りたいページのFormId (FormIdはsend内に記載)
	*/
	public function send(Int $id){
		// オーバーライド前提
		$this->sendModal("未設定", "ページが未設定です", "OK", 1, "了解", 1);
	}

	/**
	*	プレイヤーにエラーメッセージを送りつける send() の簡易版
	*	@param String [$title]	上に出る文字
	*	@param String [$content]	そのエラー内容
	*	@param Int [$b1jump]		プレイヤーがエラー内容を了承し、ボタンのどちらかをきちんと押したときの挙動
	*/
	public function sendErrorModal($title, $content, $b1jump){
		$this->sendModal($title, $content, "OK", $b1jump);
	}

	/**
	*	プレイヤー側の落ち度でない場合のエラーにはこっちを使え
	*	@param String [$content]	そのエラー内容
	*	@param Int [$b1jump]		プレイヤーがエラー内容を了承し、ボタンのどちらかをきちんと押したときの挙動
	*/
	public function sendInternalErrorModal($content, $b1jump){
		$this->sendModal("エラー", $content, "OK", $b1jump);
	}

	/**
	*	プレイヤーにエラーメッセージを送りつける send() の簡易版
	*	@param String [$title]		そのmodalFormのタイトル
	*	@param String [$content]	そのエラー内容
	*	@param String [$b1rabel]	ボタン1のテキスト
	*	@param Int [$b1jump]		ボタン1が押された時、次にsend()するid
	*	@param String [$b1rabel]	ボタン2のテキスト (省略した場合には ボタン1と同じテキストになる)
	*	@param Int [$b1jump]		ボタン2が押された時、次にsend()するid  (省略した場合には ボタン1と同じidのものを送信する)
	*/
	public function sendModal($title, $content, $b1rabel, $b1jump, $b2rabel = "", $b2jump = 0){
		$data = [
			'type'    => "modal",
			'title'   => $title,
			'content' => $content,
			'button1' => $b1rabel,
			'button2' => $b2rabel ? $b2rabel : $b1rabel,
		];
		$this->cache = [$b1jump, $b2jump ? $b2jump : $b1jump];

		$this->lastSendData = $data;
		$this->Show($this->playerData, 1000, $data);
	}

	/**
	*	次のsend()のために、送られてきたデータを格納する。
	*	@param Int [$id]		受け取るformId (packetからダイレクトに来る)
	*	@param String [$data] 	いろいろはいってる
	*	@return bool
	*/
	public function Receive($id, $data){
		# echo "Receive {$id} {$data}\n";
		if($data === null){	// [x]ボタンを押して閉じたとき
			$this->close();
			return false;
		}

		// ほけん
		/*
		if($this->lastFormId && $this->lastFormId != $id){
			echo "idがちがうため処理を取りやめ";
			return false;
		}
		*/

		if($id !== 1000){
			$this->lastFormId = (int) $id;
		}

		# echo "lastSendData ";
		# print_r($this->lastSendData);
		# echo "cache ";
		# print_r($this->cache);

		switch($this->lastSendData['type']){
			case 'form':
				$buttonNo = $data;
				$formid = isset($this->cache[$buttonNo]) ? $this->cache[$buttonNo] : $this->cache[0];
				$this->lastMode = self::TYPE_FORM;
				$this->lastData = $data;
				$this->Send($formid);
				# echo "form send {$formid}\n";
			break;
			case 'modal':
				$this->lastMode = self::TYPE_MODAL;
				switch($data){
					case "true\n":
					case 0:
						$this->lastData = 0;
						$this->Send($this->cache[0]);
						# echo "true send "; 
						# echo $this->cache[0];
						# echo "\n";
					break;
					case "false\n":
					case 1:
						$this->lastData = 1;
						$formid = isset($this->cache[1]) ? $this->cache[1] : $this->cache[0];
						$this->Send($formid);
						# echo "false send {$formid}\n";
					break;
				}
			break;
			case 'custom_form':
				$this->lastMode = self::TYPE_MODAL;
				$this->lastData = json_decode($data, true);
				$this->Send($this->cache[0]);
			break;
		}
		return true;
	}

	protected $cache = [];
	protected $lastSendData = [];

	protected $lastFormId = 0;
	protected $lastData = "";
	protected $lastMode = 0;

	const TYPE_FORM = 1;
	const TYPE_MODAL = 2;
	const TYPE_CUSTOM_FORM = 3;

}