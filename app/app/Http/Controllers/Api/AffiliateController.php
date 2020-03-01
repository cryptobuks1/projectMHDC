<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductAffiiate;
use App\Models\PackageDailyUser;
use DB;
use App\Models\UserTree;
use App\Helpers\Commons;
use App\Models\PackageUser;
use App\Models\Package;
use App\Models\PackageInfo;
use App\Models\Shop;
use App\Models\ProductPresssAff;
use App\Jobs\SendAffProductNotification;
use App\Models\Notification;

/**
 * Description of AffiliateController
 *
 * @author hoanvu
 */
class AffiliateController extends ApiController {

    //put your code here
    /**
     * @SWG\Post(
     *     path="/api/v1/me/addProduct/{id}",
     *     summary="aff - Add sãn phẩm đăng bán cho tài khoản aff",
     *     tags={"Products"},
     *     operationId="Products",
     *     description="aff - Add sãn phẩm đăng bán cho tài khoản aff",
     *     produces={"application/json"},
     *     @SWG\Response(
     *         response=200,
     *         description="Kết quả thu được sau khi add",
     *         @SWG\Schema(ref="#/definitions/Trip")
     *     )
     * )
     */
    public function addProduct($id, Request $req) {

        $user = $req->user();
        $productdb = (new Product)->getTable();
        $productAff = (new ProductAffiiate)->getTable();
        $ids = [$id];
        $totalAfCate = PackageDailyUser::getTotalAfCate($user->use_id);
        $number_cat = 30 + (int) $totalAfCate;
        $hasAdded = ProductAffiiate::where(['use_id' => $user->use_id, 'pro_id' => $id])->count();

        if ($hasAdded > 0) {
            return response([
                'msg' => Lang::get('product.added'),
                ], 422);
        }
        $query = Product::where([])->join($productAff, $productAff . '.pro_id', $productdb . '.pro_id');
        $totalAdded = $query->select($productdb . '.pro_id', $productdb . '.pro_category')
                ->where($productAff . '.use_id', $user->use_id)->count();

//            ->groupBy($productdb . '.pro_id', $productdb . '.pro_category')
//            ->havingRaw('COUNT(' . $productdb . '.pro_id) >1');
      
        $total = $this->getTotalCategories($user, $ids);

        if ($total > $number_cat) {
            return response([
                'msg'=>Lang::get('product.role_add_product_cat', ['number' => $number_cat]),
                ], 422);
//     $return = array('error' => true, 'message' => 'Bạn chỉ có thể gắp hàng tối đa '.$number_cat.' danh mục. Để có thể gắp thêm vui lòng mua <a class="link_popup" href="'.base_url().'account/service">Dịch vụ kệ hàng</a>');
        }
//        if ($totalAdded >= ProductAffiiate::NUMBER_ALLOW_ADD_PRO) {
//            return response([
//                'msg' => Lang::get('product.role_add_product', ['number' => ProductAffiiate::NUMBER_ALLOW_ADD_PRO]),
//                ], 422);
//        }

        try {
            $products = [];
            foreach ($ids as $k => $id) {
                $product = new ProductAffiiate([
                    'use_id' => $user->use_id,
                    'pro_id' => $id,
                    'date_added' => time(),
                    'homepage' => 1
                ]);
                $product->save();
                $products[] = $product;
            }
            return response([
                'msg' => Lang::get('response.success'),
                'data' => $products
            ]);
        } catch (Exception $ex) {
            return response(['msg' => Lang::get('response.server_error')], 500);
        }
    }

    /**
     * @SWG\Delete(
     *     path="/api/v1/me/removeProduct/{id}",
     *     summary="affiliate - Xóa sãn phẩm đăng bán  ",
     *     tags={"Products"},
     *     operationId="Products",
     *     description="affiliate - Xóa sãn phẩm đăng",
     *     produces={"application/json"},
     *     @SWG\Response(
     *         response=200,
     *         description="Kết quả thu được sau khi Xóa"
     *     )
     * )
     */
    public function removeProduct($id, Request $req) {
        
        $result = ProductAffiiate::where([
                'use_id' => $req->user()->use_id,
                'pro_id' => $id,
            ])->first();
        if (empty($result)) {
            return response([
                'msg' => Lang::get('response.product_not_found'),
                ], 404);
        }
        DB::delete('DELETE FROM ' . (new ProductAffiiate)->getTable() . ' where use_id=' . $req->user()->use_id . ' AND pro_id =' . $id);
        if (!empty($result)) {
            $data = $result->toArray();
            
            dispatch(new SendAffProductNotification(Notification::TYPE_AFFILIATE_REMOVE_SELECT_BUY_PRODUCT, $data));
        }
        return response([
            'msg' => Lang::get('response.success')
        ],200);
    }

    protected function getTotalCategories($user, $ids) {
        $productdb = (new Product)->getTable();
        $productAffdb = (new ProductAffiiate)->getTable();

        $shop_parent = 0;
        $return = 0;
        $categories = [];
        if ($user->parent_id > 0) {
            $user_parent = User::where(['use_id' => $user->parent_id, 'use_status' => User::STATUS_ACTIVE])->first();
            if (!empty($user_parent)) {
                $query = ProductAffiiate::where(['use_id' => $user->use_id]);
                $query->select($productdb . '.pro_category');
                $query->join($productdb, $productdb . '.pro_id', $productAffdb . '.pro_id');
                if ($user_parent->use_group === User::TYPE_AffiliateStoreUser) {
                    $shop_parent = $user_parent->use_id;
                    $query->where('pro_user', '<>', $shop_parent);
                } elseif ($user_parent->use_group !== User::TYPE_AffiliateStoreUser && $user->parent_shop > 0) {
                    $shop_parent = $user->parent_shop;
                    $query->where('pro_user', '<>', $shop_parent);
                }
                $query->groupBy($productdb . '.pro_category');
                $categories = $query->get();
            }
        }
         $product = null;
        if (count($ids) == 1) {
            $product = Product::where('pro_id', $ids[0])->select('pro_category', 'pro_user')->first();
            if ($shop_parent == $product->pro_user) { // neu gap san pham cua shop cha thi luon cho gap
                $return = 0;
            }
        } elseif (count($ids) == 2 && $ids[0] == 1) {
            $product = Product::where('pro_id', $ids[1])->select('pro_category', 'pro_user')->first();
            if ($shop_parent == $product->pro_user) { // neu gap san pham cua shop cha thi luon cho gap
                $return = 0;
            }
        }

        if (count($categories) > 0) {
            $plucked = $categories->pluck('pro_category')->toArray();
            // Nhiều id 
            if (count($ids) >= 2 && $ids[0] > 1) {
                $restCat = 0;
                $counter = Product::whereIn('pro_id', $ids)->whereIn('pro_category', $plucked)->select('pro_category', 'pro_user')->count();

                $restCat = count($ids) - $counter;
                $return = count($categories) + $restCat;
            }
        }
        
       
        $return = count($categories) + 1;
        if (count($categories) > 0) {
            if (!empty($product)) {
                if (in_array($product->pro_category, $plucked)) {
                    $return = count($categories);
                }
            }
        }

        return $return;
    }

    /**
     * @SWG\Get(
     *     path="/api/v1/me/aff-selected-products",
     *     operationId="products",
     *     description="Danh sach Sản Phẩm aff đã chọn",
     *     produces={"application/json"},
     *     tags={"affiliate-products"},
     *     summary="Danh sach Sản Phẩm aff",
    *  @SWG\Parameter(
     *         name="keywords",
     *         in="query",
     *         description="Tìm kiếm theo từ khóa",
     *         required=false,
     *         type="string",
     *     ),
     *  @SWG\Parameter(
     *         name="categoryId",
     *         in="query",
     *         description="categoryId, default -1",
     *         required=false,
     *         type="string",
     *     ),
     *  @SWG\Parameter(
     *         name="price_from",
     *         in="query",
     *         description="Gia từ",
     *         required=false,
     *         type="integer",
     *     ),
     *  @SWG\Parameter(
     *         name="price_to",
     *         in="query",
     *         description="Giá giớ hạn",
     *         required=false,
     *         type="integer",
     *     ),
     *    @SWG\Parameter(
     *         name="price_type",
     *         in="query",
     *         description="Tìm kiếm theo Giá : 1, Hoa hồng tiền : 2, Hoa hồng % : 3",
     *         required=false,
     *         type="integer",
     *     ),
     *   @SWG\Parameter(
     *         name="pro_type",
     *         in="query",
     *         description="Tìm kiếm sản phẩm : 0, Tìm kiếm coupon : 2 ",
     *         required=false,
     *         type="integer",
     *     ),
     *   @SWG\Parameter(
     *         name="shop_guarantee",
     *         in="query",
     *         description="Tìm kiếm theo gian hàng đảm bảo",
     *         required=false,
     *         type="integer",
     *     ),
     *  @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="page",
     *         required=false,
     *         type="integer",
     *     ),
     *    @SWG\Parameter(
     *         name="limit",
     *         in="query",
     *         description="limit",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="orderBy",
     *         in="query",
     *         description="orderBy",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="public trips"
     *     )
     * )
     */
    public function myProducts(Request $req) {
        return response([
            'msg' => Lang::get('response.success'),
            'data' => $this->selectProduct($req, $req->user())
        ]);
    }

    /**
     * @SWG\Get(
     *     path="/api/v1/user/{id}/aff-selected-products",
     *     operationId="products",
     *     description="Danh sach Sản Phẩm aff đã chọn",
     *     produces={"application/json"},
     *     tags={"affiliate-products"},
     *     summary="Danh sach Sản Phẩm aff",
    *  @SWG\Parameter(
     *         name="keywords",
     *         in="query",
     *         description="Tìm kiếm theo từ khóa",
     *         required=false,
     *         type="string",
     *     ),
     *  @SWG\Parameter(
     *         name="categoryId",
     *         in="query",
     *         description="categoryId, default -1",
     *         required=false,
     *         type="string",
     *     ),
     *  @SWG\Parameter(
     *         name="price_from",
     *         in="query",
     *         description="Gia từ",
     *         required=false,
     *         type="integer",
     *     ),
     *  @SWG\Parameter(
     *         name="price_to",
     *         in="query",
     *         description="Giá giớ hạn",
     *         required=false,
     *         type="integer",
     *     ),
     *    @SWG\Parameter(
     *         name="price_type",
     *         in="query",
     *         description="Tìm kiếm theo Giá : 1, Hoa hồng tiền : 2, Hoa hồng % : 3",
     *         required=false,
     *         type="integer",
     *     ),
     *   @SWG\Parameter(
     *         name="pro_type",
     *         in="query",
     *         description="Tìm kiếm sản phẩm : 0, Tìm kiếm coupon : 2 ",
     *         required=false,
     *         type="integer",
     *     ),
     *   @SWG\Parameter(
     *         name="shop_guarantee",
     *         in="query",
     *         description="Tìm kiếm theo gian hàng đảm bảo",
     *         required=false,
     *         type="integer",
     *     ),
     *  @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="page",
     *         required=false,
     *         type="integer",
     *     ),
     *    @SWG\Parameter(
     *         name="limit",
     *         in="query",
     *         description="limit",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="orderBy",
     *         in="query",
     *         description="orderBy",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="public trips"
     *     )
     * )
     */
    public function userProducts($id, Request $req) {
        $user = User::find($id);
        if (empty($user)) {
            return response([
                'msg' => Lang::get('response.user_not_found'),
            ], 404);
        }
        $viewer = $req->user();
        return response([
            'msg' => Lang::get('response.success'),
            'data' => $this->selectProduct($req, $user,$viewer)
        ]);
    }

    private function selectProduct($req, $user = null,$viewer = null) {
        $id_my_parent = null;
        if ($user) {
            $parent = $user->parentActiveInfo;
            #Get my parent

            if ($parent && ($parent->use_group == User::TYPE_AffiliateStoreUser || $parent->use_group == User::TYPE_BranchUser)) {
                $id_my_parent = $parent->use_id;
            } elseif ($parent && ($parent->use_group == User::TYPE_StaffUser || $parent->use_group == User::TYPE_StaffStoreUser)) {
                #Get parent of parent
                $paren_of_parent = $parent->parentActiveInfo;

                if ($paren_of_parent && ($paren_of_parent->use_group == User::TYPE_AffiliateStoreUser || $paren_of_parent->use_group == User::TYPE_AffiliateUser)) {
                    $id_my_parent = $paren_of_parent->use_id;
                }
            } else {
                $id_my_parent = $user->parent_shop;
            }
        }
        $prodb = (new Product)->getTable();
        $proAffdb = (new ProductAffiiate)->getTable();
        $shopdb = Shop::tableName();
        $query = Product::where(['is_product_affiliate' => Product::IS_AFF_PRODUCT, 'pro_status' => Product::STATUS_ACTIVE]);
        $query->join($shopdb, $shopdb . '.sho_user', $prodb . '.pro_user');
        $query->where(function($q) use($id_my_parent, $user, $proAffdb) {
            if (!empty($id_my_parent)) {
                $q->orWhere('pro_user', $id_my_parent);
            }
            $q->orWhereIn('pro_id', function($qin) use($user, $proAffdb) {
                $qin->select('pro_id')
                    ->from($proAffdb)
                    ->where('use_id', $user->use_id)
                    ->where('homepage', 1);
            });
        });
        if (!empty($req->keywords)) {
            $query->where(function($q) use ($prodb, $shopdb, $req) {
                $keywords = $req->keywords;
                $q->orWhere($prodb . '.pro_name', 'LIKE', '%' . $keywords . '%');
                $q->orWhere($prodb . '.pro_descr', 'LIKE', '%' . $keywords . '%');
                $q->orWhere($shopdb . '.sho_link', 'LIKE', '%' . $keywords . '%');
                $q->orWhere($shopdb . '.sho_name', 'LIKE', '%' . $keywords . '%');
            });
        }
        if (!empty($req->orderBy)) {
            $req->orderBy = explode(',', $req->orderBy);
            $key = $req->orderBy[0];
            $value = $req->orderBy[1] ? $req->orderBy[1] : 'DESC';
            $query->orderBy($prodb . '.' . $key, $value);
        } else {
            $query->orderBy($prodb . '.pro_id', 'DESC');
        }
       
        if ($req->shop_guarantee) {
            $query->whereExists(function($query) {
                $packageUserdb = PackageUser::tableName();
                $packagedb = Package::tableName();
                $packageInfodb = PackageInfo::tableName();
                $query->select($packageUserdb . '.package_id')
                    ->from($packageUserdb)
                    ->leftJoin($packagedb, $packagedb . '.id', $packageUserdb . '.package_id')
                    ->leftJoin($packageInfodb, $packageInfodb . '.id', $packagedb . '.info_id')
                    ->where($packageUserdb . '.user_id', 'tbtt_shop.sho_user')
                    ->where($packagedb . '.info_id', '>=', 3)
                    ->whereDate($packageUserdb . '.begined_date', '<', date('Y-m-d'))
                    ->whereDate($packageUserdb . '.ended_date', '>', date('Y-m-d'))
                    ->where([
                        $packageUserdb . '.status' => PackageUser::STATUS_ACTIVE,
                        $packageUserdb . '.payment_status' => PackageUser::PAYMENT_DONE,
                        $packageInfodb . '.pType' => 'package',
                ]);
            });
        }


        if ($req->price_from) {
            switch ($req->price_type) {
                case Product::PRICE_TYPE_DEFAULT:
                    $query->where($prodb . '.pro_cost', '>=', $req->price_from);
                    break;
                case Product::PRICE_TYPE_PROMOTION_MONEY:
                    $query->where($prodb . '.af_amt', '>=', $req->price_from);
                    break;
                case Product::PRICE_TYPE_PROMOTION_PERCENT:
                    $query->where($prodb . '.af_rate', '>=', $req->price_from);
                    break;
            }
        }
        if ($req->price_to) {
            switch ($req->price_type) {
                case Product::PRICE_TYPE_DEFAULT:
                    $query->where($prodb . '.pro_cost', '<=', $req->price_to);
                    break;
                case Product::PRICE_TYPE_PROMOTION_MONEY:
                    $query->where($prodb . '.af_amt', '<=', $req->price_to);
                    break;
                case Product::PRICE_TYPE_PROMOTION_PERCENT:
                    $query->where($prodb . '.af_rate', '<=', $req->price_to);
                    break;
            }
        }
        
        $product_type = 0;
        if ($req->pro_type) {
            $product_type = $req->pro_type;
        }
        if ($req->categoryId && $req->categoryId != -1) {
            $query->whereIn($prodb . '.pro_category', Category::getAllLevelCategorieById($req->categoryId));
        }
        $query->where($prodb . '.pro_type',$product_type);
        $query->select($prodb.".*",DB::raw($prodb.'.af_rate as aff_rate, af_amt as af_amt_ori'),DB::raw(Product::queryDiscountProduct()));
        $limit = $req->limit ? (int) $req->limit : 10;
        $page = $req->page ? (int) $req->page : 0;

        $results = $query->paginate($limit, ['*'], 'page', $page);

        //populate shop
        foreach ($results as $value) {
            $value->shop;
            $value->buildPrice($user);
            $value->generateLinks(null,$viewer);
            $value->isSelected($user);
            $value->additionFiled();
            $value->productParent($user);
            $value->detailProducts;
            if (!empty($user) && $user->use_id == $value->pro_user) {
                $value->promotions = null;
            } else {
                $value->promotions;
            }
        }

        return $results;
    }
    
      /**
     * @SWG\Get(
     *     path="/api/v1/me/affiliate-product",
     *     operationId="affiliate-products",
     *     description="Kho hàng Cộng tác viên online",
     *     produces={"application/json"},
     *     tags={"affiliate-products"},
     *     summary="Kho hàng Cộng tác viên online",
     *  @SWG\Parameter(
     *         name="keywords",
     *         in="query",
     *         description="Tìm kiếm theo từ khóa",
     *         required=false,
     *         type="string",
     *     ),
     *  @SWG\Parameter(
     *         name="price_from",
     *         in="query",
     *         description="Gia từ",
     *         required=false,
     *         type="integer",
     *     ),
     *  @SWG\Parameter(
     *         name="price_to",
     *         in="query",
     *         description="Giá giớ hạn",
     *         required=false,
     *         type="integer",
     *     ),
     *  @SWG\Parameter(
     *         name="categoryId",
     *         in="query",
     *         description="categoryId, default -1",
     *         required=false,
     *         type="string",
     *     ),
     *         name="price_type",
     *         in="query",
     *         description="Tìm kiếm theo Giá : 1, Hoa hồng tiền : 2, Hoa hồng % : 3",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="shop_guarantee",
     *         in="query",
     *         description="Tìm kiếm theo gian hàng đảm bảo",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="free_category",
     *         in="query",
     *         description="Tìm kiếm theo danh mục miển phí 1 , Tìm kiếm theo gian hàng công ty : 0",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="pro_type",
     *         in="query",
     *         description="Tìm kiếm sản phẩm : 0, Tìm kiếm coupon : 2 ",
     *         required=false,
     *         type="integer",
     *     ),
       *  @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="page",
     *         required=false,
     *         type="integer",
     *     ),
     *    @SWG\Parameter(
     *         name="limit",
     *         in="query",
     *         description="limit",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="orderBy",
     *         in="query",
     *         description="orderBy",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="public trips"
     *     )
     * )
     */
    public function products(Request $req){
        return response([
            'msg' => Lang::get('response.success'),
            'data' => $this->_affiliateProducts($req->user(), $req)
        ]);
    }

    /**
     * @SWG\Get(
     *     path="/api/v1/user/{id}/affiliate-product",
     *     operationId="affiliate-products-user",
     *     description="Kho hàng Cộng tác viên online",
     *     produces={"application/json"},
     *     tags={"affiliate-products"},
     *     summary="Kho hàng Cộng tác viên online",
     *  @SWG\Parameter(
     *         name="keywords",
     *         in="query",
     *         description="Tìm kiếm theo từ khóa",
     *         required=false,
     *         type="string",
     *     ),
     *  @SWG\Parameter(
     *         name="price_from",
     *         in="query",
     *         description="Gia từ",
     *         required=false,
     *         type="integer",
     *     ),
     *  @SWG\Parameter(
     *         name="price_to",
     *         in="query",
     *         description="Giá giớ hạn",
     *         required=false,
     *         type="integer",
     *     ),
     *  @SWG\Parameter(
     *         name="categoryId",
     *         in="query",
     *         description="categoryId, default -1",
     *         required=false,
     *         type="string",
     *     ),
     *         name="price_type",
     *         in="query",
     *         description="Tìm kiếm theo Giá : 1, Hoa hồng tiền : 2, Hoa hồng % : 3",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="shop_guarantee",
     *         in="query",
     *         description="Tìm kiếm theo gian hàng đảm bảo",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="free_category",
     *         in="query",
     *         description="Tìm kiếm theo danh mục miển phí 1 , Tìm kiếm theo gian hàng công ty : 0",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="pro_type",
     *         in="query",
     *         description="Tìm kiếm sản phẩm : 0, Tìm kiếm coupon : 2 ",
     *         required=false,
     *         type="integer",
     *     ),
       *  @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="page",
     *         required=false,
     *         type="integer",
     *     ),
     *    @SWG\Parameter(
     *         name="limit",
     *         in="query",
     *         description="limit",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="orderBy",
     *         in="query",
     *         description="orderBy",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="public trips"
     *     )
     * )
     */
    public function userAffiliateProducts($id, Request $req) {
        $user = User::find($id);
        if (empty($user)) {
            return response([
                'msg' => Lang::get('response.user_not_found'),
            ], 404);
        }
        return response([
            'msg' => Lang::get('response.success'),
            'data' => $this->_affiliateProducts($req->user(), $user)
        ]);
    }

    public function _affiliateProducts($user, $req) {
        $prodb = (new Product)->getTable();
        $proAffdb = (new ProductAffiiate)->getTable();
        $shopdb = (new Shop)->getTable();
        $query = Product::where([])->join($shopdb, $shopdb . '.sho_user', $prodb . '.pro_user')
            ->where($shopdb . '.shop_type', '<>', 1)->where(['is_product_affiliate' => 1, 'pro_status' => Product::STATUS_ACTIVE])
            ->select($prodb . '.*', DB::raw($prodb . '.`af_amt` + ' . $prodb . '.`af_rate` * ' . $prodb.'.`pro_cost` / 100 as amt'));

        if(!empty($req->keywords)){
            $query->where(function($q) use ($req, $prodb, $shopdb) {
                $keywords = $req->keywords;
                $q->orWhere($prodb . '.pro_name', 'LIKE', '%' . $keywords . '%');
                $q->orWhere($prodb . '.pro_descr', 'LIKE', '%' . $keywords . '%');
                $q->orWhere($shopdb . '.sho_link', 'LIKE', '%' . $keywords . '%');
                $q->orWhere($shopdb . '.sho_name', 'LIKE', '%' . $keywords . '%');
            });
        }
        $catids = $this->searchCategory($req);
        if (!empty($catids)) {
            $query->whereIn('pro_category', $catids);
        }
        if ($req->categoryId && $req->categoryId != -1) {
            $query->whereIn('pro_category', Category::getAllLevelCategorieById($req->categoryId));
        }
        if ($req->price_from) {
            switch ($req->price_type) {
                case Product::PRICE_TYPE_DEFAULT:
                    $query->where($prodb . '.pro_cost', '>=', $req->price_from);
                    break;
                case Product::PRICE_TYPE_PROMOTION_MONEY:
                    $query->where($prodb . '.af_amt', '>=', $req->price_from);
                    break;
                case Product::PRICE_TYPE_PROMOTION_PERCENT:
                    $query->where($prodb . '.af_rate', '>=', $req->price_from);
                    break;
            }
        }
        if ($req->price_to) {
            switch ($req->price_type) {
                case Product::PRICE_TYPE_DEFAULT:
                    $query->where($prodb . '.pro_cost', '<=', $req->price_to);
                    break;
                case Product::PRICE_TYPE_PROMOTION_MONEY:
                    $query->where($prodb . '.af_amt', '<=', $req->price_to);
                    break;
                case Product::PRICE_TYPE_PROMOTION_PERCENT:
                    $query->where($prodb . '.af_rate', '<=', $req->price_to);
                    break;
            }
        }
        

        
        if ($req->shop_guarantee) {
            $query->whereExists(function($query) {
                $packageUserdb = PackageUser::tableName();
                $packagedb = Package::tableName();
                $packageInfodb = PackageInfo::tableName();
                $query->select($packageUserdb . '.package_id')
                    ->from($packageUserdb)
                    ->leftJoin($packagedb, $packagedb . '.id', $packageUserdb . '.package_id')
                    ->leftJoin($packageInfodb, $packageInfodb . '.id', $packagedb . '.info_id')
                    ->where($packageUserdb . '.user_id', 'tbtt_shop.sho_user')
                    ->where($packagedb . '.info_id', '>=', 3)
                    ->whereDate($packageUserdb . '.begined_date', '<', date('Y-m-d'))
                    ->whereDate($packageUserdb . '.ended_date', '>', date('Y-m-d'))
                    ->where([
                        $packageUserdb . '.status' => PackageUser::STATUS_ACTIVE,
                        $packageUserdb . '.payment_status' => PackageUser::PAYMENT_DONE,
                        $packageInfodb . '.pType' => 'package',
                ]);
            });
        }
        
        $product_type = 0;
        if ($req->pro_type) {
            $product_type = $req->pro_type;
        }
        $query->where($prodb . '.pro_type',$product_type);

        $get_p1 = $user->parentInfo;
        $get_p2 = $get_p1->parentInfo;
        if (!empty($get_p2)) {
            $get_p3 = $get_p2->parentInfo;
            if (!empty($get_p3)) {
                $get_p4 = $get_p3->parentInfo;
            }
        }


        if (!empty($get_p3) && $get_p3->use_group == User::TYPE_StaffStoreUser) {
            if ($req->free_category == 0) {
                $query->whereIn($prodb . '.pro_user', [$get_p2->use_id]);
            } else {
                $query->where($prodb . '.pro_user', '<>', $get_p4->use_id); //Gh la cha cap 4 //GH-NVGH-CN-NV-AF
            }
        } else {
            
            //Trường hợp Cha của affliliate là như bên dưới Chi nhánh , Gian hàng 
            if (in_array($get_p2->use_group, [User::TYPE_AffiliateStoreUser, User::TYPE_StaffStoreUser, User::TYPE_BranchUser])) {
                if ($get_p2->use_group == User::TYPE_AffiliateStoreUser) {
                    //Gh la cha cap 2
                    if ($req->free_category == 0) {
                        if ($get_p1->use_group == User::TYPE_BranchUser) {
                            $query->whereIn($prodb . '.pro_user', [$get_p1->use_id]); // GH-CN-Aff
                        } else {
                            $query->where($prodb . '.pro_user', $get_p2->use_id); //GH-NVGH-AF, GH-NV-AF
                        }
                    } else {
                        $query->where($prodb . '.pro_user', '<>', $get_p2->use_id);
                    }
                } else {
                    $afId = (int) $get_p3->use_id; //Gh la cha cap 3
                    if ($req->free_category == 0) {
                        if ($get_p2->use_group == User::TYPE_BranchUser) {
                            $query->whereIn($prodb . '.pro_user', [$get_p2->use_id]); //GH-CN-NV-AF
                        } else {
                            if ($get_p1->use_group == User::TYPE_StaffUser) {
                                $query->whereIn($prodb . '.pro_user', [$get_p3->use_id]);
                                //GH-NVGH-NV-AF
                            } else {
                                $query->whereIn($prodb . '.pro_user', [$get_p1->use_id]);
                                //GH-NVGH-CN-AF
                            }
                        }
                    } else {
                        $query->where($prodb . '.pro_user', '<>', $get_p3->use_id);
                    }
                }
            } else {
                if ($get_p2->use_group == User::TYPE_StaffStoreUser) {
                  
                } else {
                    if ($req->free_category == 0) {
                        $query->whereIn($prodb . '.pro_user', [$get_p1->use_id]);
                    } else {
                        
                        $query->where($prodb . '.pro_user', '<>', $get_p1->use_id);
                    }
                }
            }
        }
        
         if (!empty($req->orderBy)) {
            $req->orderBy = explode(',', $req->orderBy);
            $key = $req->orderBy[0];
            $value = $req->orderBy[1] ? $req->orderBy[1] : 'DESC';
            $query->orderBy($key, $value);
        } else {
            $query->orderBy('pro_name', 'ASC');
        }
        $query->select($prodb.".*",DB::raw($prodb.'.af_rate as aff_rate, af_amt as af_amt_ori'),DB::raw(Product::queryDiscountProduct()));

        $limit = $req->limit ? (int) $req->limit : 10;
        $page = $req->page ? (int) $req->page : 0;
        
        $results = $query->paginate($limit, ['*'], 'page', $page);
     
        //populate shop
        foreach ($results as $value) {
            $value->shop;
            $value->buildPrice($user);
            $value->generateLinks(null,$req->user());
            $value->additionFiled();
            $value->isSelected($user);
            $value->productParent($user);
            $value->detailProducts;
            if (!empty($user) && $user->use_id == $value->pro_user) {
                $value->promotions = null;
            } else {
                $value->promotions;
            }
        }

        return $results;
    }
    
    
    /**
     * @SWG\Get(
     *     path="/api/v1/me/affiliate-press-products",
     *     operationId="affiliate-press-products",
     *     description="Danh sách sản phẩm kí gửi online",
     *     produces={"application/json"},
     *     tags={"affiliate-products"},
     *     summary="Danh sách sản phẩm kí gửi online",
     *  @SWG\Parameter(
     *         name="keywords",
     *         in="query",
     *         description="Tìm kiếm theo từ khóa",
     *         required=false,
     *         type="string",
     *     ),
     *  @SWG\Parameter(
     *         name="categoryId",
     *         in="query",
     *         description="categoryId, default -1",
     *         required=false,
     *         type="string",
     *     ),
     *  @SWG\Parameter(
     *         name="price_from",
     *         in="query",
     *         description="Gia từ",
     *         required=false,
     *         type="integer",
     *     ),
     *  @SWG\Parameter(
     *         name="price_to",
     *         in="query",
     *         description="Giá giớ hạn",
     *         required=false,
     *         type="integer",
     *     ),
     *    @SWG\Parameter(
     *         name="price_type",
     *         in="query",
     *         description="Tìm kiếm theo Giá : 1, Hoa hồng tiền : 2, Hoa hồng % : 3",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="shop_guarantee",
     *         in="query",
     *         description="Tìm kiếm theo gian hàng đảm bảo",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="free_category",
     *         in="query",
     *         description="Tìm kiếm theo danh mục miển phí 1 , Tìm kiếm theo gian hàng công ty : 0",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="pro_type",
     *         in="query",
     *         description="Tìm kiếm sản phẩm : 0, Tìm kiếm coupon : 2 ",
     *         required=false,
     *         type="integer",
     *     ),
       *  @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="page",
     *         required=false,
     *         type="integer",
     *     ),
     *    @SWG\Parameter(
     *         name="limit",
     *         in="query",
     *         description="limit",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="orderBy",
     *         in="query",
     *         description="orderBy",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Danh sách sản phẩm kí gửi online"
     *     )
     * )
     */
    public function pressProducts(Request $req) {
        $user = $req->user();

        return response([
            'msg' => Lang::get('response.success'),
            'data' => $this->_pressProducts($user, $req)
        ]);
    }

    /**
     * @SWG\Get(
     *     path="/api/v1/user/{id}/affiliate-press-products",
     *     operationId="affiliate-press-products-user",
     *     description="Danh sách sản phẩm kí gửi online",
     *     produces={"application/json"},
     *     tags={"affiliate-products"},
     *     summary="Danh sách sản phẩm kí gửi online",
     *  @SWG\Parameter(
     *         name="keywords",
     *         in="query",
     *         description="Tìm kiếm theo từ khóa",
     *         required=false,
     *         type="string",
     *     ),
     *  @SWG\Parameter(
     *         name="categoryId",
     *         in="query",
     *         description="categoryId, default -1",
     *         required=false,
     *         type="string",
     *     ),
     *  @SWG\Parameter(
     *         name="price_from",
     *         in="query",
     *         description="Gia từ",
     *         required=false,
     *         type="integer",
     *     ),
     *  @SWG\Parameter(
     *         name="price_to",
     *         in="query",
     *         description="Giá giớ hạn",
     *         required=false,
     *         type="integer",
     *     ),
     *    @SWG\Parameter(
     *         name="price_type",
     *         in="query",
     *         description="Tìm kiếm theo Giá : 1, Hoa hồng tiền : 2, Hoa hồng % : 3",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="shop_guarantee",
     *         in="query",
     *         description="Tìm kiếm theo gian hàng đảm bảo",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="free_category",
     *         in="query",
     *         description="Tìm kiếm theo danh mục miển phí 1 , Tìm kiếm theo gian hàng công ty : 0",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="pro_type",
     *         in="query",
     *         description="Tìm kiếm sản phẩm : 0, Tìm kiếm coupon : 2 ",
     *         required=false,
     *         type="integer",
     *     ),
       *  @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="page",
     *         required=false,
     *         type="integer",
     *     ),
     *    @SWG\Parameter(
     *         name="limit",
     *         in="query",
     *         description="limit",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="orderBy",
     *         in="query",
     *         description="orderBy",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Danh sách sản phẩm kí gửi online"
     *     )
     * )
     */
    public function userPressProducts($id, Request $req) {
        $user = User::find($id);
        if (!$user) {
            return response([
                'msg' => Lang::get('response.user_not_found')
            ], 404);
        }

        return response([
            'msg' => Lang::get('response.success'),
            'data' => $this->_pressProducts($user, $req)
        ]);  
    }

    private function _pressProducts($user, $req) {
        $prodPresssAffdb = ProductPresssAff::tableName();
        $prodb = Product::tableName();
        $shopdb = Shop::tableName();
        $query = Product::where([])->join($prodPresssAffdb, $prodPresssAffdb . '.pro_id', $prodb . '.pro_id');
        $query->join($shopdb, $shopdb . '.sho_user', $prodb . '.pro_user');
        $query->where(['is_product_affiliate'=>1,'pro_status'=>Product::STATUS_ACTIVE]);
        $query->whereRaw($prodb.'.pro_id is not NULL');
        $query->whereRaw('FIND_IN_SET(' . $user->use_id . ','.$prodPresssAffdb.'.user_id_af ) ');
//        $query->whereRaw($prodPresssAffdb . '.user_id_af LIKE %,' . $req->user()->use_id . ',%');
        if (!empty($req->keywords)) {
            $query->where(function($q) use ($prodb, $shopdb,$req) {
                $keywords = $req->keywords;
                $q->orWhere($prodb . '.pro_name', 'LIKE', '%' . $keywords . '%');
                $q->orWhere($prodb . '.pro_descr', 'LIKE', '%' . $keywords . '%');
                $q->orWhere($shopdb . '.sho_link', 'LIKE', '%' . $keywords . '%');
                $q->orWhere($shopdb . '.sho_name', 'LIKE', '%' . $keywords . '%');
            });
        }
        $query->select($shopdb.'.sho_link',$shopdb.'.sho_name',$prodb.'.*');
        if ($req->price_from) {
            switch ($req->price_type) {
                case Product::PRICE_TYPE_DEFAULT:
                    $query->where($prodb . '.pro_cost', '>=', $req->price_from);
                    break;
                case Product::PRICE_TYPE_PROMOTION_MONEY:
                    $query->where($prodb . '.af_amt', '>=', $req->price_from);
                    break;
                case Product::PRICE_TYPE_PROMOTION_PERCENT:
                    $query->where($prodb . '.af_rate', '>=', $req->price_from);
                    break;
            }
        }
        if ($req->price_to) {
            switch ($req->price_type) {
                case Product::PRICE_TYPE_DEFAULT:
                    $query->where($prodb . '.pro_cost', '<=', $req->price_to);
                    break;
                case Product::PRICE_TYPE_PROMOTION_MONEY:
                    $query->where($prodb . '.af_amt', '<=', $req->price_to);
                    break;
                case Product::PRICE_TYPE_PROMOTION_PERCENT:
                    $query->where($prodb . '.af_rate', '<=', $req->price_to);
                    break;
            }
        }
        if ($req->categoryId && $req->categoryId != -1) {
            $query->whereIn($prodb.'.pro_category', Category::getAllLevelCategorieById($req->categoryId));
        }
         if (!empty($req->orderBy)) {
            $req->orderBy = explode(',', $req->orderBy);
            $key = $req->orderBy[0];
            $value = $req->orderBy[1] ? $req->orderBy[1] : 'DESC';
            $query->orderBy($prodb.'.'.$key, $value);
        } else {
            $query->orderBy($prodb.'.pro_id', 'DESC');
        }
        $query->select($prodb.".*",DB::raw($prodb.'.af_rate as aff_rate, af_amt as af_amt_ori'),DB::raw(Product::queryDiscountProduct()));
        $limit = $req->limit ? (int) $req->limit : 10;
        $page = $req->page ? (int) $req->page : 0;

        $results = $query->paginate($limit, ['*'], 'page', $page);
        $user = $req->user();
        //populate shop
        foreach ($results as $value) {
            $value->shop;
            $value->category;
            $value->buildPrice($user);
            $value->generateLinks(null,$req->user());
            $value->additionFiled();
            $value->productParent($user);
            $value->detailProducts;
            if (!empty($user) && $user->use_id == $value->pro_user) {
                $value->promotions = null;
            } else {
                $value->promotions;
            }
        }
        return $results;
    }
    
    /**
     * @SWG\Get(
     *     path="/api/v1/me/list-affiliate",
     *     operationId="list-affliate",
     *     description="Đại lý online đã giới thiệu",
     *     produces={"application/json"},
     *     tags={"Affiliate"},
     *     summary="Đại lý online đã giới thiệu",
     *  @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="page",
     *         required=false,
     *         type="integer",
     *     ),
     *    @SWG\Parameter(
     *         name="limit",
     *         in="query",
     *         description="limit",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="orderBy",
     *         in="query",
     *         description="orderBy",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="keywords",
     *         in="query",
     *         description="keywords",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="public list aff"
     *     )
     * )
     */
    
      /**
     * @SWG\Get(
     *     path="/api/v1/user/{id}/list-affiliate",
     *     operationId="list-affliate",
     *     description="Danh sách Affiliate của user ",
     *     produces={"application/json"},
     *     tags={"Affiliate"},
     *     summary="Danh sách Affiliate của user",
     *  @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="page",
     *         required=false,
     *         type="integer",
     *     ),
     *    @SWG\Parameter(
     *         name="limit",
     *         in="query",
     *         description="limit",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="orderBy",
     *         in="query",
     *         description="orderBy",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="keywords",
     *         in="query",
     *         description="keywords",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="public list aff"
     *     )
     * )
     */
    public function listAffiliate(Request $req) {
        $query = User::where([
                'use_status' => User::STATUS_ACTIVE,
                'use_group' => User::TYPE_AffiliateUser
        ]);
  
        if (empty($req->id)) {
            
            $tree = [];
            $listUser = $this->getTreeInList($req->user(), $tree);
            $listUser[] = $req->user()->use_id;
            $query->whereIn('parent_id', $listUser);
        } else {
            $query->where('parent_id', $req->id);
        }

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
      
        $limit = $req->limit ? (int) $req->limit : 10;
        $page = $req->page ? (int) $req->page : 0;

        $paginate = $query->paginate($limit, ['*'], 'page', $page);
        $results = [];
        foreach ($paginate->items() as $item) {
            $data = $item->publicProfile();
            $data['shop'] = $item->shop;
            $data['staff_of_user'] = null;
            if (!empty($item->staffOfUser)) {
                $parentUser = $item->staffOfUser;
                $data['staff_of_user'] = $parentUser->publicProfile();
                $data['staff_of_user']['shop'] = $parentUser->shop;
                $data['staff_of_user']['staff_of_user'] = null;
                if (in_array($parentUser->use_group, [User::TYPE_BranchUser, User::TYPE_StaffUser, User::TYPE_StaffStoreUser])) {

                    if (!empty($parentUser->staffOfUser)) {
                        $parentRoot = $parentUser->staffOfUser;
                        $data['staff_of_user']['staff_of_user'] = $parentRoot;
                        $data['staff_of_user']['staff_of_user']['shop'] = $parentRoot->shop;
                        $data['staff_of_user']['staff_of_user']['staff_of_user'] = null;
                        if ($parentRoot->use_group == User::TYPE_BranchUser && !empty($parentRoot->staffOfUser)) {
                            $data['staff_of_user']['staff_of_user']['staff_of_user'] = $parentRoot->staffOfUser;
                        }
                    }


//                    $data['staff_of_user']['staff_of_user'] = $parentUser->staffOfUser;
//                    if (!empty($parentUser->staffOfUser) && $parentUser->staffOfUser->use_group == User::TYPE_StaffUser) {
//                        if ($item->staffOfUser->use_group == User::TYPE_BranchUser) {
//                            $data['staff_of_user']['staff_of_user'] = $item->staffOfUser->staffOfUser;
//                        }
//                    }
                }
            }
            $results[] = $data;
        }
        $result = $paginate->toArray();
        $result['data'] = $results;

        return response([
            'msg' => Lang::get('response.success'),
            'data' => $result
        ]);
    }
    
    
    

    //Đại lý online toàn công ty

    /**
     * @SWG\Get(
     *     path="/api/v1/me/list-affiliate-under",
     *     operationId="list-affliate",
     *     description="Đại lý online toàn công ty",
     *     produces={"application/json"},
     *     tags={"Affiliate"},
     *     summary="Đại lý online toàn công ty",
     *  @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="page",
     *         required=false,
     *         type="integer",
     *     ),
     *    @SWG\Parameter(
     *         name="limit",
     *         in="query",
     *         description="limit",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="orderBy",
     *         in="query",
     *         description="orderBy",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="keywords",
     *         in="query",
     *         description="keywords",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="public list aff"
     *     )
     * )
     */
    public function listAllaffiliateUnder(Request $req) {
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

        $limit = $req->limit ? (int) $req->limit : 10;
        $page = $req->page ? (int) $req->page : 0;

        $paginate = $query->paginate($limit, ['*'], 'page', $page);

        $results = [];
        foreach ($paginate->items() as $item) {
            $data = [];
            $data = $item->publicProfile();
            $data['shop'] = $item->shop;
            $data['staff_of_user'] = null;
            if (!empty($item->staffOfUser)) {
                $parentUser = $item->staffOfUser;
                $data['staff_of_user'] = $parentUser->publicProfile();
                $data['staff_of_user']['shop'] = $parentUser->shop;
                $data['staff_of_user']['staff_of_user'] = null;
                if (in_array($parentUser->use_group, [User::TYPE_BranchUser, User::TYPE_StaffUser, User::TYPE_StaffStoreUser])) {
                    if (!empty($parentUser->staffOfUser)) {
                        $parentRoot = $parentUser->staffOfUser;
                        $data['staff_of_user']['staff_of_user'] = $parentRoot;
                        $data['staff_of_user']['staff_of_user']['shop'] = $parentRoot->shop;
                        $data['staff_of_user']['staff_of_user']['staff_of_user'] = null;
                        if ($parentRoot->use_group == User::TYPE_BranchUser && !empty($parentRoot->staffOfUser)) {
                            $data['staff_of_user']['staff_of_user']['staff_of_user'] = $parentRoot->staffOfUser;
                        }
                    }
                }
            }
            $results[] = $data;
        }
        $result = $paginate->toArray();
        $result['data'] = $results;

        return response([
            'msg' => Lang::get('response.success'),
            'data' => $result
        ]);
    }

    function getListChildTree($user, &$list_child = array()) {
        if (empty($user->getChild)) {
            return 0;
        }
        $child = $user->getChild->child;
        $list_child[] = $child;
        $this->getNextList($child, $list_child);
    }

    function getNextList($child, &$list_next = array()) {
        $userObject = UserTree::where(['user_id' => $child])->first();
        if ($userObject->next > 0) {
            $list_next[] = $userObject->next;
        }
        if ($userObject->next > 0) {
            $this->getNextList($userObject->next, $list_next);
        } else {
            return $list_next;
        }
    }

    function getChild($userid) {
        if ($userid > 0) {
            $userObject = UserTree::where(['user_id' => $userid])->first();
            if (empty($userObject)) {
                return 0;
            }
            return $userObject->child;
        } else {
            return 0;
        }
    }

    function getTreeInList($user, &$allChild) {

        $listChild = array();
        $this->getListChildTree($user, $listChild);
        foreach ($listChild as $child) {
            if ($child > 0) {
                $allChild[] = $child;
                $this->getTreeInList($child, $allChild);
            }
        }
    }
    
    function searchCategory($req) {
        $catsubArray = [];

        if (isset($req->category_4)) {
            $catsubArray = $req->category_4;
        } else if (isset($req->category_3)) {
            $cat = Category::where(['cat_id' => $req->category_3])->first();
            if (!empty($cat3)) {
                $catsubArray = $this->getChildCategory($cat);
                if (empty($catsubArray)) {
                    $catsubArray = [$cat->cat_id];
                }
            }
        } elseif (isset($req->category_2)) {

            $cat = Category::where(['cat_id' => $req->category_2])->first();
            if (!empty($cat)) {
                $catsubArray = $this->getChildCategory($cat);
                if (empty($catsubArray)) {
                    $catsubArray = [$cat->cat_id];
                }
            }
        } else if (isset($req->category_1)) {
            $cat = Category::where(['cat_id' => $req->category_1])->first();
            if (!empty($cat)) {
                $catsubArray = $this->getChildCategory($cat);
                if (empty($catsubArray)) {
                    $catsubArray = [$cat->cat_id];
                }
            }
        }
        return $catsubArray;
    }

    function getChildCategory($category, $data = null) {

        $childs = $category->child;


        if (count($childs) == 0) {

            return [];
        }

        $result = $childs->pluck('cat_id')->toArray();

        foreach ($childs as $child) {

            $childData = $this->getChildCategory($child);

            $result = array_merge($result, $childData);
        }
        return $result;
    }

}
