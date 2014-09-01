<?php

namespace Mii\Qa\Controller;

use Mii\Qa\Entity\Question;
use Mii\Qa\Entity\Answer;
use Pagekit\Component\Database\ORM\Repository;
use Pagekit\Framework\Controller\Controller;
use Pagekit\Framework\Controller\Exception;
use Pagekit\User\Entity\UserRepository;

/**
 * @Route(name="@miiQA/admin/answer")
 * @Access("miiQA: manage answers", admin=true)
 */
class AnswerController extends Controller
{
	const POSTS_PER_PAGE = 20;

    /**
     * @var Repository
     */
    protected $questions;

    /**
     * @var Repository
     */
    protected $answers;

    /**
     * @var Repository
     */
    protected $roles;

    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->questions    = $this['db.em']->getRepository('Mii\Qa\Entity\Question');
        $this->answers 	    = $this['db.em']->getRepository('Mii\Qa\Entity\Answer');
        $this->roles        = $this['users']->getRoleRepository();
        $this->users 		= $this['users']->getUserRepository();
    }

    /**
     * @Request({"filter": "array", "page":"int"})
     * @Response("extension://miiqa/views/admin/answer/index.razr")
     */
    public function indexAction($filter = null, $page = 0)
    {
        $query = $this->answers->query();

        if (isset($filter['search']) && strlen($filter['search'])) {
            $query->where(function($query) use ($filter) {
                $query->orWhere(['content LIKE :search'], ['search' => "%{$filter['search']}%"]);
            });
        }


        $limit = 10; //$this->extension->getConfig('posts.posts_per_page');
        $count = $query->count();
        $total = ceil($count / $limit);
        $page  = max(0, min($total - 1, $page));
        $answers = $query->offset($page * $limit)->limit($limit)->related('user')->related('question')->orderBy('date', 'DESC')->get();

        if ($this['request']->isXmlHttpRequest()) {
            return $this['response']->json([
                'table' => $this['view']->render('extension://miiqa/views/admin/answer/table.razr', compact('count', 'answers')),
                'total' => $total
            ]);
        }

        return [
            'head.title' => __('Answers'), 
            'answers' => $answers, 
            'filter' => $filter, 
            'total' => $total, 
            'count' => $count, 
        ];
    }

    /**
     * @Response("extension://miiqa/views/admin/answer/edit.razr")
     */
    public function addAction()
    {
        $answer = new Answer;
        $answer->setUser($this['user']);

        return [
            'head.title' => __('Add Answer'), 
            'answer' => $answer, 
            'questions' => $this->questions->findAll(), 
            'roles' => $this->roles->findAll(), 
            'users' => $this->users->findAll()
        ];
    }

    /**
     * @Request({"id": "int", "answer": "array"}, csrf=true)
     * @Response("json")
     */
    public function saveAction($id, $data)
    {
        if(!$data['content']) return ['message' => __('Answer\'s message is required.'), 'error' => true];
        try {

            if (!$question = $this->questions->find($data['question_id'])) {

                return ['message' => __('Question associated not found.'), 'error' => true];

            }

            if (!$answer = $this->answers->find($id)) {

                $answer = new Answer;
                $answer->setUser($this['user']);
                $questionData['comment_count'] = $question->commentCountPlus();

            }

            if($question->getStatus() == Question::STATUS_OPEN)
                $questionData['status'] = Question::STATUS_ANSWERED;

            $date = (isset($data['date'])) ? $data['date'] : time();
            $data['modified'] = $this['dates']->getDateTime($date)->setTimezone(new \DateTimeZone('UTC'));

            $data['date'] = $id ? $question->getDate() : $data['modified'];

            $this->answers->save($answer, $data); 

            if(isset($questionData))
                $this->questions->save($question, $questionData);       

            return ['message' => $id ? __('Answer saved.') : __('Answer created.'), 'id' => $answer->getId()];

        } catch (Exception $e) {

            return ['message' => $e->getMessage(), 'error' => true];

        }
    }

    /**
     * @Request({"id": "int"})
     * @Response("extension://miiqa/views/admin/answer/edit.razr")
     */
    public function editAction($id)
    {
        try {

            if (!$answer = $this->answers->query()->where(compact('id'))->related('user')->related('question')->first()) {
                throw new Exception(__('Invalid answer id.'));
            }

        } catch (Exception $e) {

            $this['message']->error($e->getMessage());

            return $this->redirect('@miiQA/admin/answer');
        }

        return [
            'head.title' => __('Edit Answer'), 
            'answer' => $answer, 
            'questions' => $this->questions->findAll(), 
            'roles' => $this->roles->findAll(), 
            'users' => $this->users->findAll()
        ];
    }


    /**
     * @Request({"id": "int", "vote": "boolean"})
     * @Response("json")
     */
    public function voteAction($id, $vote) 
    {
        try {

            if (!$answer = $this->answers->find($id)) {
                return ['message' => __('Answer not found.'), 'error' => true];
            }

            if (!$question = $this->questions->find($answer->getQuestionId())) {
                return ['message' => __('Question associated not found.'), 'error' => true];
            }

            if($vote)
                $answer->setVotePlus();
            else
                $answer->setVoteMinus();
            $this->answers->save($answer);

            $question->setVote($answer->getVote());
            $this->questions->save($question); 

            return ['message' => __('Vote added.'), 'vote' => $answer->getVote(), 'error' => false];

        } catch (Exception $e) {

            return ['message' => $e->getMessage(), 'error' => true];

        }
    }

    /**
     * @Request({"id": "int"})
     * @Response("json")
     */
    public function bestAction($id)
    {
        try {

            if (!$answer = $this->answers->find($id)) {
                return ['message' => __('Answer not found.'), 'error' => true];
            }

            if (!$question = $this->questions->find($answer->getQuestionId())) {
                return ['message' => __('Question associated not found.'), 'error' => true];
            }

            $this->answers->query() 
                ->from('@miiqa_answers')
                ->where(['vote_best = 1'])
                ->update(['vote_best' => 0]);

            $answer->setVoteBest();
            $this->answers->save($answer); 

            $question->setBestAnswer($id);
            $this->questions->save($question); 

            return ['message' => __('Best answer selected.'), 'vote' => $answer->getVote(), 'error' => false];

        } catch (Exception $e) {

            return ['message' => $e->getMessage(), 'error' => true];

        }
    }
}