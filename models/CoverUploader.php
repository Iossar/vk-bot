<?php

namespace app\models;

use CURLFile;
use yii\base\Model;
use Yii;

class CoverUploader extends Model
{
    public $directory;
    private $token;
    private $v;

    public function __construct($token, $v, $config = [])
    {
        $this->token = $token;
        $this->v = $v;
        $this->directory = '../web/covers/';
        parent::__construct($config);
    }

    public function Uploader($group_id)
    {
        $filename = $this->getFilename();
        if ($filename != null) {
            $this->coverPreparer($filename);
            $url = $this->photoUploadServer($group_id);
            $photo = $this->uploadPhoto($url, $this->directory . 'current_fon.jpg');
            $this->savePhoto($photo['hash'], $photo['photo']);
        }
    }

    private function getFilename()
    {
        $redis = Yii::$app->redis;
        $total_comments_count = $redis->get('total');
        if ($total_comments_count <= 15) {
            $filename = null;
        } elseif ($total_comments_count > 15 && $total_comments_count < 30) {
            $filename = '1.jpg';
        } elseif ($total_comments_count > 30 && $total_comments_count < 45) {
            $filename = '2.jpg';
        } else {
            $filename = '3.jpg';
        }
        return $filename;
    }

    private function coverPreparer($filename)
    {
        $font = $this->directory . '19319.ttf';
        $leaders = $this->prepareText();
        $cover = imagecreatefromjpeg($this->directory . $filename);
        $white = imagecolorallocate($cover, 255, 255, 255);
        $start_position = 860;
        foreach ($leaders as $key => $leader) {
            $avatar = imagecreatefromjpeg($leader['photo']);
            imagecopy($cover, $avatar, $start_position, 175, 0, 0, imagesx($avatar), imagesy($avatar));
            imagefttext($cover, 20, 0, $start_position, 330, $white, $font, $leader['name']);
            $start_position += 180;
        }
        imagejpeg($cover, $this->directory . 'current_fon.jpg', 100); //Сохраняем полученную картинку в fon.jpg в 100% качестве
        imagedestroy($cover);
    }

    private function prepareText()
    {
        $leaders = $this->getLeaders();
        $leaders_names = [];
        foreach ($leaders as $place => $leader) {
            foreach ($leader as $user_id => $count) {
                $user = $this->getUserInfo($user_id);
                $leaders_names[$user_id]['photo'] = $user->response[0]->photo_100;
                $leaders_names[$user_id]['name'] = $user->response[0]->first_name . PHP_EOL . $user->response[0]->last_name . PHP_EOL . $count;
            }
        }
        return $leaders_names;

    }

    private function getUserInfo($user_id)
    {
        return json_decode(file_get_contents("https://api.vk.com/method/users.get?user_ids={$user_id}&fields=photo_100&access_token={$this->token}&v=5.103"));
    }

    private function getLeaders()
    {
        $redis = Yii::$app->redis;
        $score = $redis->hgetall('score');
        $response = $this->parseResponse($score);
        if (krsort($response)) {
            return [
                'first_leader' => array_shift($response) ?? '',
                'second_leader' => array_shift($response) ?? '',
                'third_leader' => array_shift($response) ?? ''
            ];
        }
        return null;
    }

    private function parseResponse($data)
    {
        $result = [];
        for ($i = 0; $i < count($data); ++$i) {
            $result[$data[++$i]][$data[--$i]] = $data[++$i];
        }
        return $result;
    }

    private function photoUploadServer($group_id)
    {
        $data = [
            'group_id' => $group_id,
            'crop_x2' => 1590,
            'crop_y2' => 400,
            'v' => $this->v,
            'access_token' => $this->token,
        ];
        $out = $this->requestCover('https://api.vk.com/method/photos.getOwnerCoverPhotoUploadServer', $data);
        return $out["response"]["upload_url"];
    }

    private function uploadPhoto($url, $file)
    {
        $data = [
            'photo' => new CURLFile($file),
        ];
        $out = $this->requestCover($url, $data);
        return $out;
    }

    private function savePhoto($hash, $photo)
    {
        $data = [
            'hash' => $hash,
            'photo' => $photo,
            'v' => $this->v,
        ];
        $out = $this->requestCover('https://api.vk.com/method/photos.saveOwnerCoverPhoto', $data);
        return $out;
    }

    private function requestCover($url, $data = array()) {
        $curl = curl_init();
        $data['access_token'] = $this->token;
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER,[
            "Content-Type:multipart/form-data"
        ]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        $out = json_decode(curl_exec($curl), true);

        curl_close($curl);
        return $out;
    }
}
