<?php
namespace Eard\Utils;

use pocketmine\item\Item;
use pocketmine\item\enchantment\Enchantment;

/**
 * use pocketmine\item\Item; => use Eard\Utiles\EardItem as Item;
 * 上記のように書き換えるだけで既存のコードをいじる事無く対応できる
 */
class EardItem extends Item{

	public static $eardItemsData = [
		/**
		"{$id}:{$data}" => [
			"name" => "おなまえ",
			"hoil" => true,　//プロパティがあるとエンチャントのエフェクトが付く
		]
		 */
		"265:1" => [
			"name" => "エーテル鋼",
			"hoil" => true,
		]
	];

	public static function get(int $id, int $meta = 0, int $count = 1, $tags = "") : Item{
		$item = Item::get($id, $meta, $count, $tags);
		return self::setEardItemData($item);
	}

	public static function setEardItemData(Item $item) : Item{
		$id = $item->getId();
		$damage = $item->getDamage();
		if(isset(self::$eardItemsData["{$id}:{$damage}"])){
			$data = self::$eardItemsData["{$id}:{$damage}"];
			if(isset($data["name"])) $item->setCustomName($data["name"]);
			if(isset($data["hoil"])) $item->addEnchantment(Enchantment::getEnchantment(Enchantment::TYPE_INVALID));
		}
		return $item;
	}
}