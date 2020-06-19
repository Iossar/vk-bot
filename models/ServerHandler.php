<?php

namespace app\models;

class ServerHandler extends VK\CallbackApi\Server\VKCallbackApiServerHandler {
    const SECRET = 'ab12aba';
    const GROUP_ID = 196398363;
    const CONFIRMATION_TOKEN = '48b46c14';

    function confirmation(int $group_id, ?string $secret) {
        if ($group_id === static::GROUP_ID) {
            echo static::CONFIRMATION_TOKEN;
        }
    }

    public function messageNew(array $object) {
        return 'ok';
    }
}
?>