<?php

namespace App\Http\Controllers\Api;

use App\Models\Message;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    public static $typeArray = [
        'zx' => '123',
        'kq' => '312'
    ];

    public function store(Request $request, $type = null)
    {
        logger('<================ Message Store Start.');

        $data = json_decode($request->get('data', null), true);

        if (!$data) {
            logger("Message Store End, Message Store Bad Request ============>");
            return $this->response->errorBadRequest();
        }
        $data = $this->safeDataFilter($data);

        if (in_array($data['dialogType'], ['dt_v_ov', 'dt_v_nm'])) {
            logger("Message Store End, DialogType Is {$data['dialogType']}============>");
            return $this->response->array("ok")->setStatusCode(200);
        }

        $visitorId = $data['visitorId'];

        if (!$visitorId) {
            logger("Message Store End, Message Store Bad VisitorId ============>");
            return $this->response->errorBadRequest();
        }

        if ($data['recId'] == 743895395) {
            logger($data);
        }


        if (isset($data['dialogs'])) {
            logger('Dialogs Exists');
            $data['clue'] = $this->hasPhone($data['dialogs']);
            logger("Clue Is {$data['clue']}");
        }

        $message = Message::where('visitorId', $visitorId)->first();

        if (!$message) {
            logger('Is New Message');
            $message = new Message();
            logger("Type Value Is {$type}");
            !empty($type) && $data['data_type'] = $type;
        }

        $message->fill($data);
        $message->save();

        logger('Message Store End, Save Success. ================>');
        return $this->response->array("ok")->setStatusCode(200);
    }

    public function hasPhone($json)
    {
        $result = '';
        if ($json) {
            $arr = [];
            foreach ($json as $value) {
                if ($value['recType'] == 1) {
                    $str = preg_replace('/\\".*?\\"/m', '', $value['recContent']);
                    $str = preg_replace('/<[^>]+>/m', '', $str);
                    preg_match_all('/([0-9A-Za-z\_\-\ \ ]{6,20})/m', $str, $matches);
                    isset($matches[0]) && ($arr = array_merge($arr, $matches[0]));
                }
            }
            $result = implode('|', $arr);
        }

        return $result ? DB::connection()->getPdo()->quote(utf8_encode($result)) : '';
    }

    public function repush($type, $appName)
    {
        $app     = Message::$app[$appName];
        $pushUrl = array_get(self::$typeArray, $type, null);

        if (!$app || !$pushUrl) {
            return $this->response->errorBadRequest();
        }

        $data = getKsSign($app, $pushUrl, 'HISTORYDATA');

        new Client();
    }

}
