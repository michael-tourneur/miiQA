<?php

namespace Mii\Qa\Entity;

use Pagekit\System\Entity\DataTrait;
use Pagekit\User\Entity\AccessTrait;
use Pagekit\Comment\CommentsTrait;
use Pagekit\Framework\Database\Event\EntityEvent;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity(tableClass="@miiqa_questions")
 */
class Question
{
    use DataTrait, CommentsTrait;

    /* question open status. */
    const STATUS_OPEN = 1;

    /* question answered status. */
    const STATUS_ANSWERED = 2;

    /* question resolved status. */
    const STATUS_RESOLVED = 3;

    /** @Column(type="integer") @Id */
    protected $id;

    /** @Column(type="integer") */
    protected $user_id;

    /** @Column(type="string") */
    protected $slug;

    /** @ManyToMany(targetEntity="Tag", keyFrom="id", keyTo="id", tableThrough="@miiqa_question_tag", keyThroughFrom="question_id", keyThroughTo="tag_id") */
    protected $tags;

    /**
     * @HasMany(targetEntity="Answer", keyFrom="id", keyTo="question_id")
     * @OrderBy({"date" = "DESC"})
     */
    protected $comments;

    /**
     * @BelongsTo(targetEntity="Pagekit\User\Entity\User", keyFrom="user_id")
     */
    protected $user;

    /** @Column(type="string") */
    protected $title;

    /** @Column(type="integer") */
    protected $status = self::STATUS_OPEN;

    /** @Column */
    protected $content = '';

    /** @Column(type="datetime")*/
    protected $date;

    /** @Column(type="datetime") */
    protected $modified;

    /** @Column(type="integer") */
    protected $comment_count = 0;

    /** @Column(type="integer") */
    protected $view_count = 0;

    /** @Column(type="integer") */
    protected $vote_plus = 0;

    /** @Column(type="integer") */
    protected $vote = 0;

    /** @Column(type="integer") */
    protected $vote_minus = 0;

     /** @Column(type="integer") */
    protected $best_answer = 0;

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

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function setSlug($slug)
    {
        $this->slug = $slug;
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
            self::STATUS_OPEN           => __('Open'),
            self::STATUS_ANSWERED       => __('Answered'),
            self::STATUS_RESOLVED       => __('Resolved'),
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

    public function getViewCount()
    {
        return $this->view_count;
    }

    public function setViewCount($viewCount)
    {
        $this->view_count = $viewCount;
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

    public function getCommentCount()
    {
        return $this->comment_count;
    }

    public function setCommentCountPlus()
    {
        return $this->comment_count += 1;
    }

    public function setCommentCountMinus()
    {
        return $this->comment_count -= 1;
    }

    public function getBestAnswer()
    {
        return $this->best_answer;
    }

    public function setBestAnswer($answer)
    {
        $this->best_answer = (int) $answer;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function addTag(Tag $tag)
    {
        $this->tags->add($tag);
    }

    public function setTags($tags){
        $this->tags = $tags;
    }

    public function getTags(){
        return (array) $this->tags;
    }

    public function hasTag($tagId){
        foreach ($this->getTags() as $tag) {
            if($tag->getId() == $tagId) return true;
        }
        return false;
    }

    /**
     * @PreSave
     */
    public function preSave(EntityEvent $event)
    {
        $this->modified = new \DateTime;

        $questionRepository = $event->getEntityManager()->getRepository(get_class($this));

        $i = 2;
        $id = $this->id;

        while ($questionRepository->query()->where('slug = ?', [$this->slug])->where(function($query) use($id) { if ($id) $query->where('id <> ?', [$id]); })->first()) {
            $this->slug = preg_replace('/-\d+$/', '', $this->slug).'-'.$i++;
        }
    }

    /**
     * @PostSave
     */
    public function postSave(EntityEvent $event)
    {
        $connection = $event->getConnection();
        $connection->delete('@miiqa_question_tag', ['question_id' => $this->getId()]);

        if (is_array($this->tags)) {
            foreach ($this->tags as $tag) {
                $connection->insert('@miiqa_question_tag', ['question_id' => $this->getId(), 'tag_id' => $tag->getId()]);
            }
        }
    }

    /**
     * @PostDelete
     */
    public function postDelete(EntityEvent $event)
    {
        $connection = $event->getConnection();
        $connection->delete('@miiqa_answers', ['question_id' => $this->getId()]);
        $connection->delete('@miiqa_question_tag', ['question_id' => $this->getId()]);
    }
}
