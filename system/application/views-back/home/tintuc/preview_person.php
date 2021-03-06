<?php
$this->load->view('home/common/header_new');
$group_id = (int)$this->session->userdata('sessionGroup');
$user_id  = (int)$this->session->userdata('sessionUser');
?>
<link href="https://fonts.googleapis.com/css?family=Asap|Barlow+Condensed|Chakra+Petch|Charm|Cormorant+Upright|Cousine|Dancing+Script|Jura|Lemonada|Oswald|Pacifico|Saira+Condensed|Saira+Extra+Condensed|Taviraj"
      rel="stylesheet">
<link href="/templates/engine1/style.css" type="text/css" rel="stylesheet"/>
<script src="/templates/home/styles/plugins/boostrap/js/popper.min.js"></script>
<script src="/templates/home/styles/plugins/boostrap/js/bootstrap.min.js"></script>
<script>
    var api_common_audio_post = '<?php echo $api_common_audio_post ?>';
    var api_common_video_post = '<?php echo $api_common_video_post ?>';
    var token = '<?php echo $token ?>';
    var audios_images = '';
    var audios_url = '';
    var audios_azibai_preview = '';
    var audios_preview_url = '';
    var DOMAIN_CLOUDSERVER = '<?php echo DOMAIN_CLOUDSERVER ?>';
</script>
<script src="/templates/home/styles/js/common.js"></script>
<script src="/templates/home/js/home_news.js"></script>
<link href="/templates/home/owlcarousel/owl.carousel.min.css" rel="stylesheet" type="text/css"/>
<script src="/templates/home/owlcarousel/owl.carousel.js"></script>
<script src="/templates/home/darkroomjs/js/fabric.js"></script>
<link href="/templates/home/darkroomjs/css/darkroom.css" type="text/css" rel="stylesheet"/>
<script src="/templates/home/darkroomjs/js/darkroom.js"></script>
<script src="/templates/home/js/jquery.countdown.min.js"></script>
<script src="/templates/engine1/script.js" type="text/javascript"></script>
<script src="/templates/engine1/wowslider.js" type="text/javascript"></script>
<link href="/templates/home/css/addnews.css" type="text/css" rel="stylesheet"/>
<script src="/templates/home/js/addnews-preview-person.js?ver=<?= time(); ?>"></script>
<script src="/templates/home/js/addnews-video.js?ver=<?= time(); ?>"></script>
<script src="/templates/home/js/addnews-person-images.js?ver=<?= time(); ?>"></script>
<script src="/templates/home/js/addnews-function.js?ver=<?= time(); ?>"></script>
<script src="/templates/home/js/addnews-validate.js?ver=<?= time(); ?>"></script>
<script src="/templates/home/styles/js/addnews-common.js"></script>
<style type="text/css">
    .wrapper {
        overflow: unset !important;
    }
</style>
<main>
    <section class="main-content previewnews">
        <div class="container clearfix">
            <div class="sidebar md">
                <div class="sidebar-left"></div>
                <div class="sidebar-right">
                    <div id="preview_content">
                        <div class="title-preview">
                            <h3>XEM TRƯỚC KẾT QUẢ</h3>
                        </div>
                        <div id="prevideo"></div>
                        <div id="preslider"></div>
                        <div id="pretitlecontent">
                            <div class="r-title"><h2></h2></div>
                            <div class="r-text"></div>
                        </div>
                        <div id="prelistimagegallery">
                            <?php if (isset($images) && !empty($images)) { ?>
                                <?php foreach ($images as $iKImage => $jImage) { ?>
                                    <?php $oImage = json_decode($jImage); ?>
                                    <div class="boxaddimagegallerybox" data-id="<?= $oImage->image_id ?>"
                                         style="background-image: url(<?= $oImage->image_url ?>)">
                                        <div class="backgroundfillter"></div>
                                        <button class="setbackground" data-id="<?= $oImage->image_id ?>"></button>
                                        <button class="editimagegallary" data-id="<?= $oImage->image_id ?>"
                                                data-url="<?= $oImage->image_url ?>"></button>
                                        <button class="deleteimagegallary" data-id="<?= $oImage->image_id ?>"></button>
                                    </div>
                                <?php } ?>
                            <?php } ?>
                        </div>
                        <div id="preads"></div>
                        <div id="prestatic"></div>
                        <div id="prerelative"></div>
                        <div class="preview-content-footer">
                            <button type="button" class="cancel">Xóa</button>
                            <button type="button" id="buttonaddnews" class="save">Lưu</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="content">
                <div class="blockdangtin">
                    <?php
                    $group_id = (int)$this->session->userdata('sessionGroup');
                    $user_id  = (int)$this->session->userdata('sessionUser');
                    if (isset($currentuser) && $currentuser) {
                        $avatar = site_url('media/images/avatar/default-avatar.png');
                        if ($currentuser->avatar) {
                            $avatar = $this->config->item('avatar_user_config')['cloud_server_show_path'] . '/' . $currentuser->use_id . '/' . $currentuser->avatar;
                        }
                    }
                    ?>
                    <?php if (isset($currentuser) && $currentuser) { ?>
                        <div id="addNewsFrontEnd">
                            <div class="blockdangtin-buttontaobaiviet">
                                <p class="taobaiviet is-active">Tạo bài viết</p>
                                <p class="hr-ver"></p>
                                <p>Tạo album</p>
                            </div>
                            <div class="blockdangtin-nhaptin">
                                <img class="img-circle" src="<?php echo $avatar; ?>" alt="account"
                                     style="width:48px; height:48px">
                                <textarea class="bandangnghigi" name="" id="addtitlecontent"
                                          placeholder="Bạn đăng tin gì hôm nay?"></textarea>
                            </div>
                            <div id="boxaddimagegallery" data-num="0">
                                <?php if (!empty($not_video)) { ?>
                                    <div class="boxaddimagegallerybox"
                                         style="background-image: url(<?= DOMAIN_CLOUDSERVER . 'tmp/' . $not_video_thumb ?>)">
                                        <div class="backgroundfillter"></div>
                                        <button class="editvideogallary">
                                            <img src="<?= base_url() ?>/templates/home/styles/images/svg/play_video.svg"/>
                                        </button>
                                        <button class="deletevideo" data-id="<?= $not_video ?>"></button>
                                    </div>
                                    <script type="text/javascript">
                                        formData.set('not_video', '<?=$not_video?>');
                                        formData.set('have_video', <?=$have_video?>);
                                        formData.set('not_video_thumb', '<?=$not_video_thumb?>');
                                    </script>
                                <?php if (!empty($video_title)){ ?>
                                    <script type="text/javascript">
                                        formData.set('video_title', <?=$video_title?>);
                                    </script>
                                <?php } ?>
                                <?php if (!empty($video_content)){ ?>
                                    <script type="text/javascript">
                                        formData.set('video_content',<?=$video_content?>);
                                    </script>
                                <?php } ?>
                                <?php } ?>
                                <?php if (isset($images) && !empty($images)) { ?>
                                    <?php foreach ($images as $iKImage => $jImage) { ?>
                                        <?php $oImage = json_decode($jImage); ?>
                                        <div class="boxaddimagegallerybox" data-id="<?= $oImage->image_id ?>"
                                             style="background-image: url(<?= $oImage->image_url ?>)">
                                            <div class="backgroundfillter"></div>
                                            <button class="setbackground" data-id="<?= $oImage->image_id ?>"></button>
                                            <button class="editimagegallary" data-id="<?= $oImage->image_id ?>"
                                                    data-url="<?= $oImage->image_url ?>"></button>
                                            <button class="deleteimagegallary"
                                                    data-id="<?= $oImage->image_id ?>"></button>
                                        </div>
                                        <script type="text/javascript">
                                            formData.set('have_image', <?=count($images);?>);
                                            formData.set('images[<?=$oImage->image_id?>]', JSON.stringify(<?=$jImage?>));
                                        </script>
                                    <?php } ?>
                                <?php } ?>
                                <?php if (!empty($images) || !empty($not_video)) { ?>
                                    <script type="text/javascript">
                                        $('#boxaddimagegallery').css('display', 'block');
                                    </script>
                                <?php } ?>
                                <div class="boxaddmoreimage">
                                    <input accept="video/*,  video/x-m4v, video/webm, video/x-ms-wmv, video/x-msvideo, video/3gpp, video/flv, video/x-flv, video/mp4, video/quicktime, video/mpeg, video/ogv, .ts, .mkv, image/*"
                                           multiple="true" name="composer_photo" title="Chọn file để tải lên"
                                           display="inline-block" type="file" class="buttonAddImage"
                                           data-type="personal" id="add-more-images">
                                </div>

                            </div>
                            <div class="blockdangtin-dangtinby">
                                <button class="addvideonews">
                                    <img src="<?= base_url() ?>templates/home/images/svg/camera.svg" alt="Video">Ảnh/Video
                                    <input type="file" class="buttonAddImage" data-type="personal" name="video"
                                           accept="video/*,  video/x-m4v, video/webm, video/x-ms-wmv, video/x-msvideo, video/3gpp, video/flv, video/x-flv, video/mp4, video/quicktime, video/mpeg, video/ogv, .ts, .mkv,image/*"
                                           multiple="true">
                                </button>
                                <button data-toggle="modal" data-target="#typeDisplayNewsdetail">
                                    <img src="/templates/home/styles/images/svg/typenewdetail.svg"
                                         alt="Hiển thị chi tiết tin">Hiển thị chi tiết tin
                                </button>
                                <button class="more" id="buttonaddfunction">
                                    <img src="<?= base_url() ?>templates/home/images/svg/3dot.svg" alt="">
                                </button>
                            </div>
                            <div class="boxaddnew" data-satus="closed">
                                <textarea name="not_detail" required placeholder="Nội dung tin (bắt buộc)"
                                          rows="5"></textarea>
                            </div>
                            <div id="tabdescontentlinkmain" class="tabdescontentlink">
                                <div class="addlinkthem addlinkthem-detail no-slider-for version01" id="addlinkthem">
                                    <ul class="edit-news slider addlinkthem-slider">
                                        <?php if (!empty($not_customlink)) { ?>
                                            <?php foreach ($not_customlink as $iKCustomLink => $jCustomLink) { ?>
                                                <script type="text/javascript">
                                                    var temp_link = <?php echo($jCustomLink);  ?>;
                                                    console.log('temp_link', temp_link);
                                                    $('#tabdescontentlinkmain .addlinkthem-slider').append(news_link_template({
                                                        'detail_link': 'javascript:void(0)',
                                                        'url_image': temp_link.image,
                                                        'url_link': temp_link.save_link,
                                                        'title_link': temp_link.title,
                                                        'host_name': temp_link.host,
                                                        'num_item': '<?php echo $iKCustomLink ?>',
                                                    }, link_html_add_item));

                                                    formData.append('not_customlink[<?=$iKCustomLink?>]', JSON.stringify(<?=$jCustomLink?>));
                                                    formData.set('num_customlink', <?=count($not_customlink)?>);
                                                </script>
                                            <?php } ?>
                                        <?php } ?>
                                    </ul>
                                </div>
                            </div>
                            <div id="boxaddfunction"></div>
                            <div id="boxaddnewsexample"></div>
                            <div class="morefooter list-checkbox" data-status="closed">
                                <div class="morefooter-row">
                                    <div class="morefooter-left checkbox-style">
                                        <label>
                                            <input type="checkbox" name="category" value="1"><span
                                                    class="checkbox"></span>
                                        </label>
                                        <img class="img-circle" src="<?php echo $avatar; ?>" alt="account"
                                             style="width:32px; height:32px">
                                        <span>Bảng tin</span>
                                    </div>
                                    <div class="morefooter-right">
                                        <button class="public-type">Công khai</button>
                                    </div>
                                    <div class="clear"></div>
                                </div>
                                <div class="morefooter-row">
                                    <div class="morefooter-left checkbox-style">
                                        <label>
                                            <input type="checkbox" name="category" value="2">
                                            <span class="checkbox"></span>
                                        </label>
                                        <img class="img-circle" src="<?php echo $avatar; ?>" alt="account"
                                             style="width:32px; height:32px">
                                        <span>Tin của bạn</span>
                                    </div>
                                    <div class="morefooter-right">
                                        <button class="public-type">Bạn bè</button>
                                    </div>
                                    <div class="clear"></div>
                                </div>
                                <div class="morefooter-row">
                                    <button class="readmoreboxnew">Xem thêm</button>
                                    <button id="submitnews">Đăng tin</button>
                                    <div class="clear"></div>
                                </div>
                            </div>
                            <div id="boxwork"></div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </section>
</main>
<script type="text/javascript">
    <?php  if($not_detail != '') { ?>
    $('#addNewsFrontEnd .boxaddnew textarea[name="not_detail"]').val(br2nl('<?=$not_detail?>'));
    $('#pretitlecontent .r-text').html('<?=$not_detail?>');
    <?php } ?>
</script>

<!-- Popup Icon -->
<?php
$this->load->helper('directory');
$icons = directory_map('./images/icons');
?>
<div class="drawer-overlay drawer-toggle" data-popup=""></div>
<div id="myIconModal" class="model-content">
    <div class="wrapp-model">
        <div class="content-model">
            <div class="contents">
                <div class="btn-back js-back">
                    <a href="#">
                        <img src="<?= base_url() ?>templates/home/images/svg/close.svg">
                    </a>
                </div>
                <div class="row list-icon" style="height:450px; overflow: auto">
                    <?php
                    if (isset($icons)) {
                        foreach ($icons as $image) {
                            $imglink = base_url() . 'images/icons/' . $image;
                            ?>
                            <div class="icon-item chooseimage" style="cursor:pointer;" data-image-url="<?= $imglink ?>"
                                 data-image="<?= $image ?>" title="<?= $image ?>">
                                <?php echo '<img class="aicon img-responsive" src="' . base_url() . 'images/icons/' . $image . '"/>'; ?>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>
                <div class="footer-popup">
                    <div class="error"></div>
                    <button type="button" class="btn btn-primary insertimage">Chọn icon</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Popup Icon option -->
<div id="myIconOption" class="model-content">
    <div class="wrapp-model">
        <div class="content-model">
            <div class="contents">
                <div class="btn-back js-back">
                    <a href="#">
                        <img src="<?= base_url() ?>templates/home/images/svg/close.svg">
                    </a>
                </div>
                <div class="content-icon-option"></div>
                <div class="footer-popup">
                    <div class="error"></div>
                    <button type="button" class="btn btn-primary inserticon">Chèn icon</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Popup error -->
<div id="myError" class="model-content">
    <div class="wrapp-model">
        <div class="content-model">
            <div class="contents">
                <div class="btn-back js-back">
                    <a href="#">
                        <img src="<?= base_url() ?>templates/home/images/svg/close.svg">
                    </a>
                </div>
                <div class="content-icon-option"></div>
            </div>
        </div>
    </div>
</div>
<div id="process-file">
    <p class="alert-mess-copy bg-pink">Video của bạn đang được tải lên</p>
</div>

<?php $this->load->view('home/tintuc/popup/pop-style-show-content'); ?>
