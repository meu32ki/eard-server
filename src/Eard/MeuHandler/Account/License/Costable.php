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

    /**
    *   今有効になっているなら、コストを返す。無効になっていればコストとしては見ない。
    *   @return Int
    */
    public function getRealCost(){
        $isTimeValid = $this->isValidTime();
        return $isTimeValid ? $this->getCost() : 0;
    }

    // @License
    public function getFullName(){
        return $this->getName().$this->getRankText();
    }

    // @License
    public function getPrice(){
        return $this->getCost() * 1000;
    }

    // @License
    public function getUpdatePrice(){
        return $this->getCost() * 500;
    }

	protected static $rankcost = [];

}