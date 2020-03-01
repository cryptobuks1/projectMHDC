<?php $this->load->view('home/common/header'); ?>
<div class="container">
  <div class="row">
<?php $this->load->view('home/common/left'); ?>
<script>
jQuery(document).ready(function(){
	jQuery(".payment_baokim_table .col").hover(function(){
		jQuery(this).css("border-color","#279fff");
	}, function(){
		jQuery(this).css("border-color","#A3D6FF");
	});
	jQuery('a#popup_box').each(function(index) {
		jQuery(this).fancybox({
			'width'				: '50%',
			'height'			: '50%',
			'autoScale'			: false,
			'transitionIn'		: 'none',
			'transitionOut'		: 'none',
			'type'				: 'iframe'
		});
	});
	
	jQuery(".radio-banking").click(function(){
	   jQuery('#bank_hidden').css('display','none');
	});
	jQuery("#radio_6").click(function(){
	   jQuery('#bank_hidden').css('display','block');
	}); 
	jQuery('.radio-banking').attr('checked', false);
	jQuery('.radio_6').attr('checked', false);
});


function validateForm(){
		if(jQuery('#radio_6').attr('checked'))
		{
			if(document.getElementById('FlagBank').value=="")
			{
				alert("Bạn chưa chọn Ngân hàng ");
				return false;
			}
		}
		if(jQuery('#radio_1').attr('checked') || jQuery('#radio_4').attr('checked') || jQuery('#radio_5').attr('checked') || jQuery('#radio_6').attr('checked')) {
			document.forms["soha-step2"].submit();
			return true;			
		}else{
			alert('Vui lòng nhập chọn phương thức nạp tiền!');
			return false;
		}			
	}
function checkTaiKhoanNganHangPuplic(value){
		jQuery('#banks').css('display','none');	
		jQuery('.'+value).css('display','block');
		jQuery('.bank_info_eb').css('display','block');
		jQuery('#FlagBank').val(value);
		
	}
function checkPuplicChonNganHangKhac()
{
	jQuery('#banks').css('display','block');
	jQuery('.bank_info_eb').css('display','none');
	jQuery('.'+document.getElementById('FlagBank').value).css('display','none');	
	
}
</script>
<!--BEGIN: RIGHT-->
<div class="col-lg-9">
<div class="add_money_content">
    <!-- sunguyen add-->
    <form method="post" name="soha-step2" action="<?php echo base_url();?>account/naptien/buoc3sohapay">
    <input type="hidden" value="" id="FlagBank" name="FlagBank" />
      <div class="classic-popup-main_eb">
        <div class="classic-popup-title_eb">
          <div class="fl_eb">Chọn hình thức nạp tiền</div>
          <div class="c_eb"></div>
        </div>
        <div class="classic-popup-content_eb">
          <div class="content_eb" style="padding: 0pt 0pt 10px;">
            <div    class="paymentChooseType_eb sendOnline_eb" id="paytype_1">
              <div class="arrowRight_eb">
                <div class="radioBox_eb">
                  <input type="radio" name="pay_method" id="radio_1" class="radio-banking" value="card">
                </div>
                <div class="paymentContent_eb">
                  <div class="paymentTitle_eb">Nạp từ thẻ Visa, Master Card, thẻ ATM có Internet Banking</div>
                  <div class="paymentText_eb">Số tiền nạp được chuyển tương ứng vào tài khoản của bạn</div>
                  <div class="creditPay_eb bankPay_eb"><span id="visa"></span><span id="master"></span><a class="fl_eb" href="javascript:void(0)"><span id="vcb"></span></a><a class="fl_eb" href="javascript:void(0)"><span id="donga"></span></a><a class="fl_eb" href="javascript:void(0)"><span id="techcom"></span></a><a class="fl_eb" href="javascript:void(0)"><span id="vietin"></span></a>
                    <div class="c_eb"></div>
                  </div>
                </div>
                <div class="c_eb"></div>
              </div>
            </div>
            <!--<div    class="paymentChooseType_eb sendGold_eb" id="paytype_2">
            <div class="arrowRight_eb">
              <div class="radioBox_eb">
                <input type="radio" name="pay_method" id="radio_2" value="mcard">
              </div>
              <div class="paymentContent_eb" style="">
                <div class="paymentTitle_eb">Nạp tiền bằng thẻ cào điện thoại <font color="red">(Khấu trừ <b style="font-size: 16px;">13%</b>)</font></div>
                <div class="paymentText">Hỗ trợ thẻ của mạng Vinaphone, Viettel và Mobifone. Áp dụng cho mọi loại mệnh giá thẻ.</div>
                <div class="bankPay_eb"><a class="fl_eb" href="javascript:void(0)"><span id="mobiphone"></span></a><a class="fl_eb"><span id="vinaphone"></span></a><a class="fl_eb"><span style="position: relative; top: 2px;" id="viettel"></span></a>
                  <div class="c_eb"></div>
                </div>
                <div class="c_eb"></div>
              </div>
              <div class="c_eb"></div>
            </div>
          </div>--> 
            <!--<div    class="paymentChooseType_eb sendCop_eb" id="paytype_3">
            <div class="arrowRight_eb">
              <div class="radioBox_eb">
                <input type="radio" name="pay_method" id="radio_3" value="cop">
              </div>
              <div class="paymentContent_eb">
                <div class="paymentTitle_eb">Nạp tiền bằng SMS <font color="red">(Khấu trừ <b style="font-size: 16px;">54%</b>)</font></div>
                <ul class="paymentText_eb" style="padding-left: 15px;">
                  <li>Chấp nhận các số điện thoại di động của các mạng: Vina, Mobi, Viettel, Beeline, Vietnammobile và Sfone.</li>
                  <li>Mỗi SMS bị trừ <font color="red"><b>15.000</b></font> đồng bạn nhận được <font color="red"><b>7.000</b></font> Gold.</li>
                  <li>Mỗi SMS sẽ nhận được tin nhắn trả về.</li>
                </ul>
              </div>
              <div class="c_eb"></div>
            </div>
          </div>-->
            <div    class="paymentChooseType_eb sendCod_eb" id="paytype_4">
              <div class="arrowRight_eb pBottom10_eb">
                <div class="radioBox_eb">
                  <input type="radio" name="pay_method" id="radio_4" class="radio-banking" value="codtannoi">
                </div>
                <div class="paymentContent_eb">
                  <div class="paymentTitle_eb">Nạp tiền thu tiền tận nơi</div>
                  <div class="paymentText_eb">Trong thời gian từ 2-7 ngày làm việc, nhân viên azibai.com sẽ đến tận nơi thu tiền nạp.</div>
                </div>
                <div class="c_eb"></div>
                <ul class="paymentText_eb paymentGuide_eb hidden_eb" id="info_2">
                  <li>Quý khách có thể bị mất phí khi chọn hình thức này.</li>
                  <li>Nhân viên giao phiếu của azibai.com sẽ liên hệ với Quý khách trước khi giao</li>
                  <li>Quý khách chỉ được sử dụng dịch vụ sau khi đã thanh toán tiền cho nhân viên thu tiền.</li>
                </ul>
              </div>
            </div>
            <div    class="paymentChooseType_eb sendEb_eb" id="paytype_5">
              <div class="arrowRight_eb pBottom10_eb">
                <div class="radioBox_eb">
                  <input type="radio" name="pay_method" id="radio_5" class="radio-banking" value="codtruso">
                </div>
                <div class="paymentContent_eb">
                  <div class="paymentTitle_eb">Nạp tiền tại trụ sở azibai.com</div>
                  <div class="paymentText_eb">Khách hàng có thể đến trực tiếp tại trụ sở azibai.com để nộp tiền mặt. Tiền sẽ được nạp ngay sau khi quý khách hoàn thành thanh toán.</div>
                </div>
                <div class="c_eb"></div>
                <ul class="paymentText_eb paymentGuide_eb hidden_eb" id="info_3">
                  <li>Hình thức này quý khách phải đến trụ sở azibai.com để nộp tiền.</li>
                  <li>Tiền sẽ được nạp ngay sau khi quý khách hoàn thành thanh toán</li>
                </ul>
              </div>
            </div>
            <div    class="paymentChooseType_eb sendAtm_eb" id="paytype_6">
              <div class="arrowRight_eb">
                <div class="radioBox_eb">
                  <input type="radio" name="pay_method" class="radio_6" id="radio_6" value="atm">
                </div>
                <div class="paymentContent_eb">
                  <div class="paymentTitle_eb">Chuyển khoản <span>(Quý khách tự thanh toán chi phí chuyển khoản)</span></div>
                  <div class="paymentText_eb">- Số tiền nạp được chuyển tương ứng thành Gold theo tỉ lệ</div>
                  <div class="paymentText_eb">- Quý khách chuyển tiền vào tài khoản của azibai.com. Quý khách phải chờ từ <span style="color: rgb(91, 91, 91);"><b>4-24</b></span> giờ để azibai.com xác nhận giao dịch.</div>
                </div>
                <div class="c_eb"></div>
                <div id="bank_hidden" class="bank_eb hidden_eb" style="display:none;">
                  
                  <div id="banks">
                  <div class="bank_title_eb">Chọn tài khoản ngân hàng của azibai.com mà Quý khách sẽ chuyển tiền vào:</div>
                    <div id="vietcombank" class="bank-hidden banking" onclick="checkTaiKhoanNganHangPuplic('vietcombank')"><img  border="0" src="<?php echo base_url(); ?>images/banks/vietcombank.jpg" /></div>
                    <div id="donga" class="bank-hidden banking" onclick="checkTaiKhoanNganHangPuplic('donga')"><img  border="0" src="<?php echo base_url(); ?>images/banks/donga.jpg" /></div>
                    <div id="techcombank" class="bank-hidden banking" onclick="checkTaiKhoanNganHangPuplic('techcombank')" ><img  border="0" src="<?php echo base_url(); ?>images/banks/techcombank.jpg" /></div>
                    <div id="arigbank" class="bank-hidden banking" onclick="checkTaiKhoanNganHangPuplic('arigbank')" ><img  border="0" src="<?php echo base_url(); ?>images/banks/arigbank.jpg" /></div>
                    <div id="bidv" class="bank-hidden banking" onclick="checkTaiKhoanNganHangPuplic('bidv')" ><img  border="0" src="<?php echo base_url(); ?>images/banks/bdiv.jpg" /></div>
                    <div id="vietinbank" class="bank-hidden banking" onclick="checkTaiKhoanNganHangPuplic('vietinbank')" ><img  border="0" src="<?php echo base_url(); ?>images/banks/vietinback.jpg" /></div>
                    <div id="mbank" class="bank-hidden banking" onclick="checkTaiKhoanNganHangPuplic('mbank')"  ><img  border="0" src="<?php echo base_url(); ?>images/banks/mbank.jpg" /></div>
                    <div id="acb" class="bank-hidden banking" onclick="checkTaiKhoanNganHangPuplic('acb')" ><img  border="0" src="<?php echo base_url(); ?>images/banks/acb.jpg" /></div>
                    <div id="vib" class="bank-hidden banking" onclick="checkTaiKhoanNganHangPuplic('vib')" ><img  border="0" src="<?php echo base_url(); ?>images/banks/vib.jpg" /></div>
                  </div>
                  <div style="padding-left:20px;">
                  <div class="vietcombank"  style="display:none"  > <?php $vietcombank=Counter_model::getArticle(35); echo html_entity_decode($vietcombank->not_detail);?></div>
                  <div class="donga" style="display:none"  > <?php $donga=Counter_model::getArticle(36); echo html_entity_decode($donga->not_detail);?></div>
                  <div class="techcombank" style="display:none"   > <?php $techcombank=Counter_model::getArticle(37); echo html_entity_decode($techcombank->not_detail);?></div>
                  <div class="arigbank" style="display:none"   > <?php $arigbank=Counter_model::getArticle(38); echo html_entity_decode($arigbank->not_detail);?></div>
                  <div class="bidv" style="display:none"   > <?php $bidv=Counter_model::getArticle(39); echo html_entity_decode($bidv->not_detail);?></div>
                  <div class="vietinbank" style="display:none"   > <?php $vietinbank=Counter_model::getArticle(40); echo html_entity_decode($vietinbank->not_detail);?></div>
                  <div class="mbank" style="display:none"   > <?php $mbank=Counter_model::getArticle(41); echo html_entity_decode($mbank->not_detail);?></div>
                  <div class="acb" style="display:none"   > <?php $acb=Counter_model::getArticle(42); echo html_entity_decode($acb->not_detail);?></div>
                  <div class="vib" style="display:none"   > <?php $vib=Counter_model::getArticle(43); echo html_entity_decode($vib->not_detail);?></div>
                  </div>
                  <div style="display: none; padding-left:300px;" class="bank_info_eb">
                    <div class="bank_detail_eb"></div>
                    <a  class="bank_go_eb" onclick="checkPuplicChonNganHangKhac();">Chọn ngân hàng khác</a>
                    <div class="c_eb"></div>
                  </div>
                  <div class="bank_list_eb"><a  class="fl_eb"><span id="vcb"></span></a><a  class="fl_eb"><span id="donga"></span></a><a  class="fl_eb"><span id="techcom"></span></a><a  class="fl_eb"><span id="agri"></span></a><a  class="fl_eb"><span id="bidv"></span></a><a  class="fl_eb"><span id="vietin"></span></a><a  class="fl_eb"><span id="mb"></span></a><a  class="fl_eb"><span id="acb"></span></a><a  class="fl_eb"><span id="vib"></span></a>
                    <div class="c_eb"></div>
                  </div>
                  <ul class="paymentText_eb paymentGuide_eb" id="info_4">
                    <li>Khi chuyển khoản qua Internet Banking hoặc Quầy giao dịch, Quý khách vui lòng tự chịu phí chuyển khoản.</li>
                    <li>Sau khi chuyển khoản, Quý khách vui lòng thông báo cho azibai.com được biết</li>
                    <li>Dịch vụ được kích hoạt chỉ khi tiền đã về tài khoản của azibai.com</li>
                  </ul>
                </div>
              </div>
            </div>
            <div class="c_eb"></div>
            <div align="center_eb" style="padding-bottom: 20px;" class="mRight20_eb">
              <div class="mTop10_eb"><a  style="float: right;" class="blueButton_eb" onclick="return validateForm();"><span><span>TIẾP TỤC<b>»</b></span></span></a><a href="javascript:history.go(-1)" style="float: right;" class="blueButton_eb mRight20_eb"><span><span><b>«</b> QUAY LẠI</span></span></a></div>
              <div class="c_eb"></div>
            </div>
          </div>
        </div>
      </div>
    </form>
    
    <!-- sunguyen add --> 
    <!--    <table cellspacing="10" cellpadding="0" align="center" class="payment_baokim_table k_payment_baokim_table">
      <tbody>
        <tr>
          <td id="payment_method_6" class="col"><div class="payment_baokim_content">
              <div class="name">Sử dụng số dư tài khoản Bảo Kim</div>
              <div class="picture"><a rel="nofollow" href="<?php echo base_url(); ?>account/naptien_baokim/"><img src="<?php echo base_url(); ?>templates/home/images/logo_baokim.gif"></a></div>
            </div></td>
          <td id="payment_method_9" class="col"><div class="payment_baokim_content">
              <div class="name">Nhắn tin SMS</div>
              <div style="margin-top: 30px" class="picture"><a href="<?php echo base_url(); ?>naptien/nhantinsms.php" id="popup_box"><img src="<?php echo base_url(); ?>templates/home/images/baokim_charges_sms.gif"></a></div>
            </div></td>
          <td id="payment_method_8" class="col"><div class="payment_baokim_content">
              <div class="name">Hỗ trợ nạp tiền</div>
              <div style="margin-top: 20px;" class="picture"><a href="<?php echo base_url(); ?>naptien/hotronaptien.php" id="popup_box"><img src="<?php echo base_url(); ?>templates/home/images/baokim_charges_support.gif"></a></div>
            </div></td>
        </tr>
      </tbody>
    </table>--> 
    <script type="text/javascript">
jQuery(".prev_button").click(function(){
iData = jQuery(this).attr("iData");
current_page= parseInt(jQuery("#current_panel_" + iData).html());
current_page= current_page - 1;
if(current_page <= 0) return;

jQuery("#current_panel_" + iData).html(current_page);
jQuery("#payment_method_" + iData + " .block").hide();
jQuery("#payment_method_" + iData + " .block:eq(" + (current_page - 1) + ")").fadeIn();
});

jQuery(".next_button").click(function(){
iData = $(this).attr("iData");
current_page= parseInt(jQuery("#current_panel_" + iData).html());
current_page= current_page + 1;
if(current_page < jQuery("#payment_method_" + iData + " .block").length) return;

jQuery("#current_panel_" + iData).html(current_page);
jQuery("#payment_method_" + iData + " .block").hide();
jQuery("#payment_method_" + iData + " .block:eq(" + (current_page - 1) + ")").fadeIn();
});
</script> 
  </div></td>
</tr>
</table>
  </div>
    </div>
  </div>
<!--END RIGHT-->
<?php $this->load->view('home/common/footer'); ?>
