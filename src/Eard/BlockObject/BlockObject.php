<?php
namespace Eard\BlockObject;

use pocketmine\Player;
use pocketmine\item\Item;


interface BlockObject {


	/**
	*	ブロックが置かれた時
	*	trueが帰ると、キャンセルされる
	*	@return bool
	*/
	public function Place(Player $player);

	/**
	*	ブロックがタップされた時
	*	trueが帰ると、キャンセルされる
	*	@param Item そのブロックをタップしたアイテム
	*	@return bool
	*/
	public function Tap(Player $player);

	/**
	*	ブロック長押しされた時　キャンセルは不可
	*	@param Item そのブロックをタップしたアイテム
	*	@return bool
	*/
	public function StartBreak(Player $player);

	/**
	*	ブロック長押しされ続け、壊された時
	*	trueが帰ると、キャンセルされる
	*	@param $x, $y, $z | 座標
	*	@param Item そのブロックをタップしたアイテム
	*	@return bool
	*/
	public function Break(Player $player);

	/**
	*	破壊された後の最終処理
	*	@return void
	*/
	public function Delete();

}