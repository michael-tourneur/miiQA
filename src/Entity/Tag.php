<?php

namespace Mii\Qa\Entity;

use Mii\Taxonomy\Entity\TaxonomyTrait;

use Pagekit\Framework\Database\Event\EntityEvent;

class Tag
{

    use TaxonomyTrait;

    /** @Column(type="integer") */
    protected $count = 0;

    public function getCount()
    {
        return $this->count;
    }

    public function setCountMinus($value = 1)
    {
        $this->count = $this->getCount() - $value;
    }

    public function setCountPlus($value = 1)
    {
        $this->count = $this->getCount() + $value;
    }

    public function setCount($value) {
        $this->count = $value;
    }

}
