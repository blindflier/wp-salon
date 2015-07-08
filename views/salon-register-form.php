<?php
/**
 * Template Name: 活动注册模板
 * User: jason
 * Date: 14-6-21
 * Time: 下午9:44
 */

$salon_id = (int)$_REQUEST['salon_id'];
$avail_salon_types = array('study-salon','bodhi-salon');

if ($salon_id>0){
    //根据id获取活动
    $salon = get_post($salon_id);
    if (!$salon  || $salon->post_type != 'salon' || $salon->post_status != 'publish' )
        $salon = null;
}else{
    //获取最新活动信息
    $type  = $_REQUEST['type'];
    if ($type == 'study' || $type == 'bodhi' ){
        $salon = get_latest_open_salon( $type . '-salon');
    }else{
        $salon = get_latest_open_salon('study-salon'); //缺省为学佛沙龙
        if (!$salon)
            $salon = get_latest_open_salon('bodhi-salon');
    }
}
$can_register = false;
if ($salon){
    $salon_terms = wp_get_post_terms($salon->ID,'salon_type');
    if (count($salon_terms) > 0  ){
        $salon_type = $salon_terms[0]->name;
        $salon_meta = get_post_meta($salon->ID);
        //检查是否开放报名
        $can_register = ( $salon_meta['opening'][0] == 1 );
    }
}
?>

<?php if($can_register) : ?>
<?php 
wp_enqueue_script('jquery-form');
wp_enqueue_script('salon-form');
?>
<div class="salon-register-container">
        <form class="salon-register-form" id="salon-register-form" action="" method="post">
            <section>
                <h1><?php echo $salon->post_title; ?>报名</h1>
                <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('salon-register');?>">
                <input type="hidden" name="salon_id" value="<?php echo $salon->ID; ?>">
                <input type="hidden" name="salon-register-form-action" value="<?php echo isset($attrs['action']) ? $attrs['action'] : 'register' ?>">
                
				<ul class="salon_meta lg-80">
						<li><span class="label">主题：</span> <span class="content"> <?php echo $salon_meta['subject'][0] ?></span> </li>
						<li><span class="label">时间：</span> <span class="content"> <?php echo $salon_meta['time'][0] ?></span> </li>
						<li class="last"><span class="label">地点：</span> <span class="content"> <?php echo $salon_meta['address'][0] ?></span> </li>
				</ul>
					
            </section>

		   <div class="lg-80">
           	<div class="row form-group">
                <label for="name" class="">姓名</label>
                <input type="text" class="" name="name"  required>
                <div class="form-tips">*请务必填写真实姓名</div>
            	</div>
           
            <div class="form-group row">
                <label  for="mobile">手机</label>
                <input type="text"  class="form-control" name="mobile" required>
                <div class="form-tips">*请填写真实手机号码<?php if (in_array($salon_terms[0]->slug, $avail_salon_types)) :?>，需凭回复短信免费入场<?php endif;?></div>
            </div>
            <!--   <div class="form-group row">
                <label for="email">邮箱</label>
                <input type="email"  class="form-control" name="email">
                <div class="form-tips">建议您填写邮箱地址方便我们发送活动通知</div>
            </div>
            
             <div class="row form-group">
                <label for="bodhi_name">法名</label>
                <input type="text" class="form-control" name="bodhi_name" maxlength="3">
            </div>
            
            
			  <div class="form-group row">
                <label for="gender">性别</label>
				<select name="gender"  class="form-control">
				  <option value="">不填</option>
				  <option value="男">男</option>
				  <option value="女">女</option>
				</select>
            </div>
          
			 <div class="form-group row">
                <label for="age">年龄</label>
                <input type="number"  class="form-control" name="age" min="10" max="90">
            </div>
            
             <div class="form-group row">
                <label for="from">信息来源</label>
                <input type="text"  class="form-control" name="from" >
            </div>
         
			  <div class="form-group row">
                <label for="qq">QQ</label>
                <input type="number"  class="form-control" name="qq" >
            </div> -->

            <div class="form-group row">
                    <button type="submit" name="salon-register-button" value="Submit" class="btn btn-salon">提交</button>
            </div>
            <input type="hidden" id="redirect_url" value="<?php echo $redirect_url;?>" >
			</div>
        </form>
</div>

<?php endif; ?>