<?php

return [

    'up' => function() use ($app) {

        $util = $app['db']->getUtility();

        if ($util->tableExists('@miiqa_questions') === false) {
            $util->createTable('@miiqa_questions', function($table) {
                $table->addColumn('id', 'integer', ['unsigned' => true, 'length' => 10, 'autoincrement' => true]);
                $table->addColumn('user_id', 'integer', ['unsigned' => true, 'length' => 10, 'default' => 0]);
                $table->addColumn('slug', 'string', ['length' => 255]);
                $table->addColumn('title', 'string', ['length' => 255]);
                $table->addColumn('status', 'smallint');
                $table->addColumn('content', 'text');
                $table->addColumn('date', 'datetime', ['notnull' => false]);
                $table->addColumn('modified', 'datetime');
                $table->addColumn('best_answer', 'integer', ['unsigned' => true, 'length' => 10, 'default' => 0]);
                $table->addColumn('comment_count', 'integer', ['default' => 0]);
                $table->addColumn('view_count', 'integer', ['default' => 0]);
                $table->addColumn('vote_plus', 'integer', ['unsigned' => true, 'length' => 10, 'default' => 0]);
                $table->addColumn('vote_minus', 'string', ['unsigned' => true, 'length' => 10, 'default' => 0]);
                $table->addColumn('vote', 'integer', ['length' => 10, 'default' => 0]);
                $table->addColumn('roles', 'simple_array', ['notnull' => false]);
                $table->setPrimaryKey(['id']);
                $table->addUniqueIndex(['slug'], 'POSTS_SLUG');
                $table->addIndex(['title'], 'TITLE');
                $table->addIndex(['user_id'], 'USER_ID');
                $table->addIndex(['comment_count'], 'COMMENT_COUNT');
                $table->addIndex(['view_count'], 'VIEW_COUNT');
                $table->addIndex(['vote'], 'VOTE');
            });
        }

        if ($util->tableExists('@miiqa_answers') === false) {
            $util->createTable('@miiqa_answers', function($table) {
                $table->addColumn('id', 'integer', ['unsigned' => true, 'length' => 10, 'autoincrement' => true]);
                $table->addColumn('question_id', 'integer', ['unsigned' => true, 'length' => 10]);
                $table->addColumn('user_id', 'string', ['length' => 255, 'default' => 0]);
                $table->addColumn('content', 'text');
                $table->addColumn('vote_plus', 'integer', ['unsigned' => true, 'length' => 10, 'default' => 0]);
                $table->addColumn('vote_minus', 'string', ['unsigned' => true, 'length' => 10, 'default' => 0]);
                $table->addColumn('vote', 'integer', ['length' => 10, 'default' => 0]);
                $table->addColumn('vote_best', 'smallint', ['default' => 0]);
                $table->addColumn('date', 'datetime', ['notnull' => false]);
                $table->addColumn('modified', 'datetime');
                $table->addColumn('status', 'smallint');
                $table->setPrimaryKey(['id']);
                $table->addIndex(['status'], 'STATUS');
                $table->addIndex(['question_id'], 'QUESTION_ID');
                $table->addIndex(['vote_best'], 'VOTE_BEST');
                $table->addIndex(['vote'], 'VOTE');
            });
        }
    }

];
