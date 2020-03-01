<?php if (isset($lastnews) && !empty($lastnews)) {
    $linkShop = $this->uri->segment(1);
    ?>
    <h3 class="text-center"><span><i class="fa fa-newspaper-o fa-fw"></i> Tin tức mới nhất</span></h3>

    <div class="row text-center">
        <?php foreach ($lastnews as $k => $item) {
            if ($k < 8) { ?>
                <div class="col-sm-6 col-md-3 col-lg-3 <?php echo $k >= 3 ? 'hidden-xs' : ''; ?>">
                    <div style="<?php if ($k % 4 == 0) echo 'clear:both'; ?>">
                        <div class="thumbnail">
                            <div class="thumbox">
                                <a href="/detail/<?php echo $item->not_id; ?>/<?php echo RemoveSign($item->not_title); ?>">
                                
                                <?php $filename = 'media/images/tintuc/'.$item->not_dir_image.'/'.show_thumbnail($item->not_dir_image,$item->not_image,3,'tintuc');
                                if(file_exists($filename) && $item->not_image !=''){
                                    ?>
                                    <img src="<?php echo $filename; ?>"  class="img-responsive"/>
                                <?php } else{?>
                                    <img width="300" height="200"  class="img-responsive" src="<?php echo $URLRoot; ?>media/images/no_photo_icon.png" />
                                <?php }?>                                    
                                </a>
                            </div>
                        </div>
                        <div style="padding:0 15px;">
                            <h4>
                                <a href="/detail/<?php echo $item->not_id; ?>/<?php echo RemoveSign($item->not_title); ?>"><?php echo $item->not_title; ?></a>
                            </h4>
                            <p>
                                <i class="fa fa-calendar fa-fw"></i><?php echo date('d/m/Y', $item->not_begindate); ?>
                                <a href="/detail/<?php echo $item->not_id; ?>/<?php echo RemoveSign($item->not_title); ?>">
                                    <i class="fa fa-file-o fa-fw"></i> Xem thêm
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            <?php }
        } ?>
    </div>
<?php } else { ?>
    <h3 class="text-center"><span><i class="fa fa-newspaper-o fa-fw"></i> Tin tức mới nhất</span></h3>
    <div class="row text-center">
        <div class="col-sm-12">
            <p class="alert alert-info">Chưa có cập nhật tin tức nào</p>
        </div>

    </div>
<?php } ?>