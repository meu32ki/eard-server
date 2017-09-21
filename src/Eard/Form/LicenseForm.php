<?php
namespace Eard\Form;


# Eard
use Eard\MeuHandler\Account;


class LicenseForm extends ModalForm {
	
	public function Receive($id, $data){
		switch($id){
			case 1:
			print_r($data);
			break;
		}
	}

	public function Send($id){
		switch($id){
			case 1:
				$playerData = $this->$playerData;
				$buttons = [];
				foreach(Lisence::getAll() as $l){
					if($l instanceof Costable){

						$lNo = $l->getLicenseNo();
						if($license = $playerData->getLicense($lNo) ){
							$status = "§d".$license->getValidTimeText();
							$url = $license->getImgPath();
						}else{
							$status = "§f未所持";
							$url = $l->getImgPath();
						}

						$buttons[] = [
							'text' => $l->getFullName()." ". $status,
							'image' => [
								'type' => 'url',
								'data' => $url
							]
						]
					}
				}
				$data = [
					'type'    => 'form',
					'title'   => 'ライセンスメニュー',
					'content' => '',
					'buttons' =>
					[
						[
							'text' => 'button #1',
							'image' => 
							[
								'type' => 'url',
								'data' => 'https://github.com/NLOGPlugins/Form_Json/blob/master/form/form.jpg?raw=true'
							]
						],
						[
							'text' => 'button #2',
							'image' => 
							[
								'type' => 'url',
								'data' => 'https://github.com/NLOGPlugins/Form_Json/blob/master/form/form.jpg?raw=true'
							]
						]
					]
				];
			break;
		}
		
		$this->Show($playerData, $id, $data);
	}

}