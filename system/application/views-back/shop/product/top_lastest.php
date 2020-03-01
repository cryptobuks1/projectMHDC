<?php if(isset($siteGlobal)){ ?>
	<?php if(count($topLastestProduct) > 0){ ?>
		<div class="module">
			<h3 class="module-title"><?php echo $this->lang->line('title_top_lastest_product_right_detail_global'); ?></h3>
			<div class="module-content">
				<table border="0" width="100%" cellpadding="0" cellspacing="0">
					<?php foreach($topLastestProduct as $topLastestProductArray){ ?>
					<tr>
						<td valign="top" style="padding-top:4px;"><img src="<?php echo $URLRoot; ?>templates/shop/<?php echo $siteGlobal->sho_style; ?>/images/list_dot.gif" border="0" /></td>
						<td class="list_1" valign="top">
							<a class="menu_1" href="<?php echo $URLRoot; ?>/product/detail/<?php echo $topLastestProductArray->pro_id; ?>/<?php echo RemoveSign($topLastestProductArray->pro_name);?>"  onmouseout="hideddrivetip();">
							<?php echo cut_string_unicodeutf8($topLastestProductArray->pro_name,30); ?></a>
						</td>
					</tr>
					<?php } ?>
					<tr>
						<td valign="top" class="view_all" colspan="2"><a class="menu_2" href="<?php echo $URLRoot; ?>/product"><?php echo $this->lang->line('view_all_right_detail_global'); ?> ...</a></td>
					</tr>
				</table>
			</div>
		</div>	
	<?php } ?>
<?php } ?>