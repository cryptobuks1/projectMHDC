<?php

namespace App\Models;
use App\BaseModel;
use App\Models\User;
use DB;
use App\Helpers\Commons;
/**
 * ChatThreads model
 *
 */
class Resume extends BaseModel {

    protected $table = 'tbtt_resume';
    protected $fillable = [
        'userid',
        'logo',
        'banner',
        'fullname',
        'career',
        'sex',
        'birthday',
        'religion',
        'department',
        'mobile',
        'email',
        'education',
        'favorites',
        'marriage',
        'accommodation',
        'sayings',
        'company_name',
        'company_image',
        'company_intro',
        'slogan',
        'slogan_by',
        'slogan_bg',
        'title_service',
        'service_desc',
        'service_0',
        'service_1',
        'service_2',
        'service_3',
        'service_4',
        'service_5',
        'statistic',
        'statistic_bg',
        'show_statistic',
        'product_desc',
        'title_product',
        'product_cat',
        'product_list_0',
        'product_list_1',
        'product_list_2',
        'product_list_3',
        'show_product',
        'title_customer',
        'customer_0',
        'customer_1',
        'customer_2',
        'customer_3',
        'customer_bg',
        'show_customer',
        'certification',
        'title_certification',
        'show_certification',
        'title_history',
        'history_0',
        'history_1',
        'history_2',
        'history_3',
        'history_4',
        'history_5',
        'history_6',
        'history_7',
        'history_8',
        'history_9',
        'show_history',
        'facebook',
        'twitter',
        'google',
        'show_company',
        'show_slogan',
        'show_service',
        'show_contactUs'

    ];

    public static $table_name = 'tbtt_resume';


    public static function updateResume($arrData) {
        $data = Resume::where('userid', $arrData['userid'])->first();
        if($data) {
            return DB::table(Resume::$table_name)->where('userid', $arrData['userid'])->update($arrData);

        }
        else {
            return DB::table(Resume::$table_name)->where('userid', $arrData['userid'])->insert($arrData);
        }
    }

    public static function getDetailResume($userid) {
        $detail = Resume::where('userid', $userid)->first();
        if(!$detail) {
            return null;
        }
        $detail['service_0'] = $detail['service_0'] == null? null: json_decode($detail['service_0'], true);
        $detail['service_1'] = $detail['service_1'] == null? null: json_decode($detail['service_1'], true);
        $detail['service_2'] = $detail['service_2'] == null? null: json_decode($detail['service_2'], true);
        $detail['service_3'] = $detail['service_3'] == null? null: json_decode($detail['service_3'], true);
        $detail['service_4'] = $detail['service_4'] == null? null: json_decode($detail['service_4'], true);
        $detail['service_5'] = $detail['service_5'] == null? null: json_decode($detail['service_5'], true);
        $detail['statistic'] = $detail['statistic'] == null? null: json_decode($detail['statistic'], true);
        $detail['certification'] = $detail['certification'] == null? null: json_decode($detail['certification'], true);
        $detail['product_cat'] = $detail['product_cat'] == null? null: json_decode($detail['product_cat'], true);
        $detail['product_list_0'] = $detail['product_list_0'] == null? null: json_decode($detail['product_list_0'], true);
        $detail['product_list_1'] = $detail['product_list_1'] == null? null: json_decode($detail['product_list_1'], true);
        $detail['product_list_2'] = $detail['product_list_2'] == null? null: json_decode($detail['product_list_2'], true);
        $detail['product_list_3'] = $detail['product_list_3'] == null? null: json_decode($detail['product_list_3'], true);
        $detail['customer_0'] = $detail['customer_0'] == null? null: json_decode($detail['customer_0'], true);
        $detail['customer_1'] = $detail['customer_1'] == null? null: json_decode($detail['customer_1'], true);
        $detail['customer_2'] = $detail['customer_2'] == null? null: json_decode($detail['customer_2'], true);
        $detail['customer_3'] = $detail['customer_3'] == null? null: json_decode($detail['customer_3'], true);
        $detail['history_0'] = $detail['history_0'] == null? null: json_decode($detail['history_0'], true);
        $detail['history_1'] = $detail['history_1'] == null? null: json_decode($detail['history_1'], true);
        $detail['history_2'] = $detail['history_2'] == null? null: json_decode($detail['history_2'], true);
        $detail['history_3'] = $detail['history_3'] == null? null: json_decode($detail['history_3'], true);
        $detail['history_4'] = $detail['history_4'] == null? null: json_decode($detail['history_4'], true);
        $detail['history_5'] = $detail['history_5'] == null? null: json_decode($detail['history_5'], true);
        $detail['history_6'] = $detail['history_6'] == null? null: json_decode($detail['history_6'], true);
        $detail['history_7'] = $detail['history_7'] == null? null: json_decode($detail['history_7'], true);
        $detail['history_8'] = $detail['history_8'] == null? null: json_decode($detail['history_8'], true);
        $detail['history_9'] = $detail['history_9'] == null? null: json_decode($detail['history_9'], true);
        return $detail;

    }



}
