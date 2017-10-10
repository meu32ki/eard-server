<?php
namespace Eard\Form;


# Eard
use Eard\DBCommunication\Connection;
use Eard\MeuHandler\Account;
use Eard\MeuHandler\Bank;
use Eard\MeuHandler\Account\License\License;
use Eard\Utils\Time;


class BankForm extends FormBase {

	public function send(int $id){
		$playerData = $this->playerData;
		$cache = [];
		$bank = Bank::getInstance();
		$account = $bank->getBankAccount($playerData);

		if(!$id){
			$this->close();
		}

		if(!$account){
			$data = [
				'type'    => "modal",
				'title'   => "銀行 > お預入れ",
				'content' => "§f銀行口座がまだ開設されていないようです。\n".
							"新たに口座を開設しますか？\n".
							"\n".
							"【条件】\n".
							"§fデポジット: §7500μ 以上\n".
							"§f生活ライセンス: §7一般 以上\n".
							"§f滞在時間:§7 2時間 以上\n".
							"\n",
				'button1' => "はい",
				'button2' => "いいえ",
			];
			$cache = [3, 0];
			break;
		}

		switch($id){
			case 1:
				// メニュー一覧
				$buttons = [
					['text' => "お預入れ"],
					['text' => "お引き出し"],
					['text' => "お借入れ"],
					['text' => "ご返済"],
					['text' => "通帳"]
				];
				$cache = [2, 6, 8, 12, 17];

				$data = [
					'type'    => "form",
					'title'   => "銀行",
					'content' => "§fご希望のお取引を選択してください。\n",
					'buttons' => $buttons
				];
			break;
			case 2:
				$data = [
					'type'    => "custom_form",
					'title'   => "銀行 > お預入れ",
					'content' => [
						[
							'type' => "input",
							'text' =>
									"\n".
									"§f預金額を入力してください。\n".
									"\n",
							'placeholder' => "預金額 (μ)"
						]
					]
				];
			$cache = [5];
			break;
			case 3:
				$data = [
					'type'    => "custom_form",
					'title'   => "銀行 > 新規口座開設 > 預金",
					'content' => [
						[
							'type' => "input",
							'text' =>
									"\n".
									"§fデポジット (新規預金) をいれてください。\n".
									"※500μ以上である必要があります。".
									"\n",
							'placeholder' => "デポジット (半角数字)"
						]
					]
				];
				$cache = [4];
			break;
			case 4:
			$amount = $this->lastData[0];

			if(!preg_match("/^[0-9]+$/", $amount) || empty($amount)){
				$this->sendErrorModal(
					"銀行 > 新規口座開設",
					"フォームには半角数字以外入力できません。", 1
				);
				break;
			}

			$create = Bank::getInstance()->createBankAccount($playerData, $amount);
			if($create){
				$data = [
					'type'    => "form",
					'title'   => "銀行 > 新規口座開設",
					'content' =>
						"\n§f{$playerData->getName()}さんの口座が開設されました。\n".
						"現在の残金は{$amount}μです。",
					'buttons' => [
						['text' => '戻る']
					]
				];
				$cache = [1];
			}else{
				$this->sendErrorModal(
					"銀行 > 新規口座開設",
					"§f口座開設に失敗しました。\n".
					"以下の条件を満たしていない可能性があります。\n".
					"§fデポジット: §7500μ 以上\n".
					"§f生活ライセンス: §7一般 以上\n".
					"§f滞在時間:§7 2時間 以上\n", 1
				);
			}
			break;
			case 5:
				$amount = $this->lastData[0];

				if(!preg_match("/^[0-9]+$/", $amount) || empty($amount)){
					$this->sendErrorModal(
						"銀行 > お預入れ",
						"フォームには半角数字以外入力できません。", 1
					);
					break;
				}

				$de = bank::getInstance()->deposit($playerData, $amount);
				if($de){
					$data = [
						'type'    => "form",
						'title'   => "銀行 > お預入れ",
						'content' =>
							"\n§f{$amount}μが預金されました。\n",
						'buttons' => [
							['text' => '戻る']
						]
					];
					$cache = [1];
				}else{
					$this->sendErrorModal(
						"銀行 > お預入れ",
						"	預金に失敗しました。", 1
					);
				}
			break;
			case 6:
				$data = [
					'type'    => "custom_form",
					'title'   => "銀行 > お引き出し",
					'content' => [
						[
							'type' => "input",
							'text' =>
									"\n".
									"§f引き出す額を入力してください。\n".
									"\n",
							'placeholder' => "引き出す額 (μ)"
						]
					]
				];
			$cache = [7];
			break;
			case 7:
			$amount = $this->lastData[0];

			if(!preg_match("/^[0-9]+$/", $amount) || empty($amount)){
				$this->sendErrorModal(
					"銀行 > お引き出し",
					"フォームには半角数字以外入力できません。", 1
				);
				break;
			}

			$de = $bank->withdraw($playerData, $amount);
			if($de){
				$data = [
					'type'    => "form",
					'title'   => "銀行 > お引き出し",
					'content' =>
						"\n§f{$amount}μが引き出されました。\n",
					'buttons' => [
						['text' => '戻る']
					]
				];
				$cache = [1];
			}else{
				$this->sendErrorModal(
					"銀行 > お引き出し",
					"引き出しに失敗しました。", 1
				);
			}
			break;
			case 8:
			$canLend = $bank->exsitBankDebit($playerData);
			if(!$canLend){
				$data = [
					'type'    => "custom_form",
					'title'   => "銀行 > お借入れ",
					'content' => [
						[
							'type' => "input",
							'text' =>
									"\n".
									"§fご希望の金額を入力してください。\n".
									"\n",
									'placeholder' => "お借入れ額 (μ)"
								]
							]
					];
					$cache = [9];
				}else{
					$this->sendErrorModal(
						"銀行 > お借入れ",
						"未返済の借り入れがあります。", 1
					);
				}

			break;
			case 9:
			$amount = $this->lastData[0];

			if(!preg_match("/^[0-9]+$/", $amount)){
				$this->sendErrorModal(
					"銀行 > お借入れ",
					"フォームには半角数字以外入力できません。", 1
				);
				break;
			}

			$this->lists = $bank->getList($amount);
			$buttons = [];
			$month = ['1週間', '1か月', '2か月'];
			foreach ($this->lists as $key => $text) {
				$buttons[] = ['text' => "【{$month[$key]}】 金利 §l{$text[0]}§r ％ 返済金額 §l{$text[1]}§rμ"];
			}
			$this->lists[3] = $amount;
			$cache = [10];

			$data = [
				'type'    => "form",
				'title'   => "銀行 > お借入れ",
				'content' => "§fご希望の返済方法を選択してください。\n",
				'buttons' => $buttons
			];
			break;
			case 10:
				if($this->lastFormId === 9 ){
					$key = $this->lastData;
					$list = $this->lists;

					if(!$bank->checkLimit($list[3])){
						$this->sendErrorModal(
							"銀行 > お借入れ",
							"現在、銀行の貸し出し能力が限界に達しています。\n".
							"申し訳ありませんが、金額を減らすか、もうしばらくしてから、再度お試しください。", 1
						);
						break;
					}

					$date = 0;
					switch ($key) {
						case 1: $date = strtotime( "+1 month" ); break;
						case 2: $date = strtotime( "+2 month" ); break;
						default: $date = strtotime( "+1 week" ); break;
					}
					$date = date("Y年m月j日", $date);
					$data = [
						'type'    => "modal",
						'title'   => "銀行 > お借入れ > 確認",
						'content' =>
									"§e以下の内容をご確認ください。\n".
									"§f[お借入れ]\n".
									"§fお借入れ額: §7{$list[3]}μ\n".
									"§f金利: §7{$list[$key][0]} ％\n".
									"§f返済金額: §7{$list[$key][1]}μ\n".
									"§f返済期限: §7{$date} \n".
									"§f以上の内容で借り入れを行います。よろしいですか？\n",
						'button1' => "はい",
						'button2' => "いいえ",
					];
					$this->lists[4] = $key;
					$cache = [11, 1];
				}else{
					$this->sendInternalErrorModal("FormIDは9からであるべき", 1);
				}
			break;
			case 11:
			$list = $this->lists;
			if($bank->lend($playerData, $list[3], $list[4])){
				$data = [
					'type'    => "form",
					'title'   => "銀行 > お借入れ",
					'content' =>
						"\n§f{$list[3]}μが銀行口座に記帳されました。ご確認ください。\n",
						'buttons' => [
							['text' => '戻る']
						]
					];
					$cache = [1];
			}else{
				$this->sendErrorModal(
					"銀行 > お借入れ",
					"借り入れに失敗しました\n".
					"申し訳ありませんが、もうしばらくしてから、再度お試しください。", 1
				);
			}
			$this->lists = [];
			break;
			case 12:
				$balance = $bank->exsitBankDebit($playerData);
				if($balance){
					$buttons = [
						['text' => "分割(一部)支払"]
					];
					$cache = [15];

					if($balance[3] == 5){
						$buttons[] = ['text' => "一括払い"];
						$cache[] = 13;
					}

					$this->lists = $balance;
					$data = [
						'type'    => "form",
						'title'   => "銀行 > ご返済",
						'content' => "§fご希望の返済方法を選択してください。。\n",
						'buttons' => $buttons
					];
				}else{
					$this->sendErrorModal(
						"銀行 > ご返済",
						"あなたの借り入れが存在しませんでした。\n".
						"", 1
					);
				}
			break;
			case 13:
				$debit = $this->lists;
				$total = $debit[0] * (1 + $debit[2]);
				$rate = $debit[2] * 100;
				$date = date("Y年m月j日", $debit[1]);
				$data = [
					'type'    => "modal",
					'title'   => "銀行 > 返済 > 確認",
					'content' =>
								"§e以下の内容をご確認ください。\n".
								"§f[返済]\n".
								"§fお借入れ額: §7{$debit[0]}μ\n".
								"§f金利: §7{$rate} ％\n".
								"§f返済金額: §7{$total}μ\n".
								"§f返済期限: §7{$date} \n".
								"§f以上の内容で返済作業を行います。よろしいですか？\n",
					'button1' => "はい",
					'button2' => "いいえ",
				];
				$cache = [14, 1];
			break;
			case 14:
				$debit = $this->lists;
				$total = $debit[0] * (1 + $debit[2]);
				$repay = $bank->repay($playerData, $total, 0, $debit);
				if($repay){
					$data = [
						'type'    => "form",
						'title'   => "銀行 > ご返済",
						'content' =>
							"\n§f全額返済が完了しました。\n",
							'buttons' => [
								['text' => '戻る']
							]
						];
						$cache = [1];
				}else{
					$this->sendErrorModal(
						"銀行 > ご返済",
						"返済に失敗しました。\n", 1
					);
				}

			break;
			case 15:
			$debit = $this->lists;
			$total = ($debit[3] == 5) ? $debit[0] * (1 + $debit[2]) : $debit[4];
			$remainder =  $total % $debit[3];
			$per_time = ($total - $remainder) / $debit[3];
			$first_time = ($remainder) ? $per_time + $remainder : 0;

			if($first_time){
				$buttons[] = ['text' => "初回 {$first_time}μ"];
				$repay_amount = $first_time;
			}else{
				$buttons[] = ['text' => "{$per_time}μ 残り{$debit[3]}回"];
				$repay_amount = $per_time;
			}

			$cache = [16];
			$this->lists = [$repay_amount, $debit[3], $debit];

			$data = [
				'type'    => "form",
				'title'   => "銀行 > ご返済 > 分割払い",
				'content' => "§fここでは分割払い(5回払い)ができます\n",
				'buttons' => $buttons
			];
			break;
			case 16:
				$list = $this->lists;
				$repay = $bank->repay($playerData, $list[0], 1, $list[2]);
				if($repay){
					$data = [
						'type'    => "form",
						'title'   => "銀行 > ご返済",
						'content' =>
							"\n§f{$list[0]}μの返済が完了しました。\n",
							'buttons' => [
								['text' => '戻る']
							]
						];
						$cache = [1];
				}else{
					$this->sendErrorModal(
						"銀行 > ご返済",
						"分割払いに失敗しました。\n", 1
					);
				}
			break;
			case 17:
			$bankbook = $bank->getBankBook($playerData);
			$data = [
				'type'    => "form",
				'title'   => "銀行 > 通帳",
				'content' => $bankbook,
					'buttons' => [
						['text' => '戻る']
					]
				];
				$cache = [1];
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

	public $data = null;
	private $lists = [];
}
