<?php
namespace Eard\MeuHandler\Account\License;


class Residence extends License {

	/*
		1 => 低レベル
		2 => ふつう。
		3 => 家があって稼ぎもそこそこ？
	*/

	public function getLicenseNo(){
		return self::RESIDENCE;
	}

	protected static $ranktxt = [
		1 => "浮浪者",
		2 => "一般",
		3 => "中流",
		4 => "上流",
		5 => "富裕"
	];
	protected static $name = "生活レベル";

}