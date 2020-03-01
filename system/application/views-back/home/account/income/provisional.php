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
        <?php
            if($this->session->userdata('sessionGroup') == AffiliateUser) {
                $tntt = 'Thu nhập bán hàng tạm tính';
                $txt = 'Bạn chưa giới thiệu bán sản phẩm nào';
            }else{
                $tntt = 'Thu nhập tạm tính';
                $txt = 'Không có dữ liệu';
            }
        ?>
       <div class="col-md-9 col-sm-8 col-xs-12">
           <h4 class="page-header text-uppercase" style="margin-top:10px">
                <?php echo $tntt?>
            </h4>
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
            <form name="frmAccountPro" id="frmAccountPro" class="form-inline" method="post">
		
		<div class="panel panel-default">
		    <div class="panel-body">
    			<div class="form-group">
    			    <select name="month_af" id="month_af" class="form-control">
				    <?php
				    for ($i = 1; $i <= 12; $i++) {
					$x = str_pad($i, 2, 0, STR_PAD_LEFT);
					?>
					<option <?php
					if (isset($month_af) && $i == $month_af) {
					    echo 'selected = "selected"';
					} elseif ($month_af == '' && $i == date('n')) {
					    echo 'selected = "selected"';
					}
					?> value="<?php echo $i; ?>"> Tháng <?php echo $x; ?></option>
					<?php } ?>
    			    </select>
			</div>
			<div class="form-group">    
    			    <select name="year_af" id="year_af" class="form-control">
				<?php foreach (range(2015, (int) date("Y")) as $year) { ?>
				    <option <?php
				    if (isset($year_af) && $year_af == $year) {
					echo 'selected = "selected"';
				    } elseif (date("Y") == $year && (int) $year_af <= 0) {
					echo 'selected = "selected"';
				    }
				    ?> value='<?php echo $year; ?>'><?php echo $year; ?></option>
				<?php } ?>
    			    </select>
			</div>
			<button type="submit" class="btn btn-azibai">Tìm kiếm</button>
    		    </div>
		</div>
		
	    <?php if (count($provisional) > 0) { ?>
                <div class="table-responsive">
                    <table class="table table-bordered" tyle="margin-bottom: 0; ">
                        
                            <thead>
                            
                            <tr>
                                <th width="40">STT</th>
                                <th width="100" class="text-center">
                                    Hình ảnh
                                </th>
                                <th class="aligncenter">
                                    Tên sản phẩm
                                </th>
                                <th width="50">
                                    SL
                                </th>
                                <th width="150" class="aligncenter">
                                    Đơn giá
                                </th>
                                <th width="150">
                                    Ngày tháng
                                </th>
                                <th width="150" >
                                    Trạng thái
                                </th>

                                <th width="150">
                                    Thu nhập
                                </th>
                            </tr>
                            </thead>
                            <?php
                            $idDiv = 1;
                            $total = 0;

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

                                $get_domain = $this->user_model->fetch_join('use_id,parent_id, use_group, sho_link,domain', "LEFT", "tbtt_shop", "sho_user = use_id", 'sho_user = "' . $productArray->shc_saler . '"');
                                $shop = $protocol . $get_domain[0]->sho_link . $duoi.'shop/';
                                if ($get_domain[0]->domain != '') {
                                    $shop = $protocol . $get_domain[0]->domain . '/shop/';
                                }

                                ?>
                                <tr>
                                    <td height="45" class="aligncenter hidden-xs hidden-sm"><?php echo $idDiv; ?></td>
                                    <td class="img_prev aligncenter">
                                        <?php
                                        if($productArray->shc_dp_pro_id > 0){
                                            $filename = 'media/images/product/' . $productArray->pro_dir . '/thumbnail_2_' . $productArray->dp_images;
                                            $imglager = 'media/images/product/' . $productArray->pro_dir . '/' . show_thumbnail($productArray->pro_dir, $productArray->dp_images);
                                        }else{
                                            // $filename = 'media/images/product/' . $productArray->pro_dir . '/thumbnail_1_' . $productArray->pro_image;
                                            $filename = 'media/images/product/' . $productArray->pro_dir . '/' . show_thumbnail($productArray->pro_dir, $productArray->pro_image);
                                            $imglager = 'media/images/product/' . $productArray->pro_dir . '/' . show_thumbnail($productArray->pro_dir, $productArray->pro_image, 3);
                                        }

                                        if(@getimagesize(DOMAIN_CLOUDSERVER . $filename)){ ?>
                                            <a rel="tooltip" data-toggle="tooltip" data-html="true" data-placement="auto right" data-original-title="<img src='<?php echo DOMAIN_CLOUDSERVER . $imglager; ?>' />">
                                                <img width="80" src="<?php echo DOMAIN_CLOUDSERVER . $filename; ?>"/>
                                            </a>
                                        <?php } else { ?>
                                            <img width="80" src="<?php echo base_url(); ?>media/images/no_photo_icon.png"/>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <a class="menu_1"
                                           href="<?php echo $shop . $pro_type . '/detail/' . $productArray->shc_product . '/' . RemoveSign($productArray->pro_name) ?>"
                                           target="_blank">
                                            <?php echo sub($productArray->pro_name, 100); ?>
                                        </a>
                                        <p style="font-size: 13px; font-weight: 600" class="text-info"><i>Mã đơn hàng: #
                                                <a href="<?php echo base_url() ?>account/orderDetailTKSP/<?php echo $productArray->shc_orderid; ?>"
                                                   target="_blank" style="color: #31708f">
                                                    <?php echo $productArray->shc_orderid; ?>
                                                </a></i></p>
                                    </td>
                                    <td width="5%" class="" style="text-align:right;">
                                        <?php echo $productArray->shc_quantity; ?>
                                    </td>
                                    <td class="aligncenter hidden-xs hidden-sm">
                                        <span
                                            class="product_price"><?php echo number_format($productArray->pro_price, 0, ",", "."); ?>
                                            đ</span>
                                        <p style="font-size: 12px;  font-weight: 600" class="text-success"><i>
                                                <?php
                                                if ($productArray->af_rate > 0) {
                                                    echo 'Hoa hồng: ' . $productArray->af_rate . ' %';
                                                } else {
                                                    echo 'Hoa hồng: ' . number_format($productArray->af_amt, 0, ",", ".") . ' Đ';
                                                }
                                                ?>
                                            </i></p>
                                    </td>
                                    <td style="text-align:center;">
                                        <?php echo date('d/m/Y', $productArray->shc_change_status_date); ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <?php
                                        if ($productArray->shc_status == '98') {
                                            echo 'Đã hoàn thành';
                                        } else {
                                            echo 'Chưa hoàn thành';
                                        }

                                        ?>
                                    </td>

                                    <td class="hidden-xs hidden-sm" style="text-align:center;">
                                        <a href="<?php echo base_url() ?>account/orderDetailTKSP/<?php echo $productArray->shc_orderid; ?>"
                                           target="_blank" style="color: #31708f">
                                            <?php
                                            if ($productArray->af_rate > 0) {
                                                $total += ($productArray->af_rate * ($productArray->shc_total+$productArray->em_discount)) / 100;
                                                echo ' <span class="product_price">' . number_format(($productArray->af_rate * ($productArray->shc_total+$productArray->em_discount)) / 100, 0, ",", ".") . ' đ </span>';
                                            } else {
                                                echo ' <span class="product_price">' . number_format($productArray->af_amt * $productArray->shc_quantity, 0, ",", ".") . ' đ </span>';
                                                $total += $productArray->af_amt * $productArray->shc_quantity;
                                            }
                                            ?>
                                        </a>
                                    </td>
                                </tr>
                                <?php $idDiv++; ?>
                            <?php } ?>
                            <tr>
                                <td class="text-right" colspan="7"><b>Tổng thu nhập tạm tính: </b></td>
                                <td class="text-left"><span
                                        class="product_price"><?php echo number_format($total, 0, ",", ".") . ' đ'; ?></span>
                                </td>
                            </tr>
                        
                    </table>
                </div>
                <?php if (isset($linkPage)) { ?>
                    <?php echo $linkPage; ?>
                <?php } ?>
	   
	    <?php } else { ?>
		<div class="none_record">
		   <p class="text-center"><?php echo $txt; ?></p>
		</div>
            <?php } ?>
            </form>
           <br>
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
