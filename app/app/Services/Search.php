<?php

namespace App\Services;

use App\Models\Content;
use App\Models\LandingPage;
use App\Models\Shop;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use App\Helpers\Utils;
/**
 * Category model
 *
 */
class Search {

    /**
     * The table associated with the model.
     *
     * @var string
     */
    const TYPE_ALL = 'all';
    const TYPE_AFFILIATES = 'affiliates';
    const TYPE_PICTURES = 'pictures';
    const TYPE_VIDEOS = 'videos';
    const TYPE_NEWS = 'news';
    const TYPE_LANDING_PAGES = 'landing_pages';
    const TYPE_DOMAINS = 'domains';
    const TYPE_CATEGORIES = 'categories';
    const TYPE_PRODUCTS = 'products';
    const TYPE_SHOPS = 'shops';

    public static function searchAllNew($user = null, $search = null) {
        $list = [
    		self::TYPE_SHOPS => self::searchByShops($user, $search),
            self::TYPE_AFFILIATES => self::searchByAffiliates($user, $search),
            self::TYPE_NEWS => self::searchByNews($user, $search),
            self::TYPE_PICTURES => self::searchByPictures($user, $search),
            self::TYPE_VIDEOS => self::searchByVideos($user, $search),
            self::TYPE_PRODUCTS => self::searchByProducts(null, $search),
			self::TYPE_CATEGORIES => self::searchByCategories($user, $search),
            self::TYPE_DOMAINS => self::searchByDomains($user, $search),
            self::TYPE_LANDING_PAGES => self::searchByLandingPages($user, $search)
        ];

        $results = array();
        foreach ($list as $key => $value) {
            if (sizeof($value) > 0) {
                $results[] = [
                    'type' => $key,
                    'data' => $value
                ];
            }
        }
        return $results;
    }


    public static function searchAll($user = null, $search = null) {
        return [
            self::TYPE_SHOPS => self::searchByShops($user, $search),
            self::TYPE_AFFILIATES => self::searchByAffiliates($user, $search),
            self::TYPE_NEWS => self::searchByNews($user, $search),
            self::TYPE_PICTURES => self::searchByPictures($user, $search),
            self::TYPE_VIDEOS => self::searchByVideos($user, $search),
            self::TYPE_PRODUCTS => self::searchByProducts(null, $search),
            self::TYPE_CATEGORIES => self::searchByCategories($user, $search),
            self::TYPE_DOMAINS => self::searchByDomains($user, $search),
            self::TYPE_LANDING_PAGES => self::searchByLandingPages($user, $search)
        ];
    }



    public static function searchByAffiliates($user = null, $search, $paging = false, $page = 0, $limit = 6) {
    	$query = User::where('use_group', User::TYPE_AffiliateUser)
            ->whereNotNull('use_fullname')
            ->where('use_fullname', '<>', '')
            ->where('use_status', User::STATUS_ACTIVE)
            ->orderBy('use_fullname', 'ASC')
            ->select('use_id', 'avatar', 'use_fullname','use_group','parent_id')
            ->where(function($q) use ($search) {
            $q->orWhere('use_fullname', 'LIKE', '%' . $search . '%');
        });

        if ($paging) {
        	$paginate = $query->paginate($limit, ['*'], 'page', $page);

            $arraysData = [];
            foreach ($paginate->items() as $value) {
                $data = $value->publicProfile();
                $data['hasFollow'] = $value->hasFollow($user ? $user->use_id : null);
                $arraysData[] = $data;
            }
            $results = $paginate->toArray();
            $results['data'] = $arraysData;
            return $results;
        }

        $arraysData = [];
        foreach ($query->limit($limit)->get() as $value) {
            $data = $value->toArray();
            $data['hasFollow'] = $value->hasFollow($user ? $user->use_id : null);
            $arraysData[] = $data;
        }
        return $arraysData;
    }

    public static function searchByNews($user = null, $search, $paging = false, $page = 0, $limit = 6) {
    	$query = Content::where([
            'not_status' => 1,
            'id_category'=>16,
            'not_publish' => 1
        ])
        ->leftJoin('tbtt_user', 'use_id', 'not_user')
        ->select('tbtt_content.*', 'tbtt_user.use_fullname')
        ->orderBy('not_begindate', 'DESC')
        ->where(function($q) use ($search) {
            $q->orWhere('not_title', 'LIKE', '%' . $search . '%');
            $q->orWhere('not_detail', 'LIKE', '%' . $search . '%');
            $q->orWhere('use_fullname', 'LIKE', '%' . $search . '%');
        });
        Content::filter($query,$user);
        if ($paging) {
        	$paginate = $query->paginate($limit, ['*'], 'page', $page);

        	$arraysData = [];
	        foreach ($paginate->items() as $value) {
	            $value->populate($user);
	            $data = $value->toArray();
	            $data['user'] = $value->user->publicProfile();
	            $data['user']['shop'] = $value->user->shop;
	            $arraysData[] = $data;
	        }
	        $results = $paginate->toArray();
	        $results['data'] = $arraysData;
	        return $results;
        }

        $arraysData = [];

        foreach ($query->limit($limit)->get() as $value) {
        	$value->populate($user);
        	$data = $value->toArray();
        	$data['user'] = $value->user->publicProfile();
        	$data['user']['shop'] = $value->user->shop;
        	$arraysData[] = $data;
        }
        return $arraysData;
    }

    public static function searchByLandingPages($user = null, $search, $paging = false, $page = 0, $limit = 6) {
    	$query = LandingPage::orderBy('created_date', 'DESC')
    	->select('id', 'name')
        ->where(function($q) use ($search) {
            $q->orWhere('name', 'LIKE', '%' . $search . '%');
        });

        if ($paging) {
        	$paginate = $query->paginate($limit, ['*'], 'page', $page);

            $arraysData = [];
            foreach ($paginate->items() as $value) {
                $value->url();
                $arraysData[] = $value->toArray();
            }
            $results = $paginate->toArray();
            $results['data'] = $arraysData;
            return $results;
        }

        $arraysData = [];

        foreach ($query->limit($limit)->get() as $value) {
            $value->url();
            $arraysData[] = $value->toArray();
        }
        return $arraysData;
    }

    public static function searchByDomains($user = null, $search, $paging = false, $page = 0, $limit = 6) {
    	$query = Shop::whereNotNull('domain')
            ->where('domain', '<>', '')
            ->where('sho_status', Shop::STATUS_ACTIVE)
            ->orderBy('domain', 'ASC')
            ->select('sho_id', 'domain')
            ->where(function($q) use ($search) {
            $q->orWhere('domain', 'LIKE', '%' . $search . '%');
        });

        if ($paging) {
            return $query->paginate($limit, ['*'], 'page', $page);
        }

        return $query->limit($limit)->get();
    }

    public static function searchByShops($user = null, $search, $paging = false, $page = 0, $limit = 6) {
    	$query = Shop::orderBy('sho_name', 'ASC')
            ->join(User::tableName(), Shop::tableName().'.sho_user', '=', User::tableName().'.use_id')
            ->where('sho_status', Shop::STATUS_ACTIVE)
            ->select('sho_id', 'sho_name', 'sho_logo', 'sho_dir_logo', 'sho_link', 'sho_user')
            ->where(function($q) use ($search) {
                $q->orWhere('sho_name', 'LIKE', '%' . $search . '%');
            });

        if ($paging) {
        	$paginate = $query->paginate($limit, ['*'], 'page', $page);

            $arraysData = [];
            foreach ($paginate->items() as $value) {
                $data = $value->toArray();
                $data['hasFollow'] = $value->hasFollow($user ? $user->use_id : null);
                $arraysData[] = $data;
            }
            $results = $paginate->toArray();
            $results['data'] = $arraysData;
            return $results;
        }

        $arraysData = [];
        foreach ($query->limit($limit)->get() as $value) {
            $data = $value->toArray();
            $data['hasFollow'] = $value->hasFollow($user ? $user->use_id : null);
            $arraysData[] = $data;
        }
        return $arraysData;
    }

    public static function searchByPictures($user = null, $search, $paging = false, $page = 0, $limit = 6) {
        $query = Content::whereNotNull('not_image')
            ->where('not_image', '<>', '')
            ->where(['not_status' => 1,
                'id_category' => 16,
                //  'cat_type' => 1,
                'not_publish' => 1])
            ->orderBy('not_id', 'DESC')
            ->leftJoin('tbtt_user', 'use_id', 'not_user')
            ->select('not_id', 'not_title', 'not_image', 'not_user', 'not_dir_image', 'use_fullname')
            ->where(function($q) use ($search) {
            $q->orWhere('not_title', 'LIKE', '%' . $search . '%');
        });
         Content::filter($query,$user);

        if ($paging) {
            return $query->paginate($limit, ['*'], 'page', $page);
        }

        return $query->limit($limit)->get();
    }

    public static function searchByVideos($user = null, $search, $paging = false, $page = 0, $limit = 6) {
        $query = Content::whereNotNull('not_video_url')
            ->where('not_video_url', '<>', '')
            ->where(['not_status' => 1,
                'id_category' => 16,
                //  'cat_type' => 1,
                'not_publish' => 1])
            ->orderBy('not_id', 'DESC')
            ->leftJoin('tbtt_user', 'use_id', 'not_user')
            ->select('not_id', 'not_title', 'not_video_url', 'not_user', 'use_fullname')
            ->where(function($q) use ($search) {
            $q->where('not_title', 'LIKE', '%' . $search . '%');
        });
        Content::filter($query,$user);
        if ($paging) {
        	$paginate = $query->paginate($limit, ['*'], 'page', $page);

        	$arraysData = [];
	        foreach ($paginate->items() as $value) {
	            $data = $value->toArray();
	            $data['thumbnail'] = Utils::getThumnailFromYouUrl($data['not_video_url']);
	            $arraysData[] = $data;
	        }
	        $results = $paginate->toArray();
	        $results['data'] = $arraysData;
	        return $results;
        }

        $arraysData = [];
        foreach ($query->limit($limit)->get() as $value) {
        	$data = $value->toArray();
        	$data['thumbnail'] = Utils::getThumnailFromYouUrl($data['not_video_url']);
        	$arraysData[] = $data;
        }
        return $arraysData;
    }

    public static function searchByCategories($user = null, $search, $paging = false, $page = 0, $limit = 6) {
    	$query = Category::orderBy('cat_id','ASC')
    	->select('cat_id', 'cat_name')
        ->where('cat_status', Category::STATUS_ACTIVE)
        ->where(function($q) use ($search) {
            $q->orWhere('cat_name', 'LIKE', '%' . $search . '%');
        });

        if ($paging) {
        	return $query->paginate($limit, ['*'], 'page', $page);
        }

        return $query->limit($limit)->get();
    }

    public static function searchByProducts($shop_id = null, $search, $paging = false, $page = 0, $limit = 6) {
    	$query = Product::orderBy('pro_name','ASC')
    	->select('pro_id', 'pro_name', 'pro_image', 'pro_dir', 'pro_cost')
        ->where('pro_category', '<>', 0)
        ->where('pro_status', Product::STATUS_ACTIVE)
        ->where(function($q) use ($search) {
            $q->orWhere('pro_name', 'LIKE', '%' . $search . '%');
        });

        if ($shop_id && $shop_id > 0) {
        	$shop = Shop::where('sho_id', $shop_id)->select('sho_id', 'sho_user', 'sho_name', 'sho_descr')->first();
        	$sho_user = $shop && isset($shop) ? $shop->sho_user : -1;
        	$query->where('pro_user', $sho_user);
        }

        if ($paging) {
        	$paginate = $query->paginate($limit, ['*'], 'page', $page);
            $results = $paginate->toArray();
            if (isset($shop) && $shop) {
                $results['shop'] = [
                    'sho_id' => $shop_id ? $shop_id : 0,
                    'sho_name' => $shop->sho_name,
                    'sho_descr' => $shop->sho_descr
                ];    
            } else {
                $results['shop'] = [
                    'sho_id' => 0,
                    'sho_name' => 'Tất cả sản phẩm',
                    'sho_descr' => ''
                ];
            }
            return $results;
        }

        return $query->limit($limit)->get();
    }

    public static function searchByShopsProducts($search, $page = 0, $limit = 6) {
    	$shop_table = Shop::tableName();
    	$product_table = Product::tableName();
    	$query = Shop::orderBy('sho_name','ASC')
    	->select('sho_id', 'sho_name', 'sho_descr')
    	->leftJoin($product_table . ' as p', function($join) use ($search, $product_table, $shop_table) {
            $join->on('p.pro_user', '=', $shop_table . '.sho_user');
            $join->where('pro_name', 'LIKE', '%' . $search . '%')
            ->where('p.pro_category', '<>', 0)
            ->where('p.pro_status', Product::STATUS_ACTIVE);
        });

        $colTotalTrip = 'count(p.pro_id)';
        //$query->selectRaw($colTotalTrip.' as "totalTrip"');
        $query->havingRaw($colTotalTrip.' > 0');
        $query->groupBy($shop_table.'.sho_id');
        $paginate = $query->paginate($limit, ['*'], 'page', $page);

        $arraysData = [];
        if ($paginate->total() > 0) {
        	$arraysData[] = [
        		'sho_id' => 0,
        		'sho_name' => 'Tất cả sản phẩm',
        		'sho_descr' => '',
        		'products' => self::searchByProducts(0, $search)
        	];
        }
        foreach ($paginate->items() as $value) {
        	$data = $value->toArray();
        	$data['products'] = self::searchByProducts($data['sho_id'], $search);
        	$arraysData[] = $data;
        }
        $results = $paginate->toArray();
        $results['data'] = $arraysData;
        return $results;
    }
}