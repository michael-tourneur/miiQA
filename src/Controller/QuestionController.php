<?php

namespace Mii\Qa\Controller;

use Mii\Qa\MiiQaExtension;
use Mii\Qa\Entity\Question;
use Pagekit\Component\Database\ORM\Repository;
use Pagekit\Framework\Controller\Controller;
use Pagekit\Framework\Controller\Exception;
use Pagekit\User\Entity\UserRepository;

/**
 * @Route(name="@miiQA/admin/question")
 * @Access("miiQA: manage questions", admin=true)
 */
class QuestionController extends Controller
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
    protected $roles;

    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * Constructor.
     */
    public function __construct(MiiQaExtension $extension)
    {
        $this->extension    = $extension;
        $this->questions 	= $this['db.em']->getRepository('Mii\Qa\Entity\Question');
        $this->roles        = $this['users']->getRoleRepository();
        $this->users 		= $this['users']->getUserRepository();
    }

    /**
     * @Request({"filter": "array", "page":"int"})
     * @Response("extension://miiQA/views/admin/question/index.razr")
     */
    public function indexAction($filter = null, $page = 0)
    {
        $query = $this->questions->query();

        if (isset($filter['status']) && is_numeric($filter['status'])) {
            $query->where(['status' => intval($filter['status'])]);
        }

        if (isset($filter['search']) && strlen($filter['search'])) {
            $query->where(function($query) use ($filter) {
                $query->orWhere(['title LIKE :search', 'slug LIKE :search'], ['search' => "%{$filter['search']}%"]);
            });
        }


        $limit = $this->extension->getConfig('posts.posts_per_page');
        $count = $query->count();
        $total = ceil($count / $limit);
        $page  = max(0, min($total - 1, $page));
        $questions = $query->offset($page * $limit)->limit($limit)->related('user')->orderBy('date', 'DESC')->get();

        if ($this['request']->isXmlHttpRequest()) {
            return $this['response']->json([
                'table' => $this['view']->render('extension://miiQA/views/admin/question/table.razr', compact('count', 'questions')),
                'total' => $total
            ]);
        }

        return [
            'head.title' => __('Questions'), 
            'questions' => $questions, 
            'statuses' => Question::getStatuses(), 
            'filter' => $filter, 
            'total' => $total, 
            'count' => $count, 
        ];
    }

    /**
     * @Response("extension://miiQA/views/admin/question/edit.razr")
     */
    public function addAction()
    {
        $question = new Question;
        $question->setUser($this['user']);
        // $question->setCommentStatus(true);

        return [
        	'head.title' => __('Add Question'), 
        	'question' => $question, 
        	'statuses' => Question::getStatuses(), 
        	'roles' => $this->roles->findAll(), 
        	'users' => $this->users->findAll()
        ];
    }

    /**
     * @Request({"id": "int", "question": "array"}, csrf=true)
     * @Response("json")
     */
    public function saveAction($id, $data)
    {
        try {

            if (!$question = $this->questions->find($id)) {

                $question = new Question;
                $question->setUser($this['user']);

            }
            $slug = (isset($data['slug'])) ? $data['slug'] : $data['title'];
            if (!$data['slug'] = $this->slugify($slug)) {
                throw new Exception('Invalid slug.');
            }

            $now = $this['dates']->getDateTime(time())->setTimezone(new \DateTimeZone('UTC'));
            $data['modified'] = $now;

            $data['date'] = (isset($data['date'])) ? $this['dates']->getDateTime(strtotime($data['date']))->setTimezone(new \DateTimeZone('UTC')) : $now;
            $data['date'] = $id ? $data['date'] : $now;

            $this->questions->save($question, $data);

            return ['message' => $id ? __('Question saved.') : __('Question created.'), 'id' => $question->getId()];

        } catch (Exception $e) {

            return ['message' => $e->getMessage(), 'error' => true];

        }
    }

    /**
     * @Request({"id": "int"})
     * @Response("extension://miiQA/views/admin/question/edit.razr")
     */
    public function editAction($id)
    {
        try {

            if (!$question = $this->questions->query()->where(compact('id'))->related('user')->first()) {
                throw new Exception(__('Invalid question id.'));
            }

        } catch (Exception $e) {

            $this['message']->error($e->getMessage());

            return $this->redirect('@miiQA/admin/question');
        }

        return [
            'head.title' => __('Edit Question'), 
            'question' => $question, 
            'statuses' => Question::getStatuses(), 
            'roles' => $this->roles->findAll(), 
            'users' => $this->users->findAll()
        ];
    }

    /**
     * @Request({"ids": "int[]"}, csrf=true)
     * @Response("json")
     */
    public function deleteAction($ids = [])
    {
        foreach ($ids as $id) {
            if ($question = $this->questions->find($id)) {
                $this->questions->delete($question);
            }
        }

        return ['message' => _c('{0} No question deleted.|{1} Question deleted.|]1,Inf[ Questions deleted.', count($ids))];
    }

    /**
     * @Request({"ids": "int[]"}, csrf=true)
     * @Response("json")
     */
    public function copyAction($ids = [])
    {
        foreach ($ids as $id) {
            if ($question = $this->questions->find((int) $id)) {

                $question = clone $question;
                $question->setId(null);
                $question->setStatus(Question::STATUS_OPEN);
                $question->setSlug($question->getSlug());
                $question->setTitle($question->getTitle().' - '.__('Copy'));

                $this->questions->save($question);
            }
        }

        return ['message' => _c('{0} No question copied.|{1} Question copied.|]1,Inf[ Questions copied.', count($ids))];
    }

    /**
     * @Request({"status": "int", "ids": "int[]"}, csrf=true)
     * @Response("json")
     */
    public function statusAction($status, $ids = [])
    {
        $statuses = Question::getStatuses();
        if(array_key_exists($status, $statuses)) {
            foreach ($ids as $id) {
                if ($question = $this->questions->find($id) and $question->getStatus() != $status) {
                    $question->setStatus($status);
                    $this->questions->save($question);
                }
            }
            $message = _c('{0} No question '. $statuses[$status] .'.|{1} Question '. $statuses[$status] .'.|]1,Inf[ Questions '. $statuses[$status] .'.', count($ids));
        }
        else {
            $message = __('Status unavailable');
        }

        return compact('message');

    }

    /**
     * @Request({"id": "int", "vote": "boolean"})
     * @Response("json")
     */
    public function voteAction($id, $vote) 
    {
        try {

            if (!$question = $this->questions->find($id)) {
                return ['message' => __('Question not found.'), 'error' => true];
            }

            if($vote)
                $question->setVotePlus();
            else
                $question->setVoteMinus();
            $this->questions->save($question);

            return ['message' => __('Vote added.'), 'vote' => $question->getVote(), 'error' => false];

        } catch (Exception $e) {

            return ['message' => $e->getMessage(), 'error' => true];

        }
    }
    

    protected function slugify($slug)
    {
        $slug = preg_replace('/\xE3\x80\x80/', ' ', $slug);
        $slug = str_replace('-', ' ', $slug);
        $slug = preg_replace('#[:\#\*"@+=;!><&\.%()\]\/\'\\\\|\[]#', "\x20", $slug);
        $slug = str_replace('?', '', $slug);
        $slug = trim(mb_strtolower($slug, 'UTF-8'));
        $slug = preg_replace('#\x20+#', '-', $slug);

        return $slug;
    }

}