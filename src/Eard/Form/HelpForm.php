<?php
namespace Eard\Form;


# Eard
use Eard\Enemys\AI;
use Eard\Utils\ItemName;
use Eard\MeuHandler\Account\License\License;


class HelpForm extends FormBase {


	public function send(int $id){
		$playerData = $this->playerData;
		$cache = [];
		switch($id){
			case 1:
				$data = [
					'type'    => "form",
					'title'   => "ヘルプ",
					'content' => "Eardの遊び方について解説しています\n",
					'buttons' => [
						['text' => "脱出する"],
						['text' => "ライセンス"]
					]
				];
				$cache = [2,3];
			break;
			case 2:
				$player = $playerData->getPlayer();
				$pos = $player->getPosition()->add(AI::getFrontVector($player))->floor();
				$pos->y = $player->getLevel()->getHighestBlockAt($pos->x, $pos->z);
				$player->teleport($pos->add(0.5, 1, 0.5));
			break;
			case 3:
				$data = [
					'type'    => "form",
					'title'   => "ヘルプ > ライセンス",
					'content' => "",
					'buttons' => [
						['text' => "ライセンスとは"],
						['text' => "一覧"],
						['text' => "クラフト/使用可能アイテム一覧"],
						//['text' => "クラフト詳細検索"],
						['text' => "戻る"],
					]
				];
				$cache = [4,5,6,1];
			break;
			case 4:
				$data = [
					'type'    => "custom_form",
					'title'   => "ヘルプ > ライセンス > ライセンスとは",
					'content' => [
						[
							'type' => "label",
							'text' => self::getLicenseOverviewText()
						]
					]
				];
				$cache = [3];
			break;
			case 5:
				$data = [
					'type'    => "custom_form",
					'title'   => "ヘルプ > ライセンス > 一覧",
					'content' => [
						[
							'type' => "label",
							'text' => self::getLicenseDetailText()
						]
					]
				];
				$cache = [3];
			break;
			case 6:
				$buttons = [];
				$ar = [1,31,41,51,61,62,71,72,81,82,83,91,92,93,101,102,103];
				foreach($ar as $realLicenseNo){
					if($realLicenseNo === 1){
						$buttons[] = ['text' => "通常"];
						$cache[] = 7;
					}else{
						$l = License::getByRealLicenseNo($realLicenseNo);
						$buttons[] = ['text' => $l->getFullName()];
						$cache[] = 7;
					}
				}
				$data = [
					'type'    => "form",
					'title'   => "ヘルプ > ライセンス > クラフト/使用可能アイテム一覧",
					'content' => "",
					'buttons' => $buttons
				];
			break;
			case 7:
				$ar = [1,31,41,51,61,62,71,72,81,82,83,91,92,93,101,102,103];
				$realLicenseNo = $ar[$this->lastData];
				$content = self::getLicenseDetailText($realLicenseNo)."\n\n §7".ItemName::getAllItemNameCanBeCreatedBy($realLicenseNo);
				$lname = $realLicenseNo === 1 ? "通常" : License::getByRealLicenseNo($realLicenseNo)->getFullName();
				$data = [
					'type'    => "custom_form",
					'title'   => "ヘルプ > クラフト/使用可能アイテム一覧 > {$lname}",
					'content' => [
						[
							'type' => "label",
							'text' => $content
						]
					]
				];
				$cache = [3];
			break;
		}

		// みせる
		if($cache){
			// sendErrorMoralのときとかは動かないように
			$this->lastSendData = $data;
			$this->cache = $cache;
			$this->show($id, $data);
		}
	}

	public static function getLicenseOverviewText(){
		$out = 
		"§fEard では、お金(μ)を支払ってライセンスを買うことで、クラフトできるアイテムが増えていきます。\n".
		"\n".
		"§l§f【概要】§r\n".
		"§fライセンス： 様々な種類があり、クラフトできるアイテムや使用できるブロック、扱えるアイテムが変わります。§7詳細は[ヘルプ>ライセンス>一覧]へ。\n".
		"§fコスト： ライセンスにはコストがあります。このコストが5以下になるように、自分で自由に選んで有効にしておくことができます。§7詳細は[ヘルプ>ライセンス>一覧]へ。\n".
		"§f有効: ライセンスが有効期限内にあり、使える状態になっていることを言います。\n".
		"§f無効: ライセンスの効力を失い、持っていないのと同じ状態なことをいいます。\n".
		"§f有効期限: ライセンスには有効期限があります。期限を過ぎると、自動的に無効化されます。期限を延ばすこともできます\n".
		/*"\n".
		"\n".
		"§fライセンスには、扱えるアイテムやクラフトできるアイテムが割り当てられています。§8すなわち、あるアイテムを使うorクラフトするためには、特定のライセンスが必要なわけです。\n".
			"例1) 「鉄インゴット」x3 + 「棒」 x2 => 「鉄ピッケル」 のクラフトには、'加工2ライセンス'が必要です。\n".
			"例2) 「鉄鉱石」=>「鉄インゴット」 には「かまど」を使用しますが、「かまど」を使用するには、'精錬ライセンス'が必要です。\n".
		"すなわち、\n".*/
		"";
		return $out;
	}

	public static function getLicenseDetailAllText(){
		$ar = [1,31,41,51,61,62,71,72,81,82,83,91,92,93,101,102,103];
		$out = "";
		foreach($ar as $realLicenseNo){
			$out .= self::getLicenseDetailText($realLicenseNo)."\n\n";
		}
		return $out;
	}

	public static function getLicenseDetailText($realLicenseNo){
		$ar = [
			1 => "通常§7 (コスト0)\n §f初めから持っている。PCレシピでみたとき、「2x2におさまるやつ」、木ツール。",
			31 => "精錬§7 (コスト3)\n §f焼き物をすることができる(かまどを使える)",
			41 => "農家§7 (コスト3)\n §f土地を耕し、作物を植えることができる、サトウキビやサボテンの育成、お菓子系統のクラフトはこれが必要",
			51 => "危険物取扱§7 (コスト1)\n §fTNTを爆発させたり、溶岩を置いたり、火打石で火をつけるのに必要",
			61 => "採掘士1§7 (コスト2)\n §f黒曜石、金鉱石、ダイヤ鉱石、エメラルド鉱石の破壊に必要。このライセンスがないと、これら4種の破壊ができない",
			62 => "採掘士2§7 (コスト3)\n §f採掘速度upのエフェクトが常に得られる。",
			71 => "服飾1§7 (コスト2)\n §f革装備、鉄装備",
			72 => "服飾2§7 (コスト3)\n §f金装備、ダイヤ装備",
			81 => "加工1§7 (コスト1)\n §f石系統(模様は除く)、石ツール、木材系統、砂岩系統、レンガ系統",
			82 => "加工2§7 (コスト3)\n §f一部特殊木材系統、鉄系統、鉄ツール",
			83 => "加工3§7 (コスト5)\n §f金ツール、ダイヤツール",
			91 => "ハンター1§7 (コスト2)\n §f",
			92 => "ハンター2§7 (コスト4)\n §f",
			93 => "ハンター3§7 (コスト6)\n §f",
			101 => "細工師1§7 (コスト1)\n §f模様入りの石(一部除く)",
			102 => "細工師2§7 (コスト3)\n §f染料を使った系統(羊毛、コンクリなど)",
			103 => "細工師3§7 (コスト5)\n §f鉱石ブロック(ダイヤモンドブロック、金ブロックetc)、クォーツの模様、建築系",
		];
		return isset($ar[$realLicenseNo]) ? $ar[$realLicenseNo] : "";
	}
}