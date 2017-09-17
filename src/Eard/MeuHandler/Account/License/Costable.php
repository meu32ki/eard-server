<?php
namespace Eard\MeuHandler\Account\License;


class Costable extends License {

    /*
     * すべての、コストのかかるライセンスはこれをextendsするべき。
     */

    /**
     *	ライセンスのコストがあれば、コストを返す
     *	@return Int
     */
	public function getCost(){
	    $rank = $this->getRank();
		return isset(self::$rankcost[$rank]) ? self::$rankcost[$rank] : 0;
	}

	protected static $rankcost = [];

}