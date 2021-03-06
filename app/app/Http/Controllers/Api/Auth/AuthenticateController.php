<?php

namespace App\Http\Controllers\Api\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Uuid;
use Validator;
use App\Models\User;
use App\Models\Device;
use App\Helpers\Commons;
use App\Helpers\Utils;
use Lang;
use App\Helpers\Hash;
use Illuminate\Support\Facades\Log;

class AuthenticateController extends Controller {

    /**
     * @SWG\Post(
     *     path="/api/v1/login",
     *     operationId="authenticated",
     *     description="Verify user phone number then create new account or just response new token",
     *     tags={"Auth"},
     *     summary="Api login",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="username",
     *         in="body",
     *         description="User usernamer",
     *         required=true,
     *         type="string",
     *         @SWG\Schema(ref="#/definitions/Authenticated"),
     *     ),
     *     @SWG\Parameter(
     *         name="password",
     *         in="body",
     *         description="Verified code",
     *         required=true,
     *         type="string",
     *         @SWG\Schema(ref="#/definitions/Authenticated"),
     *     ),
     *     @SWG\Parameter(
     *         name="deviceType",
     *         in="body",
     *         description="Allow ios or android",
     *         required=false,
     *         type="string",
     *         enum="ios|android"
     *     ),
     *     @SWG\Parameter(
     *         name="deviceId",
     *         in="body",
     *         description="deviceId",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="success",
     *     ),
     *     @SWG\Response(
     *         response="default",
     *         description="unexpected error",
     *     )
     * )
     */
    public function login(Request $req) {
        $validator = Validator::make($req->all(), [
                'username' => 'required|string',
                'password' => [
                    'required'
                ], //check VN mobile number
                'deviceToken' => 'required|string',
                'deviceType' => 'string|in:android,ios',
                'deviceId' => 'required|string'
        ]);

        if ($validator->fails()) {
            if (empty($req->deviceToken)) {
                return response([
                    'msg' => 'Please login with device',
                    'errors' => 'Please dont try using on browser'
                    ], 422);
            }
            return response([
                'msg' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('use_username', $req->username)->first();

        if (!$user) {
            return response([
                'msg' => Lang::get('auth.account_not_found')
            ], 404);
        }

        if (!$user->checkPassword($req->password)) {
            return response([
                'msg' => Lang::get('auth.wrong_password')
            ], 400);
        }

        if ($user->use_status !== User::STATUS_ACTIVE) {
            return response([
                'msg' => Lang::get('auth.account_in_active')
            ], 400);
        }

        $user->initAuthToken();

        try {

            $user->save();
            if ($user->use_group == User::TYPE_NormalUser && env('GROUP_DISABLE_LOGIN') == $user->use_group) {
                return response(['msg' => 'Hệ thống đang hoàn tất với nhóm người dùng của bạn'], 422);
            }
            $token = $user->generateJwt();
            if (!$token) {
                return response(['msg' => Lang::get('response.server_error')], 500);
            }

            if ($req->deviceToken) {
                Device::where([
                    'imei' => $req->deviceId
                ])->delete();

                $token_voip = null;
                if(isset($req->token_voip)) {
                    $token_voip = $req->token_voip;
                }

                $activeDevice = new Device([
                    'type' => $req->deviceType ? $req->deviceType : Device::TYPE_ANDROID,
                    'userId' => $user->use_id,
                    'token' => $req->deviceToken,
                    'imei' => $req->deviceId,
                    'token_voip' => $req->token_voip,
                    'active' => true
                ]);
                $activeDevice->save();
            }

        } catch (Exception $e) {
            return response([
                'msg' => \Lang::get('response.server_error')
            ], 500);
        }

        return response([
            'msg' => Lang::get('response.success'),
            'data' => [
                'token' => $token,
                'group' => $user->use_group,
                'userId' => $user->use_id
            ]
        ]);
    }

    /**
     * @SWG\Post(
     *     path="/api/v1/logout",
     *     operationId="logout",
     *     description="logout user and remove token code",
     *     tags={"Auth"},
     *     summary="Api logout",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="deviceToken",
     *         in="body",
     *         description="Verified code",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="deviceId",
     *         in="body",
     *         description="deviceId",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="deviceType",
     *         in="body",
     *         description="Allow ios or android",
     *         required=false,
     *         type="string",
     *         enum="ios|android"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="success",
     *         @SWG\Schema(ref="#/definitions/Authenticated/logout")
     *     )
     * )
     */
    public function logout(Request $req) {
        $validator = Validator::make($req->all(), [
                'deviceToken' => 'string',
                'deviceType' => 'string|in:android,ios',
                'deviceId' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response([
                'msg' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }
        $user = $req->user();
        //$user->use_auth_token = null;
        try {
            $infoDevice = Device::where([
                'imei' => $req->deviceId
            ])->first();
            if($infoDevice) {
                $device_voip = $infoDevice->token_voip;
                if($device_voip != "" && $device_voip != NULL ) {
                    Device::where([
                        'token_voip' => $device_voip
                    ])->delete();
                }
                else {
                    Device::where([
                        'imei' => $req->deviceId
                    ])->delete();
                }
            }
            /*Device::where([
                'imei' => $req->deviceId
            ])->delete();*/
            //$user->save();
            //TODO: Remove active device token
            return [
                'msg' => Lang::get('response.success')
            ];
        } catch (Exception $ex) {
            return response(['msg' => Lang::get('response.server_error')], 500);
        }
    }


    /**
     * @SWG\Post(
     *     path="/api/v1/register/affiliate",
     *     operationId="registerAffiliate",
     *     description="logout user and remove token code",
     *     tags={"Auth"},
     *     summary="Đăng ký Affiliate",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="use_username",
     *         in="body",
     *         description="User usernamer",
     *         required=true,
     *         type="string",
     *         @SWG\Schema(ref="#/definitions/Authenticated"),
     *     ),
     *     @SWG\Parameter(
     *         name="use_password",
     *         in="body",
     *         description="use_password",
     *         required=true,
     *         type="string",
     *         @SWG\Schema(ref="#/definitions/Authenticated"),
     *     ),
     *     @SWG\Parameter(
     *         name="re_use_password",
     *         in="body",
     *         description="re_use_password",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="use_email",
     *         in="body",
     *         description="email",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="use_mobile",
     *         in="body",
     *         description="use_mobile",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="use_province",
     *         in="body",
     *         description="use_province",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="use_district",
     *         in="body",
     *         description="use_district",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="parent_id",
     *         in="body",
     *         description="Id của công ty, Đăng ký chi nhánh nếu đã đăng nhập thì k cần nhập field này.",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="success",
     *         @SWG\Schema(ref="#/definitions/Authenticated/logout")
     *     )
     * )
     */
    public function registerAffiliate(Request $req) {
        $validator = Validator::make($req->all(), [
                'use_username' => 'required|without_spaces|string|min:6|unique:tbtt_user',
                'use_password' => 'required',
                're_use_password' => 'required|same:use_password',
                'use_email' => 'required|email|unique:tbtt_user',
                'use_mobile' => 'required|unique:tbtt_user',
                'use_province' => 'required',
                'use_district' => 'required',
                'parent_id' => 'integer',
        ]);

        if ($validator->fails()) {
            return response([
                'msg' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $salt = User::randomSalt();
        $user = new User([
            'use_group' => User::TYPE_AffiliateUser,
            'use_status' => User::STATUS_ACTIVE,
            'use_username' => trim($req->use_username),
            'use_salt' => $salt,
            'use_password' => User::hashPassword($req->use_password, $salt),
            'use_email' => $req->use_email,
            'use_province' => $req->use_province,
            'user_district' => $req->use_district,
            'use_phone' => $req->use_phone ? $req->use_phone : '',
            'use_mobile' => $req->use_mobile,
            'use_regisdate' => time(),
            'use_key' => Hash::create($req->use_username, $req->use_email, 'sha256md5'),
            'use_lastest_login' => time()
        ]);

        $parent = $req->user() ? $req->user() : $req->parent_id ? User::find($req->parent_id) : null;
        $parentId = 0;
        if ($parent && $parent->use_status == User::STATUS_ACTIVE) {
            if (in_array($parent->use_group, [User::TYPE_AffiliateStoreUser, User::TYPE_BranchUser, User::TYPE_StaffStoreUser, User::TYPE_StaffUser])) {

                $limit = $user->checkLimitService($parent);
                if ($limit) {
                    return response([
                        'msg' => Lang::get('response.reached_limit_service', ['limit' => $limit]),
                        ], 400);
                }
                $user->parent_id = $parent->use_id;
                $parentId = $parent->use_id;
            } else {
                $parentId = $parent->use_id;
            }
        }
        if ($parentId == 0) {
            $query = User::where(['use_status' => 1, 'use_province' => $req->use_province, 'user_district' => $req->use_district]);
            $query->whereIn('use_group', [6, 7, 8, 9, 10]);

            $treeObject = $query->first();
            if (!empty($treeObject)) {
                $user->parent_id = $treeObject->use_id;
            } else {
                $query = User::where(['use_status' => 1, 'use_province' => $req->use_province]);
                $query->whereIn('use_group', [6, 7, 8, 9, 10]);
                $treeObject1 = $query->first();
                if (!empty($treeObject1)) {
                    $user->parent_id = $treeObject1->use_id;
                } else {
                    $query = User::where(['use_status' => 1]);
                    $query->whereIn('use_group', [6, 7, 8, 9, 10]);
                    $treeObject2 = $query->first();
                    if (!empty($treeObject2)) {
                        $user->parent_id = $treeObject2->use_id;
                    }
                }
            }
        }


        try {
            $user->save();

            return [
                'msg' => Lang::get('response.success'),
                'data' => $user
            ];
        } catch (Exception $ex) {
            return response(['msg' => Lang::get('response.server_error')], 500);
        }
    }

    /**
     * @SWG\Post(
     *     path="/api/v1/register/shop",
     *     operationId="registerShop",
     *     description="logout user and remove token code",
     *     tags={"Auth"},
     *     summary="Đăng ký Shop",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="use_username",
     *         in="body",
     *         description="User usernamer",
     *         required=true,
     *         type="string",
     *         @SWG\Schema(ref="#/definitions/Authenticated"),
     *     ),
     *     @SWG\Parameter(
     *         name="use_password",
     *         in="body",
     *         description="use_password",
     *         required=true,
     *         type="string",
     *         @SWG\Schema(ref="#/definitions/Authenticated"),
     *     ),
     *     @SWG\Parameter(
     *         name="re_use_password",
     *         in="body",
     *         description="re_use_password",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="use_email",
     *         in="body",
     *         description="email",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="use_mobile",
     *         in="body",
     *         description="use_mobile",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="use_province",
     *         in="body",
     *         description="use_province",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="use_district",
     *         in="body",
     *         description="use_district",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="sponsor",
     *         in="body",
     *         description="ID kích hoạt gian hàng",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="id_card",
     *         in="body",
     *         description="Chứng minh nhân dân",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="tax_type",
     *         in="body",
     *         description="0:Mã số thuế cá nhân, 1:Mã số thuế doanh nghiệp ",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="tax_code",
     *         in="body",
     *         description="mã số thuế",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="bank_name",
     *         in="body",
     *         description="Tên ngân hàng",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="account_name",
     *         in="body",
     *         description="Tên tài khoản",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="num_account",
     *         in="body",
     *         description="Số tài khoản",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="bank_add",
     *         in="body",
     *         description="Chi Nhánh",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="success",
     *         @SWG\Schema(ref="#/definitions/Authenticated/logout")
     *     )
     * )
     */
    public function registerShop(Request $req) {
        $validator = Validator::make($req->all(), [
                'use_username' => 'required|without_spaces|string|min:6|unique:tbtt_user',
                'use_password' => 'required',
                're_use_password' => 'required|same:use_password',
                'use_email' => 'required|email|unique:tbtt_user',
                 'use_mobile' => 'required|unique:tbtt_user',
                'use_province' => 'required',
                'use_district' => 'required',
                'sponsor' => 'required',
                'id_card' => 'required',
                'tax_type' => 'required|in:0,1',
                'tax_code' => 'required',
                'bank_name' => 'required',
                'account_name' => 'required',
                'num_account' => 'required',
                'bank_add' => 'required',
        ]);

        if ($validator->fails()) {
            return response([
                'msg' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $sponsor = User::where(function($q) use ($req) {
            $q->orWhere('use_username',  $req->sponsor);
            $q->orWhere('use_email', $req->sponsor);
        })->select('use_group', 'use_id')->first();

        $salt = User::randomSalt();
        $user = new User([
            'use_group' => User::TYPE_AffiliateStoreUser,
            'use_status' => User::STATUS_INACTIVE,
            'use_username' => trim($req->use_username),
            'use_salt' => $salt,
            'use_password' => User::hashPassword($req->use_password, $salt),
            'use_email' => $req->use_email,
            'use_province' => $req->use_province,
            'user_district' => $req->use_district,
            'use_phone' => $req->use_phone ? $req->use_phone : '',
            'use_mobile' => $req->use_mobile,
            'use_regisdate' => time(),
            'use_key' => Hash::create($req->use_username, $req->use_email, 'sha256md5'),
            'use_lastest_login' => time(),
            'parent_id' => $sponsor && $sponsor->use_group > 3 ? $sponsor->use_id : 0,

            'id_card' => $req->id_card,
            'tax_type' => $req->tax_type, //mã số danh nghiệm 1, cấ nhân 0
            'tax_code' => $req->tax_code,
            'bank_name' => $req->bank_name,
            'bank_add' => $req->bank_add,
            'account_name' => $req->account_name,
            'num_account' => $req->num_account
        ]);

        try {
            $user->save();

            return [
                'msg' => Lang::get('response.success'),
                'data' => $user
            ];
        } catch (Exception $ex) {
            return response(['msg' => Lang::get('response.server_error')], 500);
        }
    }

    /**
     * @SWG\Post(
     *     path="/api/v1/register",
     *     operationId="registerMember",
     *     description="logout user and remove token code",
     *     tags={"Auth"},
     *     summary="Đăng ký Thành viên",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="use_username",
     *         in="body",
     *         description="User usernamer",
     *         required=true,
     *         type="string",
     *         @SWG\Schema(ref="#/definitions/Authenticated"),
     *     ),
     *     @SWG\Parameter(
     *         name="use_password",
     *         in="body",
     *         description="use_password",
     *         required=true,
     *         type="string",
     *         @SWG\Schema(ref="#/definitions/Authenticated"),
     *     ),
     *     @SWG\Parameter(
     *         name="re_use_password",
     *         in="body",
     *         description="re_use_password",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="use_email",
     *         in="body",
     *         description="email",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="use_mobile",
     *         in="body",
     *         description="use_mobile",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="use_province",
     *         in="body",
     *         description="use_province",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="use_district",
     *         in="body",
     *         description="use_district",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="success",
     *         @SWG\Schema(ref="#/definitions/Authenticated/logout")
     *     )
     * )
     */
    public function registerMember(Request $req){
          $validator = Validator::make($req->all(), [
                'use_username' => 'required|without_spaces|string|min:6|unique:tbtt_user',
                'use_password' => 'required',
                're_use_password' => 'required|same:use_password',
                'use_email' => 'required|email|unique:tbtt_user',
                 'use_mobile' => 'required|unique:tbtt_user',
                'use_province' => 'required',
                'use_district' => 'required',
        ]);

        if ($validator->fails()) {
            return response([
                'msg' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }
        $parentID = 0;
        $query = User::where(['use_status' => 1, 'use_province' => $req->use_province, 'user_district' => $req->use_district]);
        $query->whereIn('use_group', [6, 7, 8, 9, 10]);

        $treeObject = $query->first();
        if (!empty($treeObject)) {
            $parentID = $treeObject->use_id;
        } else {
            $query = User::where(['use_status' => 1, 'use_province' => $req->use_province]);
            $query->whereIn('use_group', [6, 7, 8, 9, 10]);
            $treeObject1 = $query->first();
            if (!empty($treeObject1)) {
                $parentID = $treeObject1->use_id;
            } else {
                $query = User::where(['use_status' => 1]);
                $query->whereIn('use_group', [6, 7, 8, 9, 10]);
                $treeObject2 = $query->first();
                if (!empty($treeObject2)) {
                    $parentID = $treeObject2->use_id;
                }
            }
        }


        $salt = User::randomSalt();
        $user = new User([
            'use_group' => User::TYPE_NormalUser,
            'use_status' => User::STATUS_INACTIVE,
            'use_username' => trim($req->use_username),
            'use_salt' => $salt,
            'use_password' => User::hashPassword($req->use_password, $salt),
            'use_email' => $req->use_email,
            'use_province' => $req->use_province,
            'user_district' => $req->use_district,
            'use_phone' => $req->use_phone ? $req->use_phone : '',
            'use_mobile' => $req->use_mobile,
            'use_regisdate' => time(),
            'use_key' => Hash::create($req->use_username, $req->use_email, 'sha256md5'),
            'use_lastest_login' => time(),
            'parent_id' => $parentID,

            'id_card' => "",
            'tax_type' => 0, //mã số danh nghiệm 1, cấ nhân 0
            'tax_code' => "",
            'bank_name' => "",
            'bank_add' => "",
            'account_name' => "",
            'num_account' => "",
            'parent_shop' => 0
        ]);

        try {
            $user->save();

            return [
                'msg' => Lang::get('response.success'),
                'data' => $user
            ];
        } catch (Exception $ex) {
            return response(['msg' => Lang::get('response.server_error')], 500);
        }
    }
}
