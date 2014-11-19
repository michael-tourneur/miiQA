<?php

namespace Mii\Qa\Entity;

use Pagekit\System\Entity\DataTrait;
use Pagekit\Framework\Database\Event\EntityEvent;

/**
 * @Entity(tableClass="@miiqa_tags")
 */
class Tag
{
    use DataTrait;

    /** @Column(type="integer") @Id */
    protected $id;

    /** @Column */
    protected $label = '';

    /** @Column(type="integer") */
    protected $count = 0;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setLabel($label)
    {
        $this->label = $label;
    }

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

    /**
     * @PostDelete
     */
    public function postDelete(EntityEvent $event)
    {
        $connection = $event->getConnection();
        $connection->delete('@miiqa_question_tag', ['tag_id' => $this->getId()]);
    }

}
