<div class="window " id="sms-window">
	<div class="title">
		<h2>发送短消息</h2>
		<a href="#" class="modal_close"></a>
	</div>
	<form class="sms-email-form" data-window="#sms-window" action="<?php echo admin_url('admin-ajax.php');?>"  method="post">
		<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('send-sms-form');?>">
        <input type="hidden" name="register-id" value="">
        <input type="hidden" name="action" value="sendsms">   
             
		<div class="form-group clearfix">
			<label>手机号码</label>
			<input type="text"  name="mobile"  >
		</div>
		<div class="form-group clearfix">
			<label>短信内容</label>
			<textarea type="text" name="sms-text" rows="10"></textarea>
		</div>
		<div class="button-group clearfix">
			<button type="submit" value="Submit" class="button">发送</button>
		</div>
		
	</form>
</div>


<div class="window " id="email-window">
	<div class="title">
		<h2>发送电子邮件</h2>
		<a href="#" class="modal_close"></a>
	</div>
	<form class="sms-email-form" data-window="#email-window" action="<?php echo admin_url('admin-ajax.php');?>"  method="post">
		<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('send-email-form');?>">
        <input type="hidden" name="register-id" value="">
        <input type="hidden" name="action" value="sendemail">   
             
		<div class="form-group clearfix">
			<label>电子邮箱</label>
			<input type="text"  name="to"  >
		</div>
		<div class="form-group clearfix">
			<label>标题</label>
			<input type="text"  name="email-title"  >
		</div>
		<div class="form-group clearfix">
			<label>邮件内容</label>
			<textarea type="text" name="email-text"  rows="10"></textarea>
		</div>
		<div class="button-group clearfix">
			<button type="submit" value="Submit" class="button">发送</button>
		</div>
		
	</form>
</div>

<div id="salon-mask"><img class="loading" src="<?php echo plugins_url("../images/loading.gif",__FILE__) ?>" > </div>