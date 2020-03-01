<?php $this->load->view('admin/common/header'); ?>
<?php $this->load->view('admin/common/menu'); ?>
    <tr>
        <td>
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
                                                <img
                                                    src="<?php echo base_url(); ?>templates/home/images/icon/favourite-heart-icon.png"
                                                    border="0"/>
                                            </td>
                                            <td width="40%" height="67"
                                                class="item_menu_middle"><?php echo $this->lang->line('ser_title_edit'); ?></td>
                                            <td width="55%" height="67" class="item_menu_right">
                                                <div class="icon_item" id="icon_item_1"
                                                     onclick="ActionLink('<?php echo $cancel; ?>')"
                                                     onmouseover="ChangeStyleIconItem('icon_item_1',1)"
                                                     onmouseout="ChangeStyleIconItem('icon_item_1',2)">
                                                    <table width="100%" height="100%" align="center" border="0"
                                                           cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td align="center">
                                                                <img
                                                                    src="<?php echo base_url(); ?>templates/admin/images/icon_reset.png"
                                                                    border="0"/>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text_icon_item"
                                                                nowrap="nowrap"><?php echo $this->lang->line('cancel_tool'); ?></td>
                                                        </tr>
                                                    </table>
                                                </div>
                                                <div class="icon_item" id="icon_item_2"
                                                     onclick="$('#frmAdminForm').trigger('submit');"
                                                     onmouseover="ChangeStyleIconItem('icon_item_2',1)"
                                                     onmouseout="ChangeStyleIconItem('icon_item_2',2)">
                                                    <table width="100%" height="100%" align="center" border="0"
                                                           cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td align="center">
                                                                <img
                                                                    src="<?php echo base_url(); ?>templates/admin/images/icon_save.png"
                                                                    border="0"/>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text_icon_item"
                                                                nowrap="nowrap"><?php echo $this->lang->line('save_tool'); ?></td>
                                                        </tr>
                                                    </table>
                                                </div>

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
                                                <form id="frmAdminForm" name="frmAdminForm" method="post"
                                                      action="<?=base_url()?>administ/service/simple/add">
                                                    <table width="585" class="form_main" cellpadding="0" cellspacing="0"
                                                           border="0">
                                                        <tr>
                                                            <td colspan="2" height="30" class="form_top"></td>
                                                        </tr>
                                                        <tr>
                                                            <td width="150" valign="top" class="list_post"><font
                                                                    color="#FF0000"><b>*</b></font> <?php echo $this->lang->line('ser_title'); ?>
                                                                : </td>
                                                            <td align="left">
                                                                <select class="select_search" name="group">
                                                                    <?php foreach($aListGService as $item){?>
                                                                        <option <?php echo ($item['group'] == $group) ? 'selected="selected"':'';?> value="<?php echo $item['group'];?>"><?php echo $item['text']; ?></option>
                                                                    <?php } ?>
                                                                </select>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td width="150" valign="top" class="list_post"><font
                                                                    color="#FF0000"><b>*</b></font>Tên : </td>
                                                            <td align="left">
                                                                <input class="input_formpost" type="text" name="name">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td width="150" valign="top" class="list_post"><font
                                                                    color="#FF0000"><b>*</b></font> Giới hạn
                                                                :
                                                            </td>
                                                            <td align="left">
                                                                <input type="text" name="limit" class="input_formpost"
                                                                       id="limit"
                                                                       value=""
                                                                    />
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td width="150" valign="top" class="list_post"> Đơn vị
                                                                :
                                                            </td>
                                                            <td align="left">
                                                                <input type="text" name="unit" class="input_formpost"
                                                                       id="unit"
                                                                       value=""
                                                                    />
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td width="150" valign="top"
                                                                class="list_post"><?php echo $this->lang->line('ser_desc'); ?>
                                                                :
                                                            </td>
                                                            <td align="left" style="padding-top:7px;">
                                                                <textarea id="desc" name="desc" class="input_select"
                                                                          required></textarea>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td width="150" valign="top"
                                                                class="list_post">Giá :
                                                            </td>
                                                            <td align="left" >
                                                                <input type="text" name="month_price" class="input_formpost"
                                                                       id="month_price"
                                                                       value="<?php echo $month_price;?>"
                                                                    />
                                                            </td>
                                                        </tr>
                                                        <?php if(isset($affiliatelevel) && !empty($affiliatelevel)) { ?>
                                                            <?php foreach ($affiliatelevel as $iKey => $oAf) { ?>
                                                                <tr>
                                                                    <td width="150" valign="top"
                                                                        class="list_post">Giá <?=$oAf->name?> :
                                                                    </td>
                                                                    <td align="left" >
                                                                        <input type="text" name="price_aff_<?=$oAf->id?>" class="input_formpost" value="<?=$oAf->price?>"/>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td width="150" valign="top"
                                                                        class="list_post">HH <?=$oAf->name?> :
                                                                    </td>
                                                                    <td align="left" >
                                                                        <input type="number" name="percen_aff_<?=$oAf->id?>" min="0" max="100" class="input_formpost" value="<?=$oAf->percen?>"/>
                                                                    </td>
                                                                </tr>
                                                            <?php } ?>
                                                        <?php } ?>
                                                        <tr>
                                                            <td width="150" valign="top"
                                                                class="list_post"><?php echo $this->lang->line('service_install'); ?>
                                                                :
                                                            </td>
                                                            <td align="left" style="padding-top:7px;">
                                                                <input type="checkbox" name="install"
                                                                       id="install"
                                                                       value="1" <?php if ($install == '1') {
                                                                    echo 'checked="checked"';
                                                                } ?> />
                                                            </td>
                                                        </tr>

                                                        <tr>
                                                            <td width="150" valign="top"
                                                                class="list_post"><?php echo $this->lang->line('service_install_note'); ?>
                                                                :
                                                            </td>
                                                            <td align="left" style="padding-top:7px;">
                                                                <textarea id="note" name="note" class="input_select"
                                                                    ><?php if ($note != '') {
                                                                        echo $note;
                                                                    } ?></textarea>
                                                            </td>
                                                        </tr>

                                                        <tr>
                                                            <td width="150" valign="top"
                                                                class="list_post"><?php echo $this->lang->line('ser_publised'); ?>
                                                                :
                                                            </td>
                                                            <td align="left" style="padding-top:7px;">
                                                                <input type="checkbox" name="published"
                                                                       id="published"
                                                                       value="1" <?php if ($published == '1') {
                                                                    echo 'checked="checked"';
                                                                } ?> />
                                                            </td>
                                                        </tr>

                                                        <tr>
                                                            <td colspan="2" height="30" class="form_bottom"></td>
                                                        </tr>
                                                    </table>
                                                    <input type="hidden" name="group_servi" value="<?php echo $group; ?>">
                                                </form>

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