<?php
namespace App\Http\Controllers\Api;
use App\Jobs\SendChatNotification;
use Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use App\Models\ChatThreads;
use App\Models\ChatMessages;
use App\Models\ChatThreadUser;
use App\Models\ChatUserOff;
use App\Models\ChatBackground;
use App\Models\ChatUserBackground;
use App\Models\ChatUserAlias;
use App\Models\ChatUserRead;
use App\Models\ChatUserInvite;
use App\Models\ChatMessageRead;
use App\Models\ChatEmoij;
use App\Models\ChatMessageEmoij;
use App\Models\User;
use App\Models\Shop;
use App\Models\ChatFacePhone;
use App\Models\UserFollow;
use App\Models\ChatBrowser;
use App\Models\ChatMessagePin;
use App\Models\CallApi;
use Lang;
use App\Helpers\Hash;
use App\Helpers\Commons;
use App\Helpers\Utils;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Facades\File;
use DB;
use QrCode;
use Carbon\Carbon;
use DateTime;
class ChatController extends ApiController {


    public function listRooms(Request $req){
        $user = $req->user();
        $search = "";
        $type="group";
        if( isset($req->search)) {
            $search = $req->search;
        }
        if( isset($req->type)) {
            $type = $req->type;
        }
        $params = ['userId' => $user->use_id, 'search' =>  $search, 'type' => $type];
        $page = $req->page;
        $pageSize = $req->limit;
        /*$list = ChatThreads::getList($page, $pageSize, $params);*/
        $list = ChatThreads::getListAlias($page, $pageSize, $params);
        $results = $list->paginate($pageSize);
        $results = $results->toArray();
        if( $type == 'private') {
            foreach( $results['data'] as $k => &$v) {
                if( $v['ownerId'] == $user->use_id) {
                    /*$infoUser = ChatThreadUser::getInfoUser($v['requesterId']);*/
                    $infoUser = ChatThreadUser::getInfoUserAlias($v['requesterId'], $user['use_id']);
                    $infoUser['statusRead'] = ChatThreadUser::getStatusReadThreadUser($v['requesterId'], $v['groupChatId']);
                }
                else {
                    /*$infoUser = ChatThreadUser::getInfoUser($v['ownerId']);*/
                    $infoUser = ChatThreadUser::getInfoUserAlias($v['ownerId'], $user['use_id']);
                    $infoUser['statusRead'] = ChatThreadUser::getStatusReadThreadUser($v['ownerId'], $v['groupChatId']);
                }

                $threadId = $v['groupChatId'];
                $ownerId = $v['ownerId'];
                $v['background'] = ChatUserBackground::getBackgroundUser(['groupChatId' => $v['groupChatId'], 'userId' => $user['use_id']]);

                $v['show'] = 1;
                /*if( $v['ownerId'] == $user->use_id && $v['idGroupDefault'] == 1) {
                    $check = ChatMessages::where(['threadId' => $threadId, 'ownerId' => $v['requesterId']])->first();
                    if(!$check) {
                        $v['show'] = 0;
                    }
                }*/
                $v = array_merge($v, $infoUser);



                $lastListMessage = ChatThreads::getListLastMessage($v['groupChatId'], $user['use_id']);
                $lastMessage = (object)[];
                if(count($lastListMessage) > 0) {
                    //count($lastListMessage)-1
                    $lastMessage = ChatThreads::returnInfoLast($lastListMessage[0]);
                }
                //$lastMessage = ChatThreads::getLastMessage($v['groupChatId']);
                $v = array_merge($v, ['lastMessage' => $lastMessage ]);
                $v = array_merge($v, ['lastListMessage' => $lastListMessage ]);
            }
        }
        else {
            foreach( $results['data'] as $k => &$v) {
                $v['background'] = ChatUserBackground::getBackgroundUser(['groupChatId' => $v['groupChatId'], 'userId' => $user['use_id']]);


                $lastListMessage = ChatThreads::getListLastMessage($v['groupChatId'], $user['use_id']);
                $lastMessage = (object)[];
                if(count($lastListMessage) > 0) {
                     $lastMessage = ChatThreads::returnInfoLast($lastListMessage[0]);
                }
                //$lastMessage = ChatThreads::getLastMessage($v['groupChatId']);
                /*$infoAdmin  = ChatThreads::getInfoIsAdmin($v['use_id'], $v['groupChatId']);*/
                $infoAdmin  = ChatThreads::getInfoIsAdmin($user['use_id'], $v['groupChatId']);
                $v['admin'] = $infoAdmin['admin'];
                $v = array_merge($v, ['lastMessage' => $lastMessage ]);
                $v = array_merge($v, ['lastListMessage' => $lastListMessage ]);
            }
        }
        return response([
            'msg' => Lang::get('response.success'),
            'data' => $results
        ]);
    }

    public function detailUserJoinGroup(Request $req) {
        $user = $req->user();
        $groupChatId = $req->groupChatId;
        $userId = $req->userId;
        $infoGroup = ChatThreads::where(['id' => $groupChatId])->first();
        if($infoGroup) {
            $type = $infoGroup['type'];
            /*$data = ChatThreads::getDetailUserJoinGroup($type, $groupChatId, $userId);*/
            $data = ChatThreads::getDetailUserJoinGroupAlias($type, $groupChatId, $userId, $user['use_id']);
            return response([
                'msg' => Lang::get('response.success'),
                'data' => $data
            ]);
        }
        return response([
            'msg' => Lang::get('response.failed'),
            'data' => []
        ]);

    }

    public function listMessages_v1(Request $req) {
        $groupChat = $req->groupChatId;
        $infoGroup = ChatThreads::where(['id' => $groupChat])->first();
        if($infoGroup['type'] == 'secret') {
            $user = $req->user();
            $groupChat = $req->groupChatId;
            $page = $req->page;
            $pageSize = $req->limit;
            ChatUserRead::setRead($user->use_id, $groupChat);
            $infoGroup = ChatThreads::where(['id' => $groupChat])->first();
            $params = ['threadId' => $groupChat, 'userLogin' => $user['use_id'],'lastIdMessage' => $infoGroup['lastIdMessage'], 'idDelete' => $infoGroup['idDelete']];
            $list = ChatMessages::getListSecretAlias($page, $pageSize, $params);
            $results = $list->paginate($pageSize);
            if(count($results) > 0 ) {
                $statusLastChat = null;
                /*if($infoGroup['type'] == 'secret') {
                     $statusLastChat = ChatMessages::statusLastChat($infoGroup);

                }*/

                foreach( $results as $k => &$v) {
                    $v['created_ts'] = Commons::convertDateTotime($v['createdAt']);
                    $v['updated_ts'] = Commons::convertDateTotime($v['updatedAt']);
                    if($v['typedata'] == 'multiImage') {
                        $v['listImage'] = json_decode($v['listImage'], true);
                    }
                    else {
                        $v['listImage'] = [];
                    }
                    if($v['typedata'] == 'product') {
                        $infoProduct = ChatMessages::getDetailProduct($v['productId']);
                        $v['infoProduct'] = $infoProduct;
                    }
                    else {
                        $v['infoProduct'] = null;
                    }
                    if($v['ownerId'] == $infoGroup['ownerId']) {
                        $statusLastChat = ChatMessages::statusReadOfUser($infoGroup, $v['id'],$infoGroup['requesterId']);
                    }
                    else {
                        $statusLastChat = ChatMessages::statusReadOfUser($infoGroup, $v['id'], $infoGroup['ownerId']);
                    }

                    if($statusLastChat){
                        $v['statusRead'] = $statusLastChat['statusRead'];
                        $v['timeRead'] = $statusLastChat['timeRead'];
                    }

                    /*if($statusLastChat == 1) {
                        if($infoGroup['ownerId'] == $user['use_id']) {
                            $v['timeRead'] = ChatMessages::getTimeReadOfUser($infoGroup['requesterId'], $groupChat);
                        }
                        else {
                            $v['timeRead'] = ChatMessages::getTimeReadOfUser($infoGroup['ownerId'], $groupChat);
                        }
                    }
                    else {
                        $v['timeRead'] = null;
                    }*/
                    $v['emoij'] = ChatMessageEmoij::getEmoijMessage($v['id']);
                    /*$v['chooseEmoij'] = ChatMessageEmoij::checkChooseEmoij($v['id'], $user['use_id']);*/
                    $infoEmoi = ChatMessageEmoij::infoChooseEmoij($v['id'], $user['use_id']);
                    if(count($infoEmoi) > 0) {
                        $v['chooseEmoij'] = 1;
                        $v['typeEmoij'] = $infoEmoi['emoij'];
                    }
                    else {
                        $v['chooseEmoij'] = 0;
                        $v['typeEmoij'] = "";
                    }

                }
            }

            return response([
                'msg' => Lang::get('response.success'),
                'data' => $results
            ]);
        }
        $user = $req->user();

        $page = $req->page;
        $pageSize = $req->limit;
        ChatUserRead::setRead($user->use_id, $groupChat);

        if($infoGroup['idGroupDefault'] == 0) {
            $params = ['threadId' => $groupChat, 'userLogin' => $user['use_id'],'lastIdMessage' => $infoGroup['lastIdMessage'], 'idDelete' => $infoGroup['idDelete']];
        }
        else {
            //$params = ['threadId' => $infoGroup['idGroupDefault'], 'userLogin' => $user['use_id']];
            $params = ['threadId' => $groupChat, 'userLogin' => $user['use_id'],'lastIdMessage' => $infoGroup['lastIdMessage'], 'idDelete' => $infoGroup['idDelete']];
        }
        /*$list = ChatMessages::getList($page, $pageSize, $params);*/
        $list = ChatMessages::getListAlias($page, $pageSize, $params);
        $results = $list->paginate($pageSize);

        if(count($results) > 0 ) {
            $statusLastChat = null;
            if($infoGroup['type'] == 'private') {
                 $statusLastChat = ChatMessages::statusLastChat($infoGroup);

            }
            /*else {
                $statusLastChat = ChatMessages::statusReadOfGroup($infoGroup);
            }*/

            if($statusLastChat == null) {
                $statusLastChat = 0;
            }
            foreach( $results as $k => &$v) {
                $v['created_ts'] = Commons::convertDateTotime($v['createdAt']);
                $v['updated_ts'] = Commons::convertDateTotime($v['updatedAt']);
                if($v['typedata'] == 'multiImage') {
                    $v['listImage'] = json_decode($v['listImage'], true);
                }
                else {
                    $v['listImage'] = [];
                }
                if($v['typedata'] == 'product') {
                    $infoProduct = ChatMessages::getDetailProduct($v['productId']);
                    $v['infoProduct'] = $infoProduct;
                }
                else {
                    $v['infoProduct'] = null;
                }
                $messageId = $v['messageId'];
                if($messageId != 0) {
                    //$infoMessage = ChatMessages::getDetailMesssageParent($messageId, $user['use_id']);
                    //$v['parentMessage'] = $infoMessage;
                    $v['parentMessage'] = null;
                }
                else {
                    $v['parentMessage'] = null;
                }
                if($infoGroup['type'] != 'private') {
                    $statusLastChat = ChatMessages::statusReadOfMessage($infoGroup, $v['id']);
                }
                $v['statusRead'] = $statusLastChat;
                $v['emoij'] = ChatMessageEmoij::getEmoijMessage($v['id']);
                $infoEmoi = ChatMessageEmoij::infoChooseEmoij($v['id'], $user['use_id']);
                if(count($infoEmoi) > 0) {
                    $v['chooseEmoij'] = 1;
                    $v['typeEmoij'] = $infoEmoi['emoij'];
                    $v['emoijId'] = $infoEmoi['emoijId'];
                }
                else {
                    $v['chooseEmoij'] = 0;
                    $v['typeEmoij'] = "";
                    $v['emoijId'] = 0;
                }

                if($messageId != 0) {
                    $v['parentMessageArr'] = ChatMessages::getArrayMessageParent($messageId, $user['use_id']);
                }
                else {
                    $v['parentMessageArr'] = null;
                }

            }
        }

        return response([
            'msg' => Lang::get('response.success'),
            'data' => $results
        ]);
    }

    public function listMessages(Request $req) {
        $groupChat = $req->groupChatId;
        $infoGroup = ChatThreads::where(['id' => $groupChat])->first();
        if($infoGroup['type'] == 'secret') {
            $user = $req->user();
            $groupChat = $req->groupChatId;
            $page = $req->page;
            $pageSize = $req->limit;
            ChatUserRead::setRead($user->use_id, $groupChat);
            $infoGroup = ChatThreads::where(['id' => $groupChat])->first();
            $params = ['threadId' => $groupChat, 'userLogin' => $user['use_id'],'lastIdMessage' => $infoGroup['lastIdMessage'], 'idDelete' => $infoGroup['idDelete']];
            $list = ChatMessages::getListSecretAlias($page, $pageSize, $params);
            $results = $list->paginate($pageSize);
            if(count($results) > 0 ) {
                $statusLastChat = null;
                /*if($infoGroup['type'] == 'secret') {
                     $statusLastChat = ChatMessages::statusLastChat($infoGroup);

                }*/

                foreach( $results as $k => &$v) {
                    $v['created_ts'] = Commons::convertDateTotime($v['createdAt']);
                    $v['updated_ts'] = Commons::convertDateTotime($v['updatedAt']);
                    if($v['typedata'] == 'multiImage') {
                        $v['listImage'] = json_decode($v['listImage'], true);
                    }
                    else {
                        $v['listImage'] = [];
                    }
                    if($v['typedata'] == 'product') {
                        $infoProduct = ChatMessages::getDetailProduct($v['productId']);
                        $v['infoProduct'] = $infoProduct;
                    }
                    else {
                        $v['infoProduct'] = null;
                    }

                    if($v['typedata'] == 'share_location' ) {
                        $created_share = $v['createdAt'];
                        $share_time = $v['share_time'];
                        $time_submius = Carbon::now()->subMinutes($share_time);
                        if($created_share < $time_submius) {
                            $v['stop_share'] = true;
                            $v['text'] = "Chia sẻ vị trí đã kết thúc";
                        }
                        else {
                            $v['stop_share'] = false;
                        }
                    }
                    else {
                        $v['stop_share'] = null;
                    }

                    if($v['ownerId'] == $infoGroup['ownerId']) {
                        $statusLastChat = ChatMessages::statusReadOfUser($infoGroup, $v['id'],$infoGroup['requesterId']);
                    }
                    else {
                        $statusLastChat = ChatMessages::statusReadOfUser($infoGroup, $v['id'], $infoGroup['ownerId']);
                    }

                    if($statusLastChat){
                        $v['statusRead'] = $statusLastChat['statusRead'];
                        $v['timeRead'] = $statusLastChat['timeRead'];
                    }


                    $v['emoij'] = ChatMessageEmoij::getEmoijMessage($v['id']);

                    $infoEmoi = ChatMessageEmoij::infoChooseEmoij($v['id'], $user['use_id']);
                    if(count($infoEmoi) > 0) {
                        $v['chooseEmoij'] = 1;
                        $v['typeEmoij'] = $infoEmoi['emoij'];
                    }
                    else {
                        $v['chooseEmoij'] = 0;
                        $v['typeEmoij'] = "";
                    }

                }
            }

            return response([
                'msg' => Lang::get('response.success'),
                'data' => $results
            ]);
        }
        $user = $req->user();
        $userLogin = $user['use_id'];

        $page = $req->page;
        $pageSize = $req->limit;
        ChatUserRead::setRead($user->use_id, $groupChat);

        if($infoGroup['idGroupDefault'] == 0) {
            $params = ['threadId' => $groupChat, 'userLogin' => $user['use_id'],'lastIdMessage' => $infoGroup['lastIdMessage'], 'idDelete' => $infoGroup['idDelete']];
        }
        else {

            $params = ['threadId' => $groupChat, 'userLogin' => $user['use_id'],'lastIdMessage' => $infoGroup['lastIdMessage'], 'idDelete' => $infoGroup['idDelete']];
        }

        $list = ChatMessages::getListAlias_V2($page, $pageSize, $params);

        $results = $list->paginate($pageSize);
        if(count($results) > 0 ) {
            $statusLastChat = null;
            if($infoGroup['type'] == 'private') {
                 $statusLastChat = ChatMessages::statusLastChat($infoGroup);

            }
            if($statusLastChat == null) {
                $statusLastChat = 0;
            }
            foreach( $results as $k => &$v) {
                                $v->created_ts = Commons::convertDateTotime($v->createdAt);
                $v->updated_ts = Commons::convertDateTotime($v->updatedAt);
                if($v->typedata == 'multiImage') {
                    $v->listImage = json_decode($v->listImage, true);
                }
                else {
                    $v->listImage = [];
                }
                if($v->typedata == 'product') {
                    $infoProduct = ChatMessages::getDetailProduct($v->productId);
                    $v->infoProduct = $infoProduct;
                }
                else {
                    $v->infoProduct = null;
                }

                if($v->typedata == 'share_location' ) {
                    $created_share = $v->createdAt;
                    $share_time = $v->share_time;
                    $time_submius = Carbon::now()->subMinutes($share_time);
                    if($created_share < $time_submius) {
                        $v->stop_share = true;
                        $v->text = "Chia sẻ vị trí đã kết thúc";
                    }
                    else {
                        $v->stop_share = false;
                    }
                }
                else {
                    $v->stop_share = null;
                }
                $messageId = $v->messageId;
                if($messageId != 0) {

                    $v->parentMessage = null;
                }
                else {
                    $v->parentMessage = null;
                }
                if($infoGroup->type != 'private') {
                    $statusLastChat = ChatMessages::statusReadOfMessage($infoGroup, $v->id);
                }
                $user_receive = $v->user_receive;
                $v->user_receive_name = null;
                if($user_receive != 0) {
                    $info_user_receive = ChatThreadUser::getInfoUserAlias($user_receive, $user['use_id']);
                    if($info_user_receive) {
                        $v->user_receive_name = $info_user_receive['use_fullname'];
                    }
                }
                $v->statusRead = $statusLastChat;
                $v->emoij = ChatMessageEmoij::getEmoijMessage($v->id);
                $infoEmoi = ChatMessageEmoij::infoChooseEmoij($v->id, $user['use_id']);
                if(count($infoEmoi) > 0) {
                    $v->chooseEmoij = 1;
                    $v->typeEmoij = $infoEmoi['emoij'];
                    $v->emoijId = $infoEmoi['emoijId'];
                }
                else {
                    $v->chooseEmoij = 0;
                    $v->typeEmoij = "";
                    $v->emoijId = 0;
                }

                $v->countElement = 0;
                if($v->element != null  && $v->element != "") {
                    $v->element = json_decode($v->element, true);
                }
                if($messageId != 0) {
                    $v->parentMessageArr = ChatMessages::getArrayMessageParent($messageId, $user['use_id'], $v->element);
                    if(count($v->parentMessageArr) >= 2 && $v->element != null) {  
                        $v->countElement = 1;    
                    }   
                    
                    /*$parentMessId =  $v['parentMessId'];
                    if($parentMessId == 0) {
                        $arrLevel = ChatMessages::getArrElementChild($v['messageId'], $user['use_id']);
                        if(count($arrLevel) == 1) {
                            $arrLevelId = [$v['messageId']];
                        }
                        else {
                            $arrLevelId = [$v['messageId'], $arrLevel[1]];
                        }

                    }
                    else {
                        $arrLevel = ChatMessages::getArrElementChild($parentMessId, $user['use_id']);
                        if(count($arrLevel) == 1) {
                            $arrLevelId = [$parentMessId];
                        }
                        else {
                            $arrLevelId = [$parentMessId, $arrLevel[1]];
                        }
                    }
                    $v['parentMessageArr'] = ChatMessages::getMessageParentRecuriseLevel($arrLevelId);
                    */


                }
                else {
                    $v->parentMessageArr = null;
                }

                $parentMessId  = $v->parentMessId;
                if($parentMessId != 0) {
                    $infoSubname = ChatMessages::where("id", $parentMessId)->pluck("subjectName")->first();
                    if($infoSubname) {
                        $v->subjectName = $infoSubname;
                    }

                }

                if($v->message_repeat == 1) {
                    if($v->ownerId == $userLogin && $v->parentIdRepeat == 0 ) {
                        $v->status_repeat = 0;
                    }
                    else {
                        $v->status_repeat = 1;
                    }
                }
                else {
                   $v->status_repeat = 0;
                }

                

            }
        }

        return response([
            'msg' => Lang::get('response.success'),
            'data' => $results
        ]);


    }

    public function setCountMessage(Request $req) {
        $user = $req->user();
        $groupChatId  = $req->groupChatId;
        ChatUserRead::setRead($user->use_id, $groupChatId);
        return response([
                'msg' => Lang::get('response.success'),
                'status' => 200,
                'data' => $groupChatId
        ]);

    }

    public function stopShareLocation(Request $req) {
        $user = $req->user();
        $messageId = $req->messageId;
        $groupChatId = $req->groupChatId;
        $time_stop_share = Carbon::now();
        $update = ChatMessages::where("id", $messageId)->update(['share_time' => 0, 'time_stop_share' => $time_stop_share]);
        $listUser = ChatThreadUser::where(['threadId' => $groupChatId, 'accept_request' => 1])->select("userId")->pluck("userId");
        $info = ChatMessages::getDetailMesssage($messageId, $user['use_id']);
        if($info) {
            $info = $info->toArray();
        }
        else {
            $info = [];
        }

        return response([
            'msg' => Lang::get('response.success'),
            'status' => 200,
            'data' => $info,
            'listUser' => $listUser
        ]);

    }

    public function checkShareLocation(Request $req) {
        $user = $req->user();
        $lastMessageLocation = ChatMessages::where(['threadId' => $req->threadId, 'ownerId' => $user['use_id'], 'typedata' => 'share_location'])->select("time_stop_share", "id", "share_lat","share_lng","share_time","createdAt")->orderby("id","desc")->limit(0,1)->first();
        $arrReturn  = [
            'id' => 0,
            'share_lat' => 0,
            'share_lng' => 0,
            "share_time" => 0,
            "created_ts" => 0
        ];
        if($lastMessageLocation) {
            $time_stop_share  = $lastMessageLocation->time_stop_share;
            $share_time  = $lastMessageLocation->share_time;
            $date_now =  Carbon::now();
            if($share_time == 0)  {
                return response([
                    'msg' => Lang::get('response.success'),
                    'data' => $arrReturn
                ]);
            }
            else {
                if($time_stop_share < $date_now) {
                    return response([
                        'msg' => Lang::get('response.success'),
                        'data' => $arrReturn
                    ]);
                }
                else {
                    $arrReturn = [
                            "id" => $lastMessageLocation->id,
                            "share_lat" => doubleval($lastMessageLocation->share_lat),
                            "share_lng" => doubleval($lastMessageLocation->share_lng),
                            "share_time" => $lastMessageLocation->share_time,
                            "created_ts" => Commons::convertDateTotime( $lastMessageLocation->createdAt),

                    ];
                    return response([
                        'msg' => Lang::get('response.success'),
                        'data' => $arrReturn
                    ]);
                }
            }


        }
        else {
            return response([
                'msg' => Lang::get('response.success'),
                'data' => $arrReturn
            ]);
        }

    }


    public function  detailMessage(Request $req) {
        $id = $req->id;
        $user = $req->user();
        $userLogin = $user['use_id'];
        $v = ChatMessages::join("tbtt_user","tbtt_user.use_id","=","chatmessages.ownerId")
                ->leftJoin(DB::raw("(select * from chatuseralias where userId = $userLogin) as chatuseralias"), function($join) {
                            $join->on('chatmessages.ownerId', '=', 'chatuseralias.userId_alias');
                    })
                ->where("chatmessages.id","=", $id)
                ->select("tbtt_user.avatar","tbtt_user.use_username",DB::raw('(CASE WHEN chatuseralias.name_alias is null THEN tbtt_user.use_fullname ELSE chatuseralias.name_alias END) AS use_fullname'), "chatmessages.*" )
                ->first();
        if($v) {
            $infoGroup = ChatThreads::where(['id' => $v['threadId']])->first();
            $v['created_ts'] = Commons::convertDateTotime($v['createdAt']);
            $v['updated_ts'] = Commons::convertDateTotime($v['updatedAt']);
            if($v['typedata'] == 'multiImage') {
                $v['listImage'] = json_decode($v['listImage'], true);
            }
            else {
                $v['listImage'] = [];
            }
            if($v['typedata'] == 'product') {
                $infoProduct = ChatMessages::getDetailProduct($v['productId']);
                $v['infoProduct'] = $infoProduct;
            }
            else {
                $v['infoProduct'] = null;
            }

            if($v['typedata'] == 'share_location' ) {
                $created_share = $v['createdAt'];
                $share_time = $v['share_time'];
                $time_submius = Carbon::now()->subMinutes($share_time);
                if($created_share < $time_submius) {
                    $v['stop_share'] = true;
                }
                else {
                    $v['stop_share'] = false;
                }
            }

            if($v['element'] != null) {
                    $v['element'] = json_decode( $v['element'], true);
            }

            $messageId = $v['messageId'];
            /*if($messageId != 0) {
                $infoMessage = ChatMessages::getDetailMesssageParent($messageId, $user['use_id']);
                $v['parentMessage'] = $infoMessage;
            }
            else {
                $v['parentMessage'] = null;
            }*/
            if($v['ownerId'] == $infoGroup['ownerId']) {
                $statusLastChat = ChatMessages::statusReadOfUser($infoGroup, $v['id'],$infoGroup['requesterId']);
            }
            else {
                $statusLastChat = ChatMessages::statusReadOfUser($infoGroup, $v['id'], $infoGroup['ownerId']);
            }

            if($statusLastChat){
                $v['statusRead'] = $statusLastChat['statusRead'];
                $v['timeRead'] = $statusLastChat['timeRead'];
            }

            $v['emoij'] = ChatMessageEmoij::getEmoijMessage($v['id']);
            $infoEmoi = ChatMessageEmoij::infoChooseEmoij($v['id'], $user['use_id']);
            if(count($infoEmoi) > 0) {
                $v['chooseEmoij'] = 1;
                $v['typeEmoij'] = $infoEmoi['emoij'];
                $v['emoijId'] = $infoEmoi['emoijId'];
            }
            else {
                $v['chooseEmoij'] = 0;
                $v['typeEmoij'] = "";
                $v['emoijId'] = 0;
            }

            if($messageId != 0) {
                $v['parentMessageArr'] = ChatMessages::getArrayMessageParent($messageId, $user['use_id']);
            }
            else {
                $v['parentMessageArr'] = null;
            }
            return response([
                'msg' => Lang::get('response.success'),
                'data' => $v
            ]);
        }
        return response([
            'msg' => Lang::get('response.success'),
            'data' => null
        ]);

    }

    public function listUserGroup(Request $req) {
        $user = $req->user();
        $group = $req->groupChatId;
        $infoGroup = ChatThreads::where(['id' => $group])->first();
        $typegroup = $infoGroup->typegroup;
        $pageSize = $req->limit;
        $page = $req->page;
        $params = ['threadId' => $group, 'userId' => $user['use_id']];
        if($typegroup == 'default') {
            switch ($infoGroup['alias']) {
                case 'agent':
                    $results = $this->getListBrandPagination( $user['use_id'] ,$req);
                    break;

                case 'afflliate':
                    $results = $this->listAllaffiliateUnderPagination($req);
                    break;

                case 'staff':
                    $results = $this->listStaffPagination( $user['use_id'] ,$req);

                    break;

                case 'customer_bought':
                    $results = $this->customer_boughtpagination($user['use_id'], $req);
                    break;

                case 'customer_sell':
                    # code...
                    $results = [];
                    break;

                case 'user_follow':
                    $results = ChatThreads::getUserGroupFolowPagination(['userId' => $user['use_id'], 'limit' => $pageSize, 'page' => $pageSize]);
                    $results = $results->paginate($pageSize, [], 'page', $page);
                    break;

                default:
                    # code...
                    break;
            }
        }
        else {
            /*$list = ChatThreadUser::getListUserOfGroup($page, $pageSize, $params);*/
            $list = ChatThreadUser::getListUserOfGroupAlias($page, $pageSize, $params);
            $results = $list->paginate($pageSize);
        }
        foreach( $results as $k => &$v) {
            $v['userCreateGroup'] = $infoGroup['ownerId'];
        }
        return response([
            'msg' => Lang::get('response.success'),
            'data' => $results
        ]);

    }

    public function listGroupDefault(Request $req) {
        // group default
        $user = $req->user();
        $use_group = $user['use_group'];
        $userId = $user['use_id'];
        $check = DB::table('chatthreads')->where(['ownerId' => $userId, 'typegroup' => 'default'])->exists();
        if(!$check) {
            switch ($use_group) {
                case '3':
                    //shop

                    $arrName = [
                                   ['name' => 'Cộng tác viên của tôi', 'alias' => 'afflliate', 'avatar' => 'icon_default/ic_gianhang_menu.png' ],
                                   ['name' => 'Nhân viên', 'alias' => 'staff', 'avatar' => 'icon_default/ic_nhanvien.png' ],
                                   ['name' => 'Chi nhánh', 'alias' => 'agent' , 'avatar' => 'icon_default/ic_search_group.png'],
                                   ['name' => 'Khách hàng đã mua', 'alias' => 'customer_bought', 'avatar' => 'icon_default/cart.png' ],
                                   ['name' => 'Người chọn bán hàng cho mình', 'alias' => 'customer_sell', 'avatar' => 'icon_default/ic_search_all_product.png' ],

                                ];
                    foreach( $arrName as $k => $v ) {
                        $arrData = ['type' => 'group',
                                     'ownerId' =>  $userId,
                                     'namegroup' => $v['name'],
                                     'alias' => $v['alias'],
                                     'avatar' => $v['avatar'],
                                     'typegroup' => 'default',
                                     'typechat' => 1,
                                     'requesterId' => 0,
                                     'createdAt'=> date('Y-m-d H:i:s'),
                                     'updatedAt'=> date('Y-m-d H:i:s')
                                    ];
                        $threadId  = ChatThreads::createGroup($arrData);
                    }

                    break;
                case '2':

                    $arrName = [
                                   ['name' => 'Khách hàng đã mua', 'alias' => 'customer_bought', 'avatar' => 'icon_default/cart.png' ],
                                ];
                    foreach( $arrName as $k => $v ) {
                        $arrData = ['type' => 'group',
                                     'ownerId' =>  $userId,
                                     'namegroup' => $v['name'],
                                     'alias' => $v['alias'],
                                     'avatar' => $v['avatar'],
                                     'typegroup' => 'default',
                                     'typechat' => 1,
                                     'requesterId' => 0,
                                     'createdAt'=> date('Y-m-d H:i:s'),
                                     'updatedAt'=> date('Y-m-d H:i:s')
                                    ];
                        $threadId  = ChatThreads::createGroup($arrData);
                    }
                    break;

                case '15':
                    $arrName = [
                                   ['name' => 'Cộng tác viên của tôi', 'alias' => 'afflliate' , 'avatar' => 'icon_default/ic_gianhang_menu.png'],
                                   ['name' => 'Chi nhánh', 'alias' => 'agent', 'avatar' => 'icon_default/ic_search_group.png' ],

                                ];
                    foreach( $arrName as $k => $v ) {
                        $arrData = ['type' => 'group',
                                     'ownerId' =>  $userId,
                                     'namegroup' => $v['name'],
                                     'alias' => $v['alias'],
                                     'avatar' => $v['avatar'],
                                     'typegroup' => 'default',
                                     'typechat' => 1,
                                     'requesterId' => 0,
                                     'createdAt'=> date('Y-m-d H:i:s'),
                                     'updatedAt'=> date('Y-m-d H:i:s')
                                    ];
                        $threadId  = ChatThreads::createGroup($arrData);
                    }
                    break;

                default:

                    break;
            }
        }

        $checkFollowGroup = DB::table('chatthreads')->where(['ownerId' => $userId, 'typegroup' => 'default', 'alias' => 'user_follow' ])->exists();
        if(!$checkFollowGroup) {
            $arrData = ['type' => 'group',
                                     'ownerId' =>  $userId,
                                     'namegroup' => "Nhóm người theo dõi",
                                     'alias' => 'user_follow',
                                     'avatar' => 'icon_default/ic_follow.png',
                                     'typegroup' => 'default',
                                     'typechat' => 1,
                                     'requesterId' => 0,
                                     'createdAt'=> date('Y-m-d H:i:s'),
                                     'updatedAt'=> date('Y-m-d H:i:s')
                                    ];
            $threadId  = ChatThreads::createGroup($arrData);
        }

        $list = ChatThreads::getListThreadDefault($userId);
        return response([
            'msg' => Lang::get('response.success'),
            'data' => $list
        ]);
    }

    public function acceptJoinRoom(Request $req) {
        $user = $req->user();
        $groupChatId = $req->groupChatId;
        $valueRequest = $req->valueRequest;
        if($valueRequest == -1) {
            //ChatThreadUser::where(['userId' => $user['use_id'], 'threadId' => $groupChatId])->delete();
            DB::table('chatthreaduser')->where(['userId' => $user['use_id'], 'threadId' => $groupChatId])->delete();
        }
        else {
            /*ChatThreadUser::where(['userId' => $user['use_id'], 'threadId' => $groupChatId])->update(['accept_request' => $valueRequest ]);*/
            DB::table('chatthreaduser')->where(['userId' => $user['use_id'], 'threadId' => $groupChatId])->update(['accept_request' => $valueRequest ]);

        }
        ChatUserInvite::minusCountInvite($user['use_id']);
        /*$infoGroup = ChatThreads::getDetailThread($groupChatId, $user['use_id']);*/
        $infoGroup = ChatThreads::getDetailThreadAlias($groupChatId, $user['use_id']);
        return response([
            'msg' => Lang::get('response.success'),
            'data' => $infoGroup,
            'groupChatId' => $groupChatId,
            'userId' =>  $infoGroup['use_id']
        ]);
    }

    public function createGroupChat(Request $req) {
        $namegroup = $req->namegroup;
        $user = $req->user();
        $listUserJoinGroup = $req->listUser;
        $typechat = 2;
        if( isset($req->typechat)) {
            $typechat = $req->typechat;
        }
        $arrUserDefault = [];
        if( isset($req->groupDefault)) {
            $infoThreadDefault = ChatThreads::where("id", "=",$req->groupDefault )->first();
            if($infoThreadDefault) {
                switch ($infoThreadDefault['alias']) {
                    case 'agent':
                        $listUser = $this->getListBrand( $user['use_id'] ,$req);
                        foreach($listUser as $k => $v) {
                            $arrUserDefault[] = $v['use_id'];
                            ChatUserInvite::updateCountInvite($v['use_id']);
                        }

                        break;

                    case 'afflliate':
                        # code...
                        $listUser = $this->listAllaffiliateUnder($req);
                        foreach($listUser as $k => $v) {
                            $arrUserDefault[] = $v['use_id'];
                            ChatUserInvite::updateCountInvite($v['use_id']);
                        }
                        break;

                    case 'staff':
                        $listUser = $this->listStaff( $user['use_id'] ,$req);
                        foreach($listUser as $k => $v) {
                            $arrUserDefault[] = $v['use_id'];
                            ChatUserInvite::updateCountInvite($v['use_id']);
                        }
                        break;

                    case 'customer_bought':
                        $listUser = $this->customer_bought($user['use_id'], $req);
                        foreach($listUser as $k => $v) {
                            $arrUserDefault[] = $v['use_id'];
                            ChatUserInvite::updateCountInvite($v['use_id']);
                        }
                        break;

                    case 'customer_sell':
                        # code...
                        break;

                    case 'user_follow':
                        $listUser = ChatThreads::getUserGroupFolow(['userId' =>$user['use_id']]);
                        foreach($listUser as $k => $v) {
                            $arrUserDefault[] = $v['use_id'];
                            ChatUserInvite::updateCountInvite($v['use_id']);
                        }

                        break;


                    default:
                        # code...
                        break;
                }
            }
        }
        $avatar = "";
        if( isset($req->avatar)) {
            $avatar = $req->avatar;
        }
        $arrData = ['type' => 'group',
                     'ownerId' =>  $user->use_id,
                     'namegroup' => $namegroup,
                     'avatar' => $avatar,
                     'typegroup' => '' ,
                     'typechat' => $typechat,
                     'requesterId' => 0,
                     'createdAt'=> date('Y-m-d H:i:s'),
                     'updatedAt'=> date('Y-m-d H:i:s')
                    ];
        $threadId  = ChatThreads::createGroup($arrData);
        if( $threadId) {
            $listUserJoinGroup = array_diff($listUserJoinGroup, $arrUserDefault);
            //array_push($listUserJoinGroup, $user->use_id );
            ChatThreadUser::addUserToGroup($threadId, [$user->use_id], 1, 1);
            if( count($listUserJoinGroup) > 0 ) {
                ChatThreadUser::addUserToGroup($threadId, $listUserJoinGroup);
                /*$dataPushnotification = [
                                        'userIds' => $listUserJoinGroup,
                                        'ownerName' => $user->use_username,
                                        'groupName' => $namegroup
                                ];
                dispatch(new SendChatNotification('join-group',$dataPushnotification));*/
            }
            if( count($arrUserDefault) > 0  &&  $typechat == 1) {
                ChatThreadUser::addUserToGroup($threadId, $arrUserDefault, 1);
            }
            else {
                ChatThreadUser::addUserToGroup($threadId, $arrUserDefault);
            }
            /*$detailThread = ChatThreads::getDetailThread($threadId, $user->use_id);*/
            $detailThread = ChatThreads::getDetailThreadAlias($threadId, $user->use_id);
            foreach( $listUserJoinGroup as $k => $v ) {
                ChatUserInvite::updateCountInvite($v);
            }
            return response([
                'msg' => Lang::get('response.success'),
                'data' => $detailThread,
                'listUserJoinGroup' => $listUserJoinGroup,
                'userId' => $user['use_id'],
                'arrUserDefault' => $arrUserDefault,
                'typechat' => $req->typechat
            ]);
        }
        return response([
                'msg' => Lang::get('response.failed'),

        ]);
    }


    public function createPrivateChat(Request $req) {
        $user = $req->user();
        $ownerId = $req->ownerId;
        $memberId = $req->memberId;
        $data = ChatThreads::where(function ($query) use ($ownerId, $memberId)  {
                     $query->where(['ownerId' => $ownerId, 'requesterId' => $memberId])
                            ->orWhere(['ownerId' => $memberId, 'requesterId' => $ownerId])
                     ;
                })->where("type","=","private")->first();
        if($data) {
            /*$detailThread = ChatThreads::detailGroupPrivate($user['use_id'], $data['id']);*/
            $detailThread = ChatThreads::detailGroupPrivateAlias($user['use_id'], $data['id']);
            return response([
                'msg' => Lang::get('response.success'),
                'data' => ['thread' => $detailThread, 'ownerId' => $ownerId, 'memberId' => $memberId]

            ]);
        }
        else {
            $arrData = ['type' => 'private',
                     'ownerId' =>  $ownerId,
                     'namegroup' => '',
                     'requesterId' => $memberId,
                     'typegroup' => '',
                     'avatar' => '',
                     'alias' => '',
                     'createdAt' => date('Y-m-d H:i:s'),
                     'updatedAt' => date('Y-m-d H:i:s'),
                    ];

            $threadId  = ChatThreads::createGroup($arrData);
            if( $threadId) {
                $arr = [$ownerId, $memberId];
                ChatThreadUser::addUserToGroup($threadId, $arr, 1 );
                //$data = ChatThreads::where("id","=", $threadId)->first();
                /*$detailThread = ChatThreads::detailGroupPrivate($user['use_id'], $threadId);*/
                $detailThread = ChatThreads::detailGroupPrivateAlias($user['use_id'], $threadId);
                return response([
                    'msg' => Lang::get('response.success'),
                    'data' => ['thread' => $detailThread, 'ownerId' => $ownerId, 'memberId' => $memberId]

                ]);
            }
            else {
                return response([
                        'msg' => Lang::get('response.failed'),

                ]);
            }
        }
    }



    public function addUserToGroup(Request $req) {
        $user = $req->user();
        $groupChatId = $req->groupChatId;
        $listUser = $req->userId;
        try {
            ChatThreadUser::addUserToGroup($groupChatId,$listUser);
            $user['groupChatId'] = $groupChatId;
            foreach($listUser as $k => $v ) {
                ChatUserInvite::updateCountInvite($v);
            }
            /*$detailThread = ChatThreads::getDetailThread($groupChatId, $user['use_id']);*/
            $detailThread = ChatThreads::getDetailThreadAlias($groupChatId, $user['use_id']);
            return response([
                'msg' => Lang::get('response.success'),
                'data' => $detailThread,
                'listUserJoinGroup' => $listUser
            ]);
        }catch(Exception $e) {
            return response([
                'msg' => Lang::get('response.failed'),
            ]);
        }
    }


    public function sendMedia(Request $req) {
        $rule = [
            'file'=>[
                'required',
            ],
            //'type'=>'image|mimes:jpg,png,gif,jpeg',
            'dir_image'=>'string'
        ];
        $validator = Validator::make($req->all(), $rule);
        if ($validator->fails()) {
            return response([
                'msg' => $validator->errors()->first(),
                'errors' => $validator->errors()
                ], 422);
        }
        $dir_image = 'chat_media';
        $fileName = Utils::randomFilename() . '.' . $req->file->extension(); // renameing image
        //FTP
        $pathFTP = Utils::getUploadsRootFTP('chat_media', $dir_image);
        Utils::uploadFileToFTP($req->file, $pathFTP.DIRECTORY_SEPARATOR.$fileName);

        /*$pathUpload = Utils::getUploadsRoot('chat_media', $dir_image);
        $req->file->move($pathUpload, $fileName);
        $size = getimagesize($pathUpload . DIRECTORY_SEPARATOR. $fileName);
        if($size) {
            $width=$size[0];
            $height=$size[1];
            if($width > 1024) {
                $image_resize = Image::make($pathUpload . DIRECTORY_SEPARATOR. $fileName);
                $image_resize->resize(1024, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $image_resize->save($pathUpload . DIRECTORY_SEPARATOR  . $fileName, null);
            }
        }*/

        return response([
            'msg' => Lang::get('response.success'),
            'data' => [
                'file' => $fileName,
                'dir_file' => $dir_image,
                'type' => $req->type

            ]
        ]);
    }

    public function customer_bought($userId, $req) {
        $userdb = User::tableName();
        $from_view = '( SELECT tbtt_order.order_user, COUNT(tbtt_order.id) '
            . ' FROM tbtt_order '
            . ' INNER JOIN tbtt_showcart ON tbtt_showcart.shc_orderid = tbtt_order.id '
            . ' WHERE tbtt_showcart.shc_saler = ' .$userId
            . ' GROUP BY tbtt_order.id ) AS from_view';

        $query = User::select(DB::raw('count(from_view.order_user) as count_order'),'from_view.order_user', DB::raw($userdb.'.use_id, '
            . $userdb.'.use_username,'.$userdb.'.use_email,'.$userdb.'.use_address,'.$userdb.'.use_fullname,'.$userdb.'.use_mobile'));
        $query->from(DB::raw($from_view));
        $query->join($userdb, 'tbtt_user.use_id', 'from_view.order_user');
        $query->groupBy('order_user');
        $query->orderBy('order_user','DESC');
        if ($req->use_username) {
            $query->where($userdb . '.use_username', 'LIKE', '%' . $req->use_username . '%');
        }
        if ($req->use_fullname) {
            $query->where($userdb . '.use_fullname', 'LIKE', '%' . $req->use_fullname . '%');
        }

        if ($req->use_fullname) {
            $query->where($userdb . '.use_fullname', 'LIKE', '%' . $req->use_fullname . '%');
        }
        if ($req->use_mobile) {
            $query->where($userdb . '.use_mobile', 'LIKE', '%' . $req->use_mobile . '%');
        }
        /*$limit = $req->limit ? (int) $req->limit : 10;
        $page = $req->page ? (int) $req->page : 0;

        $results = $query->paginate($limit, ['*'], 'page', $page);
        return $results;*/
        $data = $query->get()->toArray();
        return $data;

    }


    public function customer_boughtpagination($userId, $req) {
        $userdb = User::tableName();
        $from_view = '( SELECT tbtt_order.order_user, COUNT(tbtt_order.id) '
            . ' FROM tbtt_order '
            . ' INNER JOIN tbtt_showcart ON tbtt_showcart.shc_orderid = tbtt_order.id '
            . ' WHERE tbtt_showcart.shc_saler = ' .$userId
            . ' GROUP BY tbtt_order.id ) AS from_view';

        $query = User::select(DB::raw('count(from_view.order_user) as count_order'),'from_view.order_user', DB::raw($userdb.'.use_id, '
            . $userdb.'.use_username,'.$userdb.'.use_email,'.$userdb.'.use_address,'.$userdb.'.use_fullname,'.$userdb.'.use_mobile'));
        $query->from(DB::raw($from_view));
        $query->join($userdb, 'tbtt_user.use_id', 'from_view.order_user');
        $query->groupBy('order_user');
        $query->orderBy('order_user','DESC');
        if ($req->use_username) {
            $query->where($userdb . '.use_username', 'LIKE', '%' . $req->use_username . '%');
        }
        if ($req->use_fullname) {
            $query->where($userdb . '.use_fullname', 'LIKE', '%' . $req->use_fullname . '%');
        }

        if ($req->use_fullname) {
            $query->where($userdb . '.use_fullname', 'LIKE', '%' . $req->use_fullname . '%');
        }
        if ($req->use_mobile) {
            $query->where($userdb . '.use_mobile', 'LIKE', '%' . $req->use_mobile . '%');
        }
        $limit = $req->limit ? (int) $req->limit : 10;
        $page = $req->page ? (int) $req->page : 0;
        $query->leftJoin(DB::raw("(select * from chatuseralias where userId = $userId) as chatuseralias"), function($join) {
                         $join->on('tbtt_user.use_id', '=', 'chatuseralias.userId_alias');
                })
        ->select("tbtt_user.use_id as use_id","tbtt_user.avatar","tbtt_user.use_username",
                    DB::raw('(CASE WHEN chatuseralias.name_alias is null THEN tbtt_user.use_fullname ELSE chatuseralias.name_alias END) AS use_fullname'));

        $results = $query->paginate($limit, ['*'], 'page', $page);
        return $results;

    }


    public function getListBrand($id, $req) {
        $shopdb = (new Shop)->getTable();
        $userdb = (new User)->getTable();
        $query = User::where(['use_status' => User::STATUS_ACTIVE, 'use_group' => User::TYPE_BranchUser]);
        if ($req->createdByMeOnly) {
            $query->whereIn('parent_id', $id);
        }
        $query->whereIn('parent_id', function($q) use($id) {
                $q->select('use_id');
                $q->from((new User)->getTable());
                $q->where(function($q2) use ($id) {
                    $q2->whereIn('use_group', [User::TYPE_StaffStoreUser]);
                    $q2->where(['parent_id' => $id]);
                });
                $q->orWhere('use_id', $id);
            })->join($shopdb, $shopdb . '.sho_user', $userdb . '.use_id')
            ->with('shop')->withCount('affNumber');

        if (!empty($req->orderBy)) {
            $req->orderBy = explode(',', $req->orderBy);
            $key = $req->orderBy[0];
            $value = $req->orderBy[1] ? $req->orderBy[1] : 'DESC';
            $query->orderBy($key, $value);
        } else {
            $query->orderBy('use_fullname', 'ASC');
        }

        if (!empty($req->keywords)) {
            $query->where(function($q) use ($req) {
                $q->orWhereRaw('LOWER(use_fullname) like ?', array('%' . mb_strtolower($req->keywords) . '%'));
                $q->orWhereRaw('LOWER(use_username) like ?', array('%' . mb_strtolower($req->keywords) . '%'));
                $q->orWhereRaw('LOWER(use_fullname) like ?', array('%' . mb_strtolower($req->keywords) . '%'));
                $q->orWhereRaw('LOWER(use_mobile) like ?', array('%' . mb_strtolower($req->keywords) . '%'));
                $q->orWhereRaw('LOWER(use_phone) like ?', array('%' . mb_strtolower($req->keywords) . '%'));
            });
        }
        if ($req->sum) {
            return $data['total'] = $query->count();
        }

        /*$limit = $req->limit ? (int) $req->limit : 1000;
        $page = $req->page ? (int) $req->page : 0;

        $paginate = $query->paginate($limit, ['tbtt_user.use_id'], 'page', $page);
        $results = $paginate->toArray();*/
        $data = $query->get()->toArray();
        return $data;

    }

    public function getListBrandPagination($id, $req) {
        $shopdb = (new Shop)->getTable();
        $userdb = (new User)->getTable();
        $query = User::where(['use_status' => User::STATUS_ACTIVE, 'use_group' => User::TYPE_BranchUser]);
        if ($req->createdByMeOnly) {
            $query->whereIn('parent_id', $id);
        }
        $query->whereIn('parent_id', function($q) use($id) {
                $q->select('use_id');
                $q->from((new User)->getTable());
                $q->where(function($q2) use ($id) {
                    $q2->whereIn('use_group', [User::TYPE_StaffStoreUser]);
                    $q2->where(['parent_id' => $id]);
                });
                $q->orWhere('use_id', $id);
            })->join($shopdb, $shopdb . '.sho_user', $userdb . '.use_id')
            ->leftJoin(DB::raw("(select * from chatuseralias where userId = $id) as chatuseralias"), function($join) {
                         $join->on('tbtt_user.use_id', '=', 'chatuseralias.userId_alias');
                })
            ->select("tbtt_user.use_id as use_id","tbtt_user.avatar","tbtt_user.use_username",
                    DB::raw('(CASE WHEN chatuseralias.name_alias is null THEN tbtt_user.use_fullname ELSE chatuseralias.name_alias END) AS use_fullname'))
            ;
            //->with('shop')->withCount('affNumber');

        if (!empty($req->orderBy)) {
            $req->orderBy = explode(',', $req->orderBy);
            $key = $req->orderBy[0];
            $value = $req->orderBy[1] ? $req->orderBy[1] : 'DESC';
            $query->orderBy($key, $value);
        } else {
            $query->orderBy('use_fullname', 'ASC');
        }

        if (!empty($req->keywords)) {
            $query->where(function($q) use ($req) {
                $q->orWhereRaw('LOWER(use_fullname) like ?', array('%' . mb_strtolower($req->keywords) . '%'));
                $q->orWhereRaw('LOWER(use_username) like ?', array('%' . mb_strtolower($req->keywords) . '%'));
                $q->orWhereRaw('LOWER(use_fullname) like ?', array('%' . mb_strtolower($req->keywords) . '%'));
                $q->orWhereRaw('LOWER(use_mobile) like ?', array('%' . mb_strtolower($req->keywords) . '%'));
                $q->orWhereRaw('LOWER(use_phone) like ?', array('%' . mb_strtolower($req->keywords) . '%'));
            });
        }
        if ($req->sum) {
            return $data['total'] = $query->count();
        }
        $limit = $req->limit ? (int) $req->limit : 1000;
        $page = $req->page ? (int) $req->page : 0;
        $paginate = $query->paginate($limit, ["tbtt_user.use_id as use_id","tbtt_user.avatar","tbtt_user.use_username","tbtt_user.use_fullname"], 'page', $page);
        return $paginate;

    }

    public function listStaff($userId, $req) {
        $limit = $req->limit ? (int) $req->limit : 1000;
        $page = $req->page ? (int) $req->page : 0;
        $group = [User::TYPE_StaffUser,User::TYPE_StaffStoreUser];
        if($req->showAll){
            $group[] = User::TYPE_BranchUser;
        }
        $query = User::where(['use_status' => User::STATUS_ACTIVE, 'parent_id' => $userId])
            ->whereIn('use_group', $group);
        $query->withCount('affNumber')->withCount('branchNumber');
        if(!empty($req->keywords)){
             $query->where(function($q) use ($req) {
                $q->orWhereRaw('LOWER(use_fullname) like ?', array('%' . mb_strtolower($req->keywords) . '%'));
                $q->orWhereRaw('LOWER(use_username) like ?', array('%' . mb_strtolower($req->keywords) . '%'));
                $q->orWhereRaw('LOWER(use_fullname) like ?', array('%' . mb_strtolower($req->keywords) . '%'));
                $q->orWhereRaw('LOWER(use_mobile) like ?', array('%' . mb_strtolower($req->keywords) . '%'));
                $q->orWhereRaw('LOWER(use_phone) like ?', array('%' . mb_strtolower($req->keywords) . '%'));
            });
        }
        if (!empty($req->orderBy)) {
            $req->orderBy = explode(',', $req->orderBy);
            $key = $req->orderBy[0];
            $value = $req->orderBy[1] ? $req->orderBy[1] : 'DESC';
            $query->orderBy($key, $value);
        }else{
            $query->orderBy('use_regisdate','DESC');
        }
        /*$paginate = $query->paginate($limit, ['*'], 'page', $page);
        $results = $paginate->toArray();
        return $results['data'];*/
        $data = $query->get()->toArray();
        return $data;
    }

    public function listStaffPagination($userId, $req) {
        $limit = $req->limit ? (int) $req->limit : 1000;
        $page = $req->page ? (int) $req->page : 0;
        $group = [User::TYPE_StaffUser,User::TYPE_StaffStoreUser];
        if($req->showAll){
            $group[] = User::TYPE_BranchUser;
        }
        $query = User::where(['use_status' => User::STATUS_ACTIVE, 'parent_id' => $userId])
            ->whereIn('use_group', $group);
        $query->withCount('affNumber')->withCount('branchNumber');
        if(!empty($req->keywords)){
             $query->where(function($q) use ($req) {
                $q->orWhereRaw('LOWER(use_fullname) like ?', array('%' . mb_strtolower($req->keywords) . '%'));
                $q->orWhereRaw('LOWER(use_username) like ?', array('%' . mb_strtolower($req->keywords) . '%'));
                $q->orWhereRaw('LOWER(use_fullname) like ?', array('%' . mb_strtolower($req->keywords) . '%'));
                $q->orWhereRaw('LOWER(use_mobile) like ?', array('%' . mb_strtolower($req->keywords) . '%'));
                $q->orWhereRaw('LOWER(use_phone) like ?', array('%' . mb_strtolower($req->keywords) . '%'));
            });
        }
        if (!empty($req->orderBy)) {
            $req->orderBy = explode(',', $req->orderBy);
            $key = $req->orderBy[0];
            $value = $req->orderBy[1] ? $req->orderBy[1] : 'DESC';
            $query->orderBy($key, $value);
        }else{
            $query->orderBy('use_regisdate','DESC');
        }
        $query->leftJoin(DB::raw("(select * from chatuseralias where userId = $userId) as chatuseralias"), function($join) {
                         $join->on('tbtt_user.use_id', '=', 'chatuseralias.userId_alias');
                })
        ->select("tbtt_user.use_id as use_id","tbtt_user.avatar","tbtt_user.use_username",
                    DB::raw('(CASE WHEN chatuseralias.name_alias is null THEN tbtt_user.use_fullname ELSE chatuseralias.name_alias END) AS use_fullname'));
        $paginate = $query->paginate($limit, ["tbtt_user.use_id as use_id","tbtt_user.avatar","tbtt_user.use_username","tbtt_user.use_fullname"], 'page', $page);
        return $paginate;
    }

    public function listAllaffiliateUnder($req) {
        $tree = [];
        $tree[] = $req->user()->id;
        $user = $req->user();
        $query = User::where(['use_status' => User::STATUS_ACTIVE, 'use_group' => User::TYPE_AffiliateUser]);
        $query->whereIn('parent_id', function($q) use ($user) {
            $q->select('use_id');
            $q->from((new User)->getTable());
            $q->where('use_status', User::STATUS_ACTIVE);
            $q->where(function($q2) use ($user) {
                $q2->whereIn('use_group', [User::TYPE_StaffStoreUser, User::TYPE_StaffUser, User::TYPE_BranchUser,
                    User::TYPE_AffiliateStoreUser,User::TYPE_Partner2User,User::TYPE_Partner1User,User::TYPE_Developer1User,User::TYPE_Developer2User]);
                $q2->where(['use_status' => User::STATUS_ACTIVE, 'parent_id' => $user->use_id]);
            });
            $q->orWhere(function($q) use($user) {
                $q->where('use_group', User::TYPE_StaffUser);
                $q->whereIn('parent_id', function($q) use($user) {
                    $q->select('use_id');
                    $q->from(User::tableName());
                    $q->where(function($q) use ($user) {
                        $q->where('use_group', User::TYPE_BranchUser);
                        $q->where(['use_status' => User::STATUS_ACTIVE, 'parent_id' => $user->use_id]);
                    });
                });
            });
            $q->orWhere(function($q) use($user) {
                $q->where('use_group', User::TYPE_StaffUser);
                $q->whereIn('parent_id', function($q) use($user){
                    $q->select('use_id');
                    $q->from(User::tableName());
                    $q->where('use_status', User::STATUS_ACTIVE);
                    $q->where('use_group', User::TYPE_BranchUser);
                    $q->whereIn('parent_id', function($q) use($user) {
                        $q->select('use_id');
                        $q->from(User::tableName());
                        $q->where('use_group', User::TYPE_StaffStoreUser);
                        $q->where('parent_id',$user->use_id);
                    });
                });
            });
            $q->orWhere(function($q) use($user) {
                $q->where('use_group', User::TYPE_BranchUser);
                $q->where('use_status', User::STATUS_ACTIVE);
                $q->whereIn('parent_id', function($q) use($user) {
                    $q->select('use_id');
                    $q->from(User::tableName());
                    $q->where('use_group', User::TYPE_StaffStoreUser);
                     $q->where('parent_id',$user->use_id);
                });
            });
            $q->orWhere('use_id', $user->use_id);
        });

        if (!empty($req->orderBy)) {
            $req->orderBy = explode(',', $req->orderBy);
            $key = $req->orderBy[0];
            $value = $req->orderBy[1] ? $req->orderBy[1] : 'DESC';
            $query->orderBy($key, $value);
        } else {
            $query->orderBy('use_regisdate', 'DESC');
        }

         if (!empty($req->keywords)) {
            $query->where(function($q) use ($req) {
                $q->orWhereRaw('LOWER(use_fullname) like ?', array('%' . mb_strtolower($req->keywords) . '%'));
                $q->orWhereRaw('LOWER(use_username) like ?', array('%' . mb_strtolower($req->keywords) . '%'));
                $q->orWhereRaw('LOWER(use_fullname) like ?', array('%' . mb_strtolower($req->keywords) . '%'));
                $q->orWhereRaw('LOWER(use_mobile) like ?', array('%' . mb_strtolower($req->keywords) . '%'));
                $q->orWhereRaw('LOWER(use_phone) like ?', array('%' . mb_strtolower($req->keywords) . '%'));
            });
        }

        $limit = $req->limit ? (int) $req->limit : 1000;
        $page = $req->page ? (int) $req->page : 0;

        /*$paginate = $query->paginate($limit, ['*'], 'page', $page);
        $result = $paginate->toArray();
        return $results['data'];*/
        $data = $query->get()->toArray();
        return $data;
    }


    public function listAllaffiliateUnderPagination($req) {
        $tree = [];
        $tree[] = $req->user()->id;
        $user = $req->user();
        $query = User::where(['use_status' => User::STATUS_ACTIVE, 'use_group' => User::TYPE_AffiliateUser]);
        $query->whereIn('parent_id', function($q) use ($user) {
            $q->select('use_id');
            $q->from((new User)->getTable());
            $q->where('use_status', User::STATUS_ACTIVE);
            $q->where(function($q2) use ($user) {
                $q2->whereIn('use_group', [User::TYPE_StaffStoreUser, User::TYPE_StaffUser, User::TYPE_BranchUser,
                    User::TYPE_AffiliateStoreUser,User::TYPE_Partner2User,User::TYPE_Partner1User,User::TYPE_Developer1User,User::TYPE_Developer2User]);
                $q2->where(['use_status' => User::STATUS_ACTIVE, 'parent_id' => $user->use_id]);
            });
            $q->orWhere(function($q) use($user) {
                $q->where('use_group', User::TYPE_StaffUser);
                $q->whereIn('parent_id', function($q) use($user) {
                    $q->select('use_id');
                    $q->from(User::tableName());
                    $q->where(function($q) use ($user) {
                        $q->where('use_group', User::TYPE_BranchUser);
                        $q->where(['use_status' => User::STATUS_ACTIVE, 'parent_id' => $user->use_id]);
                    });
                });
            });
            $q->orWhere(function($q) use($user) {
                $q->where('use_group', User::TYPE_StaffUser);
                $q->whereIn('parent_id', function($q) use($user){
                    $q->select('use_id');
                    $q->from(User::tableName());
                    $q->where('use_status', User::STATUS_ACTIVE);
                    $q->where('use_group', User::TYPE_BranchUser);
                    $q->whereIn('parent_id', function($q) use($user) {
                        $q->select('use_id');
                        $q->from(User::tableName());
                        $q->where('use_group', User::TYPE_StaffStoreUser);
                        $q->where('parent_id',$user->use_id);
                    });
                });
            });
            $q->orWhere(function($q) use($user) {
                $q->where('use_group', User::TYPE_BranchUser);
                $q->where('use_status', User::STATUS_ACTIVE);
                $q->whereIn('parent_id', function($q) use($user) {
                    $q->select('use_id');
                    $q->from(User::tableName());
                    $q->where('use_group', User::TYPE_StaffStoreUser);
                     $q->where('parent_id',$user->use_id);
                });
            });
            $q->orWhere('use_id', $user->use_id);
        });



        if (!empty($req->orderBy)) {
            $req->orderBy = explode(',', $req->orderBy);
            $key = $req->orderBy[0];
            $value = $req->orderBy[1] ? $req->orderBy[1] : 'DESC';
            $query->orderBy($key, $value);
        } else {
            $query->orderBy('use_regisdate', 'DESC');
        }

         if (!empty($req->keywords)) {
            $query->where(function($q) use ($req) {
                $q->orWhereRaw('LOWER(use_fullname) like ?', array('%' . mb_strtolower($req->keywords) . '%'));
                $q->orWhereRaw('LOWER(use_username) like ?', array('%' . mb_strtolower($req->keywords) . '%'));
                $q->orWhereRaw('LOWER(use_fullname) like ?', array('%' . mb_strtolower($req->keywords) . '%'));
                $q->orWhereRaw('LOWER(use_mobile) like ?', array('%' . mb_strtolower($req->keywords) . '%'));
                $q->orWhereRaw('LOWER(use_phone) like ?', array('%' . mb_strtolower($req->keywords) . '%'));
            });
        }

        $userId = $req->user()->use_id;
        $query->leftJoin(DB::raw("(select * from chatuseralias where userId = $userId) as chatuseralias"), function($join) {
                         $join->on('use_id', '=', 'chatuseralias.userId_alias');
                })->select("tbtt_user.use_id as use_id","tbtt_user.avatar","tbtt_user.use_username",
                    DB::raw('(CASE WHEN chatuseralias.name_alias is null THEN tbtt_user.use_fullname ELSE chatuseralias.name_alias END) AS use_fullname'));

        $limit = $req->limit ? (int) $req->limit : 1000;
        $page = $req->page ? (int) $req->page : 0;
        $paginate = $query->paginate($limit, ['*'], 'page', $page);
        return $paginate;


    }

    public function checkGetInfo(Request $req) {

        $data = $this->getListBrand($req->id,$req);
        echo '<pre>';
        print_r($data);


    }

    public function sendMessage(Request $req) {
        $user = $req->user();
        $messageId = 0;
        if( isset($req->messageId)) {
            $messageId = $req->messageId;
        }
        $width = 0;
        $height = 0;
        $size = "";
        $caption = "";
        if( isset($req->width)) {
            $width = $req->width;
        }
        if( isset($req->height)) {
            $height = $req->height;
        }
        if( isset($req->size)) {
            $size = $req->size;
        }
        if( isset($req->caption)) {
            $caption = $req->caption;
        }
        $textmessage = $req->text;
        if($req->typedata == 'multiImage') {
            $textmessage = json_encode($textmessage);
        }
        $productId = 0;
        if( isset($req->productId)) {
            $productId = $req->productId;
        }
        $public = 1;
        if( isset($req->public)) {
            $public = $req->public;
        }

        $timeDelete = 1;
        if( isset($req->timeDelete)) {
            $timeDelete = $req->timeDelete;
        }

        $share_lat = 0;
        $share_lng = 0;
        $share_time = 0;
        if( isset($req->share_lat)) {
            $share_lat = $req->share_lat;
        }
        if( isset($req->share_lng)) {
            $share_lng = $req->share_lng;
        }
        if( isset($req->share_time)) {
            $share_time = $req->share_time;
        }

        $idRemove = 0;
        $parentMessId = 0;
        if($messageId != 0 ) {
            //$parentMessId = ChatMessages::getArrayIDMessageParent($messageId);
            $info_p = ChatMessages::where("id", $messageId)->select("messageId", "parentMessId")->first();

            if($info_p) {
                $parentMessId = $info_p->parentMessId;
                if($parentMessId == 0) {
                    $parentMessId = $messageId;
                }
            }
            if($info_p->parentMessId == 0) {
                $arrRemove = ChatMessages::where("parentMessId", $messageId)->orderby("id","desc")->limit(2)->select("id")->get()->toArray();
                if(count($arrRemove) >= 2) {
                    $idRemove = $arrRemove[1]['id'];
                }
                else {
                    $idRemove = 0;
                }
            }
            else {
                $arrRemove = ChatMessages::where("parentMessId", $parentMessId)->select("id")->orderby("id","desc")->limit(3)->get();
                if(count($arrRemove) >= 2) {
                    $idRemove = $arrRemove[1]['id'];
                }
                else {
                    $idRemove = 0;
                }
            }

            /*$arrRemove = ChatMessages::getArrElementChild($parentMessId, $user['use_id'], 0,2);
            if(count($arrRemove) == 1) {
                $idRemove = 0;
            }
            else {
                $idRemove = $arrRemove[1];
            }
            */


        }
        else {
            $parentMessId = 0;
        }
        $time_repeat = $req->time_repeat;
        $message_repeat = $req->message_repeat;
        $content_repeat = $req->content_repeat;
        if($req->content_repeat != "") {
            $message_repeat = 1;
        }
        $time_set_repeat = Carbon::now()->addMinutes($time_repeat)->format('Y-m-d H:i:00');
        $element = null;
        if(isset($req->element) &&  $req->element != null) {
            $element = json_encode($req->element);
        }

        $time_stop_share = null;
        $arrData = ['type' => $req->type,
                    'ownerId' => $req->ownerId,
                    'threadId' => $req->threadId,
                    'text' => $textmessage,
                    'typedata' => $req->typedata,
                    'messageId' => $messageId,
                    'width' => intval($width),
                    'height' => intval( $height),
                    'size' => $size,
                    'createdAt' => date('Y-m-d H:i:s'),
                    'updatedAt' => date('Y-m-d H:i:s'),
                    'listImage' => json_encode($req->listImage),
                    'productId' => $productId,
                    'public' => $public,
                    'caption' => $caption,
                    'timeDelete' => $timeDelete,
                    'parentMessId' => $parentMessId,
                    'share_lat' => $share_lat,
                    'share_lng' => $share_lng,
                    "share_time" => $share_time,
                    "time_stop_share" => $time_stop_share,
                    'time_repeat' => $time_repeat,
                    'message_repeat' => $message_repeat,
                    'content_repeat' => $content_repeat,
                    'time_set_repeat' => $time_set_repeat,
                    'element' => $element
                ];

        /*$listUser = [['use_id' => '1352'], ['use_id' => '1353']];
        $infoThread = ChatThreads::getInfoThread(8);
        $arrReturn = ChatThreads::createMessageForGroup($user->use_id, $listUser, $arrData);
        return response([
                    'msg' => Lang::get('response.success'),
                    'data' => $arrReturn,
                    'arrUser' => $listUser
        ]);
        die();
        */
        $dataDefault = [];
        $reload = 0;
        $namegroup_return = "";
        if( $req->type == 'secret') {
            $info = ChatThreads::where(['id' => $req->threadId ])->first();
        }

        if( $req->type == 'private') {
            $info = ChatThreads::where(['id' => $req->threadId ])->first();
            if( $info['ownerId'] == $user->use_id && $info['idGroupDefault'] == 1) {
                $check = ChatMessages::where(['threadId' => $req->threadId, 'ownerId' => $info['requesterId']])->exists();
                if(!$check) {
                    $reload = 1;
                }
            }
            else {
                if( $info['requesterId'] == $user->use_id && $info['idGroupDefault'] == 1) {
                    $check = ChatMessages::where(['threadId' => $req->threadId, 'ownerId' => $user->use_id ])->exists();
                    if(!$check) {
                        $reload = 1;
                    }
                }
            }
        }
        if( $req->type == 'private' || $req->type == 'secret' ) {
            $info_1 = DB::table("chatthreads")->where(['id' => $req->threadId])->where('userDeleteGroup', '<>', 0)->first();
            if($info_1) {
                $updateUserDelete = DB::table("chatthreads")->where(['id' => $req->threadId])
                ->update(['userDeleteGroup' => 0 , 'idDelete' => $info_1->userDeleteGroup ]);
                $reload = 1;
            }
            else {
                $countMess = ChatMessages::where("threadId", $req->threadId)->count();
                if($countMess == 0) {
                    $reload = 1;
                }
            }

        }
        if( $req->type == 'secret') {
            $reload = 1;
        }
        switch ($req->typedata) {
            case 'share_location':
                $time_stop_share = Carbon::now()->addMinutes($share_time);
                $arrData['time_stop_share'] = $time_stop_share;
                $lastMessageLocation = ChatMessages::where(['threadId' => $req->threadId, 'ownerId' => $user['use_id'], 'typedata' => 'share_location'])->orderby("id","desc")->limit(0,1)->first();
                if($lastMessageLocation) {
                    $time_submius = Carbon::now()->subMinutes($lastMessageLocation->share_time);
                    $created_share = $lastMessageLocation->createdAt;
                    if($created_share < $time_submius) {
                        $messageId  = ChatMessages::sendMessage($arrData);
                    }
                    else {
                        $lastMessageLocation->update([ 'share_lat' => $share_lat,
                                                        'share_lng' => $share_lng,
                                                        "share_time" => $share_time]
                                                    );
                        $messageId = $lastMessageLocation->id;
                    }
                }
                else {
                    $messageId  = ChatMessages::sendMessage($arrData);
                }
                break;

            default:
                $messageId  = ChatMessages::sendMessage($arrData);
                break;
        }

        $condGroup = 0;
        $arrAlias  =[];

        if( $messageId) {
            ChatUserRead::updateCountMessageUnread($req->threadId, $user['use_id']);
            ChatMessageRead::createRowMessage(['messageId' => $messageId, 'threadId' => $req->threadId, 'userLogin' => $user['use_id'] ]);
            if( $req->type == 'secret') {
                $detail = ChatMessages::getDetailMesssageSecret($messageId, $user['use_id'], $req->threadId);
            }
            else {
                $detail = ChatMessages::getDetailMesssage($messageId, $user['use_id']);
            }

            $arrCountMessage = [];
            if( $req->type == 'private' || $req->type == 'secret') {
                $arrUser = [$info['ownerId'], $info['requesterId']];
                $arrCountMessage[$info['ownerId']] = ChatUserRead::getSumCountUnreadPrivate($info['ownerId']);
                $arrCountMessage[$info['requesterId']] = ChatUserRead::getSumCountUnreadPrivate($info['requesterId']);

            }
            else {

                $infoThread = ChatThreads::getInfoThread($req->threadId);
                $namegroup_return = $infoThread['namegroup'];
                $typegroup = $infoThread['typegroup'];

                if( $typegroup == 'default') {
                    $arrUser = [$user['use_id']];
                    switch ($infoThread['alias']) {
                        case 'agent':
                            $listUser = $this->getListBrand( $user['use_id'] ,$req);
                            $dataDefault =  ChatThreads::createMessageForGroup($user->use_id, $listUser, $arrData);

                            foreach($listUser as $k => $v) {
                                $arrUser[] = $v['use_id'];
                                $arrCountMessage[$v['use_id']] = ChatUserRead::getSumCountUnreadPrivate($v['use_id']);
                            }
                            /*ChatThreads::createGroupForDefault('agent', $listUser, $infoThread, $user);*/

                            break;

                        case 'afflliate':
                            # code...
                            $listUser = $this->listAllaffiliateUnder($req);
                            $dataDefault =  ChatThreads::createMessageForGroup($user->use_id, $listUser, $arrData);
                            foreach($listUser as $k => $v) {
                                $arrUser[] = $v['use_id'];
                                $arrCountMessage[$v['use_id']] = ChatUserRead::getSumCountUnreadPrivate($v['use_id']);
                            }
                            /*ChatThreads::createGroupForDefault('afflliate', $listUser, $infoThread, $user);*/

                            break;

                        case 'staff':
                            $listUser = $this->listStaff( $user['use_id'] ,$req);
                            $dataDefault =   ChatThreads::createMessageForGroup($user->use_id, $listUser, $arrData);
                            foreach($listUser as $k => $v) {
                                $arrUser[] = $v['use_id'];
                                $arrCountMessage[$v['use_id']] = ChatUserRead::getSumCountUnreadPrivate($v['use_id']);
                            }

                            /*ChatThreads::createGroupForDefault('staff', $listUser, $infoThread, $user);*/
                            break;

                        case 'customer_bought':
                            $listUser = $this->customer_bought($user->use_id,$req);
                            $dataDefault =   ChatThreads::createMessageForGroup($user->use_id, $listUser, $arrData);
                            foreach($listUser as $k => $v) {
                                $arrUser[] = $v['use_id'];
                                $arrCountMessage[$v['use_id']] = ChatUserRead::getSumCountUnreadPrivate($v['use_id']);
                            }
                            /*ChatThreads::createGroupForDefault('customer_bought', $listUser, $infoThread, $user);*/

                            break;

                        case 'customer_sell':
                            # code...
                            break;


                        case 'user_follow':
                            $listUser = ChatThreads::getUserGroupFolow(['userId' => $user->use_id]);
                            $dataDefault =   ChatThreads::createMessageForGroup($user->use_id, $listUser, $arrData);
                            foreach($listUser as $k => $v) {
                                $arrUser[] = $v['use_id'];
                                $arrCountMessage[$v['use_id']] = ChatUserRead::getSumCountUnreadPrivate($v['use_id']);
                            }

                            break;

                        default:
                            # code...
                            break;
                    }
                }
                else {
                    $typechat = $infoThread->typechat;
                    $listUser = ChatThreadUser::where(['threadId' => $req->threadId, 'accept_request' => 1])->get();
                    $arrUser = [];
                    foreach($listUser as $k => $v) {
                        $arrUser[] = $v['userId'];
                        $arrCountMessage[$v['userId']] = ChatUserRead::getSumCountUnreadGroup($v['userId']);
                    }
                    if( $typechat == 1) {
                        $condGroup = 1;
                        // chat nhom 1chieu
                        $dataDefault =  ChatThreads::createMessageForGroup1($user->use_id, $arrUser, $arrData);
                    }
                }
            }
            ChatThreadUser::updateStatusReadMessage($req->ownerId, $req->threadId);
            $detail['groupChatId'] = $detail['threadId'];
            $detail['idRemove'] = $idRemove;

            if( $req->type == 'private' || $req->type == 'secret') {
                $infoA = ChatThreadUser::where(['userId' => $user['use_id'], 'threadId' => $req->threadId])->first();
                if($infoA) {
                    $detail['blocked'] = $infoA['blocked'];
                    $detail['blockedNotify'] = $infoA['blockedNotify'];
                }
                if($info['requesterId'] != $user['use_id']) {
                    $infoB = ChatThreadUser::where(['userId' => $info['requesterId'] , 'threadId' => $req->threadId])->first();

                    if($infoB) {
                        $detail['blocked_B'] = $infoB['blocked'];
                        $detail['blockedNotify_B'] = $infoB['blockedNotify'];
                    }
                    $userLogin = $user->use_id;
                    $userGroup = User::where(['use_id' => $info['requesterId'] ])
                                    ->leftJoin(DB::raw("(select * from chatuseralias where userId = $userLogin and userId_alias = ".$info['requesterId']." ) as chatuseralias"), function($join) {
                                            $join->on('tbtt_user.use_id', '=', 'chatuseralias.userId_alias');
                                    })
                                    ->select(DB::raw('(CASE WHEN chatuseralias.name_alias is null THEN tbtt_user.use_fullname ELSE chatuseralias.name_alias END) AS use_fullname'))
                                    ->first();
                    $alias = DB::table("chatuseralias")
                            ->where(['userId_alias' => $user['use_id'], 'userId' => $info['requesterId'] ])
                            ->first();
                    if($alias) {
                        $arrAlias[$info['requesterId']] = $alias->name_alias;
                    }
                    else {
                         $arrAlias[$info['requesterId']] = $user['use_fullname'];
                    }


                }
                else {
                    $infoB = ChatThreadUser::where(['userId' => $info['ownerId'] , 'threadId' => $req->threadId])->first();
                    if($infoB) {
                        $detail['blocked_B'] = $infoB['blocked'];
                        $detail['blockedNotify_B'] = $infoB['blockedNotify'];

                    }
                    $userLogin = $user->use_id;
                    $userGroup = User::where(['use_id' => $info['ownerId'] ])
                                     ->leftJoin(DB::raw("(select * from chatuseralias where userId = $userLogin and userId_alias = ".$info['ownerId']." ) as chatuseralias"), function($join) {
                                            $join->on('tbtt_user.use_id', '=', 'chatuseralias.userId_alias');
                                    })
                                    ->select(DB::raw('(CASE WHEN chatuseralias.name_alias is null THEN tbtt_user.use_fullname ELSE chatuseralias.name_alias END) AS use_fullname'))->first();
                    $alias = DB::table("chatuseralias")->where("userId_alias","=", $user['use_id'])
                                ->where("userId","=", $info['ownerId'])
                                ->select("name_alias")->first();
                    if($alias) {
                        $arrAlias[$info['ownerId']] =  $alias->name_alias;
                    }
                    else {
                        $arrAlias[$info['ownerId']] = $user['use_fullname'];
                    }
                }
                if($reload == 1) {
                    $detail['reload'] = 1;
                }
                $namegroup_return = $userGroup['use_fullname'];
            }
            else {
                $userLogin = $user->use_id;
                $infoThreadUser = ChatThreadUser::where('threadId', $req->threadId)
                                    ->join("tbtt_user","tbtt_user.use_id", "=", "chatthreaduser.userId")
                                    ->leftJoin(DB::raw("(select * from chatuseralias where userId_alias = $userLogin  ) as chatuseralias"), function($join) {
                                            $join->on('chatthreaduser.userId', '=', 'chatuseralias.userId');
                                    })
                                    ->select("chatthreaduser.userId as use_id","blockedNotify",DB::raw('(CASE WHEN chatuseralias.name_alias is null THEN null ELSE chatuseralias.name_alias END) AS use_fullname') )->get();
                $detail['info'] = $infoThreadUser;
                $arrAlias = [];
                if(count($infoThreadUser) > 0 ) {
                    foreach( $infoThreadUser as $lll => $vvv) {
                        if($vvv['use_fullname'] == null) {
                            $arrAlias[$vvv['use_id']] = $user['use_fullname'];
                        }
                        else {
                            $arrAlias[$vvv['use_id']] = $vvv['use_fullname'];
                        }

                    }
                }
            }
            $detail['namegroup'] = $namegroup_return;
            $detailPush =  $detail->toArray();
            $detailPush['userIds'] = $arrUser;
            //$object = new SendChatNotification('send-new-message',$detailPush);
            //$object->pushNotificationSendMessage();
            //dispatch(new SendChatNotification('send-new-message',$detailPush));
            $job = (new SendChatNotification('send-new-message',$detailPush));
            $this->dispatch($job);
            $info_create_follow = ['userId' =>  $user['use_id'], 'arrUserFollow' => $arrUser];
            $job_create_follow = (new SendChatNotification('create-follow',$info_create_follow));
            $this->dispatch($job_create_follow);
            return response([
                    'msg' => Lang::get('response.success'),
                    'data' => $detail,
                    'arrUser' => $arrUser,
                    'dataDefault' => $dataDefault,
                    'arrCountMessage' => $arrCountMessage,
                    'condGroup' => $condGroup,
                    'userSend' => $user['use_id'],
                    'type' => $req->type,
                    'arrAlias' => $arrAlias,
                    'idRemove' => $idRemove
            ]);
        }
        else {
            return response([
                    'msg' => Lang::get('response.success'),

            ]);
        }
    }

    public function deleteChatSecret(Request $req) {
        $user = $req->user();
        $messageId = $req->messageId;
        $infoChat = ChatMessages::where(['id' => $messageId])->first();
        $delete = ChatMessages::where(['id' => $messageId])->delete();
        if( $delete) {
            $listUser = ChatThreadUser::where(['threadId' => $infoChat['threadId']])->get();
            $arrUser = [];
            foreach($listUser as $k => $v) {
                $arrUser[] = $v['userId'];
            }
            return response([
                'msg' => Lang::get('response.success'),
                'data' => ['messageId' => $messageId],
                'arrUser' => $arrUser,
                'userId' => $user['use_id']
            ]);
        }
        else {
            return response([
                'msg' => Lang::get('response.failed'),
            ]);
        }

    }

    public function deleteMessage(Request $req) {
        $user = $req->user();
        $messageId = $req->messageId;
        $condDelete = 2;
        if( isset($req->condDelete)) {
            $condDelete = $req->condDelete;
        }
        $hasReply = ChatMessages::checkMessageHasReply($messageId);
        if($hasReply) {
            return response([
                'data' => ['messageId' => 0, 'info' => "Đã có người trả lời cho tin nhắn này. Bạn không thể xóa" ] ,
                'msg' => 'failed',
                'userId' => $user['use_id']

            ]);
        }
        $infoChat = ChatMessages::where(['id' => $messageId])->first();
        if( $condDelete == 1) {
            // xoa minh toi
            $check = DB::table('chatmessages')
                        ->where('id', "=", $messageId)
                        ->where('userDelete',"<>", 0)->exists();
            if( $check) {
                $delete = ChatMessages::where(['id' => $messageId])->delete();
            }
            else {
                $delete = DB::table('chatmessages')
                    ->where('id', "=", $messageId)
                    ->update(['userDelete' => $user['use_id'] ]);
            }
        }
        else {
            // xoa ca hai ben
            $delete = ChatMessages::where(['id' => $messageId])->delete();
        }

        if( $delete) {
            if( $condDelete == 1) {
                $arrUser = [$user['use_id']];
            }
            else {
                $threadId = $infoChat['threadId'];
                $listUser = ChatThreadUser::where(['threadId' => $threadId])->get();
                $arrUser = [];
                foreach($listUser as $k => $v) {
                    $arrUser[] = $v['userId'];
                }
            }

            return response([
                'msg' => Lang::get('response.success'),
                'data' => ['messageId' => $messageId],
                'arrUser' => $arrUser,
                'userId' => $user['use_id']
            ]);
        }
        else {
            return response([
                'msg' => Lang::get('response.failed'),
            ]);
        }
    }

    public function deleteConversation(Request $req) {
        $threadId = $req->threadId;
        $type = 'private';
        if( isset($req->type)) {
            $type = $req->type;
        }

        if($type == 'private' || $type == 'secret') {
            $user = $req->user();
            $arrUser = [$user['use_id']];
            $check =  DB::table("chatthreads")->where(['id' => $threadId, 'userDeleteGroup' => 0])->first();
            if($check) {
                $infolastmessage = ChatMessages::where(['threadId' => $threadId])->orderby('id','desc')->limit(0,1)->first();
                if($infolastmessage) {
                    $lastIdMes = $infolastmessage['id'];
                }
                else {
                    $lastIdMes = 0;
                }
                $delete = DB::table("chatthreads")->where(['id' => $threadId])->update(['userDeleteGroup' => $user['use_id'],'lastIdMessage' => $lastIdMes]);
                DB::table("chatuserread")->where(['threadId' => $threadId, 'userId' => $user['use_id'] ])->update(['countUnread' => 0]);
                DB::table("chatmessageread")->where(['threadId' => $threadId, 'userId' => $user['use_id'] ])->delete();
            }
            else {
                /*$delete = ChatThreads::where(['id' => $threadId])->delete();*/
                $delete = ChatThreads::deleteTypeGroup($threadId);
            }
        }
        else {
            $arrUser = ChatThreads::getUserOfThread($threadId, $type);
            $delete = ChatThreads::deleteTypeGroup($threadId);
        }
        $arrCountMessage = [];
        foreach($arrUser as $k => $v ) {
            $arrCountMessage[$v] = ChatUserRead::getSumCountUnreadOfUser($v);
        }
        if( $delete) {
            return response([
                'msg' => Lang::get('response.success'),
                'data' => $arrUser,
                'arrCountMessage' => $arrCountMessage

            ]);
        }
        else {
            return response([
                'msg' => Lang::get('response.failed'),
            ]);
        }
    }

    public function getListUsers(Request $req) {
        $user = $req->user();
        $search = "";
        if( isset($req->search)) {
            $search = $req->search;
        }
        $typegroup = 0;
        if( isset($req->typegroup)) {
            $typegroup = $req->typegroup;
        }

        $params = ['search' =>  $search, 'userId' => $user['use_id'], 'typegroup'=> $typegroup];
        $page = $req->page;
        $pageSize = $req->limit;
        if( isset($req->follow) && $req->follow == 1 ) {
            /*$list = User::getListUsersFolow($page, $pageSize, $params);*/
            $list = User::getListUsersFolowAlias($page, $pageSize, $params);
        }
        else {
            /*$list = User::getListUsers($page, $pageSize, $params);*/
            $list = User::getListUsersAlias($page, $pageSize, $params);
        }

        $results = $list->paginate($pageSize);
        return response([
            'msg' => Lang::get('response.success'),
            'data' => $results
        ]);

    }


    public function deleteUserInGroup(Request $req) {
        $user = $req->user();
        $threadId = $req->roomId;
        $userId =  $req->userId;
        $infoGroup = ChatThreads::where(['id' => $threadId])->first();
        $ownerId = $infoGroup->ownerId;
        $listUser = ChatThreadUser::where(['threadId' => $threadId])->get();
        if( $userId == $ownerId) {
            // kiem tra thang leave group co phai la chu group ko?
            $countUser = ChatThreadUser::where(['threadId' => $threadId])->count();
             if($countUser > 1) {
                   /* $delete = ChatThreadUser::where(['userId' => $userId, 'threadId' => $threadId])->delete();*/
                $delete = ChatThreadUser::deleteUserIngroup($userId, $threadId);
                $listUserAfterdelete = ChatThreadUser::where(['threadId' => $threadId])->first();
                $idafter = $listUserAfterdelete->userId;
                ChatThreadUser::updateOwnerGroup($idafter, $threadId);
             }
             else {
                $delete = ChatThreads::deleteTypeGroup($threadId);
             }
        }
        else {
             $countUser = ChatThreadUser::where(['threadId' => $threadId])->count();
             if($countUser > 1) {
                   /* $delete = ChatThreadUser::where(['userId' => $userId, 'threadId' => $threadId])->delete();*/
                $delete = ChatThreadUser::deleteUserIngroup($userId, $threadId);
             }
             else {
                $delete = ChatThreads::deleteTypeGroup($threadId);
             }
        }


        /*$infoUserDelete = User::where(['use_id'=> $userId])->first();*/
        $infoUserDelete = ChatThreadUser::getInfoUserAlias($userId, $user['use_id']);
        $infoUserDelete['groupChatId'] = $threadId;
        $delete = true;
        if( $delete) {
            $arrUser = [];
            foreach($listUser as $k => $v) {
                $arrUser[] = $v['userId'];
            }
            return response([
                'msg' => Lang::get('response.success'),
                'status' => 200,
                'threadId' => $threadId,
                'infoUserDelete' => $infoUserDelete,
                'arrUser' => $arrUser
            ]);
        }
        else {
            return response([
                'msg' => Lang::get('response.failed'),
                'status' => 400,
            ]);
        }
    }

    public function updateLatLngUser(Request $req) {
        $limit = $req->limit;
        $page = $req->page;
        $start = ($page - 1) * $limit;
        $listUser = User::limit($start, $limit)->get();
        foreach ( $listUser as $k => $v) {
            $address = $v['use_address'];
            $infoLatLng = Commons::getLatLng($address);
            DB::table('tbtt_user')
            ->where('use_id', $v['use_id'])
            ->update(['use_lat' => $infoLatLng['lat'],'use_lng' => $infoLatLng['lng']]);

        }

    }

    public function getListUserNotJoinGroup(Request $req) {
        $page = $req->page;
        $limit = $req->limit;
        $threadId = $req->groupChatId;
        $search = "";
        if( isset($req->search)) {
            $search = $req->search;
        }
        $user = $req->user();
        $userId = $user['use_id'];
        $params = ['search' => $search, 'userId' => $userId];
        if( isset($req->follow) && $req->follow == 1 ) {
            /*$listUser = ChatThreadUser::getListUserNotJoinFolow($threadId, $page, $limit, $params);*/
            $listUser = ChatThreadUser::getListUserNotJoinFolowAlias($threadId, $page, $limit, $params);
        }
        else {
            /*$listUser = ChatThreadUser::getListUserNotJoin($threadId, $page, $limit, $params);*/
            $listUser = ChatThreadUser::getListUserNotJoinAlias($threadId, $page, $limit, $params);
        }
        $results = $listUser->paginate($limit);
        $results = $results->toArray();
        return response([
            'msg' => Lang::get('response.success'),
            'data' => $results
        ]);
    }

    public function searchFriendRadius(Request $req){
        /*$lat = $req->lat;
        $lng = $req->lng;*/
        $user = $req->user();
        $lat = $user['use_lat'];
        $lng = $user['use_lng'];
        $userId = $user['use_id'];
        $configRadius = 10;
        $distance = "( 6371 * acos( cos( radians('".$lat."') ) * cos( radians( use_lat ) ) * cos( radians( use_lng ) - radians('".$lng."') ) + sin( radians('".$lat."') ) * sin( radians( use_lat ) ) ) ) AS distance";
        if( isset($req->use_group)) {
            if(count($req->use_group) > 0 ) {

                if(in_array(9999, $req->use_group)) {
                    $arrFollow = [];
                    $listFollow = UserFollow::where("user_id", "=", $userId)->get();
                    if( $listFollow) {
                        foreach( $listFollow as $kk => $vv) {
                            $arrFollow[] = $vv['follower'];
                        }
                    }
                    $use_group = $req->use_group;
                    $query = User::where(['use_status' => 1])
                                 ->where(function ($query) use ($use_group,$arrFollow) {
                                        $query->whereIn('use_group', $use_group)
                                            ->orWhereIn('use_id', $arrFollow );
                                 });

                }
                else {
                    $query = User::where(['use_status' => 1])->whereIn('use_group', $req->use_group);
                }
            }
            else {
                $query = User::where(['use_status' => 1]);
            }

        }
        else {
            $query = User::where(['use_status' => 1]);
        }
        /*$users = $query->selectRaw("tbtt_user.use_id,tbtt_user.use_fullname,tbtt_user.use_username,tbtt_user.use_phone, tbtt_user.use_address, tbtt_user.avatar ," . $distance)->having('distance', '<=', $configRadius)->get()->toArray();*/
        $users = $query->leftJoin(DB::raw("(select * from chatuseralias where userId = $userId) as chatuseralias"), function($join) {
                         $join->on('tbtt_user.use_id', '=', 'chatuseralias.userId_alias');
                })->selectRaw("tbtt_user.use_id, (CASE WHEN chatuseralias.name_alias is null THEN tbtt_user.use_fullname ELSE chatuseralias.name_alias END) AS use_fullname ,tbtt_user.use_username,tbtt_user.use_phone, tbtt_user.use_address, tbtt_user.avatar ," . $distance)->having('distance', '<=', $configRadius)->get()->toArray();
        return response([
            'msg' => Lang::get('response.success'),
            'data' => $users
        ]);
    }

    public function searchShopRadius(Request $req) {
        $user = $req->user();
        $userId = $user['use_id'];
        $lat = $req->lat;
        $lng = $req->lng;
        $configRadius = 30;
        $distance = "( 6371 * acos( cos( radians('".$lat."') ) * cos( radians( sho_lat ) ) * cos( radians( sho_lng ) - radians('".$lng."') ) + sin( radians('".$lat."') ) * sin( radians( sho_lat ) ) ) ) AS distance";
        $query = User::where(['sho_status' => 1]);
        /*$users = $query->selectRaw("tbtt_user.use_id,tbtt_user.use_fullname,tbtt_user.use_username,tbtt_user.use_phone, tbtt_user.use_address ," . $distance)->having('distance', '<=', $configRadius)->get()->toArray();*/
        $users = $query->leftJoin(DB::raw("(select * from chatuseralias where userId = $userId) as chatuseralias"), function($join) {
                         $join->on('tbtt_user.use_id', '=', 'chatuseralias.userId_alias');
                })->selectRaw("tbtt_user.use_id,(CASE WHEN chatuseralias.name_alias is null THEN tbtt_user.use_fullname ELSE chatuseralias.name_alias END) AS use_fullname ,tbtt_user.use_username,tbtt_user.use_phone, tbtt_user.use_address ," . $distance)->having('distance', '<=', $configRadius)->get()->toArray();

        return response([
            'msg' => Lang::get('response.success'),
            'data' => $users
        ]);
    }

    public function setStatusMessage(Request $req) {
        $user = $req->user();
        $groupChatId = $req->groupChatId;
        $update = DB::table('chatthreaduser')->where(['userId' => $user->use_id, 'threadId' => $groupChatId])->update(['statusRead' => 1]);
        ChatUserRead::setRead($user->use_id, $groupChatId);
        //$detailThread = ChatThreads::getDetailThread($groupChatId);
        $info = ChatThreads::where(['id' => $groupChatId ])->first();
        $detailThread = ChatThreads::getDetailThreadStatus($groupChatId, $user->use_id);
        $type = $detailThread['type'];
        if( $type == 'private' || $type == 'secret') {
            if( $info['ownerId'] == $user->use_id) {
                $idSend = $info['requesterId'];
            }
            else {
                $idSend = $info['ownerId'];
            }
           /* $params = ['userId' => $user['use_id']];
            $listGroup = ChatThreads::getListGroupUser($params);
            $sum = ChatUserRead::getSumCountUnread($user['use_id'], $listGroup);*/

            return response([
                'msg' => Lang::get('response.success'),
                'data' =>  $detailThread,
                'idSend' => $idSend,
                'type' => $type
                //'countmessage' => intval($sum)

            ]);
        }
        else {
            $messageLast = ChatMessages::where(['threadId' => $groupChatId])->orderby('id','desc')->first();
            $ownerId = $messageLast['ownerId'];
            $statusRead =  ChatMessages::statusReadOfMessage(['id' => $groupChatId], $messageLast['id']);
            return response([
                'msg' => Lang::get('response.success'),
                'data' => ['messageId' => $messageLast['id'], 'statusRead' => $statusRead ],
                'ownerMessage' => $ownerId,
                'type' => $type,
                'statusRead' => $statusRead
            ]);

        }


    }

    public function detailGroup(Request $req) {
        $user = $req->user();
        $groupChatId = $req->groupChatId;
        /*$detailThread = ChatThreads::getDetailThread($groupChatId, $user['use_id']);*/
        $detailThread = ChatThreads::getDetailThreadAlias($groupChatId, $user['use_id']);
        $info = ChatThreadUser::where(['threadId' => $groupChatId, 'userId' => $user['use_id']])->first();
        $detailThread['blockedNotify'] = $info['blockedNotify'];
        return response([
            'msg' => Lang::get('response.success'),
            'data' =>  $detailThread
        ]);

    }

    public function updateGroup(Request $req){
        $namegroup = $req->namegroup;
        $threadId = $req->groupChatId;
        if( isset($req->avatar) && $req->avatar != "") {
            $avatar = $req->avatar;
            $update = DB::table('chatthreads')->where(['id' => $threadId])->update(['namegroup' => $namegroup,'avatar' => $avatar]);
        }
        else {
           $update = DB::table('chatthreads')->where(['id' => $threadId])->update(['namegroup' => $namegroup]);
        }
        if( $update) {
            //$detail = ChatThreads::where(['id' => $threadId])->first();
            $user = $req->user();
            /*$detail = ChatThreads::getDetailThread($threadId, $user['use_id']);*/
            $detail = ChatThreads::getDetailThreadAlias($threadId, $user['use_id']);
            return response([
                'msg' => Lang::get('response.success'),
                'status' => 200,
                'data' => $detail
            ]);
        }
        else {
            return response([
                'msg' => Lang::get('response.failed'),
                'status' => 400,
            ]);
        }
    }

    public function blockMessage(Request $req) {
        $threadId = $req->groupChatId;
        $userBlocked = $req->userId;
        $user = $req->user();
        $update = DB::table('chatthreaduser')
                    ->where(['threadId' => $threadId,'userId' => $userBlocked])
                    ->update(['blocked' => 1]);
        /*$update = DB::table('chatthreaduser')
                    ->where(['threadId' => $threadId])
                    ->update(['blocked' => 1]);*/
        if( $update) {
            $user['groupChatId'] = $threadId;
            return response([
                'msg' => Lang::get('response.success'),
                'status' => 200,
                'data' => $user,
                'threadId' => $threadId,
                'userBlocked' => $userBlocked,
                'userId' => $user['use_id']
            ]);
        }
        else {
            return response([
                'msg' => Lang::get('response.failed'),
                'status' => 400,
            ]);
        }

    }

    public function blockNotify(Request $req) {
        $threadId = $req->groupChatId;
        $userBlocked = $req->userId;
        $user = $req->user();
        $update = DB::table('chatthreaduser')->where(['threadId' => $threadId,'userId' => $userBlocked])->update(['blockedNotify' => 1]);
        if( $update) {
            $user['groupChatId'] = $threadId;
            return response([
                'msg' => Lang::get('response.success'),
                'status' => 200,
                'data' => $user,
                'threadId' => $threadId,
                'userBlocked' => $userBlocked
            ]);
        }
        else {
            return response([
                'msg' => Lang::get('response.failed'),
                'status' => 400,
            ]);
        }

    }

    public function blockNotifyGroup(Request $req) {
        $threadId = $req->groupChatId;
        $user = $req->user();
        $update = DB::table('chatthreaduser')->where(['threadId' => $threadId,'userId' => $user['use_id']])->update(['blockedNotify' => 1]);
        if( $update) {
            $user['groupChatId'] = $threadId;
            return response([
                'msg' => Lang::get('response.success'),
                'status' => 200,
                'data' => $user,
                'threadId' => $threadId
            ]);
        }
        else {
            return response([
                'msg' => Lang::get('response.failed'),
                'status' => 400,
            ]);
        }

    }

    public function unBlockMessage(Request $req){
        $threadId = $req->groupChatId;
        $userBlocked = $req->userId;
        $user = $req->user();
        $update = DB::table('chatthreaduser')->where(['threadId' => $threadId,'userId' => $userBlocked])->update(['blocked' => 0]);
        /*$update = DB::table('chatthreaduser')->where(['threadId' => $threadId])->update(['blocked' => 0]);*/

        if( $update) {
            $user['groupChatId'] = $threadId;
            return response([
                'msg' => Lang::get('response.success'),
                'status' => 200,
                'data' => $user,
                'threadId' => $threadId,
                'userBlocked' => $userBlocked,
                'userId' => $user['use_id']
            ]);
        }
        else {
            return response([
                'msg' => Lang::get('response.failed'),
                'status' => 400,
            ]);
        }
    }

    public function unBlockNotify(Request $req){
        $threadId = $req->groupChatId;
        $userBlocked = $req->userId;
        $user = $req->user();
        $update = DB::table('chatthreaduser')->where(['threadId' => $threadId,'userId' => $userBlocked])->update(['blockedNotify' => 0]);

        if( $update) {
            $user['groupChatId'] = $threadId;
            return response([
                'msg' => Lang::get('response.success'),
                'status' => 200,
                'data' => $user,
                'threadId' => $threadId,
                'userBlocked' => $userBlocked
            ]);
        }
        else {
            return response([
                'msg' => Lang::get('response.failed'),
                'status' => 400,
            ]);
        }
    }


    public function unBlockNotifyGroup(Request $req){
        $threadId = $req->groupChatId;
        $user = $req->user();
        $update = DB::table('chatthreaduser')->where(['threadId' => $threadId,'userId' => $user['use_id']])->update(['blockedNotify' => 0]);

        if( $update) {
            $user['groupChatId'] = $threadId;
            return response([
                'msg' => Lang::get('response.success'),
                'status' => 200,
                'data' => $user,
                'threadId' => $threadId
            ]);
        }
        else {
            return response([
                'msg' => Lang::get('response.failed'),
                'status' => 400,
            ]);
        }
    }

    public function detailUserChat(Request $req) {
        $user = $req->user();
        $userId = $req->userId;
        $threadId = $req->groupChatId;
        /*$detail = ChatThreadUser::detailUserChat($userId, $threadId);*/
        $detail = ChatThreadUser::detailUserChatAlias($userId, $threadId, $user['use_id']);
        if($detail) {
            return response([
                'msg' => Lang::get('response.success'),
                'status' => 200,
                'data' => $detail
            ]);
        }
        else {
            return response([
                'msg' => Lang::get('response.failed'),
                'status' => 400,
            ]);
        }

    }

    public function detailProfile(Request $req) {
        $user = $req->user();
        $userId = $user['use_id'];

        $info = User::where("use_id", $userId)->select("avatar","use_fullname","use_username")->first();
        return response([
                'msg' => Lang::get('response.success'),
                'status' => 200,
                'data' => $info
            ]);
    }


    public function checkPhone(Request $req) {
        $user = $req->user();
        $arrParam = $req->phone;
        $phone = [];
        $phoneName = [];
        foreach($arrParam as $k => $v ) {
            $phone[] = $v['phone'];
            $phoneName[$v['phone']] = $v['name'];
        }
        $userId = $user['use_id'];

        /*$dataExist = User::where(function ($query) use ($phone ) {
                        $query->whereIn('use_mobile',  $phone );

                })->select("tbtt_user.avatar","tbtt_user.use_username", "tbtt_user.use_fullname", "tbtt_user.use_id", "tbtt_user.use_mobile")->get()->toArray();*/
        $dataExist = User::leftJoin(DB::raw("(select * from chatuseralias where userId = $userId) as chatuseralias"), function($join) {
                             $join->on('tbtt_user.use_id', '=', 'chatuseralias.userId_alias');
                })
                ->where(function ($query) use ($phone ) {
                        $query->whereIn('use_mobile',  $phone );

                })->select("tbtt_user.avatar","tbtt_user.use_username", DB::raw('(CASE WHEN chatuseralias.name_alias is null THEN tbtt_user.use_fullname ELSE chatuseralias.name_alias END) AS use_fullname'), "tbtt_user.use_id", "tbtt_user.use_mobile")->get()->toArray();
        $arrExist = [];
        foreach ($dataExist as $key => &$value) {
            /*$checkFollow = UserFollow::where("user_id", "=", $userId)
                                    ->where("follower", "=", $value['use_id'])
                                    ->where("hasFollow","=", 1)
                                    ->first();*/
            $checkFollow = UserFollow::where("user_id", "=",$value['use_id'])
                                    ->where("follower", "=", $userId )
                                    ->where("hasFollow","=", 1)
                                    ->first();

            if($checkFollow) {
                $value['follow'] = true;
            }
            else {
                $value['follow'] = false;
            }
            if( isset($phoneName[$value['use_mobile']])) {
                $value['name_local'] = $phoneName[$value['use_mobile']];
            }
            else {
                $value['name_local'] = "";
            }
            $arrExist[] = $value['use_mobile'];
            ChatFacePhone::updateInfo(['userId' => $value['use_id'], 'phone_name' => $value['name_local'] ]);

        }
        $dataNotExist=array_diff($phone,$arrExist);
        foreach( $dataNotExist as $k => $v ) {
            if( isset($phoneName[$v])) {
                $name_local = $phoneName[$v];
            }
            else {
                $name_local = "";
            }

            $dataExist[] = [
                                "avatar" => "",
                                "use_username" => "",
                                "use_fullname" => "",
                                "use_id" => "",
                                "use_mobile" => $v,
                                "follow" => false,
                                'name_local' => $name_local

                            ];
        }

        return response(['msg' => 'Success', 'data' => $dataExist]);

    }

    public function checkFace(Request $req) {
        $user = $req->user();
        $arrParam = $req->face;
        $arrReturn = [];
        $userId = $user['use_id'];

        foreach($arrParam as $k => $v ) {
            $idFace = $v['id'];
            /*$dataExist = User::where(function ($query) use ($idFace ) {
                        $query->where('use_message', "LIKE", '%' .$idFace. '%' );

                })->select("tbtt_user.avatar","tbtt_user.use_username", "tbtt_user.use_fullname", "tbtt_user.use_id", "tbtt_user.use_mobile")->first();*/
            $dataExist = User::leftJoin(DB::raw("(select * from chatuseralias where userId = $userId) as chatuseralias"), function($join) {
                         $join->on('tbtt_user.use_id', '=', 'chatuseralias.userId_alias');
                })
                ->where(function ($query) use ($idFace ) {
                        $query->where('use_message', "LIKE", '%' .$idFace. '%' );

                })->select("tbtt_user.avatar","tbtt_user.use_username", DB::raw('(CASE WHEN chatuseralias.name_alias is null THEN tbtt_user.use_fullname ELSE chatuseralias.name_alias END) AS use_fullname'), "tbtt_user.use_id", "tbtt_user.use_mobile")->first();

            if($dataExist) {
                $dataExist->toArray();
                $dataExist['face_id'] = $idFace;
               /* $checkFollow = UserFollow::where("user_id", "=", $userId)
                                    ->where("follower", "=", $dataExist['use_id'])
                                    ->where("hasFollow","=", 1)
                                    ->first();*/
                $checkFollow = UserFollow::where("user_id", "=", $dataExist['use_id'])
                                    ->where("follower", "=", $userId )
                                    ->where("hasFollow","=", 1)
                                    ->first();
                if($checkFollow) {
                    $dataExist['follow'] = true;
                }
                else {
                    $dataExist['follow'] = false;
                }
                $dataExist['face_name'] = $v['name'];
                if( isset($v['picture'])) {
                    $dataExist['face_picture'] = $v['picture'];
                }
                else {
                    $dataExist['face_picture'] = "";
                }
                $arrReturn[] = $dataExist;
                ChatFacePhone::updateInfo(['userId' => $dataExist['use_id'], 'face_name' => $dataExist['face_name'], 'face_picture' => $dataExist['face_picture'], 'face_id' => $dataExist['face_id'] ]);
            }
            else {
                if( isset($v['picture'])) {
                    $picture = $v['picture'];
                }
                else {
                    $picture = "";
                }

                $arrReturn[]  = [
                                "avatar" => "",
                                "use_username" => "",
                                "use_fullname" => "",
                                "use_id" => "",
                                "use_mobile" => "",
                                "follow" => false,
                                "face_id" => $idFace,
                                "face_name" => $v['name'],
                                "face_picture" => $picture


                            ];
            }

        }

        return response(['msg' => 'Success', 'data' => $arrReturn]);

    }

    public function listInviteChat(Request $req){
        // chat

        $user = $req->user();
        ChatUserInvite::setInviteRead($user['use_id']);
        $page = $req->page;
        $limit = $req->limit;
        $offset = ($page - 1) * $limit;

       /* $list = ChatThreadUser::join("chatthreads.id","=","chatthreaduser.threadId")
                        ->where(['chatthreaduser.userId' => $user['use_id'], 'chatthreaduser.accept_request' => 0])
                        ->select("chatthreads.namegroup", "chatthreaduser.*")
                        ->first()->toArray();*/
        $list = ChatThreadUser::join("chatthreads","chatthreads.id","=","chatthreaduser.threadId")
                        ->where(['chatthreaduser.userId' => $user['use_id'], 'chatthreaduser.accept_request' => 0])
                        ->select("chatthreaduser.*","chatthreads.namegroup","chatthreads.avatar as avatargroup","chatthreads.ownerId as ownerIdGroup");
        $list = $list->paginate($limit);
        $list = $list->toArray();
        foreach( $list['data'] as $k => &$v) {
            $v = (array)$v;
            if($v['namegroup'] == "") {
                $ownerId = $v['ownerIdGroup'];
                /*$user = User::where('use_id',"=", $ownerId)->first();*/
                $user = ChatThreadUser::getInfoUserAlias($ownerId, $user['use_id']);
                $v['namegroup'] =  $user['use_fullname'];
                if($v['avatargroup'] == "" ) {
                    $v['avatar'] =  $user['avatar'];
                }
                else {
                    $v['avatar'] = $v['avatargroup'];
                }
                $v['use_username'] =  $user['use_username'];
                $v['use_fullname'] =  $user['use_fullname'];

            }
            else {
                if($v['avatargroup'] == "" ) {
                    $ownerId = $v['ownerIdGroup'];
                    /*$user = User::where('use_id',"=", $ownerId)->first();*/
                    $user = ChatThreadUser::getInfoUserAlias($ownerId, $user['use_id']);
                    $v['avatar'] =  $user['avatar'];
                    $v['use_username'] =  $user['use_username'];
                    $v['use_fullname'] =  $user['use_fullname'];
                }
                else{
                    $v['avatar'] = $v['avatargroup'];
                    $ownerId = $v['ownerIdGroup'];
                    /*$user = User::where('use_id',"=", $ownerId)->first();*/
                    $user = ChatThreadUser::getInfoUserAlias($ownerId, $user['use_id']);
                    $v['use_username'] =  $user['use_username'];
                    $v['use_fullname'] =  $user['use_fullname'];
                }
            }
        }


        return response([
                'msg' => Lang::get('response.success'),
                'data' => $list
            ]);

    }

    public function getArrUserPrivate(Request $req) {
        $user = $req->user();
        $task = $req->task;
        $arrId = ChatThreads::getArrUserPrivate($user['use_id']);
        return response([
                'msg' => Lang::get('response.success'),
                'status' => 200,
                'data' => $arrId,
                'task' => $task
            ]);

    }

    public function updateTimeOffline(Request $req) {
        $userId = $req->userId;
        $ts = time();
        $timeDate = date('Y-m-d H:i:s');
        $save = ChatUserOff::updateTime(['userId' => $userId, 'timeTs' => $ts, 'timeDate'=> $timeDate]);
        if($save) {
            return response([
                'msg' => Lang::get('response.success'),
                'status' => 200,

            ]);
        }
        else {
            return response([
                'msg' => Lang::get('response.failed'),
                'status' => 500,

            ]);
        }


    }

    public function getTimeOffUser(Request $req) {
        $userId = $req->userId;
        $info =  DB::table('chatuseroffline')
            ->where('userId', $userId)->first();
        if(!$info)  {
            $timedate =  'Đang offline';

        }
        else {
            $timedate = ChatUserOff::time_elapsed_string($info->timeDate);

        }
        return response([
                'msg' => Lang::get('response.success'),
                'data' => $timedate,

        ]);

    }

    public function renderQrcode(Request $req) {
        $user = $req->user();
        $user = $user->toArray();
        $arr = ['use_id' => $user['use_id'], 'use_username' => $user['use_username'], 'use_fullname' => $user['use_fullname']];
        $png =  base64_encode(QrCode::format('png')->size(399)->color(40,40,40)->generate((string)json_encode($arr)));
        return "data:image/png;base64,".$png;
    }

    public function listBackgroundChat(Request $req) {
        $user = $req->user();
        $list = ChatBackground::getList(['userId' => $user['use_id']]);
        return response([
                'msg' => Lang::get('response.success'),
                'data' => $list,

        ]);
    }

    public function updateBackground(Request $req) {
        $user = $req->user();
        $groupChatId = $req->groupChatId;
        $side = $req->side;
        $background = $req->background;
        $update = ChatUserBackground::updateBackground(['userId' => $user['use_id'], 'groupChatId' => $groupChatId, 'side' => $side, 'background'=> $background]);
        $detailThread = ChatThreads::getDetailThreadBackground($groupChatId, $user['use_id']);
        $listUser = [];
        if( $side == 2) {
            $listUser = ChatUserBackground::getListUser($groupChatId,$user['use_id']);
        }
        return response([
                'msg' => Lang::get('response.success'),
                'data' => $detailThread,
                'side' => $side,
                'listUser' => $listUser,
                'userId' => $user['use_id']
        ]);

    }

    public function updateBackgroundUpload(Request $req) {
        $user = $req->user();
        $background = $req->background;

        $save = ChatBackground::saveBackground(['userId' => $user['use_id'], 'name' => $background]);
        if( $save) {
            return response([
                'msg' => Lang::get('response.success'),
                'data' => $save,

            ]);
        }
        else {
            return response([
                    'msg' => Lang::get('response.failed'),
                    'data' => [],

            ]);
        }


    }

    public function userNotAcceptGroup(Request $req) {
        $user = $req->user();
        $userId = $user['use_id'];
        $page = $req->page;
        $limit = $req->limit;
        $groupChatId =  $req->groupChatId;
        $list = ChatThreadUser::join("chatthreads","chatthreads.id","=","chatthreaduser.threadId")
                        ->join("tbtt_user","tbtt_user.use_id","=","chatthreaduser.userId")
                        ->leftJoin(DB::raw("(select * from chatuseralias where userId = $userId) as chatuseralias"), function($join) {
                                 $join->on('chatthreaduser.userId', '=', 'chatuseralias.userId_alias');
                        })
                        ->where(['chatthreaduser.threadId' => $groupChatId, 'chatthreaduser.accept_request' => 0])
                        ->select("tbtt_user.use_id as use_id","tbtt_user.avatar","tbtt_user.use_username",DB::raw('(CASE WHEN chatuseralias.name_alias is null THEN tbtt_user.use_fullname ELSE chatuseralias.name_alias END) AS use_fullname'), "chatthreads.id as groupChatId", "chatthreads.namegroup");
                       /* ->select("chatthreaduser.*","chatthreads.namegroup","chatthreads.ownerId as ownerIdGroup","tbtt_user.use_username","tbtt_user.use_fullname","tbtt_user.avatar");*/
        $list = $list->paginate($limit);
        return response([
                'msg' => Lang::get('response.success'),
                'data' => $list

        ]);

    }

    public function resendRequestJoinGroup(Request $req){
        $user = $req->user();
        $groupChatId = $req->groupChatId;
        $userId =  $req->userId;
        $check = DB::table('chatthreaduser')->where(['userId' => $userId, 'threadId' => $groupChatId])->first();
        if(!$check) {
            ChatThreadUser::addUserToGroup($groupChatId, [$userId]);
        }
        ChatUserInvite::updateCountInvite($userId);
        /*$detailThread = ChatThreads::getDetailThread($groupChatId, $user['use_id']);*/
        $detailThread = ChatThreads::getDetailThreadAlias($groupChatId, $user['use_id']);
        return response([
                    'msg' => Lang::get('response.success'),
                    'data' => $detailThread,
                    'userId' => $userId

        ]);

    }

    public function renameUser(Request $req) {
        $user = $req->user();
        $data = $req->data;
        foreach( $data as $k => $v ) {
            $userId_alias = $v['userId_alias'];
            $name_alias = $v['name_alias'];
            $arrData = ['userId' => $user['use_id'], 'userId_alias' => $userId_alias, 'name_alias' => $name_alias];
            $rename = ChatUserAlias::updateAlias($arrData);
        }

        //$detail = ChatThreadUser::detailUserChatAlias($userId_alias, 560, $user['use_id']);
        return response([
                        'msg' => Lang::get('response.success'),
                        'data' => $data

        ]);

    }

    public function listUserNotAdmin(Request $req){
        $user = $req->user();
        $page = $req->page;
        $limit = $req->limit;
        $threadId = $req->groupChatId;
        $search = "";
        if(isset($req->search)) {
            $search = $req->search;
        }
        $arrData = ['userId' => $user['use_id'],'threadId' => $threadId, 'search' => $search];
        $info = ChatThreadUser::getListUserOfGroupNotAdmin($page, $limit, $arrData);
        if($info) {
            $results = $info->paginate($limit);
            return response([
                            'msg' => Lang::get('response.success'),
                            'data' => $results

            ]);
        }
        else {
            return response([
                            'msg' => Lang::get('response.success'),
                            'data' => []

            ]);
        }

    }

    public function listUserIsAdmin(Request $req){
        $user = $req->user();
        $page = $req->page;
        $limit = $req->limit;
        $threadId = $req->groupChatId;
        $search = "";
        if(isset($req->search)) {
            $search = $req->search;
        }
        $arrData = ['userId' => $user['use_id'],'threadId' => $threadId, 'search' => $search];
        $info = ChatThreadUser::getListUserOfGroupIsAdmin($page, $limit, $arrData);
        if($info) {
            $results = $info->paginate($limit);
            return response([
                            'msg' => Lang::get('response.success'),
                            'data' => $results

            ]);
        }
        else {
            return response([
                            'msg' => Lang::get('response.success'),
                            'data' => []

            ]);
        }

    }

    public function addUserToAdmin(Request $req) {
        $user = $req->user();
        $groupChatId = $req->groupChatId;
        $listUser = $req->userId;
        foreach ( $listUser as $k => $v ) {
            $add = ChatThreadUser::updateUserAdmin($v, $groupChatId, 1);
        }
        array_push($listUser, $user['use_id']);
        //$detail = ChatThreadUser::getDetailUserAdmin($userId, $groupChatId, $user['use_id']);
        $detail = ChatThreads::getDetailThreadAlias($groupChatId, $user['use_id']);
        return response([
                            'msg' => Lang::get('response.success'),
                            'data' => $detail,
                            'listUser' => $listUser

        ]);

    }

    public function deleteUserAdminInGroup(Request $req) {
        $user = $req->user();
        $groupChatId = $req->groupChatId;
        $userId = $req->userId;
        $delete = ChatThreadUser::updateUserAdmin($userId, $groupChatId, 0);
        $listUser = [$user['use_id'], $userId];
        $detail = ChatThreadUser::getDetailUserAdmin($userId, $groupChatId, $user['use_id']);
        return response([
                            'msg' => Lang::get('response.success'),
                            'data' => $detail,
                            'listUser' => $listUser

        ]);

    }

    public function countMessageNotRead(Request $req) {
        $user = $req->user();
        $params = ['userId' => $user['use_id']];
        /*$listGroup = ChatThreads::getListGroupUser($params);*/
        /*$listGroup = ChatThreads::getListGroupUserPrivate($params);*/
        $listGroup = ChatThreads::getListGroupUserPrivateSecret($params);
        $sum = ChatUserRead::getSumCountUnread($user['use_id'], $listGroup);
        return response([
                            'msg' => Lang::get('response.success'),
                            'data' => intval($sum),
                            'userId' => $user['use_id']

        ]);
    }

    public function countInviteUnread(Request $req) {
        $user = $req->user();
        $params = ['userId' => $user['use_id']];
        $sum = ChatUserInvite::getSumCountInvite($user['use_id']);
        $listGroup = ChatThreads::getListGroupUserGroup($params);
        $sumMessageGroup = ChatUserRead::getSumCountUnread($user['use_id'], $listGroup);
        $sum = $sum + $sumMessageGroup;
        return response([
                            'msg' => Lang::get('response.success'),
                            'data' => intval($sum),
                            'userId' => $user['use_id'],

        ]);
    }

    public function userViewMessageIngroup(Request $req){
        $user = $req->user();
        $messageId = $req->messageId;
        $page = $req->page;
        $pageSize = $req->limit;
        $detailMessage = ChatMessages::where('id', $messageId)->select("threadId")->first();
        $groupChatId = $detailMessage->threadId;
        $list = ChatThreadUser::getListUserViewMessage($page, $pageSize, $messageId, $groupChatId, $user['use_id']);
        $list = $list->paginate($pageSize);
        return response([
                            'msg' => Lang::get('response.success'),
                            'data' => $list,

        ]);

    }

    public function userUnreadMessageIngroup(Request $req) {
        $user = $req->user();
        $messageId = $req->messageId;
        $page = $req->page;
        $pageSize = $req->limit;
        $detailMessage = ChatMessages::where('id', $messageId)->select("threadId")->first();
        $groupChatId = $detailMessage->threadId;
        $list = ChatThreadUser::getListUserUnreadMessage($page, $pageSize, $messageId, $groupChatId, $user['use_id']);
        $list = $list->paginate($pageSize);
        return response([
                            'msg' => Lang::get('response.success'),
                            'data' => $list,

        ]);

    }

    public function userAnswerMessage(Request $req) {
        $user = $req->user();
        $messageId = $req->messageId;
        $page = $req->page;
        $pageSize = $req->limit;
        $arrIdRecu = ChatMessages::getArrayMessageParentId($messageId);
        //Trong trường hợp chưa có ai trả lời cho nội dung cuối cùng: Show câu trả lời ngay trước đó
        $list = ChatMessages::listUserAnswerMessage($page, $pageSize, $messageId,$user['use_id'], $arrIdRecu);
        $list = $list->paginate($pageSize);
        if($list->total() == 0) {
            $list = ChatMessages::listUserAnswerMessageCond($page, $pageSize, $messageId,$user['use_id']);
            $list = $list->paginate($pageSize);
        }
        if(count($list) > 0) {
                    foreach( $list as $k => &$v) {
                        $userId = $v['use_id'];
                        $threadId = $v['threadId'];
                        $parentId = $v['parentId'];
                        $message = ChatMessages::where(['ownerId' => $userId, 'threadId' => $threadId])->whereIn("id", $arrIdRecu)->orderby("id","desc")->first();
                        if($message) {
                            $v['message'] = $message->text;
                            $v['messageId'] = $message->id;
                        }
                        else {
                            $message = ChatMessages::where(['id' => $v['messageId']])->first();
                            $v['message'] = $message->text;
                        }
                    }
                }
                return response([
                                    'msg' => Lang::get('response.success'),
                                    'data' => $list,

                ]);

        /*$user = $req->user();
        $messageId = $req->messageId;
        $page = $req->page;
        $pageSize = $req->limit;
        $count = ChatMessages::sumUserAnswerMessage($messageId,$user['use_id']);
        $list = ChatMessages::listUserAnswerMessage1($page, $pageSize, $messageId,$user['use_id']);
        if(count($list) > 0) {
            foreach( $list as $k => &$v) {
                $userId = $v['use_id'];
                $message = ChatMessages::where(['id' => $v['messageId']])->first();
                if($message) {
                    $v['message'] = $message->text;
                }
            }
        }
        $offset = ($page - 1) * $pageSize;
        $to = ($offset + $pageSize);
        if($to < $pageSize) {
            $to = $count;
        }
        $arrReturn = [
            "total" => $count,
            "per_page" => $pageSize,
            "current_page" => $page,
            "last_page" => ceil($count/$pageSize),
            "next_page_url" => "",
            "prev_page_url" => null,
            "from" =>  $offset,
            "to" => $to,
            "data" => $list];
        return response([
                            'msg' => Lang::get('response.success'),
                            'data' => $arrReturn,
        ]);*/
    }

    public function allDetailUserReplymessage_v1(Request $req) {
        $user = $req->user();
        $messageId = $req->messageId;
        $userId = $req->userId;
        $page = 1;
        if( isset($req->page)) {
            $page = $req->page;
        }
        $pageSize = 10;
        if( isset($req->limit)) {
            $pageSize = $req->limit;
        }
        $detail = ChatMessages::where("id", $messageId)->first();
        $infoUser = ChatThreadUser::getInfoUserAlias($userId, $user['use_id']);
        if($detail->parentMessId != 0) {
            $messageId = $detail->parentMessId;
        }
        $list = ChatMessages::listReplyMessageAll($page, $pageSize, $messageId,$userId, $user['use_id'], $detail->threadId);
        if(count($list) > 0) {
            foreach( $list as $k => &$v) {
                $v['created_ts'] = Commons::convertDateTotime($v['createdAt']);
                $v['updated_ts'] = Commons::convertDateTotime($v['updatedAt']);
                if($v['typedata'] == 'multiImage') {
                    //if($v['listImage'] != "" && $v['listImage'] != null) {
                        //$v['listImage'] = json_decode($v['listImage'], true);
                    //}
                }
                else {
                    $v['listImage'] = [];
                }

            }
        }
        $info =  DB::table('chatuseroffline')
            ->where('userId', $userId)->first();
        if(!$info)  {
            $timedate =  'Đang offline';

        }
        else {
            $timedate = ChatUserOff::time_elapsed_string($info->timeDate);
        }
        $infoUser['statusUser'] = $timedate;
        $infoUser['lastAnswer'] = ChatMessages::getLastTimeAnswer($messageId,$userId, $user['use_id']);
        /*return response([
                            'msg' => Lang::get('response.success'),
                            'data' => $list,
                            'infoUser' => $infoUser

        ]);*/
        $count = ChatMessages::countReplyMessage($messageId,$userId, $user['use_id'], $detail->threadId);
        $offset = ($page - 1) * $pageSize;
        $to = ($offset + $pageSize);
        if($count < $pageSize) {
            $to = $count;
        }
        if( $count == 0) {
            $last_page =  0;
        }
        else {
            $last_page = ceil($count/$pageSize);
        }

        $arrReturn = [
            "total" => $count,
            "per_page" => intval($pageSize),
            "current_page" => intval( $page),
            "last_page" => intval($last_page),
            "next_page_url" => "",
            "prev_page_url" => null,
            "from" =>  $offset,
            "to" => $to,
            "data" => $list];

        return response([
                            'msg' => Lang::get('response.success'),
                            'data' => $arrReturn,
                            'infoUser' => $infoUser

        ]);

    }

    public function allDetailUserReplymessage(Request $req) {
        $user = $req->user();
        $messageId = $req->messageId;
        $userId = $req->userId;
        $page = 1;
        if( isset($req->page)) {
            $page = $req->page;
        }
        $pageSize = 10;
        if( isset($req->limit)) { 
            $pageSize = $req->limit;
        }
        $detail = ChatMessages::where("id", $messageId)->first();
        $infoUser = ChatThreadUser::getInfoUserAlias($userId, $user['use_id']);
        if($detail->parentMessId != 0) {
            $messageId = $detail->parentMessId;
        }
        if(isset($req->element) && $req->element != "" ) {
            $list = ChatMessages::detailUserReplyImageMessage($user['use_id'], $messageId, $detail->threadId, $req->element); 
        }
        else {
            $list = ChatMessages::detailUserReplymessage($user['use_id'], $messageId, $detail->threadId);
        }
        
        $list = $list->paginate($pageSize);
        $list = $list->toArray();
        if(count($list['data']) > 0) {
            foreach( $list['data'] as $k => &$v) {
                $v['created_ts'] = Commons::convertDateTotime($v['createdAt']);
                $v['updated_ts'] = Commons::convertDateTotime($v['updatedAt']);
                if($v['typedata'] == 'multiImage') {
                    if($v['listImage'] != "" && $v['listImage'] != null) {
                        if( !is_array($v['listImage'])) {
                            $v['listImage'] = json_decode($v['listImage'], true);
                        }

                    }
                }
                else {
                    $v['listImage'] = [];
                }

                if($v['typedata'] == 'product') {
                    $infoProduct = ChatMessages::getDetailProduct($v['productId']);
                    $v['infoProduct'] = $infoProduct;
                }
                else {
                    $v['infoProduct'] = null;
                }
                if($v['typedata'] == 'share_location' ) {
                    $created_share = $v['createdAt'];
                    $share_time = $v['share_time'];
                    $time_submius = Carbon::now()->subMinutes($share_time);
                    if($created_share < $time_submius) {
                        $v['stop_share'] = true;
                    }
                    else {
                        $v['stop_share'] = false;
                    }
                }

                if($v['element'] != null  && $v['element'] != "") {
                    $v['element'] = json_decode($v['element'], true);
                }

                if(isset($req->element) && $req->element != "" && $v['messageId'] == 0 ) {
                    $v['element'] = json_decode($detail->element, true);    
                }

                $v['emoij'] = ChatMessageEmoij::getEmoijMessage($v['id']);
                $infoEmoi = ChatMessageEmoij::infoChooseEmoij($v['id'], $user['use_id']);
                if(count($infoEmoi) > 0) {
                    $v['chooseEmoij'] = 1;
                    $v['typeEmoij'] = $infoEmoi['emoij'];
                }
                else {
                    $v['chooseEmoij'] = 0;
                    $v['typeEmoij'] = "";
                }




            }
        }
        $info =  DB::table('chatuseroffline')
            ->where('userId', $userId)->first();
        if(!$info)  {
            $timedate =  'Đang offline';

        }
        else {
            $timedate = ChatUserOff::time_elapsed_string($info->timeDate);
        }
        $infoUser['statusUser'] = $timedate;
        $infoUser['lastAnswer'] = ChatMessages::getLastTimeAnswer($messageId,$userId, $user['use_id']);

        return response([
                            'msg' => Lang::get('response.success'),
                            'data' => $list,
                            'infoUser' => $infoUser

        ]);

    }

    public function detailUserReplymessage_v1(Request $req) {
        $user = $req->user();
        $messageId = $req->messageId;
        $userId = $req->userId;
        $page = 1;
        if( isset($req->page)) {
            $page = $req->page;
        }
        $pageSize = 10;
        if( isset($req->limit)) {
            $pageSize = $req->limit;
        }
        $detail = ChatMessages::where("id", $messageId)->first();

        if(isset($req->case)) {
            $threadId = $detail->threadId;
            $userParent = $detail->ownerId;
        }
        else {
            $userParent = $user['use_id'];
            $threadId = $detail->threadId;
        }
        if($detail->parentMessId != 0) {
            $messageId = $detail->parentMessId;
        }

        $infoUser = ChatThreadUser::getInfoUserAlias($userId, $user['use_id']);
        $list = ChatMessages::listReplyMessage1($page, $pageSize, $messageId,$userId, $userParent, $threadId );
        //$list = $list->paginate($pageSize);
        if(count($list) > 0) {
            foreach( $list as $k => &$v) {
                $v['created_ts'] = Commons::convertDateTotime($v['createdAt']);
                $v['updated_ts'] = Commons::convertDateTotime($v['updatedAt']);
                if($v['typedata'] == 'multiImage') {
                    if(!is_array($v['listImage'])) {
                        $v['listImage'] = json_decode($v['listImage'], true);
                    }

                }
                else {
                    $v['listImage'] = [];
                }
                //$v['messageId'] = $v['id'];
                if($v['typedata'] == 'product') {
                    $infoProduct = ChatMessages::getDetailProduct($v['productId']);
                    $v['infoProduct'] = $infoProduct;
                }
                else {
                    $v['infoProduct'] = null;
                }
                if($v['typedata'] == 'share_location' ) {
                    $created_share = $v['createdAt'];
                    $share_time = $v['share_time'];
                    $time_submius = Carbon::now()->subMinutes($share_time);
                    if($created_share < $time_submius) {
                        $v['stop_share'] = true;
                    }
                    else {
                        $v['stop_share'] = false;
                    }
                }


                $v['emoij'] = ChatMessageEmoij::getEmoijMessage($v['id']);
                $infoEmoi = ChatMessageEmoij::infoChooseEmoij($v['id'], $user['use_id']);
                if(count($infoEmoi) > 0) {
                    $v['chooseEmoij'] = 1;
                    $v['typeEmoij'] = $infoEmoi['emoij'];
                }
                else {
                    $v['chooseEmoij'] = 0;
                    $v['typeEmoij'] = "";
                }


            }
        }
        $info =  DB::table('chatuseroffline')
            ->where('userId', $userId)->first();
        if(!$info)  {
            $timedate =  'Đang offline';

        }
        else {
            $timedate = ChatUserOff::time_elapsed_string($info->timeDate);
        }
        $infoUser['statusUser'] = $timedate;
        $infoUser['lastAnswer'] = ChatMessages::getLastTimeAnswer($messageId,$userId, $userParent);

        $count = ChatMessages::countReplyMessage1($messageId,$userId, $userParent, $threadId);
        $offset = ($page - 1) * $pageSize;
        $to = ($offset + $pageSize);
        if($count < $pageSize) {
            $to = $count;
        }
        if( $count == 0) {
            $last_page =  0;
        }
        else {
            $last_page = ceil($count/$pageSize);
        }

        $arrReturn = [
            "total" => $count,
            "per_page" => intval($pageSize),
            "current_page" => intval( $page),
            "last_page" => intval($last_page),
            "next_page_url" => "",
            "prev_page_url" => null,
            "from" =>  $offset,
            "to" => $to,
            "data" => $list];

        return response([
                            'msg' => Lang::get('response.success'),
                            'data' => $arrReturn,
                            'infoUser' => $infoUser

        ]);

        /*$user = $req->user();
        $messageId = $req->messageId;
        $userId = $req->userId;
        $infoUser = ChatThreadUser::getInfoUserAlias($userId, $user['use_id']);
        $info =  DB::table('chatuseroffline')
            ->where('userId', $userId)->first();
        if(!$info)  {
            $timedate =  'Đang offline';

        }
        else {
            $timedate = ChatUserOff::time_elapsed_string($info->timeDate);

        }
        $detail = ChatMessages::getDetailMesssage($messageId);
        $infoUser['statusUser'] = $timedate;
        $infoUser['lastAnswer'] = ChatMessages::getLastTimeAnswer($messageId, $userId, $user['use_id']);
        $infoUser['detail'] = $detail;
        return response([
                            'msg' => Lang::get('response.success'),
                            'data' => $infoUser


        ]);*/
    }

    public function detailUserReplymessage(Request $req) {
        $user = $req->user();
        $messageId = $req->messageId;
        $userId = $req->userId;
        $page = 1;
        if( isset($req->page)) {
            $page = $req->page;
        }
        $pageSize = 10;
        if( isset($req->limit)) {
            $pageSize = $req->limit;
        }
        $detail = ChatMessages::where("id", $messageId)->first();

        if(isset($req->case)) {
            $threadId = $detail->threadId;
            $userParent = $detail->ownerId;
        }
        else {
            $userParent = $user['use_id'];
            $threadId = $detail->threadId;
        }
        if($detail->parentMessId != 0) {
            $messageId = $detail->parentMessId;
        }

        $infoUser = ChatThreadUser::getInfoUserAlias($userId, $user['use_id']);
        $cond = 0;
        if(isset($req->element) && $req->element != "") {
            $list = ChatMessages::detailUserReplymessageImagePrivate($user['use_id'], $userId, $messageId, $threadId, $cond, $req->element, $detail->element  );   
        }
        else {
            $list = ChatMessages::detailUserReplymessagePrivate($user['use_id'], $userId, $messageId, $threadId, $cond  );
        }
        
        $list = $list->paginate($pageSize);
        $list = $list->toArray();
        if(count($list['data']) > 0) {
            foreach( $list['data'] as $k => &$v) {
                $v['created_ts'] = Commons::convertDateTotime($v['createdAt']);
                $v['updated_ts'] = Commons::convertDateTotime($v['updatedAt']);
                if($v['typedata'] == 'multiImage') {
                    if(!is_array($v['listImage'])) {
                        $v['listImage'] = json_decode($v['listImage'], true);
                    }

                }
                else {
                    $v['listImage'] = [];
                }
                //$v['messageId'] = $v['id'];
                if($v['typedata'] == 'product') {
                    $infoProduct = ChatMessages::getDetailProduct($v['productId']);
                    $v['infoProduct'] = $infoProduct;
                }
                else {
                    $v['infoProduct'] = null;
                }
                if($v['typedata'] == 'share_location' ) {
                    $created_share = $v['createdAt'];
                    $share_time = $v['share_time'];
                    $time_submius = Carbon::now()->subMinutes($share_time);
                    if($created_share < $time_submius) {
                        $v['stop_share'] = true;
                    }
                    else {
                        $v['stop_share'] = false;
                    }
                }


                $v['emoij'] = ChatMessageEmoij::getEmoijMessage($v['id']);
                $infoEmoi = ChatMessageEmoij::infoChooseEmoij($v['id'], $user['use_id']);
                if(count($infoEmoi) > 0) {
                    $v['chooseEmoij'] = 1;
                    $v['typeEmoij'] = $infoEmoi['emoij'];
                }
                else {
                    $v['chooseEmoij'] = 0;
                    $v['typeEmoij'] = "";
                }

                if($v['element'] != null  && $v['element'] != "") {
                    $v['element'] = json_decode($v['element'], true);
                }
                if(isset($req->element) && $req->element != "" && $v['messageId'] == 0) {
                      $v['element'] = json_decode($detail->element, true);   
                }  


            }
        }
        $info =  DB::table('chatuseroffline')
            ->where('userId', $userId)->first();
        if(!$info)  {
            $timedate =  'Đang offline';

        }
        else {
            $timedate = ChatUserOff::time_elapsed_string($info->timeDate);
        }
        $infoUser['statusUser'] = $timedate;
        $infoUser['lastAnswer'] = ChatMessages::getLastTimeAnswer($messageId,$userId, $userParent);
        return response([
                    'msg' => Lang::get('response.success'),
                    'data' => $list,
                    'infoUser' => $infoUser
        ]);


    }


    public function detailMessageP2P(Request $req) {
        $user = $req->user();
        $messageId = $req->messageId;
        $userId = $req->userId;
        $page = 1;
        if( isset($req->page)) {
            $page = $req->page;
        }
        $pageSize = 10;
        if( isset($req->limit)) {
            $pageSize = $req->limit;
        }
        $infoUser = ChatThreadUser::getInfoUserAlias($userId, $user['use_id']);
        $userMesOrigin = "";
        $list = ChatMessages::listReplyMessageP2P($page, $pageSize, $messageId,$userId, $userMesOrigin, $user['use_id']);
        //$list = $list->paginate($pageSize);
        if(count($list) > 0) {
            foreach( $list as $k => &$v) {
                $v['created_ts'] = Commons::convertDateTotime($v['createdAt']);
                $v['updated_ts'] = Commons::convertDateTotime($v['updatedAt']);
                if($v['typedata'] == 'multiImage') {
                    $v['listImage'] = json_decode($v['listImage'], true);
                }
                else {
                    $v['listImage'] = [];
                }
                //$v['messageId'] = $v['id'];
            }
        }
        $info =  DB::table('chatuseroffline')
            ->where('userId', $userId)->first();
        if(!$info)  {
            $timedate =  'Đang offline';

        }
        else {
            $timedate = ChatUserOff::time_elapsed_string($info->timeDate);
        }
        $infoUser['statusUser'] = $timedate;
        $infoUser['lastAnswer'] = ChatMessages::getLastTimeAnswer($messageId,$userId, $user['use_id']);

        $count = ChatMessages::countReplyMessage1($messageId,$userId, $userMesOrigin, $user['use_id']);
        $offset = ($page - 1) * $pageSize;
        $to = ($offset + $pageSize);
        if($count < $pageSize) {
            $to = $count;
        }
        if( $count == 0) {
            $last_page =  0;
        }
        else {
            $last_page = ceil($count/$pageSize);
        }

        $arrReturn = [
            "total" => $count,
            "per_page" => intval($pageSize),
            "current_page" => intval( $page),
            "last_page" => intval($last_page),
            "next_page_url" => "",
            "prev_page_url" => null,
            "from" =>  $offset,
            "to" => $to,
            "data" => $list];

        return response([
                            'msg' => Lang::get('response.success'),
                            'data' => $arrReturn,
                            'infoUser' => $infoUser

        ]);
    }


    public function searchAll(Request $req) {
        $user = $req->user();
        $search = $req->search;
        $page = $req->page;
        $pageSize = $req->limit;
        $params = ['search' => $search, 'userLogin' => $user['use_id']];
        $list = ChatThreadUser::searchAll($page, $pageSize, $params);
        $list = $list->paginate($pageSize);
        if(count($list) > 0) {
            foreach( $list as $k => &$v) {
                if($v->groupChatId == null) {
                   $info = ChatThreadUser::getInfoSearchAll($v->use_id, $user['use_id']);
                   $v->avatar = $info['avatar'] ;
                   $v->groupChatId = $info['groupChatId'] ;
                   $v->type = $info['typechat'];
                   if($info['groupChatId'] != null && $info['groupChatId'] != 0  ) {
                        $v->lastListMessage = ChatThreads::getListLastMessage($info['groupChatId'], $user['use_id']);
                   }
                   else {
                        $v->lastListMessage = [];
                   }
                }
            }
        }
        return response([
                            'msg' => Lang::get('response.success'),
                            'userLogin' => $user['use_id'],
                            'data' => $list


        ]);

    }

    public function forwardMessage(Request $req) {
        $user = $req->user();
        $arrUserReq = $req->arrUser;
        $arrGroupDefault = $req->arrGroupDefault;
        $arrGroup = $req->arrGroup;
        $messageId = $req->messageId;
        $detailMessage = ChatMessages::where(['id' => $messageId])->first();
        $arrUserDefault = [];
        $arrCountMessageDefault = [];
        $arrUser = [];
        $arrCountMessageUser = [];
        $arrUserData = [];
        $arrUserGroup = [];
        $arrCountMessageGroup = [];
        $arrGroupData = [];

        $element = "";
        if( isset($req->element)) {

            $element = $req->element;
        }

        $dataDefault = [];
        if(count($arrUserReq) > 0) {
            foreach( $arrUserReq as $k => $v ) {
                $arrUser[] = $v;
                $message = ChatMessages::forwardMessage($detailMessage, $user['use_id'], $v, $element);
                $arrCountMessageUser[$v] = ChatUserRead::getSumCountUnreadOfUser($v);
                $arrUserData[$v] = $message;
            }
        }


        if(count($arrGroup) > 0) {

            foreach( $arrGroup as $k => $v_group ) {
                $message =  ChatMessages::forwardMessageInGroup($detailMessage, $user['use_id'], $v_group, $element  );
                $detailGroup = ChatThreads::where(['id' => $v_group ])->first();
                if($detailGroup) {
                    $typechat = $detailGroup->typechat;
                    $listUser = ChatThreadUser::where(['threadId' => $v_group, 'accept_request' => 1])->get();
                    foreach($listUser as $k => $v) {
                        $arrUserGroup[] = $v['userId'];
                        ChatUserRead::updateCountMessageUnreadUser($v_group, $v['userId']);
                        ChatMessageRead::createRowMessageUser(['messageId' => $message['id'], 'threadId' => $v_group, 'userId' => $v['userId'] ]);
                        $arrCountMessageGroup[$v['userId']] = ChatUserRead::getSumCountUnreadOfUser($v['userId']);
                        $arrGroupData[$v['userId']] = $message;
                    }

                }
            }
        }

        if(count($arrGroupDefault) > 0) {
            $arrData = ['type' => 'private',
                    'ownerId' => $user['use_id'],
                    'text' => $detailMessage->text ,
                    'typedata' =>$detailMessage->typedata ,
                    'messageId' =>$detailMessage->messageId ,
                    'width' => $detailMessage->width ,
                    'height' => $detailMessage->height ,
                    'size' => $detailMessage->size ,
                    'createdAt' => date('Y-m-d H:i:s'),
                    'updatedAt' => date('Y-m-d H:i:s'),
                    'listImage' => $detailMessage->listImage
                ];

            foreach( $arrGroupDefault as $k => $v_group ) {
                ChatMessages::forwardMessageInGroup($detailMessage, $user['use_id'], $v_group  );
                $detailGroup = ChatThreads::where(['id' => $v_group ])->first();
                if($detailGroup) {
                    switch ($detailGroup['alias']) {
                        case 'agent':
                            $listUser = $this->getListBrand( $user['use_id'] ,$req);
                            $dataDefault =  ChatThreads::createMessageForGroup($user->use_id, $listUser, $arrData);
                            foreach($listUser as $k => $v) {
                                $arrUserDefault[] = $v['use_id'];
                                $arrCountMessageDefault[$v['use_id']] = ChatUserRead::getSumCountUnreadOfUser($v['use_id']);
                            }
                            break;

                        case 'afflliate':
                            $listUser = $this->listAllaffiliateUnder($req);
                            $dataDefault =  ChatThreads::createMessageForGroup($user->use_id, $listUser, $arrData);
                            foreach($listUser as $k => $v) {
                                $arrUserDefault[] = $v['use_id'];
                                $arrCountMessageDefault[$v['use_id']] = ChatUserRead::getSumCountUnreadOfUser($v['use_id']);
                            }
                            break;

                        case 'staff':
                            $listUser = $this->listStaff( $user['use_id'] ,$req);
                            $dataDefault =   ChatThreads::createMessageForGroup($user->use_id, $listUser, $arrData);
                            foreach($listUser as $k => $v) {
                                $arrUserDefault[] = $v['use_id'];
                                $arrCountMessageDefault[$v['use_id']] = ChatUserRead::getSumCountUnreadOfUser($v['use_id']);
                            }
                            break;

                        case 'customer_bought':
                            $listUser = $this->customer_bought($user->use_id, $req);
                            $dataDefault =   ChatThreads::createMessageForGroup($user->use_id, $listUser, $arrData);
                            foreach($listUser as $k => $v) {
                                $arrUserDefault[] = $v['use_id'];
                                $arrCountMessageDefault[$v['use_id']] = ChatUserRead::getSumCountUnreadOfUser($v['use_id']);
                            }
                            break;

                        case 'customer_sell':
                            # code...
                            break;


                        case 'user_follow':
                            $listUser = ChatThreads::getUserGroupFolow(['userId' => $user->use_id]);
                            $dataDefault =   ChatThreads::createMessageForGroup($user->use_id, $listUser, $arrData);
                            foreach($listUser as $k => $v) {
                                $arrUserDefault[] = $v['use_id'];
                                $arrCountMessageDefault[$v['use_id']] = ChatUserRead::getSumCountUnreadOfUser($v['use_id']);
                            }

                            break;


                        default:
                            # code...
                            break;
                    }
                }
            }
        }
        return response([
                            'msg' => Lang::get('response.success'),
                            'data' => 'success',
                            'arrUser' => $arrUser,
                            'arrUserDefault' => $arrUserDefault,
                            'arrCountMessageDefault' => $arrCountMessageDefault,
                            'dataDefault' => [],
                            'arrUser' => $arrUser,
                            'arrCountMessageUser' => $arrCountMessageUser,
                            'arrUserData' => $arrUserData,
                            'arrUserGroup' => $arrUserGroup,
                            'arrCountMessageGroup' => $arrCountMessageGroup,
                            'arrGroupData' => $arrGroupData,
                            'userForward' => $user['use_id']

        ]);
    }

    public function listEmoij(Request $req) {
        $list = ChatEmoij::getList();
        return response([
                            'msg' => Lang::get('response.success'),
                            'data' =>$list

        ]);
    }

    public function setMessageEmoij(Request $req) {
        $user = $req->user();
        $userId = $user['use_id'];
        $params = [
                    'messageId' => $req->messageId,
                    'userId' => $userId,
                    'emoijId' => $req->emoijId,
                    'count' => $req->value
                ];
        $setEmoij = ChatMessageEmoij::setMessage($params);
        $countEmoij = ChatMessageEmoij::getCountEmoijMessage($params);
        $detailmessage = ChatMessages::getDetailMesssageEmoij($req->messageId, $user['use_id']);
        $listUser = ChatThreadUser::where(['threadId' => $detailmessage['threadId'], 'accept_request' => 1])->select("userId")->get();
        $arrReturn = [];
        foreach( $listUser as $k => $v) {
            /*$infoEmoi = ChatMessageEmoij::infoChooseEmoij($req->messageId, $v['userId']);
            if(count($infoEmoi) > 0) {
                $detailmessage['chooseEmoij'] = 1;
                $detailmessage['typeEmoij'] = $infoEmoi['emoij'];
                $detailmessage['emoijId'] = $infoEmoi['emoijId'];
            }
            else {
                $detailmessage['chooseEmoij'] = 0;
                $detailmessage['typeEmoij'] = "";
                $detailmessage['emoijId'] = 0;
            }*/
            $detailmessage1 = ChatMessages::getDetailMesssageEmoij($req->messageId, $v['userId']);
            $arrReturn[$v['userId']] = $detailmessage1;
        }
        return response([
                            'msg' => Lang::get('response.success'),
                            'data' => $detailmessage,
                            'arrReturn' => $arrReturn,
                            'listUser' => $listUser

        ]);

    }

    public function detailMessageEmoij(Request $req) {
        $user = $req->user();
        $page = $req->page;
        $pageSize = $req->limit;
        $params = ["messageId" => $req->messageId, 'userLogin' => $user['use_id'] ];
        $list = ChatMessageEmoij::getDetail($page, $pageSize, $params);
        $results = $list->paginate($pageSize);
        $results = $results->toArray();
        $total = ChatMessageEmoij::getTotalEmoij($req->messageId);
        $listEmoij = ChatMessageEmoij::getEmoijMessage($req->messageId);
        $arr = [
                'total' => $total,
                'listEmoij' => $listEmoij,
                'listUser' => $results
        ];
        return response([
                            'msg' => Lang::get('response.success'),
                            'data' =>$arr

        ]);


    }

    public function createSecretChat(Request $req) {
        $user = $req->user();
        $ownerId = $req->ownerId;
        $memberId = $req->memberId;
        $data = ChatThreads::where(function ($query) use ($ownerId, $memberId)  {
                     $query->where(['ownerId' => $ownerId, 'requesterId' => $memberId])
                            ->orWhere(['ownerId' => $memberId, 'requesterId' => $ownerId])
                     ;
                })->where("type","=","secret")->first();
        if($data) {
            $detailThread = ChatThreads::detailGroupPrivateAlias($user['use_id'], $data['id']);
            return response([
                'msg' => Lang::get('response.success'),
                'data' => ['thread' => $detailThread, 'ownerId' => $ownerId, 'memberId' => $memberId]

            ]);
        }
        else {
            $arrData = ['type' => 'secret',
                     'ownerId' =>  $ownerId,
                     'namegroup' => '',
                     'requesterId' => $memberId,
                     'typegroup' => '',
                     'avatar' => '',
                     'alias' => '',
                     'createdAt' => date('Y-m-d H:i:s'),
                     'updatedAt' => date('Y-m-d H:i:s'),
                    ];

            $threadId  = ChatThreads::createGroup($arrData);
            if( $threadId) {
                $arr = [$ownerId, $memberId];
                ChatThreadUser::addUserToGroup($threadId, $arr, 1 );
                //$data = ChatThreads::where("id","=", $threadId)->first();
                /*$detailThread = ChatThreads::detailGroupPrivate($user['use_id'], $threadId);*/
                $detailThread = ChatThreads::detailGroupPrivateAlias($user['use_id'], $threadId);
                return response([
                    'msg' => Lang::get('response.success'),
                    'data' => ['thread' => $detailThread, 'ownerId' => $ownerId, 'memberId' => $memberId]

                ]);
            }
            else {
                return response([
                        'msg' => Lang::get('response.failed'),

                ]);
            }
        }

    }

    public function listMessageSecret(Request $req) {
        $user = $req->user();
        $groupChat = $req->groupChatId;
        $page = $req->page;
        $pageSize = $req->limit;
        ChatUserRead::setRead($user->use_id, $groupChat);
        $infoGroup = ChatThreads::where(['id' => $groupChat])->first();
        $params = ['threadId' => $groupChat, 'userLogin' => $user['use_id'],'lastIdMessage' => $infoGroup['lastIdMessage'], 'idDelete' => $infoGroup['idDelete']];
        $list = ChatMessages::getListSecretAlias($page, $pageSize, $params);
        $results = $list->paginate($pageSize);
        if(count($results) > 0 ) {
            $statusLastChat = null;
            if($infoGroup['type'] == 'secret') {
                 $statusLastChat = ChatMessages::statusLastChat($infoGroup);

            }

            foreach( $results as $k => &$v) {
                $v['created_ts'] = Commons::convertDateTotime($v['createdAt']);
                $v['updated_ts'] = Commons::convertDateTotime($v['updatedAt']);
                if($v['typedata'] == 'multiImage') {
                    $v['listImage'] = json_decode($v['listImage'], true);
                }
                else {
                    $v['listImage'] = [];
                }
                if($v['typedata'] == 'product') {
                    $infoProduct = ChatMessages::getDetailProduct($v['productId']);
                    $v['infoProduct'] = $infoProduct;
                }
                else {
                    $v['infoProduct'] = null;
                }
                $v['statusRead'] = $statusLastChat;
                $v['emoij'] = ChatMessageEmoij::getEmoijMessage($v['id']);
                /*$v['chooseEmoij'] = ChatMessageEmoij::checkChooseEmoij($v['id'], $user['use_id']);*/
                $infoEmoi = ChatMessageEmoij::infoChooseEmoij($v['id'], $user['use_id']);
                if(count($infoEmoi) > 0) {
                    $v['chooseEmoij'] = 1;
                    $v['typeEmoij'] = $infoEmoi['emoij'];
                }
                else {
                    $v['chooseEmoij'] = 0;
                    $v['typeEmoij'] = "";
                }

            }
        }

        return response([
            'msg' => Lang::get('response.success'),
            'data' => $results
        ]);

    }

    public function updateCaption(Request $req) {
        $messageId = $req->messageId;
        $caption = $req->caption;
        $update = DB::table('chatmessages')->where(['id' => $messageId])->update(['caption' => $caption ]);
        if($update) {
            return response([
                'msg' => Lang::get('response.success'),
                'data' => ['messageId' => $messageId, 'caption' => $caption]
            ]);
        }
        else {
            return response([
                'msg' => Lang::get('response.failed')

            ]);
        }


    }

    public function updateAliasSecret(Request $req) {
        $user= $req->user();
        $userId = $user['use_id'];
        $threadId = $req->threadId;
        $alias = $req->alias;
        $update = ChatThreadUser::updateAliasSecet($userId, $threadId, $alias);
        $infoUserB = ChatThreadUser::where(['threadId' => $threadId])->where("userId", '<>', $userId)->first();
        if($update){
            $userB = 0;
            if( $infoUserB) {
                $userB = $infoUserB->userId;
            }
            return response([
                'msg' => Lang::get('response.success'),
                'data' => $update,
                "info" => ["value" => $alias, "userId" => $userB, 'type' => "alias", "threadId" => $threadId]
            ]);

        }
        else {
            return response([
                'msg' => Lang::get('response.failed')

            ]);
        }

    }

    public function updateAvatarSecret(Request $req) {
        $user= $req->user();
        $userId = $user['use_id'];
        $threadId = $req->threadId;
        $avatar = $req->avatar;
        $update = ChatThreadUser::updateAvatarSecet($userId, $threadId, $avatar);
        $infoUserB = ChatThreadUser::where(['threadId' => $threadId])->where("userId", '<>', $userId)->first();
        if($update){
            $userB = 0;
            if( $infoUserB) {
                $userB = $infoUserB->userId;
            }
            return response([
                'msg' => Lang::get('response.success'),
                'data' => $update,
                "info" => ["value" => $avatar, "userId" => $userB, 'type' => 'avatar', 'threadId' => $threadId]
            ]);
        }
        else {
            return response([
                'msg' => Lang::get('response.failed')

            ]);
        }
    }


    public function addInfoBrowser(Request $req) {
        $user= $req->user();
        $userId = $user['use_id'];
        if(isset($req->type)) {
            $type = $req->type;
        }
        else {
            $type = 'favorite';
        }

        if( $type == 'favorite') {
            $info = ChatBrowser::where(['link' => $req->link, 'type' => 'favorite' ])->first();
            if($info) {
                $add = $info;
            }
            else {
                $add = ChatBrowser::create(['userId' => $userId,
                                        'icon' => $req->icon,
                                        'title' => $req->title,
                                        'link' => $req->link,
                                        'type' => $req->type,
                                    ]);
            }
        }
        else {
            $add = ChatBrowser::create(['userId' => $userId,
                                        'icon' => $req->icon,
                                        'title' => $req->title,
                                        'link' => $req->link,
                                        'type' => $req->type,
                                    ]);
        }



        if($add) {
            return response([
                'msg' => Lang::get('response.success'),
                'data' => $add

            ]);
        }
        else {
            return response([
                'msg' => Lang::get('response.failed')

            ]);
        }
    }

    public function addArrayInfoBrowser(Request $req) {
        $user= $req->user();
        $userId = $user['use_id'];
        $type = 'favorite';
        $dataRequest = $req->data;
        if($userId) {
            ChatBrowser::where(['userId' => $userId])->delete();
        }
        foreach($dataRequest as $k => $v ) {

            $add = ChatBrowser::create(['userId' => $userId,
                                    'icon' => $v['icon'],
                                    'title' => $v['title'],
                                    'link' => $v['link'],
                                    'type' => $type,
                                    'created_ts' => $v['created_ts'],
                                    "descr" => $v['descr'],
                                    "descrImg" => $v['descrImg']
                                ]);

        }

        if($add) {
            return response([
                'msg' => Lang::get('response.success'),
                'data' => 'Success'

            ]);
        }
        else {
            return response([
                'msg' => Lang::get('response.failed')

            ]);
        }
    }

    public function listBrowser(Request $req){
        $type = $req->type;
        $limit = $req->limit;
        $user= $req->user();
        $userId = $user['use_id'];
        if($type == 'favorite') {
            //$list = ChatBrowser::where(['type' => $type, 'userId' => $userId])->groupby('link')->paginate($limit);
            $list = ChatBrowser::where(['type' => $type, 'userId' => $userId])->orderby("id",'asc')->groupby('link')->get();
        }
        else {
            //$list = ChatBrowser::where(['type' => $type, 'userId' => $userId])->paginate($limit);
            $list = ChatBrowser::where(['type' => $type, 'userId' => $userId])->orderby("id",'asc')->get();
        }
        if($list ) {
            foreach ($list as $key => &$value) {
                if( $value['created_ts'] == "") {
                    $value['created_ts'] =   Commons::convertDateTotime($value['created_at']);
                }


            }
        }

        //return response($list);
        return response([
                'msg' => Lang::get('response.success'),
                'data' => $list

            ]);
    }

    public function deleteAllInfoBrowser(Request $req) {
        $type = $req->type;
        $user= $req->user();
        $userId = $user['use_id'];
        $delete = ChatBrowser::where(['userId' => $userId ])->delete();
        if($delete) {
            return response([
                'msg' => Lang::get('response.success'),
                'data' => $delete

            ]);
        }
        else {
            return response([
                'msg' => Lang::get('response.failed')

            ]);
        }
    }

    public function deleteItemBrowser(Request $req) {
        $user= $req->user();
        $userId = $user['use_id'];
        $id = $req->id;
        $delete = ChatBrowser::where(['id' => $id ])->delete();
        if($delete) {
            return response([
                'msg' => Lang::get('response.success'),
                'data' => $delete

            ]);
        }
        else {
            return response([
                'msg' => Lang::get('response.failed')

            ]);
        }
    }

    public function infoTurnServer(Request $req) {
        if(isset($req->key) &&  $req->key == 'keyazibai@123') {
            //echo asset('config/turnserver.config');
            $path = config_path() . "/turnserver.config";
            $json = json_decode(file_get_contents($path), true);
            //return response($json);
            return response([
                'msg' => Lang::get('response.success'),
                'data' => $json

            ]);

        }
        if(isset($req->key) &&  $req->key == 'keyazibai@1234') {
            //echo asset('config/turnserver.config');
            $path = config_path() . "/turnserver_android.config";
            $json = json_decode(file_get_contents($path), true);
            //return response($json);
            return response([
                'msg' => Lang::get('response.success'),
                'data' => $json

            ]);

        }
    }

    public function getOriginMessageReply(Request $req) {
        $replyId = $req->messageId;
        $arrId = ChatMessages::getOriginMesRecurise($replyId, $messageId);
        return response($arrId);
    }



    public function setNameConversation(Request $req) {
        $user = $req->user();
        $messageId = $req->messageId;
        $groupChatId = $req->groupChatId;
        $name = $req->name;
        $set = ChatMessages::where("id",$messageId)->update(['subjectName' => $name]);
        if($set) {
            $listUser = ChatThreadUser::where(['threadId' => $groupChatId, 'accept_request' => 1])->select("userId")->pluck("userId");
            $detailMessage = ChatMessages::getDetailMesssage($messageId, $user->use_id);
            return response([
                'msg' => Lang::get('response.success'),
                'data' => $detailMessage,
                'listUser' => $listUser,
                'userId' => $user->use_id

            ]);
        }
        else {
            return response([
                'msg' => Lang::get('response.failed')

            ]);
        }
    }

    public function listMyConversationSetName(Request $req) {
        $user = $req->user();
        $threadId = $req->threadId;
        $pageSize = $req->limit;
        $page = $req->page;
        $userLogin = $user['use_id'];
        $list = ChatMessages::join("tbtt_user","tbtt_user.use_id","=","chatmessages.ownerId")
                            ->leftJoin(DB::raw("(select * from chatuseralias where userId = $userLogin) as chatuseralias"), function($join) {
                                     $join->on('tbtt_user.use_id', '=', 'chatuseralias.userId_alias');
                            })
                            ->leftJoin(DB::raw("(select * from chatmessages_pin where userId = $userLogin and threadId = $threadId) as chatmessages_pin"), function($join) {
                                     $join->on('chatmessages.id', '=', 'chatmessages_pin.messageId');
                            })
                            ->where(['chatmessages.threadId' => $threadId, 'chatmessages.messageId' => 0, 'chatmessages.ownerId' => $user['use_id']])
                            ->where('chatmessages.subjectName', '<>', null)
                            ->where('chatmessages.subjectName', '<>', '')
                            ->where('chatmessages.parentIdRepeat', '=', 0)
                            ->select("tbtt_user.avatar","tbtt_user.use_username",DB::raw('(CASE WHEN chatuseralias.name_alias is null THEN tbtt_user.use_fullname ELSE chatuseralias.name_alias END) AS use_fullname'), "chatmessages.*", "chatmessages_pin.pin as pin_new" )
                            ->orderby('pin_new', 'desc')->orderby('createdAt', 'desc');
        $list = $list->paginate($pageSize);
        if (sizeof($list->items()) > 0) {
            foreach ($list->items() as $val) {
                $val['created_ts'] = Commons::convertDateTotime($val['createdAt']);
                $val['updated_ts'] = Commons::convertDateTotime($val['updatedAt']);
                if($val['typedata'] == 'multiImage') {
                    //$v['listImage'] = json_decode($v['listImage'], true);
                }
                else {
                    $val['listImage'] = [];
                }
                //$v['messageId'] = $v['id'];
                if($val['typedata'] == 'product') {
                    $infoProduct = ChatMessages::getDetailProduct($val['productId']);
                    $val['infoProduct'] = $infoProduct;
                }
                else {
                    $val['infoProduct'] = null;
                }
            }
        }
        return response([
                'msg' => Lang::get('response.success'),
                'data' => $list

            ]);



    }

    public function listMyRepeat(Request $req) {
        $user = $req->user();
        $threadId = $req->threadId;
        $pageSize = $req->limit;
        $page = $req->page;
        $userLogin = $user['use_id'];
        $list = ChatMessages::join("tbtt_user","tbtt_user.use_id","=","chatmessages.ownerId")
                            ->leftJoin(DB::raw("(select * from chatuseralias where userId = $userLogin) as chatuseralias"), function($join) {
                                     $join->on('tbtt_user.use_id', '=', 'chatuseralias.userId_alias');
                            })
                            ->where(['chatmessages.threadId' => $threadId, 'chatmessages.messageId' => 0, 'chatmessages.ownerId' => $userLogin])
                            ->where('chatmessages.message_repeat', '=', 1)
                            ->where('chatmessages.parentIdRepeat', '=', 0)
                            ->select("tbtt_user.avatar","tbtt_user.use_username",DB::raw('(CASE WHEN chatuseralias.name_alias is null THEN tbtt_user.use_fullname ELSE chatuseralias.name_alias END) AS use_fullname'), "chatmessages.*")
                            ->orderby('createdAt', 'desc');
        $list = $list->paginate($pageSize);
        if (sizeof($list->items()) > 0) {
            foreach ($list->items() as $val) {
                $val['created_ts'] = Commons::convertDateTotime($val['createdAt']);
                $val['updated_ts'] = Commons::convertDateTotime($val['updatedAt']);
                if($val['typedata'] == 'multiImage') {
                    if(!is_array($val['listImage'])) {
                        $val['listImage'] = json_decode($val['listImage'], true);
                    }

                }
                else {
                    $val['listImage'] = [];
                }
                //$v['messageId'] = $v['id'];
                if($val['typedata'] == 'product') {
                    $infoProduct = ChatMessages::getDetailProduct($val['productId']);
                    $val['infoProduct'] = $infoProduct;
                }
                else {
                    $val['infoProduct'] = null;
                }
            }
        }
        return response([
                'msg' => Lang::get('response.success'),
                'data' => $list

        ]);



    }

    public function listGroupConversationSetName(Request $req) {
        $user = $req->user();
        $threadId = $req->threadId;
        $pageSize = $req->limit;
        $page = $req->page;
        $userLogin = $user['use_id'];
        $list = ChatMessages::join("tbtt_user","tbtt_user.use_id","=","chatmessages.ownerId")
                            ->leftJoin(DB::raw("(select * from chatuseralias where userId = $userLogin) as chatuseralias"), function($join) {
                                     $join->on('tbtt_user.use_id', '=', 'chatuseralias.userId_alias');
                            })
                            ->leftJoin(DB::raw("(select * from chatmessages_pin where userId = $userLogin and threadId = $threadId) as chatmessages_pin"), function($join) {
                                     $join->on('chatmessages.id', '=', 'chatmessages_pin.messageId');
                            })
                            ->where(['chatmessages.threadId' => $threadId, 'chatmessages.messageId' => 0])
                            ->where('chatmessages.subjectName', '<>', null)
                            ->where('chatmessages.subjectName', '<>', '')
                            ->where('chatmessages.parentIdRepeat', '=', 0)
                            ->select("tbtt_user.avatar","tbtt_user.use_username",DB::raw('(CASE WHEN chatuseralias.name_alias is null THEN tbtt_user.use_fullname ELSE chatuseralias.name_alias END) AS use_fullname'), "chatmessages.*", "chatmessages_pin.pin as pin_new" )
                            ->orderby('pin_new', 'desc')
                            ->orderby('createdAt', 'desc');
        $list = $list->paginate($pageSize);
        if (sizeof($list->items()) > 0) {
            foreach ($list->items() as $val) {
                $val['created_ts'] = Commons::convertDateTotime($val['createdAt']);
                $val['updated_ts'] = Commons::convertDateTotime($val['updatedAt']);
                if($val['typedata'] == 'multiImage') {
                    //$v['listImage'] = json_decode($v['listImage'], true);
                }
                else {
                    $val['listImage'] = [];
                }
                //$v['messageId'] = $v['id'];
                if($val['typedata'] == 'product') {
                    $infoProduct = ChatMessages::getDetailProduct($val['productId']);
                    $val['infoProduct'] = $infoProduct;
                }
                else {
                    $val['infoProduct'] = null;
                }
            }
        }
        return response([
                'msg' => Lang::get('response.success'),
                'data' => $list

            ]);
    }


    public function listGroupRepeat(Request $req) {
        $user = $req->user();
        $threadId = $req->threadId;
        $pageSize = $req->limit;
        $page = $req->page;
        $userLogin = $user['use_id'];
        $list = ChatMessages::join("tbtt_user","tbtt_user.use_id","=","chatmessages.ownerId")
                            ->leftJoin(DB::raw("(select * from chatuseralias where userId = $userLogin) as chatuseralias"), function($join) {
                                     $join->on('tbtt_user.use_id', '=', 'chatuseralias.userId_alias');
                            })

                            ->where(['chatmessages.threadId' => $threadId, 'chatmessages.messageId' => 0])
                            ->where('chatmessages.message_repeat', '=', 1)
                            ->where('chatmessages.ownerId', '<>', $userLogin)
                            ->where('chatmessages.parentIdRepeat', '=', 0)
                            ->select("tbtt_user.avatar","tbtt_user.use_username",DB::raw('(CASE WHEN chatuseralias.name_alias is null THEN tbtt_user.use_fullname ELSE chatuseralias.name_alias END) AS use_fullname'), "chatmessages.*")
                            ->orderby('createdAt', 'desc');
        $list = $list->paginate($pageSize);
        if (sizeof($list->items()) > 0) {
            foreach ($list->items() as $val) {
                $val['created_ts'] = Commons::convertDateTotime($val['createdAt']);
                $val['updated_ts'] = Commons::convertDateTotime($val['updatedAt']);
                if($val['typedata'] == 'multiImage') {
                    if(!is_array($val['listImage'])) {
                        $val['listImage'] = json_decode($val['listImage'], true);
                    }
                }
                else {
                    $val['listImage'] = [];
                }
                //$v['messageId'] = $v['id'];
                if($val['typedata'] == 'product') {
                    $infoProduct = ChatMessages::getDetailProduct($val['productId']);
                    $val['infoProduct'] = $infoProduct;
                }
                else {
                    $val['infoProduct'] = null;
                }
            }
        }
        return response([
                'msg' => Lang::get('response.success'),
                'data' => $list

            ]);
    }


    public function deleteSetNameConversation(Request $req) {
        $user = $req->user();
        $messageId = $req->messageId;
        $groupChatId = $req->groupChatId;
        $update = ChatMessages::where('id', $messageId)->update(['subjectName' => '']);
        if($update) {
            $listUser = ChatThreadUser::where(['threadId' => $groupChatId, 'accept_request' => 1])->select("userId")->pluck("userId");
            $detailMessage = ChatMessages::getDetailMesssage($messageId, $user->use_id);
            return response([
                'msg' => Lang::get('response.success'),
                'data' => $detailMessage,
                'listUser' => $listUser,
                'userId' => $user->use_id

            ]);
        }
        else {
            return response([
                'msg' => Lang::get('response.failed')

            ]);
        }
    }

    /*public function pinConversation(Request $req) {
        $threadId = $req->threadId;
        $messageId = $req->messageId;
        $max = ChatMessages::where(['threadId' => $threadId])->max('pin');
        $update = ChatMessages::where('id', $messageId)->update(['pin' => ($max + 1)]);
        if($update) {
            return response([
                'msg' => Lang::get('response.success'),
                'data' => $update

            ]);
        }
        else {
            return response([
                'msg' => Lang::get('response.failed')

            ]);
        }

    }
    */

    public function pinConversation(Request $req) {
        $user = $req->user();
        $threadId = $req->threadId;
        $messageId = $req->messageId;
        $max = ChatMessagePin::where(['threadId' => $threadId, 'userId' => $user->use_id])->max('pin');
        $check = ChatMessagePin::where(['messageId' => $messageId, 'userId' => $user->use_id])->first();
        if($check) {
            $data = ChatMessagePin::where(['messageId' => $messageId, 'userId' => $user->use_id])->update(['pin' => ($max + 1)]);
        }
        else {
            $data = ChatMessagePin::create(['messageId' => $messageId, 'userId' => $user->use_id, 'threadId' => $threadId, 'pin' => ($max + 1)]);
        }
        if($data) {
            return response([
                'msg' => Lang::get('response.success'),
                'data' => $data

            ]);
        }
        else {
            return response([
                'msg' => Lang::get('response.failed')

            ]);
        }

    }


    public function unPinConversation(Request $req) {
        $threadId = $req->threadId;
        $messageId = $req->messageId;
        $user = $req->user();
        $update = ChatMessagePin::where(['messageId' => $messageId, 'userId' => $user->use_id])->update(['pin' => 0]);
        if($update) {
            return response([
                'msg' => Lang::get('response.success'),
                'data' => $update
            ]);
        }
        else {
            return response([
                'msg' => Lang::get('response.failed')

            ]);
        }

    }


    public function getLastParentMessage(Request $req){
        /* INSERT WHEN SEND MESSAGE
        $messageId  = $req->messageId;
        $parent = ChatMessages::getArrayIDMessageParent($messageId);
        echo $parent;
        die();
        */
        /* update value parentMessId for table message
        $user = $req->user();
        $userLogin = $user['use_id'];
        $listMessages = ChatMessages::where("messageId", 0)->select("id")->get();
        foreach($listMessages as $k => $v) {
            $id = $v['id'];
            $arr = ChatMessages::getArrElementChild($id, $userLogin, $cond = 1);
            ChatMessages::whereIn("id",$arr)->update(['parentMessId' => $id ]);
        }
        echo 'thanh cong';
        */

        $groupChat = $req->groupChatId;
        $infoGroup = ChatThreads::where(['id' => $groupChat])->first();
        if($infoGroup['type'] == 'secret') {
            $user = $req->user();
            $groupChat = $req->groupChatId;
            $page = $req->page;
            $pageSize = $req->limit;
            ChatUserRead::setRead($user->use_id, $groupChat);
            $infoGroup = ChatThreads::where(['id' => $groupChat])->first();
            $params = ['threadId' => $groupChat, 'userLogin' => $user['use_id'],'lastIdMessage' => $infoGroup['lastIdMessage'], 'idDelete' => $infoGroup['idDelete']];
            $list = ChatMessages::getListSecretAlias($page, $pageSize, $params);
            $results = $list->paginate($pageSize);
            if(count($results) > 0 ) {
                $statusLastChat = null;
                /*if($infoGroup['type'] == 'secret') {
                     $statusLastChat = ChatMessages::statusLastChat($infoGroup);

                }*/

                foreach( $results as $k => &$v) {
                    $v['created_ts'] = Commons::convertDateTotime($v['createdAt']);
                    $v['updated_ts'] = Commons::convertDateTotime($v['updatedAt']);
                    if($v['typedata'] == 'multiImage') {
                        $v['listImage'] = json_decode($v['listImage'], true);
                    }
                    else {
                        $v['listImage'] = [];
                    }
                    if($v['typedata'] == 'product') {
                        $infoProduct = ChatMessages::getDetailProduct($v['productId']);
                        $v['infoProduct'] = $infoProduct;
                    }
                    else {
                        $v['infoProduct'] = null;
                    }
                    if($v['ownerId'] == $infoGroup['ownerId']) {
                        $statusLastChat = ChatMessages::statusReadOfUser($infoGroup, $v['id'],$infoGroup['requesterId']);
                    }
                    else {
                        $statusLastChat = ChatMessages::statusReadOfUser($infoGroup, $v['id'], $infoGroup['ownerId']);
                    }

                    if($statusLastChat){
                        $v['statusRead'] = $statusLastChat['statusRead'];
                        $v['timeRead'] = $statusLastChat['timeRead'];
                    }


                    $v['emoij'] = ChatMessageEmoij::getEmoijMessage($v['id']);

                    $infoEmoi = ChatMessageEmoij::infoChooseEmoij($v['id'], $user['use_id']);
                    if(count($infoEmoi) > 0) {
                        $v['chooseEmoij'] = 1;
                        $v['typeEmoij'] = $infoEmoi['emoij'];
                    }
                    else {
                        $v['chooseEmoij'] = 0;
                        $v['typeEmoij'] = "";
                    }

                }
            }

            return response([
                'msg' => Lang::get('response.success'),
                'data' => $results
            ]);
        }
        $user = $req->user();

        $page = $req->page;
        $pageSize = $req->limit;
        ChatUserRead::setRead($user->use_id, $groupChat);

        if($infoGroup['idGroupDefault'] == 0) {
            $params = ['threadId' => $groupChat, 'userLogin' => $user['use_id'],'lastIdMessage' => $infoGroup['lastIdMessage'], 'idDelete' => $infoGroup['idDelete']];
        }
        else {

            $params = ['threadId' => $groupChat, 'userLogin' => $user['use_id'],'lastIdMessage' => $infoGroup['lastIdMessage'], 'idDelete' => $infoGroup['idDelete']];
        }

        $list = ChatMessages::getListAlias_V2($page, $pageSize, $params);
        $results = $list->paginate($pageSize);
        if(count($results) > 0 ) {
            $statusLastChat = null;
            if($infoGroup['type'] == 'private') {
                 $statusLastChat = ChatMessages::statusLastChat($infoGroup);

            }
            if($statusLastChat == null) {
                $statusLastChat = 0;
            }
            foreach( $results as $k => &$v) {
                                $v->created_ts = Commons::convertDateTotime($v->createdAt);
                $v->updated_ts = Commons::convertDateTotime($v->updatedAt);
                if($v->typedata == 'multiImage') {
                    $v->listImage = json_decode($v->listImage, true);
                }
                else {
                    $v->listImage = [];
                }
                if($v->typedata == 'product') {
                    $infoProduct = ChatMessages::getDetailProduct($v->productId);
                    $v->infoProduct = $infoProduct;
                }
                else {
                    $v->infoProduct = null;
                }
                $messageId = $v->messageId;
                if($messageId != 0) {

                    $v->parentMessage = null;
                }
                else {
                    $v->parentMessage = null;
                }
                if($infoGroup->type != 'private') {
                    $statusLastChat = ChatMessages::statusReadOfMessage($infoGroup, $v->id);
                }
                $v->statusRead = $statusLastChat;
                $v->emoij = ChatMessageEmoij::getEmoijMessage($v->id);
                $infoEmoi = ChatMessageEmoij::infoChooseEmoij($v->id, $user['use_id']);
                if(count($infoEmoi) > 0) {
                    $v->chooseEmoij = 1;
                    $v->typeEmoij = $infoEmoi['emoij'];
                    $v->emoijId = $infoEmoi['emoijId'];
                }
                else {
                    $v->chooseEmoij = 0;
                    $v->typeEmoij = "";
                    $v->emoijId = 0;
                }
                if($messageId != 0) {
                    $v->parentMessageArr = ChatMessages::getArrayMessageParent($messageId, $user['use_id']);
                    /*$parentMessId =  $v['parentMessId'];
                    if($parentMessId == 0) {
                        $arrLevel = ChatMessages::getArrElementChild($v['messageId'], $user['use_id']);
                        if(count($arrLevel) == 1) {
                            $arrLevelId = [$v['messageId']];
                        }
                        else {
                            $arrLevelId = [$v['messageId'], $arrLevel[1]];
                        }

                    }
                    else {
                        $arrLevel = ChatMessages::getArrElementChild($parentMessId, $user['use_id']);
                        if(count($arrLevel) == 1) {
                            $arrLevelId = [$parentMessId];
                        }
                        else {
                            $arrLevelId = [$parentMessId, $arrLevel[1]];
                        }
                    }
                    $v['parentMessageArr'] = ChatMessages::getMessageParentRecuriseLevel($arrLevelId);
                    */


                }
                else {
                    $v->parentMessageArr = null;
                }

            }
        }

        return response([
            'msg' => Lang::get('response.success'),
            'data' => $results
        ]);

    }

    public function updateRepeatConversation(Request $req) {
        $messageId = $req->messageId;
        $content_repeat = $req->content_repeat;
        $time_repeat = $req->time_repeat;
        $time_set_repeat = Carbon::now()->addMinutes($time_repeat)->format('Y-m-d H:i:00');
        $message_repeat = 0;
        if($time_repeat != 0) {
            $message_repeat = 1;
        }
        $update = ChatMessages::where('id', $messageId)->update(['content_repeat' => $content_repeat, 'time_repeat' => $time_repeat, 'message_repeat' => $message_repeat, 'time_set_repeat' => $time_set_repeat ]);
        if($update) {
            return response([
                'msg' => Lang::get('response.success'),
                'data' => $update

            ]);
        }
        else {
            return response([
                'msg' => Lang::get('response.failed')

            ]);
        }
    }

    public function pushNotiRepeatConversation(Request $req) {
        $user = $req->user();
        $userLogin = $user['use_id'] ;
        $date_time_now = date('Y-m-d h:m:s');
        $listMessages = ChatMessages::where("message_repeat", "=", 1)
                                    ->where("time_repeat", "<>", 0)
                                    ->where("time_set_repeat", "=", $date_time_now)
                                    ->select("id", 'threadId', 'ownerId', 'time_repeat','createdAt')
                                    ->get();

        foreach($listMessages as $k => $v ) {
            $id = $v['id'];
            $threadId = $v['threadId'];
            $ownerId = $v['ownerId'];
            $listUserOfThread = ChatThreadUser::where(['threadId' => $threadId, 'accept_request' => 1])
                ->where("userId", "<>", $ownerId)
                ->select("userId")->pluck("userId")->toArray();
            //get user da tra loi cuoc dam thoai
            $listUser = ChatMessages::where(['parentMessId' => $id])
                        ->distinct("ownerId")->pluck("ownerId");
            if($listUser) {
                $listUser = $listUser->toArray();
                $arr_dif=array_diff($listUserOfThread,$listUser);
                if(count($arr_dif) > 0) {
                    $detail = [];
                    $detail['use_id'] = $userLogin;
                    $dataPushnotification = [
                        "use_id" => $userLogin,
                        "userIds" => $arr_dif,
                        "detail" => $detail
                    ];
                    dispatch(new SendChatNotification('repeat-conversation',$dataPushnotification));
                }
            }
            else {
                if(count($listUserOfThread) > 0) {
                    $detail = [];
                    $detail['use_id'] = $userLogin;
                    $dataPushnotification = [
                        "use_id" => $userLogin,
                        "userIds" => $listUserOfThread,
                        "detail" => $detail
                    ];
                    dispatch(new SendChatNotification('repeat-conversation',$dataPushnotification));
                }
            }

        }
    }

    public function listUserUnanswerConversation(Request $req) {
        $user = $req->user();
        $messageId = $req->messageId;
        $threadId = $req->threadId;
        $page = $req->page;
        $pageSize = $req->limit;
        $userLogin = $user['use_id'];
        $listUser = ChatMessages::where(function ($query) use ($messageId) {
                            $query->where('parentMessId', '=', $messageId)
                                    ->orWhere("parentIdRepeat", "=", $messageId);

                        })
                        ->distinct("ownerId")->pluck("ownerId");
        if($listUser) {
            $listUser = $listUser->toArray();
            $data = ChatThreadUser::join("tbtt_user","tbtt_user.use_id","=","chatthreaduser.userId")
                    ->leftJoin(DB::raw("(select * from chatuseralias where userId = $userLogin) as chatuseralias"), function($join) {
                             $join->on('tbtt_user.use_id', '=', 'chatuseralias.userId_alias');
                    })
                    ->where("chatthreaduser.accept_request", "=", 1)
                    ->where("chatthreaduser.threadId", "=", $threadId)
                    ->where("chatthreaduser.userId", "<>", $userLogin)
                    ->whereNotIn("chatthreaduser.userId", $listUser)
                    ->select("tbtt_user.avatar","tbtt_user.use_username",DB::raw('(CASE WHEN chatuseralias.name_alias is null THEN tbtt_user.use_fullname ELSE chatuseralias.name_alias END) AS use_fullname') )
                    ->distinct("tbtt_user.use_id")
                    ->paginate($pageSize);


            return response([
                'msg' => Lang::get('response.success'),
                'data' => $data
            ]);
        }
        else {
            return response([
                'msg' => Lang::get('response.success'),
                'data' => []
            ]);
        }




    }


    public function updateInfoMultiImage(Request $req) {
        $messageId = $req->messageId;
        $caption = $req->caption;
        $url_image = $req->url_image;
        $info = ChatMessages::where("id", $messageId)->select("listImage")->first();
        if($info) {
            $listImage = json_decode($info->listImage,true);
            $key = array_search($url_image, array_column($listImage, 'image'));
            if($key !== false) {
                $listImage[$key]['caption'] = $caption;
                ChatMessages::where("id", $messageId)->update(['listImage' => json_encode($listImage)]);
                return response([
                    'msg' => Lang::get('response.success'),
                    'data' =>  $listImage
                ]);
            }

        }
        return response([
                'msg' => Lang::get('response.failed')

        ]);
    }

    public function detailRootMessage(Request $req){
        $id = $req->id;
        $user = $req->user();
        $detail = ChatMessages::where("id", $id)->select("messageId", "element", "parentMessId")->first();
        $info = [];
        if($detail) {
            if($detail->messageId != 0) {
                $info = ChatMessages::getDetailMesssageParent($detail->parentMessId, $user->use_id);
                if($detail->element != "") {
                    $info['element'] = json_decode( $detail->element, true);
                }
            }
            else {
                $info = ChatMessages::getDetailMesssageParent($id, $user->use_id);

            }
        }
        return response([
                'msg' => Lang::get('response.success'),
                'data' => $info
        ]);


    }

    public function infoImageInMulti(Request $req) {
        $user = $req->user();
        $messageId = $req->id;
        $url_image = $req->url_image;
        $explode = explode('/', $url_image);
        $search = $explode[count($explode) -1];
        $data = ChatMessages::where('element', 'like', '%' . $search . '%')
                             ->where("messageId", $messageId)
                             ->limit(1)
                             ->orderby('id','desc')
                             ->select("id")
                             ->first();
        if($data) {
            $data = $data->toArray();
            $id = $data['id'];
            $info = ChatMessages::getDetailMesssage($id, $user['use_id']);
            return response([
                    'msg' => Lang::get('response.success'),
                    'data' => $info
                ]);

        }
        return response([
                    'msg' => Lang::get('response.success'),
                    'data' =>  []
                ]);


    }

    public function deleteImageInMulti(Request $req) {
        $user = $req->user();
        $id = $req->id;
        $url_image = $req->linkImage;
        $explode = explode('/', $url_image);
        $search = $explode[count($explode) -1];
        $count = ChatMessages::where('element', 'like', '%' . $search . '%')
                             ->where("messageId", $id)
                             ->count();
        if($count > 0) {
            return response([
                    'msg' => Lang::get('response.success'),
                    'data' =>  "Đã có người trả lời cho hình ảnh này bạn không thể xóa!"
                ]);
        }
        else {
            $message = ChatMessages::where('id', $id)->select("listImage", 'threadId')->first();
            if($message) {
                $listImage = json_decode($message->listImage, true);
                $threadId = $message->threadId;
                $delete = 0;
                $arrReturn = [];
                if(count($listImage) > 0) {
                    foreach($listImage as $k => $v ) {
                        if($v['image'] == $url_image) {
                            unset($listImage[$k]);
                            $delete = 1;
                            CallApi::deleteRemoteFile($v['image']);
                            // delete Image on server
                        }
                        else {
                            $arrReturn[] = $v;
                        }
                    }
                }
                if($delete == 1) {
                     ChatMessages::where('id', $id)->update(['listImage' => json_encode($arrReturn)]);
                }
                $info = ChatMessages::getDetailMesssage($id, $user['use_id']);
                $arrUser = ChatThreadUser::where(['threadId' => $threadId, 'accept_request' => 1])
                                ->pluck("userId");
                return response([
                    'msg' => Lang::get('response.success'),
                    'data' =>  $info,
                    'arrUser' => $arrUser
                ]);
            }

        }

    }

    public function testDeleteImage(Request $req) {
        $method = 'POST';
        $api = "azibai.org/azibai_api/public/api/v1/chat/delete-image";
        $linkImage = $req->linkImage;
        $data = ['linkImage' => $linkImage, 'keydelete' => 'azibaideleteimage'];
        $data = CallApi::callCurl($method, $api, $data);

    }

    public function listAnswerImage(Request $req) {
        $user = $req->user();
        $urlImage = $req->urlImage;  
        $threadId = $req->threadId;
        $explode = explode('/', $urlImage); 
        $search = $explode[count($explode) -1];
        $limit = 10;
        if(isset($req->limit)) {
            $limit = $req->limit; 
        }
        $list = ChatMessages::getListAnswerImage($user->use_id, $threadId, $search );
        $list = $list->paginate($limit);
        foreach( $list as $k => &$info ) {
            $info['created_ts'] = ChatMessages::convertDateTotime($info['createdAt']);
            $info['updated_ts'] = ChatMessages::convertDateTotime($info['updatedAt']);
            if($info['typedata'] =='multiImage') {
                $info['listImage'] = json_decode($info['listImage'], true);
            }
            else {
                $info['listImage'] = [];  
            }
            if($info['typedata'] =='product') {
                $infoProduct = ChatMessages::getDetailProduct($info['productId']);
                $info['infoProduct'] = $infoProduct; 
            } 
            else {  
                $info['infoProduct'] = null;  
            } 

            $info['share_lat'] = doubleval($info['share_lat']);
            $info['share_lng'] = doubleval($info['share_lng']);
            /*if($info['element'] != null  && $info['element'] != "") {
                $info['element'] = json_decode($info['element'], true);
            }
            */ 
            $info['element'] = null;

            if($info['typedata'] == 'share_location' ) { 
                $created_share = $info['createdAt'];
                $share_time = $info['share_time'];
                if($share_time == 0) {
                    $info['stop_share'] = true; 
                    $info['text'] = "Chia sẻ vị trí đã kết thúc";
                }
                else {
                    $time_submius = Carbon::now()->subMinutes($share_time);
                    if($created_share < $time_submius) {
                        $info['stop_share'] = true;
                        $info['stop_share'] = "Chia sẻ vị trí đã kết thúc";
                    }
                    else {
                        $info['stop_share'] = false;
                    }
                }

            }
        }
        return response([
                    'msg' => Lang::get('response.success'),
                    'data' => $list
                ]);

 

    }

}
