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

	protected $ranktxt = [
		1 => "土木技術者",
		2 => "見習い",
		3 => "一人前",
		4 => "高官",
		5 => "長官"
	];
	protected $name = "政府関係者";

}