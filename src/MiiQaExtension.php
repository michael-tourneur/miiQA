<?php

namespace Mii\Qa;

use Pagekit\Framework\Application;
use Pagekit\Extension\Extension;
use Pagekit\System\Event\LinkEvent;

class MiiQaExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
        parent::boot($app);

        // $app['events']->addSubscriber(new HelloListener());
 
        $app->on('system.link', function(LinkEvent $event) {
            $event->register('Mii\Qa\Link\QaLink');
        });

        $app->on('system.init', function() use ($app) {

            $this->config += $this->getConfig('defaults');

        }, 15);

        $app['events']->dispatch('miiQA.boot');
    }

    public function enable()
    {
        if ($version = $this['migrator']->create('extension://miiqa/migrations', $this['option']->get('miiQA:version'))->run()) {

            $this['option']->set('miiQA:version', $version);
        }

    }

    public function uninstall()
    {
        $this['option']->remove('miiQA:version');
    }

}
