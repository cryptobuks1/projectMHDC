<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Models;
use App\BaseModel;
/**
 * Description of CommissionStore
 *
 * @author hoanvu
 */
class DetailCommissionAff extends BaseModel {

    //put your code here
    protected $table = 'tbtt_detail_commission_aff';
    public $timestamps = false;
    protected $fillable = ['aff_id', 'commissid_percent', 'note'];

}
