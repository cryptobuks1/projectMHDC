<?php
  $af_key = '';
  if($this->session->userdata('sessionAfKey')) {
      $af_key = '?af_id='.$this->session->userdata('sessionAfKey');
  }
  if($_REQUEST['af_id']) {
      $af_key = '?af_id='.$_REQUEST['af_id'];
  }
?>
<div class="shop-slider" style="display:<?=!empty($collection_coupon)? (!empty($collection_product) ? 'none' : 'block'):'none'?>">
  <div class="container">
    <ul class="js-shop-slider list-collection-coupon">
    <?php if(!empty($collection_coupon)){
      foreach ($collection_coupon as $key => $item) { ?>
      <li>
        <a href="<?=$shop_url.'shop/collection-coupon/select/'.$item->id.$af_key?>" target="_blank">
          <img src="<?=$item->avatar_path_full?>" alt="">
          <div class="text">
            <p class="two-lines"><?=$item->name?></p>
          </div>
        </a>
      </li>
    <?php }
    }?>
    </ul>

    <div class="icon-add bg-gray">
      <span>
        <img src="/templates/home/styles/images/svg/add_pink.svg" alt="">
      </span>
    </div>
  </div>
</div>
<script type="text/javascript">
  

</script>