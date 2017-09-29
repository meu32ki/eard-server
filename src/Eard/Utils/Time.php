<?php
namespace Eard\Utils;


class Time {

	public static function calculateTime($sec){
		$s_sec = $sec % 60;
		$s_min = floor($sec / 60);
		if(60 <= $s_min){
			$s_hour = floor($s_min / 60);
			if(24 <= $s_hour){
				$s_day = floor($s_hour / 24);
				$s_hour = $s_min % 24;
				$out = "{$s_day}日{$s_hour}時間";
			}else{
				$s_min = $s_min % 60;
				$out = "{$s_hour}時間{$s_min}分";
			}
		}else{
			if($s_min < 1){
				$out = "{$s_sec}秒";
			}elseif($s_min < 60){
				$out = "{$s_min}分{$s_sec}秒";
			}
		}
		return $out;
	}
}