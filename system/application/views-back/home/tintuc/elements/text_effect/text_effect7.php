<?php 
    if(!isset($num)) {
        $detect = new Mobile_Detect();  
        $num = 5; 
        if ($detect->isAndroidOS() || $detect->isiOS() || $detect->isMobile()) {
            $num = 4;
        }
    }
?>
<?php if(!empty($aListText)) { ?>
    <?php foreach ($aListText as $TextKey => $sText) { ?>
        <div class="effect7 text_<?=$TextKey?>">
          <span class="text-wrapper">
            <span class="letters"><?=$sText?></span>
          </span>
        </div>
    <?php } ?>
    <script type="text/javascript">
    	$(document).ready(function(){

            var iCountText = 0;
            $('.effect7 .letters').each(function(){
                iCountText  = parseInt(iCountText+$(this).text().split(" ").length);
                var num  = countText($(this).text(),<?=$num?>);
                var aList   = $(this).text().split(" ");
                var limit   = parseInt(num - 1) ;
                var html    = '';
                for (var i = 0; i < 2; i++) {
                    var start = parseInt(num*i);
                    var text = '';
                    for (j=start; j <= limit + start; j++) {
                        if(aList[j] != undefined) {
                            text += " "+aList[j];
                        }
                    }
                    html += '<div>'+text.replace(/([^\x00-\x80]|\w|\,|\.|\%|\!|\:)/g, "<span class='letter'>$&</span>")+'</div>';
                }

                $(this).html(html);
            });

            var effect = anime.timeline({loop: true});
    		for (var i = 0; i < iCountText; i++) {
    			effect.add({
                    targets: '.effect7.text_'+i+' .letter',
                    scale: [0, 1],
                    duration: 1500,
                    elasticity: 600,
                    delay: function(el, i) {
                      return 45 * (i+1)
                    }
                }).add({
                    targets: '.effect7.text_'+i,
                    opacity: 0,
                    duration: 1000,
                    easing: "easeOutExpo",
                    delay: 1000
                });
    		}           
    	});
        function countText(string, num) {
            if(string.split(" ").length > num) {
                var iCount = Math.ceil(string.split(" ").length / num);
                if(iCount == 2) {
                  return num;
                }
                return countText(string,parseInt(num+1));
            }else {
                return num;
            } 
        }
    </script>
<?php } ?>