<?php $this->load->view('admin/common/header'); ?>
<?php $this->load->view('admin/common/menu'); ?>
<tr>
    <td valign="top">
        <table width="100%" border="0" align="center" class="main" cellpadding="0" cellspacing="0">
            <tr>
                <td width="2"></td>
                <td width="10" class="left_main" valign="top"></td>
                <td align="center" valign="top">
                    <!--BEGIN: Main-->
                    <table width="100%" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td height="10"></td>
                        </tr>
                        <tr>
                            <td>
                                <!--BEGIN: Item Menu-->
                                <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td width="5%" height="67" class="item_menu_left">
                                            <img src="<?php echo base_url(); ?>templates/home/images/icon/favourite-heart-icon.png" border="0" />
                                        </td>
                                        <td width="40%" height="67" class="item_menu_middle"><?php echo $this->lang->line('title_edit'); ?></td>
                                        <td width="55%" height="67" class="item_menu_right">
                                            <?php if($successEdit == false){ ?>
                                            <div class="icon_item" id="icon_item_1" onclick="ActionLink('<?php echo base_url(); ?>administ/district')" onmouseover="ChangeStyleIconItem('icon_item_1',1)" onmouseout="ChangeStyleIconItem('icon_item_1',2)">
                                                <table width="100%" height="100%" align="center" border="0" cellpadding="0" cellspacing="0">
                                                    <tr>
                                                        <td align="center">
                                                            <img src="<?php echo base_url(); ?>templates/admin/images/icon_reset.png" border="0" />
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text_icon_item" nowrap="nowrap"><?php echo $this->lang->line('cancel_tool'); ?></td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <div class="icon_item" id="icon_item_2" onclick="CheckInput_EditDistrict()" onmouseover="ChangeStyleIconItem('icon_item_2',1)" onmouseout="ChangeStyleIconItem('icon_item_2',2)">
                                                <table width="100%" height="100%" align="center" border="0" cellpadding="0" cellspacing="0">
                                                    <tr>
                                                        <td align="center">
                                                            <img src="<?php echo base_url(); ?>templates/admin/images/icon_save.png" border="0" />
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text_icon_item" nowrap="nowrap"><?php echo $this->lang->line('save_tool'); ?></td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <?php }else{ ?>
                                            <div class="icon_item" id="icon_item_2" onclick="ActionLink('<?php echo base_url(); ?>administ/district/add')" onmouseover="ChangeStyleIconItem('icon_item_2',1)" onmouseout="ChangeStyleIconItem('icon_item_2',2)">
                                                <table width="100%" height="100%" align="center" border="0" cellpadding="0" cellspacing="0">
                                                    <tr>
                                                        <td align="center">
                                                            <img src="<?php echo base_url(); ?>templates/admin/images/icon_add.png" border="0" />
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text_icon_item" nowrap="nowrap"><?php echo $this->lang->line('add_tool'); ?></td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                </table>
                                <!--END Item Menu-->
                            </td>
                        </tr>
                        <tr>
                            <td height="10"></td>
                        </tr>
                        <tr>
                            <td align="center" valign="top">
                                <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td width="20" height="20" class="corner_lt_post"></td>
                                        <td height="20" class="top_post"></td>
                                        <td width="20" height="20" class="corner_rt_post"></td>
                                    </tr>
                                    <tr>
                                        <td width="20" class="left_post"></td>
                                        <td align="center" valign="top">
                                            <!--BEGIN: Content-->
                                            <table width="585" class="form_main" cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td colspan="2" height="30" class="form_top"></td>
                                                </tr>
                                                <?php if($successEdit == false){ ?>
                                                <form name="frmEditDistrict" method="post">
                                                <tr>
                                                    <td width="150" valign="top" class="list_post"><font color="#FF0000"><b>*</b></font> <?php echo $this->lang->line('name_edit'); ?>:</td>
                                                    <td align="left">
                                                        <input type="text" name="name_district" id="name_district" value="<?php echo $name_district; ?>" maxlength="35" class="input_formpost" onkeyup="BlockChar(this,'AllSpecialChar')" onfocus="ChangeStyle('name_district',1)" onblur="ChangeStyle('name_district',2)" />
                                                        <?php echo form_error('name_district'); ?>
                                                    </td>
                                                </tr>
                                                
                                                <tr>
                                                    <td width="150" valign="top" class="list_post"><?php echo $this->lang->line('status_edit'); ?>:</td>
                                                    <td align="left" style="padding-top:7px;">
                                                        <input type="checkbox" name="active_district" id="active_district" value="1" <?php if($active_district == '1'){echo 'checked="checked"';} ?> />
                                                    </td>
                                                </tr>
                                                </form>
                                                <?php }else{ ?>
                                                <tr class="success_post">
                                                    <td colspan="2">
                                                        <p class="text-center"><a href="<?php echo base_url().'administ/district' ?>">Click vào đây để tiếp tục</a></p>
                                                		<?php echo $this->lang->line('success_edit'); ?>
                                                    </td>
                                                </tr>
                                                <?php } ?>
                                                <tr>
                                                    <td colspan="2" height="30" class="form_bottom"></td>
                                                </tr>
                                            </table>
                                            <!--END Content-->
                                        </td>
                                        <td width="20" class="right_post"></td>
                                    </tr>
                                    <tr>
                                        <td width="20" height="20" class="corner_lb_post"></td>
                                        <td height="20" class="bottom_post"></td>
                                        <td width="20" height="20" class="corner_rb_post"></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                    <!--END Main-->
                </td>
                <td width="10" class="right_main" valign="top"></td>
                <td width="2"></td>
            </tr>
            <tr>
                <td width="2" height="11"></td>
                <td width="10" height="11" class="corner_lb_main" valign="top"></td>
                <td height="11" class="middle_bottom_main"></td>
                <td width="10" height="11" class="corner_rb_main" valign="top"></td>
                <td width="2" height="11"></td>
            </tr>
        </table>
    </td>
</tr>
<?php $this->load->view('admin/common/footer'); ?>