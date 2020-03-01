<?php $this->load->view('home/common/account/header'); ?>
<div class="clearfix"></div>
<div id="product_content" class="container-fluid">
    <div class="row">
        <?php $this->load->view('home/common/left'); ?>
        <link type="text/css" href="<?php echo base_url(); ?>templates/home/css/datepicker.css" rel="stylesheet"/>
        <script type="text/javascript" src="<?php echo base_url(); ?>templates/home/js/datepicker.js"></script>
        <script type="text/javascript" src="<?php echo base_url(); ?>templates/home/js/ajax.js"></script>
        <style type="text/css">
            .fa-spinner {
                font-size: 17px;
                display: none;
            }

            .table > thead > tr > th, .table > tbody > tr > th, .table > tfoot > tr > th, .table > thead > tr > td, .table > tbody > tr > td, .table > tfoot > tr > td {
                padding: 5px;
            }
        </style>
        <!--BEGIN: RIGHT-->
        <SCRIPT TYPE="text/javascript">
            function SearchRaoVat(baseUrl) {
                product_name = '';
                if (document.getElementById('keyword_account').value != '')
                    product_name = document.getElementById('keyword_account').value;
                window.location = baseUrl + 'account/product/search/name/keyword/' + product_name + '/';
            }
            <!--
            function submitenterQ(myfield,e,baseUrl)
            {
            var keycode;
            if (window.event) keycode = window.event.keyCode;
            else if (e) keycode = e.which;
            else return true;

            if (keycode == 13)
               {
               SearchRaoVat(baseUrl);
               return false;
               }
            else
               return true;
            };
            -->
        </SCRIPT>
        <div class="col-md-9 col-sm-8 col-xs-12">
            <h4 class="page-header text-uppercase" style="margin-top:10px">Thu nhập tạm tính</h4>
	    <?php
            if ($flash_message) {
                ?>
                <div class="message success">
                    <div class="alert alert-success">
                        <?php echo $flash_message; ?>
                        <button type="button" class="close" data-dismiss="alert">×</button>
                    </div>
                </div>
                <?php
            }
            ?>
	   
                <?php if (count($provisional) > 0 || count($total_shc) > 0) { ?>
                <form name="frmAccountPro" id="frmAccountPro" class="form-inline" method="post">
                        <div class="table-responsive">
                            <table class="table table-bordered" >
                                <caption>Doanh thu theo sản phẩm</caption>
                                <thead>
                                    <tr>
                                        <th>STT </th>
                                        <th>Mã </th>
                                        <th>Sản phẩm </th>
                                        <th>SL </th>
                                        <th width="100">Đơn giá </th>
                                        <th>Danh mục </th>
                                        <th width="200">Tổng tiền </th>
                                    </tr>
                                </thead>
                                <?php $idDiv = 1;
                                $total = 0; ?>
                                <?php
                                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                                $duoi = '.' . substr(base_url(), strlen($protocol), strlen(base_url()));
                                foreach ($provisional as $k => $productArray) {
                                    if ($productArray->pro_type == 2) {
                                        $pro_type = 'coupon';
                                    } else {
                                        if ($productArray->pro_type == 0) {
                                            $pro_type = 'product';
                                        }
                                    }
                                    $get_u = $this->user_model->fetch_join('use_id,parent_id, use_group, sho_link,domain', "LEFT", "tbtt_shop", "sho_user = use_id", 'use_id = "' . $productArray->shc_saler . '"');
                                    if ($get_u[0]->domain != '') {
                                        $shoplink = $protocol . $get_u[0]->domain . '/shop/' . $pro_type . '/detail/' . $productArray->shc_product . '/' . RemoveSign($productArray->pro_name);
                                    } else {
                                        $shoplink = $protocol . $get_u[0]->sho_link . $duoi . 'shop/' . $pro_type . '/detail/' . $productArray->shc_product . '/' . RemoveSign($productArray->pro_name);
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo $idDiv; ?></td>
                                        <td>
                                            <a href="<?php echo base_url() . 'account/order_detail/' . $productArray->shc_orderid;?>"
                                               target="_blank"
                                               class="text-info">
                                                <i># <?php echo $productArray->shc_orderid;?></i>
                                            </a>
                                        </td>
                                        <td>
                                            <a class="menu_1" href="<?php echo $shoplink; ?>" target="_blank">
                                                <?php echo sub($productArray->pro_name, 100); ?>
                                            </a>
                                        </td>
                                        <td class="text-center">
                                            <?php echo $productArray->shc_quantity; ?>
                                        </td>
                                        <?php
                                        if ($productArray->af_id > 0 && ($productArray->af_rate > 0 || $productArray->af_amt > 0)) {
                                            if ($productArray->af_rate > 0 || $productArray->af_amt > 0) {
                                                if ($productArray->af_rate > 0):
                                                    $hoahong = $productArray->af_rate;
                                                    $moneyShop = ($productArray->shc_total) * (1 - ($hoahong / 100));
                                                    if ($productArray->em_discount > 0):
                                                        $hh_giasi = ($productArray->shc_total + $productArray->em_discount) * ($hoahong / 100);
                                                        $moneyShop = $productArray->shc_total - $hh_giasi;
                                                    endif;
                                                else:
                                                    $hoahong = $productArray->af_amt;
                                                    $moneyShop = $productArray->shc_total - ($hoahong * $productArray->shc_quantity); endif;
                                            }
                                        } else {
                                            $moneyShop = $productArray->shc_total;
                                            $hoahong = 0;
                                        }
                                        ?>
                                        <td class="text-right">
                                            <?php
                                            //                                        if($productArray->em_discount>0){
                                            //                                        $dongia = ($productArray->pro_price-$productArray->em_discount)/$productArray->shc_quantity;
                                            //                                        $dongia = $moneyShop/$productArray->shc_quantity;
                                            $dongia = $productArray->pro_price_original;

                                            //                                        }; ?>
                                            <span class="product_price"><?php echo number_format($dongia, 0, ",", "."); ?> đ</span>
                                        </td>
                                        <td>
                                            <?php echo $productArray->cat_name; ?>
                                        </td><?php //} ?>
                                        <td class="text-right">
                                            <a href="<?php echo base_url() . 'account/order_detail/' . $productArray->shc_orderid; ?>" target="_blank">
                                                <span class="product_price"><?php echo ' ' . number_format($moneyShop, 0, ",", "."); ?> đ</span>
                                            </a>
                                            <?php if ($productArray->af_id > 0) {
                                                ?>
                                                <br/>
                                                <span class="small"> Hoa hồng CTV:
                                                    <?php
                                                    if ($productArray->af_rate > 0):
                                                        ?>
                                                        <span
                                                            class="text-success text-right"><?php echo number_format($hoahong, 0, ",", "."); ?>
                                                            %</span>
                                                    <?php else: ?>
                                                        <span
                                                            class="text-success text-right"><?php echo number_format($hoahong, 0, ",", "."); ?>
                                                            đ</span>
                                                        <?php
                                                    endif;
                                                    ?>
                                                </span>
                                                <?php
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <?php $idDiv++; ?>
                                <?php } ?>
                            </table>

                            <table class="table table-bordered" >
                                <caption>Doanh thu theo danh mục</caption>
                                <thead>
                                <tr class="v_height29">
                                    <th>STT</th>
                                    <th>Danh mục</th>
                                    <th>Doanh thu</th>
                                    <th>Hoa hồng(%)</th>
                                    <th>Hoa hồng cho Azibai</th>
                                    <th>Doanh thu (trừ hoa hồng cho Azibai)</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                $total_re = 0;
                                $total_com = 0;
                                $totalall = 0;
                                $stt = 1;
                                $totalDTCat = array();
                                $TienKhonghoahong = array();
                                foreach ($total_shc as $k => $item) {
                                    $tongDTCat = $Khonghoahong = 0;
    //                            echo $tongDTCat;
                                    foreach ($provisional as $k => $productArray) {
                                        if ($item->cat_id == $productArray->cat_id) {
                                            if ($productArray->af_id > 0 && ($productArray->af_rate > 0 || $productArray->af_amt > 0)) {
                                                if ($productArray->af_rate > 0 || $productArray->af_amt > 0) {
                                                    if ($productArray->af_rate > 0):
                                                        $hoahong = $productArray->af_rate;
                                                        $moneyShop = ($productArray->shc_total) * (1 - ($hoahong / 100));
                                                        if ($productArray->em_discount > 0):
                                                            $hh_giasi = ($productArray->shc_total + $productArray->em_discount) * ($hoahong / 100);
                                                            $moneyShop = $productArray->shc_total - $hh_giasi;
                                                        endif;
                                                    else:
                                                        $hoahong = $productArray->af_amt;
                                                        $moneyShop = $productArray->shc_total - ($hoahong * $productArray->shc_quantity); endif;
                                                }
                                            } else {
                                                $moneyShop = $productArray->shc_total;
                                            }
                                            $tongDTCat += $moneyShop;
                                            $Khonghoahong += $productArray->shc_total;
                                        }
                                    }
                                    $totalDTCat[] = $tongDTCat;
                                    $TienKhonghoahong[] = $Khonghoahong;
    //                            echo $tongDTCat;
                                }
                                foreach ($total_shc as $k => $item) {
                                    ?>
                                    <tr>
                                        <td><?php echo $stt; ?></td>
                                        <td><?php echo $item->cat_name; ?></td>
                                        <td class="text-right"><?php
                                            //                                        echo '<span class="product_price">' . number_format($totalDTCat[$k], 0, ",", ".") . ' đ </span>';
                                            echo '<span class="product_price">' . number_format($TienKhonghoahong[$k], 0, ",", ".") . ' đ </span>';
                                            ?></td>
                                        <td><?php echo $categories[$k]->b2c_fee; ?> %</td>
                                        <td class="text-right"><?php
                                            //                                        echo '<span class="product_price">' . number_format(($totalDTCat[$k] * $categories[$k]->b2c_fee) / 100, 0, ",", ".") . ' đ </span>';
                                            echo '<span class="product_price">' . number_format(($TienKhonghoahong[$k] * $categories[$k]->b2c_fee) / 100, 0, ",", ".") . ' đ </span>';
                                        ?></td>
                                        <td class="text-right">
                                            <?php
    //                                        echo '<span class="product_price">' . number_format($totalDTCat[$k] - (($totalDTCat[$k] * $categories[$k]->b2c_fee) / 100), 0, ",", ".") . ' đ </span>';
                                            echo '<span class="product_price">' . number_format($TienKhonghoahong[$k] - (($TienKhonghoahong[$k] * $categories[$k]->b2c_fee) / 100), 0, ",", ".") . ' đ </span>';
    //                                        ?>
                                        </td>
                                    </tr>
                                    <?php
                                    $totalall += $totalDTCat[$k];
    //                                $total_com += ($totalDTCat[$k] * $categories[$k]->b2c_fee) / 100;
                                    $total_com += ($TienKhonghoahong[$k] * $categories[$k]->b2c_fee) / 100;
    //                                $total_re += $totalDTCat[$k] - (($totalDTCat[$k] * $categories[$k]->b2c_fee) / 100);
                                    $total_re += $totalDTCat[$k] - (($TienKhonghoahong[$k] * $categories[$k]->b2c_fee) / 100);
                                    $stt++;
                                } ?>
                                </tbody>
                            </table>
                            <div style="float:right; text-align:right;">
                                <div class="income-line">Tổng tiền bán hàng
                                    : <?php echo '<span class="product_price">' . number_format($totalall, 0, ",", ".") . ' đ </span>'; ?></div>
                                <div class="income-line">Hoa hồng trả cho
                                    Azibai: <?php echo '<span class="product_price">' . number_format($total_com, 0, ",", ".") . ' đ </span>'; ?></div>
                                <div class="income-line ">Tổng tiền nhận
                                    được: <?php echo '<span class="product_price">' . number_format($total_re, 0, ",", ".") . ' đ </span>'; ?></div>
                            </div>
                        </div>
                        <?php if (isset($linkPage)) { ?>
                            <?php echo $linkPage; ?>
                        <?php } ?>
                </form>
                <?php } else { ?>
                <div class="none_record"><p class="text-center">Không có dữ liệu</p></div>
                <?php } ?>
                <br/>
        </div>
    </div>
</div>
<!--END RIGHT-->
<?php $this->load->view('home/common/footer'); ?>
<script language="javascript">
    function Order_product(pro_id, order) {
        $("#iconload_" + pro_id).show();
        $.ajax({
            type: "post",
            url: "<?php echo base_url(); ?>" + 'home/account/order_pro',
            cache: false,
            data: {pro_id: pro_id, order: order},
            dataType: 'text',
            success: function (data) {
                if (data == '1') {
                    $("#iconload_" + pro_id).hide();
                } else {
                    errorAlert('Có lỗi xảy ra!');
                }
            }
        });
        return false;
    }
</script>
