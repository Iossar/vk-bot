<?php

namespace app\controllers;

use yii\web\Controller;
use app\models\CoverUploader;
use app\models\Counter;
use Yii;

class BotController extends Controller
{
    private $confirmation_token = '48b46c14';
    private $token = 'd35e0aa9e7fc514b9c15f138da48d2f2b849c085cd990511384457d6617eb0377eb8cc061f9c09ff54dcd';
    private $v = '5.103';

    public function beforeAction($action)
    {
        if (in_array($action->id, ['callback'])) {
            $this->enableCsrfValidation = false;
        };
        return parent::beforeAction($action);
    }

    public function actionCallback()
    {
        $data = json_decode(file_get_contents('php://input'));
        switch ($data->type) {
            case 'confirmation' :
                return $this->confirmation_token;
            case 'wall_reply_new':
                $message = $data->object->text;
                if ($message == '#GLUTENFREEDIET') {
                    $this->actionWallReply($data);
                }
                sleep(1);
                return 'ok';
                break;
            /*default:
                return 'ok';
                break;*/
        }
    }

    public function actionWallReply($data)
    {
        //$redis = Yii::$app->redis;
        //$redis->executeCommand('FLUSHALL');die();
        $user_id = $data->object->from_id;
        $group_id = $data->group_id;
        $user = $this->actionGetUser($user_id);
        $user_name = $user->response[0]->first_name;

        if ($user_name) {
            $request_params = $this->actionPrepareParams($data);
            $counter = new Counter($user_id);
            $message = $counter->checkUserMessageCount();
            $uploader = new CoverUploader($this->token, $this->v);
            $uploader->Uploader($group_id);
            if ($message != null) {
                $request_params['message'] = $message;
            }
            $this->request('https://api.vk.com/method/wall.createComment?', $request_params);
        }
    }

    private function actionPrepareParams($data)
    {
        return [
            'owner_id' => $data->object->owner_id,
            'post_id' => $data->object->post_id,
            'access_token' => $this->token,
            'v' => $this->v,
            'reply_to_comment' => $data->object->id
        ];
    }

    public function actionGetUser($user_id)
    {
        return json_decode(file_get_contents("https://api.vk.com/method/users.get?user_ids={$user_id}&access_token={$this->token}&v=5.103"));
    }

    private function request($url, $data)
    {
        $get_params = http_build_query($data);
        $out = file_get_contents($url . $get_params);
        return $out;
    }
}
