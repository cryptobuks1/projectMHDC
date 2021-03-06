<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Models;

use App\BaseModel;

/**
 * Description of PackageUser
 *
 * @author hoanvu
 */
class PackageUserService extends BaseModel {

    protected $table = 'tbtt_package_user_service';
    public $wasNew = false;
    public $timestamps = false;

    public static function tableName() {
        return 'tbtt_package_user_service';
    }
    
     protected $fillable = [
        'order_id',
        'service_id',
        'note',
        'status',
       
    ];
    protected $defaults = array(
      
    );

    public function __construct(array $attributes = array()) {
        $this->setRawAttributes($this->defaults, true);
        parent::__construct($attributes);
    }

    public function service() {
        return $this->hasOne('App\Models\Service', 'id', 'service_id');
    }

}
