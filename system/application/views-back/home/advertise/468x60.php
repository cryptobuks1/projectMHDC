<?php
$id=$this->uri->rsegment('3');
$counter1 = 0;
$counter2 = 0;
foreach($advertise as $advertiseArray){ ?>
<?php if((int)$advertiseArray->adv_position == 3 && $advertiseArray->adv_page == 'home'  && $advertiseArray->cat_id == $id){
		$counter1 = $counter1 +1;
if ($counter1>1)
{
	break;
}?>
	   <div class="col-sm-6 pull-left">
		   <a href="<?php echo base_url().'adv/'.$advertiseArray->adv_id; ?>" target="_blank" >
			   <?php if(strtolower(substr($advertiseArray->adv_banner, -4)) == '.swf'){ ?>
				   <object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" class="advertise_top_flash" id="FlashID_<?php echo $advertiseArray->adv_id; ?>" title="<?php echo $advertiseArray->adv_title; ?>">
					   <param name="movie" value="<?php echo base_url(); ?>media/banners/<?php echo $advertiseArray->adv_dir; ?>/<?php echo $advertiseArray->adv_banner; ?>" />
					   <param name="quality" value="high" />
					   <param name="wmode" value="opaque" />
					   <param name="swfversion" value="6.0.65.0" />
					   <param name="expressinstall" value="<?php echo base_url(); ?>templates/home/images/expressInstall.swf" />
					   <!--[if !IE]>-->
					   <object type="application/x-shockwave-flash" data="<?php echo base_url(); ?>media/banners/<?php echo $advertiseArray->adv_dir; ?>/<?php echo $advertiseArray->adv_banner; ?>" class="advertise_top_flash">
						   <!--<![endif]-->
						   <param name="quality" value="high" />
						   <param name="wmode" value="opaque" />
						   <param name="swfversion" value="6.0.65.0" />
						   <param name="expressinstall" value="<?php echo base_url(); ?>templates/home/images/expressInstall.swf" />
						   <div>
							   <h4>Content on this page requires a newer version of Adobe Flash Player.</h4>
							   <p><a href="http://www.adobe.com/go/getflashplayer"><img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player" /></a></p>
						   </div>
						   <!--[if !IE]>-->
					   </object>
					   <!--<![endif]-->
				   </object>
				   <script type="text/javascript">
					   <!--
					   swfobject.registerObject("FlashID_<?php echo $advertiseArray->adv_id; ?>");
					   //-->
				   </script>
			   <?php }else{ ?>
			   <img class="img-responsive" border="0" src="<?php echo base_url(); ?>media/banners/<?php echo $advertiseArray->adv_dir; ?>/<?php echo $advertiseArray->adv_banner; ?>" />
			   <?php } ?>
		   </a>
	   </div>
	<?php } elseif((int)$advertiseArray->adv_position ==4  && $advertiseArray->adv_page == 'home'   && $advertiseArray->cat_id == $id){
		$counter2 = $counter2 +1;
		if ($counter2>1)
		{
			break;
		}
		?>
		<div class="col-sm-6 pull-right">
			<a href="<?php echo base_url().'adv/'.$advertiseArray->adv_id; ?>" target="_blank" >
				<?php if(strtolower(substr($advertiseArray->adv_banner, -4)) == '.swf'){ ?>
					<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" class="advertise_top_flash" id="FlashID_<?php echo $advertiseArray->adv_id; ?>" title="<?php echo $advertiseArray->adv_title; ?>">
						<param name="movie" value="<?php echo base_url(); ?>media/banners/<?php echo $advertiseArray->adv_dir; ?>/<?php echo $advertiseArray->adv_banner; ?>" />
						<param name="quality" value="high" />
						<param name="wmode" value="opaque" />
						<param name="swfversion" value="6.0.65.0" />
						<param name="expressinstall" value="<?php echo base_url(); ?>templates/home/images/expressInstall.swf" />
						<!--[if !IE]>-->
						<object type="application/x-shockwave-flash" data="<?php echo base_url(); ?>media/banners/<?php echo $advertiseArray->adv_dir; ?>/<?php echo $advertiseArray->adv_banner; ?>" class="advertise_top_flash">
							<!--<![endif]-->
							<param name="quality" value="high" />
							<param name="wmode" value="opaque" />
							<param name="swfversion" value="6.0.65.0" />
							<param name="expressinstall" value="<?php echo base_url(); ?>templates/home/images/expressInstall.swf" />
							<div>
								<h4>Content on this page requires a newer version of Adobe Flash Player.</h4>
								<p><a href="http://www.adobe.com/go/getflashplayer"><img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player" /></a></p>
							</div>
							<!--[if !IE]>-->
						</object>
						<!--<![endif]-->
					</object>
					<script type="text/javascript">
						<!--
						swfobject.registerObject("FlashID_<?php echo $advertiseArray->adv_id; ?>");
						//-->
					</script>
				<?php }else{ ?>
				<img class="img-responsive" border="0" src="<?php echo base_url(); ?>media/banners/<?php echo $advertiseArray->adv_dir; ?>/<?php echo $advertiseArray->adv_banner; ?>" />
				<?php } ?>
			</a>
		</div>
<?php } ?>
<?php } ?>

<?php
if ($counter1==0)
{
	?>
	<div class="col-sm-6 pull-left">
	<img class="img-responsive" border="0" src="<?php echo base_url(); ?>media/banners/default/default_468_60.jpg" />
	</div>
	<?php


}

?>

<?php
if ($counter2==0)
{

	?>
	<div class="col-sm-6 pull-right"><img class="img-responsive" border="0" src="<?php echo base_url(); ?>media/banners/default/default_468_60.jpg" />
	</div>
	<?php


}

?>
