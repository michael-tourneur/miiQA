<?php

namespace Mii\Qa\Controller;

use Mii\Qa\MiiQaExtension;
use Mii\Qa\Entity\Question;
use Mii\Qa\Entity\Answer;
use Pagekit\Framework\Controller\Controller;
use Pagekit\Component\Database\ORM\Repository;
use Pagekit\Framework\Controller\Exception;
use Pagekit\User\Entity\UserRepository;


/**
 * @Route("miiQA", name="@miiQA/site")
 */
class SiteController extends Controller
{
    /**
     * @var MiiQaExtension
     */
    protected $extension;

    /**
     * @var Repository
     */
    protected $questions;

    /**
     * @var Repository
     */
    protected $answers;

    /**
     * Constructor.
     */
    public function __construct(MiiQaExtension $extension)
    {
        $this->extension    = $extension;
        $this->questions    = $this['db.em']->getRepository('Mii\Qa\Entity\Question');
        $this->answers      = $this['db.em']->getRepository('Mii\Qa\Entity\Answer');
    }

	/**
     * @Request({"filter": "array", "page":"int"})
     * @Response("extension://miiqa/views/index.razr")
     */
    public function indexAction($filter = null, $page = 0)
    {
        $query = $this->questions->query();

        //autocomplete filter
        if(isset($filter['autocomplete'])) {
            $query->where('title LIKE :title', [':title' => "%{$filter['autocomplete']}%"]);

            $list = [];
            foreach ($query->get() as $key => $question) {
                $list[] = ['title' => $question->getTitle(), 'id' => $question->getId(), 'url' => $this['url']->to('@miiQA/site/question/id', ['id' => $question->getId()])];
            }
            return $this['response']->json([
                'list' => $list,
                'count' => $query->count(),
            ]);
        }

        if ($filter) {
            $this['session']->set('miiQA.index.filter', $filter);
        } else {
            $filter = $this['session']->get('miiQA.index.filter', []);
        }

        if (isset($filter['status']) && is_numeric($filter['status'])) {
            $query->where(['status' => intval($filter['status'])]);
        }

        if (isset($filter['search']) && strlen($filter['search'])) {

            $query->where('title LIKE :title', [':title' => "%{$filter['search']}%"]);
        }

        if (isset($filter['orderby']) && in_array($filter['orderby'], ['vote', 'answer_count', 'view_count'])) {
            $order = ($filter['orderby'] == 'comment_count') ? 'ASC' : 'DESC';
            $query->orderBy($filter['orderby'], $order);
        }

        $limit  = $this->extension->getConfig('index.questions_per_page', 20);
        $count  = $query->count();
        $total  = ceil($count / $limit);
        $page   = max(0, min($total - 1, $page));

        $query->related('user')->offset($page * $limit)->limit($limit)->get();

        if ($this['request']->isXmlHttpRequest()) {
            return $this['response']->json([
                'table' => $this['view']->render('extension://miiqa/views/question/table.razr', ['questions' => $query->get()]),
                'total' => $total,
            ]);
        }

        return [
            'head.title' => __('QA'),
            'questions' => $query->get(),
            'filter' => $filter,
            'total' => $total,
            'question' => new Question
        ];
    }

    /**
     * @Route("/question/add", name="@miiQA/site/question/add")
     * @Response("extension://miiqa/views/question/edit.razr")
     */
    public function addQuestionAction()
    {
        $question = new Question;
        $question->setUserId((int) $this['user']->getId());
        return [
            'head.title' => __('Add Question'),
            'question' => $question,
            'statuses' => Question::getStatuses(),
        ];
    }

    /**
     * @Route("/question/save", name="@miiQA/site/question/save")
     * @Request({"id": "int", "question": "array"})
     * @Response("json")
     */
    public function saveQuestionAction($id = null, $data)
    {
        $questionController = new QuestionController($this->extension);
        $response = $questionController->saveAction($id, $data);
        if($this['request']->isXmlHttpRequest())
            return $response;

        if(isset($response['error']) && $response['error']) $this['message']->error($response['message']);
        else $this['message']->info($response['message']);

        return $this->redirect('@miiQA/site');
    }

    /**
     * @Route("/question/{id}", name="@miiQA/site/question/id")
     * @Request({"id": "int", "filter": "array"})
     * @Response("extension://miiqa/views/question/view.razr")
     */
    public function showQuestionAction($id, $filter = null)
    {

        if (!$question = $this->questions->query()->related('user')->where(['id = ?', 'date < ?'], [$id, new \DateTime])->first()) {
            return $this['response']->create(__('Question not found!'), 404);
        }

        $viewed = $this['session']->get('miiQA.questions.viewed', []);
        if(!in_array($id, $viewed)) {
            $question->setViewCount( $question->getViewCount() + 1 );
            $this->questions->save($question);
            $viewed[] = $id;
            $this['session']->set('miiQA.questions.viewed', $viewed);
        }

        if ($filter) {
            $this['session']->set('miiQA.question.answers.filter', $filter);
        } else {
            $filter = $this['session']->get('miiQA.question.answers.filter', []);
        }

        $query = $this->answers->query()->related('user');

        if (!isset($filter['order'] ) || ( isset($filter['order'] ) && !in_array($filter['order'], ['asc', 'desc']))) {
            $filter['order'] = 'desc';
        }

        if (isset($filter['orderby']) && in_array($filter['orderby'], ['vote', 'date'])) {
            $query->orderBy($filter['orderby'], $filter['order']);
        }

        $query->where(['status = ?'], [Answer::STATUS_APPROVED]);

        $this['db.em']->related($question, 'comments', $query);

        if($this['request']->isXmlHttpRequest())
            return $this['response']->json([
                'table' => $this['view']->render('extension://miiqa/views/answer/table.razr', ['answers' => $question->getComments()]),
                'count' => count($question->getComments()),
            ]);

        return [
            'head.title' => __($question->getTitle()),
            'question' => $question,
            'answers' => $question->getComments(),
            'filter' => $filter,
        ];
    }

    /**
     * @Route("/question/{id}/vote", name="@miiQA/site/question/id/vote")
     * @Request({"id": "int", "vote": "boolean"})
     * @Response("json")
     */
    public function voteQuestionAction($id, $vote)
    {
        $vote = (bool) $vote;
        $voted = $this['session']->get('miiQA.questions.voted', []);
        if(isset($voted[$id]) && $voted[$id] == $vote)
            $response = ['message' => __('Already voted.'), 'error' => true];
        else {
            $questionController = new QuestionController($this->extension);
            $response = $questionController->voteAction($id, $vote);
        }
        $voted[$id] = $vote;
        $this['session']->set('miiQA.questions.voted', $voted);

        if($this['request']->isXmlHttpRequest())
            return $response;

        if(isset($response['error']) && $response['error']) $this['message']->error($response['message']);
        else $this['message']->info($response['message']);

        return $this->redirect('@miiQA/site/question/id', ['id' => $id]);
    }

    /**
     * @Route("/answer/save", name="@miiQA/site/answer/save")
     * @Request({"id": "int", "answer": "array"})
     * @Response("json")
     */
    public function saveAnswerAction($id = null, $data)
    {
        $answerController = new AnswerController();
        $response = $answerController->saveAction($id, $data);
        if($this['request']->isXmlHttpRequest())
            return $response;

        if(isset($response['error']) && $response['error']) $this['message']->error($response['message']);
        else $this['message']->info($response['message']);

        return $this->redirect('@miiQA/site/question/id', ['id' => $data['question_id']]);
    }

    /**
     * @Route("/question/{question}/answer/{id}/vote", name="@miiQA/site/answer/id/vote")
     * @Request({"id": "int", "question": "int", "vote": "boolean"})
     * @Response("json")
     */
    public function voteAnswerAction($id, $question, $vote)
    {
        $vote = (bool) $vote;
        $voted = $this['session']->get('miiQA.question.answers.voted', []);
        if(isset($voted[$id]) && $voted[$id] == $vote)
            $response = ['message' => __('Already voted.'), 'error' => true];
        else {
            $answerController = new AnswerController();
            $response = $answerController->voteAction($id, $vote);
        }
        $voted[$id] = $vote;
        $this['session']->set('miiQA.question.answers.voted', $voted);

        if($this['request']->isXmlHttpRequest())
            return $response;

        if(isset($response['error']) && $response['error']) $this['message']->error($response['message']);
        else $this['message']->info($response['message']);

        return $this->redirect('@miiQA/site/question/id', ['id' => $question]);
    }


    /**
     * @Route("/question/{question}/answer/{id}/best", name="@miiQA/site/answer/id/best")
     * @Request({"id": "int", "question": "int"})
     * @Response("json")
     */
    public function bestAnswerAction($id, $question)
    {

        $answerController = new AnswerController();
        $response = $answerController->bestAction($id);

        if($this['request']->isXmlHttpRequest())
            return $response;

        if(isset($response['error']) && $response['error']) $this['message']->error($response['message']);
        else $this['message']->info($response['message']);

        return $this->redirect('@miiQA/site/question/id', ['id' => $question]);
    }

}
