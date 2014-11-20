<?php

namespace Mii\Qa\Controller;

use Mii\Qa\MiiQaExtension;
use Mii\Qa\Entity\Tag;
use Pagekit\Component\Database\ORM\Repository;
use Pagekit\Framework\Controller\Controller;
use Pagekit\Framework\Controller\Exception;
use Pagekit\User\Entity\UserRepository;

/**
 * @Route(name="@miiQA/admin/tag")
 * @Access("miiQA: manage tags", admin=true)
 */
class TagController extends Controller
{
    /**
     * @var MiiQaExtension
     */
    protected $extension;

    /**
     * @var Repository
     */
    protected $tags;

    /**
     * Constructor.
     */
    public function __construct(MiiQaExtension $extension)
    {
        $this->extension    = $extension;
        $this->tags 	= $this['db.em']->getRepository('Mii\Qa\Entity\Tag');
    }

    /**
     * @Request({"filter": "array", "page":"int"})
     * @Response("extension://miiqa/views/admin/tag/index.razr")
     */
    public function indexAction($filter = null, $page = 0)
    {
        $query = $this->tags->query();

        if (isset($filter['search']) && strlen($filter['search'])) {
          $query->where(['label LIKE :search'], ['search' => "%{$filter['search']}%"]);
        }


        $limit = $this->extension->getConfig('index.items_per_page');
        $count = $query->count();
        $total = ceil($count / $limit);
        $page  = max(0, min($total - 1, $page));
        $tags = $query->offset($page * $limit)->limit($limit)->orderBy('label', 'ASC')->get();

        if ($this['request']->isXmlHttpRequest()) {
            return $this['response']->json([
                'table' => $this['view']->render('extension://miiqa/views/admin/tag/table.razr', compact('count', 'tags')),
                'total' => $total
            ]);
        }
        return [
            'head.title'  => __('Tags'),
            'tags'        => $tags,
            'filter'      => $filter,
            'total'       => $total,
            'count'       => $count,
        ];
    }


    /**
     * @Request({"id": "int", "tag": "array"}, csrf=true)
     * @Response("json")
     */
    public function saveAction($id, $data)
    {
        try {

          if(empty($data['label']))
              return ['message' => 'Please enter a valid tag.', 'error' => true];
              
          if($this->tags->where(array('label' => $data['label']))->count())
              return ['message' => 'This tag already exist.', 'error' => true];

          if(!$tag = $this->tags->find($id))
              $tag = new Tag;

          $this->tags->save($tag, $data);

          return ['message' => $id ? __('Tag saved.') : __('Tag created.'), 'id' => $tag->getId()];

        } catch (Exception $e) {

            return ['message' => $e->getMessage(), 'error' => true];

        }
    }


    /**
     * @Request({"ids": "int[]"}, csrf=true)
     * @Response("json")
     */
    public function deleteAction($ids = [])
    {
        foreach ($ids as $id) {
            if ($tag = $this->tags->find($id)) {
                $this->tags->delete($tag);
            }
        }

        return ['message' => _c('{0} No tag deleted.|{1} Tag deleted.|]1,Inf[ Tags deleted.', count($ids))];
    }

}
