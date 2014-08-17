<?php

namespace Mii\Qa\Entity;

use Pagekit\System\Entity\DataTrait;
use Pagekit\User\Entity\AccessTrait;
use Pagekit\Framework\Database\Event\EntityEvent;

/**
 * @Entity(tableClass="@miiqa_answers")
 */
class Answer
{
    use DataTrait;

    /* answer blocked status. */
    const STATUS_BLOCKED = 0;

    /* answer validated status. */
    const STATUS_APPROVED = 1;

    /* answer pending status. */
    const STATUS_PENDING = 2;

    /** @Column(type="integer") @Id */
    protected $id;

    /** @Column(type="integer") */
    protected $question_id;

    /**
     * @BelongsTo(targetEntity="Mii\Qa\Entity\Question", keyFrom="question_id")
     */
    protected $question;

    /** @Column(type="integer") */
    protected $user_id;

    /**
     * @BelongsTo(targetEntity="Pagekit\User\Entity\User", keyFrom="user_id")
     */
    protected $user;

    /** @Column(type="integer") */
    protected $status = self::STATUS_APPROVED;

    /** @Column */
    protected $content = '';

    /** @Column(type="integer") */
    protected $vote_plus = 0;

    /** @Column(type="integer") */
    protected $vote = 0;

    /** @Column(type="integer") */
    protected $vote_minus = 0;

    /** @Column(type="boolean") */
    protected $vote_best = false;

    /** @Column(type="datetime") */
    protected $date;

    /** @Column(type="datetime") */
    protected $modified;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getUserId()
    {
        return $this->user_id;
    }

    public function setUserId($userId)
    {
        $this->user_id = $userId;
    }

    public function getQuestionId()
    {
        return $this->question_id;
    }

    public function setQuestionId($questionId)
    {
        $this->question_id = $questionId;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getVotePlus()
    {
        return $this->vote_plus;
    }

    public function setVotePlus($value = 1)
    {
        $this->vote_plus = $this->getVotePlus() + $value;
        $this->setVote();
    }

    public function getVoteMinus()
    {
        return $this->vote_minus;
    }

    public function setVoteMinus($value = 1)
    {
        $this->vote_minus = $this->getVoteMinus() + $value;
        $this->setVote();
    }

    public function getVote()
    {
        return $this->vote;
    }

    public function setVote() {
        $this->vote = $this->getVotePlus() - $this->getVoteMinus();
    }

    public function getVoteBest()
    {
        return $this->vote_best;
    }

    public function setVoteBest() {
        $this->vote_best = true;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function getModified()
    {
        return $this->modified;
    }

    public function setModified(\DateTime $modified)
    {
        $this->modified = $modified;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getStatusText()
    {
        $statuses = self::getStatuses();

        return isset($statuses[$this->status]) ? $statuses[$this->status] : __('Unknown');
    }

    public static function getStatuses()
    {   
        return [
            self::STATUS_BLOCKED        => __('Blocked'),
            self::STATUS_PENDING        => __('Pending'),
            self::STATUS_APPROVED     => __('Validated')
        ];
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function getQuestion()
    {
        return $this->question;
    }

    public function setQuestion($question)
    {
        $this->question = $question;
    }
}
