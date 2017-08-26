<?php
namespace Eard\MeuHandler\Account\License;

use pocketmine\network\protocol\CraftingDataPacket;
use pocketmine\Player;

use Eard\MeuHandler\Account;

/***
*
*	ライセンスによってクラフトできるやつを制限
*/
class Recipe {

	//こいつでプレイヤーごとにクラフトできるやつ返して
	public static function getRecipe($player){
		$playerData = Account::get($player);
		$recipe = [ //全員クラフト・精錬できるレシピ
			5 => true,//木材
			50 => true,//松明
			280 => true,//棒
			268 => true,//木剣
			269 => true,//木のシャベル
			270 => true,//木のツルハシ
			271 => true,//木の斧
			290 => true,//木の鍬
			272 => true,//石の剣
			273 => true,//石のシャベル
			274 => true,//石のツルハシ
			275 => true,//石の斧
			279 => true,//石のくわ
			265 => true,//鉄インゴット(精錬も含む)
			266 => true,//金インゴット(精錬も含む)
		];
		switch (true) {
			case $playerData->hasValidLicense(License::NEET):
				array_merge($recipe, [
					
				]);
			break;
			case $playerData->hasValidLicense(License::RESIDENCE):
				array_merge($recipe, [
					
				]);
			break;
			case $playerData->hasValidLicense(License::GOVERNMENT_WORKER):
				array_merge($recipe, [
					
				]);
			break;
			case $playerData->hasValidLicense(License::BUILDER):
				array_merge($recipe, [
					
				]);
			break;
			case $playerData->hasValidLicense(License::MINER):
				array_merge($recipe, [
					257 => true,//鉄のツルハシ
					278 => true,//ダイヤのツルハシ
					285 => true,//金のツルハシ
				]);
			break;
			case $playerData->hasValidLicense(License::TRADER):
				array_merge($recipe, [
					
				]);
			break;
			case $playerData->hasValidLicense(License::SERVICER):
				array_merge($recipe, [
					
				]);
			break;
			case $playerData->hasValidLicense(License::ENTREPRENEUR):
				array_merge($recipe, [
					
				]);
			break;
			case $playerData->hasValidLicense(License::FARAMER):
				array_merge($recipe, [
					
				]);
			break;
			case $playerData->hasValidLicense(License::DANGEROUS_ITEM_HANDLER):
				array_merge($recipe, [
					
				]);
			break;
		}
		return $recipe;
	}

	//いじらないで
	public static function packetFilter(CraftingDataPacket $pk, Player $player){
		$fil = self::getRecipe($player);
		$F = function ($recipe) use ($fil){
			return (isset($fil[$recipe->getResult()->getId()]));
		};
		$recipes = $pk->entries;
		$re = array_filter($recipes, $F);
		$pk->entries = $re;
		$pk->encode();
	}
}