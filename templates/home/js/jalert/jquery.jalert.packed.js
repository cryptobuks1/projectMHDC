/**
* jAlert v.1.0.0
* Copyright (c) 2008 Julian Castaneda
* http://www.smooka.com/blog/
* Requires: jQuery 1.2+
*/

(function($){jQuery.fn.jAlert=function(e,f,g,h,i){var j=this;var k=0;if(g==undefined){var d=new Date();var g=d.getMonth()+""+d.getDate()+""+d.getHours()+""+d.getMinutes()+""+d.getSeconds()}if(jQuery('#jalert_box_cont_'+g).css('display')=='block'){return}if(!f){f='warning'}if(!h){h=350}if(!i){i=0}if(k==1){jQuery('<div id="jalert_overlay_'+g+'"></div>').prependTo('body');var l=jQuery(window).width();var m=jQuery(document).height();var n=jQuery(window).height();jQuery("#jalert_overlay_"+g).css({top:0,left:0,width:l,height:n,position:"fixed",display:"block",background:"#000",zIndex:"1000"});jQuery("#jalert_overlay_"+g).css("opacity",0.7)}jQuery('<div class="msg-box-cont msg-'+f+'" id="jalert_box_cont_'+g+'"><table width="100%" border="0" cellpadding="0" cellspacing="0"><tr><td><div class="msg-text"><div class="msg-icon msg-icon-'+f+'"></div>'+e+'</div></td><td width="21" valign="top"><div class="msg-btn close-'+f+'"></div></td></tr></table></div>').appendTo('body');jQuery("#jalert_box_cont_"+g).width(h);alignCenter();var o=this.y()+i;jQuery("#jalert_box_cont_"+g).css("top",o+"px");jQuery("#jalert_box_cont_"+g).fadeIn(500);jQuery(document).click(function(){jQuery("#jalert_overlay_"+g).fadeOut(100);jQuery("#jalert_overlay_"+g).remove()});jQuery('.msg-btn').click(function(){if(k==1){jQuery("#jalert_overlay_"+g).fadeOut(100);jQuery("#jalert_overlay_"+g).remove()}jQuery("#jalert_box_cont_"+g).fadeOut(100);jQuery("#jalert_box_cont_"+g).empty();jQuery("#jalert_box_cont_"+g).remove();jQuery(window).unbind("resize")});jQuery(window).resize(function(){alignCenter()});function alignCenter(){var a=jQuery("#jalert_box_cont_"+g).width();var b=j.innerWidth();var c=j.x();var d=((b-a)/2)+c;jQuery("#jalert_box_cont_"+g).css("left",d+"px")}};jQuery.fn.y=function(n){var a=null;this.each(function(){var o=this;if(n===undefined){var y=0;if(o.offsetParent){while(o.offsetParent){y+=o.offsetTop;o=o.offsetParent}}if(a===null){a=y}else{a=Math.min(a,y)}}else{o.style.top=n+'px'}});return a};jQuery.fn.x=function(n){var a=null;this.each(function(){var o=this;if(n===undefined){var x=0;if(o.offsetParent){while(o.offsetParent){x+=o.offsetLeft;o=o.offsetParent}}if(a===null){a=x}else{a=Math.min(a,x)}}else{o.style.left=n+'px'}});return a}})(jQuery);