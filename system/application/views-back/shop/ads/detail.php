<?php $this->load->view('shop/common/header'); ?>
<?php $this->load->view('shop/common/left'); ?>
<?php if(isset($siteGlobal)){ ?>
<!--BEGIN: Center-->
<td valign="top" align="center">
<div id="DivContent">
    <?php $this->load->view('shop/common/top'); ?>
    <table width="100%" class="table_module" style="margin-top:5px;" border="0" cellpadding="0" cellspacing="0">
        <tr>
            <td height="28" class="title_module"><?php echo $this->lang->line('title_detail_ads_detail'); ?></td>
        </tr>
        <tr>
            <td class="main_module">
                <table align="center" width="100%" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td align="center" valign="top">
                            <table width="100%" class="tbl_detail" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td class="image_view_detail_top">
                                        <table width="100%" height="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td align="center">
                                                    <link type="text/css" rel="stylesheet" href="<?php echo $URLRoot; ?>templates/shop/<?php echo $siteGlobal->sho_style; ?>/css/slimbox.css" media="screen" />
													<script language="javascript" src="<?php echo $URLRoot; ?>templates/shop/<?php echo $siteGlobal->sho_style; ?>/js/mootools.js"></script>
													<script language="javascript" src="<?php echo $URLRoot; ?>templates/shop/<?php echo $siteGlobal->sho_style; ?>/js/slimbox.js"></script>
                                                    <a href="<?php echo $URLRoot; ?>media/images/raovat/<?php echo $ads->ads_dir; ?>/<?php echo show_image($ads->ads_image); ?>" rel="lightbox">
                                                        <img src="<?php echo $URLRoot; ?>media/images/raovat/<?php echo $ads->ads_dir; ?>/<?php echo show_thumbnail($ads->ads_dir, $ads->ads_image, 3, 'ads'); ?>" id="image_detail" />
                                                    </a>
                                                    <div id="click_view">
                                                        <?php if($ads->ads_image != 'none.gif'){ ?>
                                                        (<?php echo $this->lang->line('click_view_detail_ads_detail'); ?>)
                                                        <?php }else{ ?>
                                                        (<?php echo $this->lang->line('none_view_detail_ads_detail'); ?>)
                                                        <?php } ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td width="7" valign="top"></td>
                                    <td rowspan="2" class="info_view_detail" valign="top">
                                        <div id="title_detail">
                                            <?php echo $ads->ads_title; ?>
                                        </div>
                                        <table border="0" cellpadding="0" cellspacing="5" align="left">
                                            <tr>
                                                <td width="70" valign="top" class="list_detail"><?php echo $this->lang->line('category_detail_ads_detail'); ?>:</td>
                                                <td class="content_list_detail"><b><?php if((int)$category->cat_status == 1){ ?><a class="menu_1" href="<?php echo $mainURL; ?>raovat/category/<?php echo $category->cat_id; ?>/<?php echo RemoveSign($category->cat_name); ?>/" alt="<?php echo $category->cat_descr; ?>" target="_blank"><?php } ?><?php echo $category->cat_name; ?><?php if((int)$category->cat_status == 1){ ?></a><?php } ?></b></td>
                                            </tr>
                                            <tr>
                                                <td width="70" valign="top" class="list_detail"><?php echo $this->lang->line('address_detail_ads_detail'); ?>:</td>
                                                <td class="content_list_detail"><?php echo $ads->ads_address; ?></td>
                                            </tr>
                                            <tr>
                                                <td width="70" valign="top" class="list_detail"><?php echo $this->lang->line('phone_detail_ads_detail'); ?>:</td>
                                                <td class="content_list_detail"><b><?php echo $ads->ads_phone; ?><?php if(trim($ads->ads_phone) != '' && trim($ads->ads_mobile) != ''){echo ' - ';} ?><?php echo $ads->ads_mobile; ?></b></td>
                                            </tr>
                                            <tr>
                                                <td width="70" class="list_detail"><?php echo $this->lang->line('email_detail_ads_detail'); ?>:</td>
                                                <td class="content_list_detail"><b><a class="menu" href="mailto:<?php echo $ads->ads_email; ?>"><?php echo $ads->ads_email; ?></a></b></td>
                                            </tr>
                                            <tr>
                                                <td width="70" class="list_detail"><?php echo $this->lang->line('yahoo_detail_ads_detail'); ?>:</td>
                                                <td class="content_list_detail"><b><a class="menu" href="ymsgr:SendIM?<?php echo $ads->ads_yahoo; ?>"><img class="list_ten" alt="yahoo status" src="http://opi.yahoo.com/online?u=<?php echo $ads->ads_yahoo; ?>;m=g;t=5;l=us" border="0" />&nbsp;<?php echo $ads->ads_yahoo; ?></a></b></td>
                                            </tr>
                                            <tr>
                                                <td width="70" class="list_detail"><?php echo $this->lang->line('skype_detail_ads_detail'); ?>:</td>
                                                <td class="content_list_detail"><b><a class="menu" href="skype:<?php echo $ads->ads_skype; ?>?Chat"><img class="list_ten" alt="skype status" src="http://mystatus.skype.com/smallicon/<?php echo $ads->ads_skype; ?>" style="border: none;"/>&nbsp;<?php echo $ads->ads_skype; ?></a></b></td>
                                            </tr>
                                            <tr>
                                                <td width="70" valign="top" class="list_detail"><?php echo $this->lang->line('poster_detail_ads_detail'); ?>:</td>
                                                <td class="content_list_detail"><?php echo $ads->ads_poster; ?></td>
                                            </tr>
                                            <tr>
                                                <td width="70" valign="top" class="list_detail"><?php echo $this->lang->line('post_date_detail_ads_detail'); ?>:</td>
                                                <td class="content_list_detail"><?php echo date('d-m-Y', $ads->ads_begindate); ?></td>
                                            </tr>
                                            <tr>
                                                <td width="70" valign="top" class="list_detail"><?php echo $this->lang->line('view_detail_ads_detail'); ?>:</td>
                                                <td class="content_list_detail"><?php echo $ads->ads_view; ?></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr height="100%">
                                    <td class="image_view_detail_bottom" valign="bottom">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td height="25" valign="middle" style="padding-right:3px;"><a onclick="Favorite('frmOneFavorite', '<?php if(isset($isLogined) && $isLogined == true){echo 1;}else{echo $this->lang->line('must_login_message');} ?>')" href="#Favorite" title="<?php echo $this->lang->line('favorite_tip_detail_ads_detail'); ?>"><img src="<?php echo $URLRoot; ?>templates/shop/<?php echo $siteGlobal->sho_style; ?>/images/icon_favorite_detail.gif" border="0" /></a></td>
                                                <div style="display:none;"><form name="frmOneFavorite" method="post"><input type="hidden" name="checkone" value="<?php echo $ads->ads_id; ?>" /></form></div>
                                                <td width="90%" align="left" valign="middle">
                                                    <a class="menu_1" onclick="Favorite('frmOneFavorite', '<?php if(isset($isLogined) && $isLogined == true){echo 1;}else{echo $this->lang->line('must_login_message');} ?>')" href="#Favorite" alt="<?php echo $this->lang->line('favorite_tip_detail_ads_detail'); ?>">
                                                        <?php echo $this->lang->line('favorite_detail_ads_detail'); ?>
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td width="7" valign="top"></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center">
                            <table border="0" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td height="30" class="header_detail"><?php echo $this->lang->line('detail_detail_ads_detail'); ?></td>
                                </tr>
                                <tr>
                                    <td class="main_detail">
                                     <?php echo $this->bbcode->light($ads->ads_detail); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td height="30" class="bottom_detail"></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td height="10" class="bottom_module"></td>
        </tr>
    </table>
</div>
</td>
<!--END Center-->
<?php } ?>
<?php $this->load->view('shop/common/right'); ?>
<?php $this->load->view('shop/common/footer'); ?>
<?php if(isset($successFavoriteAds) && $successFavoriteAds == true){ ?>
<script>alert('<?php echo $this->lang->line('success_add_favorite_message_detail_ads_detail'); ?>');</script>
<?php } ?>