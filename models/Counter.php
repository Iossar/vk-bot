<?php

namespace app\models;

use yii\base\Model;
use Yii;

class Counter extends Model
{
    public $user_id;

    public function __construct($user_id, $config = [])
    {
        $this->user_id = $user_id;
        parent::__construct($config);
    }

    public function checkUserMessageCount()
    {
        $user_message_count = $this->userMessageCounter();
        $this->totalMessageCounter();
        if ($user_message_count % 5 == 0 && $user_message_count != 0) {
            return 'Ты красавчик, уже ' . $user_message_count . ' раз ты помог нам зарядить луч!';
        }
        return null;
    }

    private function totalMessageCounter()
    {
        $redis = Yii::$app->redis;
        $total_comments_count = $redis->get('total');
        if ($total_comments_count == null) {
            $redis->set('total', 1);
        } else {
            ++$total_comments_count;
            $redis->set('total', $total_comments_count);
        }
    }

    private function userMessageCounter()
    {
        $redis = Yii::$app->redis;
        $user_score = $redis->hget('score', $this->user_id);
        if ($user_score == null) {
            $user_score = 1;
            $redis->hset('score', $this->user_id, $user_score);
        } else {
            ++$user_score;
            $redis->hset('score', $this->user_id, $user_score);
        }
        return $user_score;
    }
}
