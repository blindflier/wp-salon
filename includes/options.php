<?php 
if( $_SERVER['REQUEST_METHOD'] == 'POST' && trim($_POST['salon-option-form'])){
	if (!wp_verify_nonce(trim($_POST['_wpnonce']),'salon-option-form'))
		wp_die('Security check not passed!');
	//保存options
	$options = array(
		'sms-username' => trim($_POST['sms-username']),
		'sms-password' => trim($_POST['sms-password']),
	   'sms-template' => trim($_POST['sms-template']),
	   'email-title' => trim($_POST['email-title']),
	   'email-template' => trim($_POST['email-template'])
	);
	update_option("salon_options",$options);
	
}else{
	$options = get_option("salon_options",array(
		'sms-template' => '{姓名}{活动时间}{活动地点}{活动主题}',
		'email-title' => '{姓名}欢迎您参加学佛活动',
		'email-template' => '{姓名}{活动时间}{活动地点}{活动主题}'
	));
} 
	
?>
<form class="option-form" action="" method="post" name="salon_option_form">
	<h2>活动插件选项设置</h2>
	<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('salon-option-form');?>">
	<input type="hidden" name="salon-option-form" value="1" >
	<div class="form-group clearfix">
			<label>短信用户</label>
			<input type="text" name="sms-username" value="<?php echo $options['sms-username']?>" >
		</div>
		<div class="form-group clearfix">
			<label>短信密码</label>
			<input type="text" name="sms-password" value="<?php echo $options['sms-password']?>" >
		</div>
		
	 <div class="form-group clearfix">
			<label>短信模板</label>
			<textarea name="sms-template" rows="10" ><?php echo $options['sms-template']?></textarea>
		</div>
	
	 <div class="form-group clearfix">
			<label>邮件标题</label>
			<input type="text" name="email-title" value="<?php echo $options['email-title']?>" >
		</div>
		
	<div class="form-group clearfix">
			<label>邮件模板</label>
			<textarea name="email-template" rows="10" ><?php echo $options['email-template']?></textarea>
	</div>	
	
	<div class="button-group">
		<button type="submit" value="Submit" class="button">保存</button>
	</div>
		
</form>