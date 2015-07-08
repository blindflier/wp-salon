(function($){
	$(document).ready(function(){
		var f
		$('#salon-register-form').ajaxForm({
			beforeSubmit:function(formData,jqform){
				  var errors = [];
				  for (var i=0; i < formData.length; i++) {
					    var val = formData[i].value;
					  	switch(formData[i].name)
					  	{
					  	case 'name':
					  		if (val.length < 2 )
					  			errors.push('姓名太短');
					  		break;
					  	case 'mobile':
					  		if (!val.match(/1\d{10}/))
					  			errors.push('请填写1开头的11位有效手机号码');
					  		break;
					  	case 'qq':
					  		if (val && val.length < 5)
					  			errors.push('qq号码只能是5位以上数字');
					  	    break;
					  			
					  	}
				    } 
				  if (errors.length > 0){
					  alert(errors.join('\n'));
					  return false;
				  }
				
				  return true;
		    },
			success: function(responseText){
				try{
					var data = JSON.parse(responseText);
					if (data.message)
						alert(data.message);
					
					if($("#redirect_url").val())
						window.location.href = $("#redirect_url").val();
					else{
						if (data.redirect){
							window.location.href = data.redirect;
						}
					}
				}
				catch(e){
					alert(e.description);
				}
			}
			
		});
	});
})(jQuery);
