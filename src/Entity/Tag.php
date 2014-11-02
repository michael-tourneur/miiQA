<?php

namespace Mii\Qa\Entity;

use Pagekit\System\Entity\DataTrait;
use Pagekit\User\Entity\AccessTrait;
use Pagekit\Framework\Database\Event\EntityEvent;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity(tableClass="@miiqa_tags")
 */
class Tag
{
    use DataTrait;

    /**
     * @ManyToMany(targetEntity="Question", keyTo="tags")
     **/
    private $questions;

    /** @Column(type="integer") @Id */
    protected $id;

    /** @Column */
    protected $label = '';

    /** @Column(type="integer") */
    protected $count = 0;

    public function __construct() {
        $this->questions = new ArrayCollection();
    }

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

    public function addQuestion(Question $question)
    {
        $this->questions[] = $question;
        $this->setCountPlus();
    }
}
