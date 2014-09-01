<?php

namespace Mii\Qa\Link;

use Pagekit\System\Link\Link;

class QaLink extends Link
{
    /**
     * @{inheritdoc}
     */
    public function getId()
    {
        return 'miiQA';
    }

    /**
     * @{inheritdoc}
     */
    public function getLabel()
    {
        return __('miiQA');
    }

    /**
     * @{inheritdoc}
     */
    public function accept($route)
    {
        return $route == '@miiQA/site' || $route == '@miiQA/site/question/id';
    }

    /**
     * @{inheritdoc}
     */
    public function renderForm($link, $params = [], $context = '')
    {
        $questions = $this['db.em']->getRepository('Mii\Qa\Entity\Question')->findAll();
        return $this['view']->render('extension://miiqa/views/admin/link/miiQA.razr', compact('link', 'params', 'questions'));
    }
}
