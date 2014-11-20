<?php

return [

    'up' => function() use ($app) {

        $util = $app['db']->getUtility();

        if ($util->tableExists('@miiqa_tags') === false) {
            $util->createTable('@miiqa_tags', function($table) {
                $table->addColumn('id', 'integer', ['unsigned' => true, 'length' => 10, 'autoincrement' => true]);
                $table->addColumn('label', 'string', ['length' => 255, 'notnull' => true]);
                $table->addColumn('count', 'integer', ['unsigned' => true, 'length' => 10, 'default' => 0]);
                $table->setPrimaryKey(['id']);
                $table->addUniqueIndex(['label'], 'TAG_LABEL');
            });
        }

        if ($util->tableExists('@miiqa_question_tag') === false) {
            $util->createTable('@miiqa_question_tag', function($table) {
                $table->addColumn('question_id', 'integer', ['unsigned' => true, 'length' => 10]);
                $table->addColumn('tag_id', 'integer', ['unsigned' => true, 'length' => 10]);
                $table->setPrimaryKey(['question_id', 'tag_id']);
            });
        }
    }

];
