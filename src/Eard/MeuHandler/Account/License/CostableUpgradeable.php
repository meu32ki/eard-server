<?php
namespace Eard\MeuHandler\Account\License;


class CostableUpgradeable extends Costable {

    // @License
    public function canUpgrade(){
        return $this->isRankExist($this->rank + 1);
    }

    // @License
    public function upgrade(){
        if($this->canUpgrade()){
            $this->rank += 1;
            return true;
        }
        return false;
    }

    // @License
    public function canDowngrade(){
        return $this->isRankExist($this->rank - 1);
    }

    // @License
    public function downgrade(){
        if($this->canDowngrade()){
            $this->rank -= 1;
            return true;
        }
        return false;
    }

}