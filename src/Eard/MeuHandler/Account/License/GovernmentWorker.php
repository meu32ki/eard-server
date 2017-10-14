<?php
namespace Eard\MeuHandler\Account\License;


class GovernmentWorker extends License {

	/*
		1 => 政府の土地の編集ができる
		2 => 政府として土地を買収できる
	*/

	public function getLicenseNo(){
		return self::GOVERNMENT_WORKER;
	}

	public $ranktxt = [
		1 => "土木技術者",
		2 => "研修者",
		3 => "係員",
		4 => "次官",
		5 => "高官",
		6 => "長官",
	];
	public $name = "政府関係者";

}