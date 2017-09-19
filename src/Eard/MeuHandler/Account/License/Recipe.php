<?php
namespace Eard\MeuHandler\Account\License;

use pocketmine\network\protocol\CraftingDataPacket;
use pocketmine\Player;
use pocketmine\entity\FishingHook;
use pocketmine\item\Item;
use Eard\MeuHandler\Account;

/***
*
*	ライセンスによってクラフトできるやつを制限
*/
class Recipe {

	public static function changeFishes(){
		$list = [
			[Item::RAW_FISH, 60], 
			[Item::RAW_SALMON, 25],
			[Item::PUFFER_FISH, 13],
			[Item::CLOWN_FISH, 2],
			[Item::STICK, 2],
			[Item::BOWL, 4]
			[Item::GLASS_BOTTLE, 2]
		];
		$fishes = [];
		foreach($list as $key => $item){
			$id = $item[0];
			$count = $item[1];
			for($i = 0; $i < $count; $i++){
				$fishes[] = $id;
			}
		}
		FishingHook::setFishes($fishes);
	}

	//こいつでプレイヤーごとにクラフトできるやつ返して
	public static function getRecipe($player){
		$playerData = Account::get($player);
		$recipe = [ //全員クラフト・精錬できるレシピ
			5 => true,
			24 => true,
			50 => true,
			53 => true,
			58 => true,
			65 => true,
			268 => true,
			269 => true,
			270 => true,
			271 => true,
			272 => true,
			273 => true,
			274 => true,
			275 => true,
			280 => true,
			281 => true,
			282 => true,
			290 => true,
		];
		switch (true) {
			// 「精錬」「採掘士」は追加レシピなし
			case $playerData->hasValidLicense(License::FARMER): // 農家
				$recipe += [
					170 => true,
					322 => true,
					353 => true,
					354 => true,
					357 => true,
					393 => true,
					396 => true,
					400 => true,
					413 => true,
					459 => true,
				];
			break;
			case $playerData->hasValidLicense(License::DANGEROUS_ITEM_HANDLER): // 危険物取扱
				$recipe += [
					46 => true,
					259 => true,
				];
			break;
			case $playerData->hasValidLicense(License::APPAREL_DESIGNER): // 服飾1
				$recipe += [
298 => true,
299 => true,
300 => true,
301 => true,
306 => true,
307 => true,
308 => true,
309 => true,				
				];
			case $playerData->hasValidLicense(License::APPAREL_DESIGNER, 2): // 服飾2
				$recipe += [
310 => true,
311 => true,
312 => true,
313 => true,
314 => true,
315 => true,
316 => true,
317 => true,						
				];
			break;
			case $playerData->hasValidLicense(License::PROCESSOR): // 加工1
				$recipe += [
1 => true,
35 => true,
43 => true,
54 => true,
61 => true,
67 => true,
69 => true,
70 => true,
72 => true,
75 => true,
76 => true,
77 => true,
80 => true,
85 => true,
91 => true,
96 => true,
101 => true,
102 => true,
107 => true,
112 => true,
113 => true,
147 => true,
148 => true,
158 => true,
180 => true,
181 => true,
182 => true,
183 => true,
184 => true,
291 => true,
321 => true,
323 => true,
324 => true,
339 => true,
340 => true,
355 => true,
427 => true,
428 => true,				
				];
			case $playerData->hasValidLicense(License::PROCESSOR, 2): // 加工2
				$recipe += [
5 => true,
43 => true,
47 => true,
85 => true,
108 => true,
113 => true,
114 => true,
128 => true,
134 => true,
135 => true,
136 => true,
139 => true,
145 => true,
158 => true,
160 => true,
163 => true,
164 => true,
167 => true,
172 => true,
182 => true,
185 => true,
186 => true,
187 => true,
256 => true,
257 => true,
258 => true,
265 => true,
267 => true,
292 => true,
325 => true,
328 => true,
330 => true,
345 => true,
346 => true,
347 => true,
351 => true,
359 => true,
380 => true,
429 => true,
430 => true,
431 => true,					
				];
			case $playerData->hasValidLicense(License::PROCESSOR, 3): // 加工3
				$recipe += [
35 => true,
43 => true,
48 => true,
49 => true,
121 => true,
155 => true,
165 => true,
168 => true,
169 => true,
173 => true,
266 => true,
276 => true,
277 => true,
278 => true,
279 => true,
283 => true,
284 => true,
285 => true,
286 => true,
293 => true,
294 => true,					
				];
			break;
			case $playerData->hasValidLicense(License::HANDIWORKER): // 細工師1
				$recipe += [
24 => true,
98 => true,
109 => true,
179 => true,
390 => true,					
				];
			case $playerData->hasValidLicense(License::HANDIWORKER, 2): // 細工師2
				$recipe += [
159 => true,
171 => true,
236 => true,
237 => true,	
				];
			case $playerData->hasValidLicense(License::HANDIWORKER, 3): // 細工師3
				$recipe += [
22 => true,
41 => true,
42 => true,
57 => true,
89 => true,
133 => true,
152 => true,
155 => true,
156 => true,
208 => true,
355 => true,					
				];
			break;
		}
		return $recipe;
	}

	//いじらないで
	public static function packetFilter(CraftingDataPacket $pk, Player $player){
		$fil = self::getRecipe($player);
		$F = function ($recipe) use ($fil){
			if(isset($fil[$recipe->getResult()->getId()])){
				$check = $fil[$recipe->getResult()->getId()];
				if($check === [] || isset($check[$recipe->getResult()->getDamage()])){
					return true;
				}
			}
			return false;
		};
		$recipes = $pk->entries;
		$re = array_filter($recipes, $F);
		$pk->entries = $re;
		$pk->encode();
	}
}