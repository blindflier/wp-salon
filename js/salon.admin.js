(function($){
  $(document).ready(function(){
	  
	  $('a.open-sms-window').click(function(e){
		  	$('.sms-email-form [name=register-id]').val( $(this).data('id'));
			$('.sms-email-form [name=mobile]').val( $(this).data('mobile'));
			$('.sms-email-form [name=sms-text]').val( $(this).data('sms-text'));
			$('.sms-email-form').data('open-btn','#sms-btn-' + $(this).data('id'));
	  });
	  $('a.open-email-window').click(function(e){
		    $('.sms-email-form [name=register-id]').val( $(this).data('id'));
			$('.sms-email-form [name=to]').val( $(this).data('to'));
			$('.sms-email-form [name=email-title]').val( $(this).data('email-title'));
			$('.sms-email-form [name=email-text]').val( $(this).data('email-text'));
			$('.sms-email-form').data('open-btn','#email-btn-' + $(this).data('id'));
	  });
	  $('a.open-window').leanModal({
		  top :150, overlay : 0.7, closeButton: ".modal_close"
	  });
	  
	  function show_loading(){
		  $('#salon-mask').fadeIn(200);
		  $('#salon-mask .loading').css({'top': $(window).height() * 0.4 });
	  }
	  
	  function hide_loading(){
		  $('#salon-mask').fadeOut(200);
	  }
	  
	  $('.sms-email-form').ajaxForm({
		    beforeSubmit:function(formdata,jqform){
		    		show_loading();
		    },
			success: function(responseText,statusText, xhr, jqform){
				hide_loading();
				try{
					var data = JSON.parse(responseText);
					if (data.message)
						alert(data.message);
					if (data.success){
						//隐藏窗口
						$(jqform.data('open-btn')).html('已发');
						$("#lean_overlay").fadeOut(200);
		        			$(jqform	.data('window')).css({ 'display' : 'none' });
					}
				}
				catch(e){
					alert(e.description);
				}
				
			},
			error: function(){
				hide_loading();
				alert('ajax请求错误');
			}
			
	  });
	  
	  $("input.sign_in").click(function(e){
		  var elem = $(this);
	      show_loading();
		  $.post(elem.data('target'),{
			'id' : elem.data('id'),
			'sign_in' : (elem.attr('checked') ? 1 : 0) ,
			'action' : elem.data('action'),
		  },function(responseText,statusText, xhr ){
			  try{
					var data = JSON.parse(responseText);
					if (data.message)
						alert(data.message);
			  }catch(e){
				  alert(e.description);
			  }
			  hide_loading();
		  }).error(hide_loading);
		  
	  });
	  
	  $("input.salon-toggle-opening").click(function(e){
		  var elem = $(this);
	      show_loading();
		  $.post(elem.data('target'),{
			'id' : elem.data('id'),
			'opening' : (elem.attr('checked') ? 1 : 0) ,
			'action' : elem.data('action'),
		  },function(responseText,statusText, xhr ){
			  try{
					var data = JSON.parse(responseText);
					if (data.message)
						alert(data.message);
			  }catch(e){
				  alert(e.description);
			  }
			  hide_loading();
		  }).error(hide_loading);
		  
	  });
	  
	  var download_iframe;
	  $('a.salon-export').click(function(e){
		  
		  function make_url(elem){
			  return elem.data('target') + "?id=" + elem.data('id') + "&action=" + elem.data('action');
		  }
		  if(typeof(download_iframe)== "undefined")
		  {
			  download_iframe = document.createElement("iframe");
		  	  document.body.appendChild(download_iframe); 
		  	  download_iframe.style.display = "none";
		  }
		  download_iframe.src = make_url($(this));
		  
	  });
  });	
  
})(jQuery);