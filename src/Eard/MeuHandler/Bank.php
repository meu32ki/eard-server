<?php
namespace Eard\MeuHandler;


class Bank {

	/*
		DB
		普通預金 type0 (負債)
		長期借入(ローン) type1 (資産)

			column
			uno = $playerData->getUniqueNo() 口座番号でもある(?)
			type = 0 or 1
			balance = その量

		銀行仕様
		まず、普通預金口座を作る必要がある
		それから長期借入ができるようになる
		会社も借りられるようにする予定なのでuniquenoをつかうことに。いまんとこmeuHandlerでなく、Accountのみの対応。

		普通預金口座を作るには
		「生活ライセンス　一般以上」「500μ以上のデポジット」がひつよう

		長期借入は1アカウントにつき1回しかできない
		プレイヤーは、新たに借りたい場合は、以前借りていたものを返してからにしないといけない

		20170928
	*/


	// @meuHandler
	public function getMeu(){
		return self::$bankMeu;
	}

	// @meuHandler
	public function getName(){
		return "銀行";
	}

	// @meuHandler
	public function getUniqueNo(){
		return 100001;
	}

	public static function getInstance(){
		if(!isset(self::$instance)){
			self::$instance = new Bank;
		}
		return self::$instance;
	}

	/**
	*	いま、そのプレイヤーは、お金を借りられる状況にあるのか
	*	オフラインでも使用可能
	*/
	public function canLend(Account $playerData, int $amount): bool{
		$player = $playerData->getPlayer();

		// 普通預金口座が存在しているか
		if($this->existSavingAccount($playerData)){
			if($player){// オンラインだったら
				$msg = Chat::Format("銀行", "あなたは普通預金口座を持っていないため、貸し付けが不可能です");
				$player->sendMessage($msg);
			}
			return false;
		}
	}

	/*
	*	実際にお金を貸し付ける処理 
	*/
	public function lend(Account $playerData, int $amount): bool{
		$player = $playerData->getPlayer();
		if($this->canLend($playerData, $amount)){

		}else{
			if($player){// オンラインだったら
				$msg = Chat::Format("銀行", "あなたにはお金(μ)を貸すことができません。");
				$player->sendMessage($msg);
			}
		}
	}

	/**
	*	普通預金口座を作る
	*	オフラインでは使用不可
	*	@param MeuHandler 
	*	@param int 初期デポジットの量
	*/
	public function createSavingAccount(Account $playerData, int $amount): bool{
		if(!$playerData->isOnline()){
			return false;
		}
	}

	/**
	*	普通預金口座が存在するかどうか
	*	オフラインでも使用可能
	*/
	public function getSavingAccount(Account $playerData){

	}

	/**
	*	普通預金口座から引き出し
	*	オフラインでも使用可能
	*	@param MeuHandler
	*	@param int 引き出す量
	*/
	public function withdraw(Account $playerData, int $amount){

	}

	/**
	*	普通預金口座に預入
	*	オフラインでも使用可能
	*	@param MeuHandler
	*	@param int 引き出す量
	*/
	public function deposit(Account $playerData, int $amount){

	}

	private static $bankMeu = null;
}