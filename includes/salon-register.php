<?php
function salon_checkForRegister() {

	if (!isset($_POST['salon-register-form-action'])) return;

	$nonce = $_REQUEST['_wpnonce'];
	if (!wp_verify_nonce($nonce,'salon-register')){
		status_header( 500 );
		die('_nonce验证错误');
	}
	
	switch($_POST['salon-register-form-action']){
		case 'training-register':
			_training_register();
		case 'register':
			_salon_register();
			break;
		case 'modify':
			_salon_modify();
			break;
		default:
			status_header( 500 );
			die(json_encode(array('error'=>'表单参数错误')));
	}
}

function _json_die($data){
	if (is_string($data))
		die(json_encode(array('message'=>$data)));
	else
		die(json_encode($data));
}

function _check_data(){
	$invalid = array();
	//salon_id
	$post = get_post($_POST['salon_id']);
	if(!$post || $post->post_type !== 'salon'){
		$invalid[] = '参加活动';
	}
	//姓名
	if (empty($_POST['name']) || strlen($_POST['name']) < 4 ||
		preg_match('/^\d+$/', $_POST['name'])){
			$invalid[] = '姓名';
	}
	//法名
	if (!empty( $_POST['bodhi_name']) && strlen($_POST['name']) < 4){
		$invalid[] = '法名';
	}
	//电话号码
	if (empty($_POST['mobile']) || !preg_match('/^\d+$/', $_POST['mobile'])){
		$invalid[] = '手机';
	}else{
		$len = strlen($_POST['mobile']);
		if ($len != 11)
			$invalid[] = '手机';
	}
	//QQ号码
	if (!empty($_POST['qq'])){
		if ( !preg_match('/^\d+$/', $_POST['qq'])){
			$invalid[] = 'QQ';
		}else{
			$len = strlen($_POST['qq']);
			if ($len < 4)
				$invalid[] = 'qq';
		}
	}
	//邮箱
	if (!empty($_POST['email']) && !is_email($_POST['email']) ){
		$invalid[] = '电子邮箱';
	}
	return $invalid;
}

function _get_register_post($salon_id,$name,$mobile,$post_type = POST_TYPE_SALON_REGISTER){
	$query = new WP_Query();
	$posts = $query->query(array(
			'post_type' => $post_type,
			'post_status' => array('public','pending'),
			'meta_query' => array(
				array('key'=>'salon_id','value'=>serialize(array($salon_id))),
				array('key'=>'name','value'=>$name),
				array('key'=>'mobile','value'=>$mobile)
			),
			'posts_per_page' => 1
	));
	if (count($posts) > 0)
		return $posts[0];
	return false;
} 
function _get_salon_register_count($salon_id){
	global $wpdb;
	
	$sql = "select count(*) from $wpdb->posts p left join $wpdb->postmeta pm " . 
		  "on p.ID = pm.post_id  where p.post_type='" . POST_TYPE_SALON_REGISTER . 
		  "' and meta_key='salon_id' and meta_value = '$salon_id'";
	
	
	return $wpdb->get_var($sql);
}

function _salon_register(){
	
	$invalid = _check_data();
	$result = array();
	
	if(count($invalid) > 0 ){
		_json_die("以下字段输入错误：" . implode(",", $invalid));
	}
	$salon = get_post(trim($_POST['salon_id'])); //前面已经验证过salon_id的有效性
	$salon_meta= get_post_meta($salon->ID);
	if (!$salon_meta['opening'][0]){ //沙龙已经关闭报名
		_json_die('沙龙已经关闭报名');
	}
	$max_audience = (int)$salon_meta['max_audience'][0];
	
	$post_title = $salon->ID . '-'. $salon->post_title ;
	//检查姓名和电话是否存在
	$post = _get_register_post(trim($_POST['salon_id']), trim($_POST['name']), trim($_POST['mobile']));
	if ($post)
		_json_die('您的姓名和电话已经存在，请不要重复报名！');
	//插入post
	$post_id = wp_insert_post( array(
			'post_type' => POST_TYPE_SALON_REGISTER ,
			'post_status' => 'pending',
			'post_title' => $post_title,
			'post_content' => '') );
	if ($post_id){
		$success = true;
		//插入meta
		if ( !add_post_meta ($post_id, 'salon_id', array($_POST['salon_id']))){
			$success = false;
		}else{
			$keys = array('name','bodhi_name','mobile','gender','age','email','from','qq');
			foreach ($keys as $k){
				if (!empty($_POST[$k]) && !add_post_meta ($post_id, $k, $_POST[$k])){
					$success = false;
					break;
				}
			}
		}
		
		if (!$success){
			wp_delete_post($post_id,true);
			_json_die('插入meta数据错误');
		}
		wp_update_post(array('ID'=>$post_id, 'post_title' => $post_title . '(' . $_POST['name'] . ')'));
		
		$ret = array('id'=>$post_id);
		if ($max_audience > 0 &&  _get_salon_register_count($salon->ID) > $max_audience )
			$ret['message'] = '本期沙龙报名人数已满，您的信息已经被保存，请等待短信或邮件通知!';
		else
			$ret['message'] = '您的信息已经被保存，请留意您的短信或邮件通知!'; 
		$ret['redirect'] = home_url() . '/?p=' . $_POST['salon_id'];
		_json_die($ret);
	}else{
		_json_die('wp_insert_post 失败');
	}
	
}



function _check_training_data(){
	$invalid = array();
	//salon_id
	$post = get_post($_POST['salon_id']);
	if(!$post || $post->post_type !== 'salon'){
		$invalid[] = '参加活动';
	}
	//班级序号
	if (empty($_POST['classseq']) || intval($_POST['classseq']) <=0 ||
		intval($_POST['classseq']) > 200   ){
		$invalid[] = '班级编号';
	}
	//姓名
	if (empty($_POST['name']) || strlen($_POST['name']) < 4 ||
		preg_match('/^\d+$/', $_POST['name'])){
		$invalid[] = '姓名';
	}
	//法名
	if (!empty( $_POST['bodhi_name']) && strlen($_POST['name']) < 4){
		$invalid[] = '法名';
	}
	//电话号码
	if (empty($_POST['mobile']) || !preg_match('/^\d+$/', $_POST['mobile'])){
		$invalid[] = '手机';
	}else {
		$len = strlen($_POST['mobile']);
		if ($len != 11)
			$invalid[] = '手机';
	}
	//邮箱
	if (empty($_POST['email']) || !is_email($_POST['email']) ){
		$invalid[] = '电子邮箱';
	}
	//身份证
	//if (empty($_POST['idcode']) || !preg_match('/^\d{17}\w$/', $_POST['idcode'])){
	//	$invalid[] = '身份证';
	//}
	//义工岗位
	//if (empty($_POST['position']) ||
	//	($_POST['position']=="其它" && empty($_POST['other_position']))){
	//	$invalid[] = '义工岗位';
	//}
	return $invalid;
}

function _training_register(){

	$invalid = _check_training_data();
	$result = array();

	if(count($invalid) > 0 ){
		_json_die("以下字段输入错误：" . implode(",", $invalid));
	}
	$salon = get_post(trim($_POST['salon_id'])); //前面已经验证过salon_id的有效性
	$salon_meta= get_post_meta($salon->ID);
	if (!$salon_meta['opening'][0]){ //沙龙已经关闭报名
		_json_die('已经关闭报名');
	}
	$max_audience = (int)$salon_meta['max_audience'][0];

	$post_title = $salon->ID . '-'. $salon->post_title ;
	//检查姓名和电话是否存在
	$post = _get_register_post(trim($_POST['salon_id']), trim($_POST['name']), trim($_POST['mobile']),POST_TYPE_TRAINING_REGISTER);
	if ($post)
		_json_die('您的姓名和电话已经存在，请不要重复报名！');
	//插入post
	$post_id = wp_insert_post( array(
		'post_type' => POST_TYPE_TRAINING_REGISTER ,
		'post_status' => 'pending',
		'post_title' => $post_title,
		'post_content' => '') );
	if ($post_id){
		$success = true;
		//插入meta
		if ( !add_post_meta ($post_id, 'salon_id', array($_POST['salon_id']))){
			$success = false;
		}else{
			$keys = array('area','city','classtype','classseq',
				          'name','bodhi_name','gender','mobile',
						  'idcode','email','position','other_position',
				'food','lodge');
			foreach ($keys as $k){
				if (!empty($_POST[$k]) && !add_post_meta ($post_id, $k, $_POST[$k])){
					$success = false;
					break;
				}
			}
		}

		if (!$success){
			wp_delete_post($post_id,true);
			_json_die('插入meta数据错误');
		}
		wp_update_post(array('ID'=>$post_id, 'post_title' => $post_title . '(' . $_POST['name'] . ')'));

		$ret = array('id'=>$post_id);
		if ($max_audience > 0 &&  _get_salon_register_count($salon->ID) > $max_audience )
			$ret['message'] = '报名人数已满，您的信息已经被保存，请等待短信或邮件通知!';
		else
			$ret['message'] = '您的信息已经被保存，请留意您的短信或邮件通知!';
		_json_die($ret);
	}else{
		_json_die('wp_insert_post 失败');
	}

}