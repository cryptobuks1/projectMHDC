<?php 
if($type == 1){ // collection content item
    $srcImgCreate = DOMAIN_CLOUDSERVER . 'media/images/content/' . $showCreate->dir . '/thumbnail_3_' . $showCreate->image;
}
else if($type == 2){ // collection product item
    $srcImgCreate = '';
}
else if($type == 3){ // collection link item
    $srcImgCreate = $showCreate->image;
}
?>
<div class="btn-back js-back"><a href="#"><img src="/templates/home/styles/images/svg/close_black.svg"></a></div>
    <form action="" method="POST" id="frmCreateCollectionContent">    
    <ul class="bosuutap-popup-danhsach-hientai">
        <div><input type="hidden" name="collection[]" value="off" /></div>
        <?php foreach ($data as $key => $value) { ?>
        <li>
            <label class="checkbox-style">
            <input type="checkbox" name="collection[]" value="<?php echo $value->id ?>" <?php if($value->checked == true) echo "checked"?>><span></span>                  
            <div class="photo">
                <img src="<?php echo $value->avatar; ?>" alt="">
            </div>
            <div class="name"><?php echo $value->name ?></div>
            </label>
        </li>
        <?php } ?>
    </ul>
    <div class="nut-xacnhan buttons-group" id="update">
        <!-- <button class="btn-bg-white">Hủy</button> -->
        <button type="submit" class="btn-bg-gray">Cập nhật</button>
    </div>
    </form>

    <div class="bosuutap-popup-taomoi">
        <form action="" method="POST" id="frmCreateCollection">
        <div class="title" >
            <label class="checkbox-style">
            <input type="checkbox" name="category" id="taomoi" value="aaa"><span>Tạo bộ sưu tập mới</span>      
            </label>
        </div>
        <div class="content" style="display: none;">
            <div class="nhap-ten">
                <div class="photo">
                    <img src="<?php echo $srcImgCreate; ?>" alt="">
                </div>
                <div class="input"><input type="text" name="name_col" placeholder="Nhập tên Bộ sưu tập"></div>
            </div>
            <div class="input"><input type="checkbox" name="name_notpublic" value="check" checked><label>Bí mật</label></div>
            <div class="nut-xacnhan buttons-group" id="create">
            <!-- <button class="btn-bg-white">Hủy</button> -->
            <button type="submit" class="btn-bg-gray">Tạo Bộ Sưu Tập</button>
            </div>
        </div>
        </form>
    </div>
    
    
</div>

<script>
$('.drawer-overlay, .js-back').on('click', function() {
    $('.btn-popup-tag').removeClass('opened');
    $('.bandangnghigi').removeClass('opened');
    $('.btn-show-comment-customer').removeClass('opened');
    $('.model-content').removeClass('is-open');
    $('.wrapper').removeClass('drawer-open');
    return false;
  });

$("#taomoi").change( function() {
    var isCheck = $(this).is(":checked");
    if (isCheck) {
    $('.bosuutap-popup-taomoi .content').slideDown();
    $('.bosuutap-popup-danhsach-hientai').slideUp();
    $('#update').hide();
    } else {
    $('.bosuutap-popup-taomoi .content').slideUp();
    $('.bosuutap-popup-danhsach-hientai').slideDown();
    $('#update').show();
    }
});

$("#frmCreateCollection").on('submit',(function(e) 
	{
		e.preventDefault();
        
        var typeReturn = 1; // 0: xài ở trang chủ, 1: xài ở chi tiết bộ sưu tập
        var data = $(this).serialize() 
                    + '&' + 
                    $.param({ 
                        'avatar': '<?php echo $showCreate->image;?>', 
                        'dir': '<?php echo $showCreate->dir;?>',
                        'typeReturn': typeReturn,
                        'typeCollection': <?php echo $type;?>
                        });
		$.ajax({
            url: "<?php echo $shop_url; ?>collection/ajax_createCollection",
			type: "POST",
			data:  data,
			success: function(response)
		    {
                $('input[name=name_col').val('');
                $('.bosuutap-popup-danhsach-hientai').append(response);
                
                $('.bosuutap-popup-taomoi .content').slideUp();
                $('.bosuutap-popup-danhsach-hientai').slideDown();
                $('#taomoi').attr('checked', false);
                $('#update').show();
            }
		});
    }));

$("#frmCreateCollectionContent").on('submit',(function(e) 
	{
        e.preventDefault();

        var data = $(this).serialize();
		$.ajax({
            url: "<?php echo $shop_url; ?>collection/ajax_createCollectionContent/<?php echo $showCreate->id;?>/<?php echo $type;?>",
			type: "POST",
			data:  data,
			success: function(response)
		    {
                if(response == 0 || response == 1) {
                    $('.btn-show-comment-customer').toggleClass('opened');
                    var modal = $('.btn-show-comment-customer').attr("data-id")
                    if ($('.btn-show-comment-customer').hasClass('opened')) {
                        $('#'+modal).addClass('is-open');
                        $('.wrapper').removeClass('drawer-open');
                        $('.model-content').removeClass('is-open');
                    } else {
                        $('#'+modal).removeClass('is-open');
                        $('.wrapper').removeClass('drawer-open');
                    }
                    return false;
                } else {
                    alert("errors.");
                }
            }
		});
    }));
</script>