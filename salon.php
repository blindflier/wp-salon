<?php
/*
Plugin Name: salon
Description: 南京菩提书院活动报名插件
Version: 0.1.3
Author: 慧颂
Email: 7415319@qq.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/
/* Copyright 2014  慧颂  (email : 7415319@qq.com)

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/
define(POST_TYPE_SALON,"salon");
define(POST_TYPE_SALON_REGISTER,"salon-register");

require_once  dirname(__FILE__) . '/includes/salon-register.php';

//初始化
add_action('init','salon_init');
//短代码
add_shortcode ('salon-register', 'salon_display_register_form');
//表单提交
add_action ('parse_request', 'salon_parse_request');
function salon_parse_request(){
	salon_checkForRegister();
}
//添加ajax请求处理钩子
add_action('wp_ajax_sendsms', 'ajax_sendsms');
add_action('wp_ajax_sendemail', 'ajax_sendemail');
add_action('wp_ajax_sign_in', 'ajax_sign_in');

add_action('wp_ajax_salon_export', 'ajax_salon_export');
add_action('wp_ajax_salon_export_all', 'ajax_salon_export_all');

add_action('wp_ajax_salon_toggle_opening', 'ajax_salon_toggle_opening');

function ajax_sendsms(){
	$ret = array('success'=>false);
	if (!wp_verify_nonce(trim($_POST['_wpnonce']),'send-sms-form')){
		$ret['message'] ='非法访问！'; 
		_json_die($ret);
	}
	$register_id = (int)trim($_POST['register-id']);
	$phone = trim($_POST['mobile']);
	if (!is_numeric($phone) || strlen($phone) != 11 ){
		$ret['message'] ='非法手机号码！' . $phone;
		_json_die($ret);
	}
	$options = get_option("salon_options");
	$body = array(
			'CorpID' => $options['sms-username'],
			'Pwd' => $options['sms-password'],
			'Mobile' => $phone,
			'Content' => trim($_POST['sms-text'])
	);
	
	$pattern = "/【.*?】$/";
	if (!preg_match($pattern,$body['Content'])){
		$ret['message'] ='短信内容不包含签名，无法发送！';
		_json_die($ret);
	}

	$url = 'http://120.132.132.102/WS/BatchSend2.aspx';
	$request = new WP_Http;
	$response = $request->request( $url, array( 'method' => 'POST', 'body' => $body) );
	//$response = array('response' => array('code'=>200 ),'body'=>1);
	if ( (int)$response['response']['code'] == 200){
		$code = (int)$response['body'];
		if ($code > 0){
			//取短信报告
			$ret['success'] = true;
			$ret['message'] = '短信发送成功！';
			//修改已发短信状态
			if ($register_id>0){
				update_post_meta($register_id, 'send_sms', 1);
				wp_publish_post($register_id);
			}
		}else{
			$ret['message'] = '短信发送失败！' . $response['body'] ;
		}
	}else{
		$ret['message']="http错误! code=" . $response['response']['code'];
	}
	_json_die($ret);
}
function ajax_sendemail (){
	$ret = array('success'=>false);
	if (!wp_verify_nonce(trim($_POST['_wpnonce']),'send-email-form')){
		$ret['message'] ='非法访问！'; 
		_json_die($ret);
	}
	$register_id = (int)trim($_POST['register-id']);
	//发送邮件
	if (wp_mail(trim($_POST['to']), 
				trim($_POST['email-title']),
				trim($_POST['email-text']))){
		$ret['success'] = true;
		$ret['message'] ='邮件发送成功！';
		//修改当前文章的邮件发送状态
		if ($register_id > 0){
			update_post_meta($register_id, 'send_mail', 1);
			wp_publish_post($register_id);
		}
	}else{
		$ret['message'] ='邮件发送失败！'  ;
	}
	_json_die($ret);
}	


function ajax_sign_in (){
	$ret = array('success'=>false);
	
	$register_id = (int)trim($_POST['id']);
	$sign_in = (int)trim($_POST['sign_in']);
	
	if(!update_post_meta($register_id, 'sign_in', $sign_in)){
		$ret['message'] ='更新签到状态失败' . $register_id . ',sign_in:' . $sign_in ;
	}else 
		$ret['success'] = true;
	_json_die($ret);
}

function ajax_salon_toggle_opening(){
	$ret = array('success'=>false);
	
	$salon_id = (int)trim($_POST['id']);
	$opening = (int)trim($_POST['opening']);
	
	if(!update_post_meta($salon_id, 'opening',$opening)){
		$ret['message'] ='更新沙龙报名状态失败' . $salon_id . ',opening:' . $opening ;
	}else
		$ret['success'] = true;
	_json_die($ret);
}


function ajax_salon_export(){
	require 'PHPExcel/PHPExcel.php';
	
	$ret = array('success'=>false);
	$post_id = (int)trim($_REQUEST['id']);
	if (! ($post = get_post($post_id))){
		$ret['message'] ='参数错误！'  ;
		_json_die($ret);
	}
	$salon_meta = get_post_meta($post_id);
	$registers = _get_salon_registers($post_id);
	
	//create excel
	$objPHPExcel = new PHPExcel();
	$objPHPExcel->setActiveSheetIndex(0);
	$sheet = $objPHPExcel->getActiveSheet();
	$sheet->setTitle('沙龙报名表-' . $post->post_title);
	$sheet->mergeCells( 'A1:I1' );
	$sheet->setCellValue('A1','学佛沙龙签到表');
	$sheet->mergeCells( 'A2:G2' );
	$sheet->setCellValue('A2','沙龙地址：');
	$sheet->mergeCells( 'H2:I2' );
	$sheet->setCellValue('H2',date("Y-m-d"));
	
	
	$sheet->mergeCells( 'A34:G34');
	$sheet->setCellValue('A34','主题：');
	$sheet->mergeCells( 'H34:I34' );
	$sheet->setCellValue('H34','主持人：' . $salon_meta['emcee'][0] );
	
	$sheet->mergeCells( 'A35:G35' );
	$sheet->setCellValue('A35','义工签到:');
	$sheet->mergeCells( 'H35:I35' );
	$sheet->setCellValue('H35','分享义工：' . $salon_meta['sharer'][0] );
	$sheet->mergeCells( 'A36:I36' );
	$sheet->setCellValue('A36','说明：1.请主持师兄在反面填写“现场情况说明”，（氛围、学员反应、问题、需总部支持的内容等）');
	$sheet->mergeCells( 'A37:I37' );
	$sheet->setCellValue('A37','2.如参加师兄姓名填写不清晰，请义工在备注栏填写清晰姓名，（方便核查沙龙次数，以便参加沙龙师兄顺利入班修学）');
	
	$sheet->getRowDimension(1)->setRowHeight(35);
	$sheet->getRowDimension(2)->setRowHeight(28);
	$sheet->getRowDimension(3)->setRowHeight(30);
	$sheet->getRowDimension(34)->setRowHeight(20);
	$sheet->getRowDimension(35)->setRowHeight(20);
	$sheet->getRowDimension(36)->setRowHeight(35);
	$sheet->getRowDimension(37)->setRowHeight(35);
	for($i=4;$i<34;$i++)
		$sheet->getRowDimension($i)->setRowHeight(20);
	
	
	$sheet->setCellValue('A3', '序号')
	->setCellValue('B3', '姓 名')
	->SetCellValue('C3', '法 名')
	->SetCellValue('D3', '性别')
	->SetCellValue('E3', '手 机')
	->SetCellValue('F3', '签 名')
	->SetCellValue('G3', '信息来源')
	->SetCellValue('H3', "菩提沙龙\n(是否报名)")
	->SetCellValue('I3', "报名书院\n(是否报名)");
	
	$sheet->getStyle("A1:I100")->getFont()->setName('宋体');
	$sheet->getStyle("A1:I100")->getAlignment()->setWrapText(true);
	$sheet->getStyle("A1:I100")->getFont()->setSize(11);
	$sheet->getStyle("A1")->getFont()->setSize(16);
	$sheet->getStyle("A2:I2")->getFont()->setSize(12);
	
	$sheet->getColumnDimension('A')->setWidth(5);
	$sheet->getColumnDimension('B')->setWidth(9);
	$sheet->getColumnDimension('C')->setWidth(8);
	$sheet->getColumnDimension('D')->setWidth(5);
	$sheet->getColumnDimension('E')->setWidth(14);
	$sheet->getColumnDimension('F')->setWidth(13);
	$sheet->getColumnDimension('G')->setWidth(8);
	$sheet->getColumnDimension('H')->setWidth(12);
	$sheet->getColumnDimension('I')->setWidth(12);
	
	
	$sheet->getStyle("A1:I100")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$sheet->getStyle("A1:I100")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
	
	$sheet->getStyle("A2")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$sheet->getStyle("H2")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	
	$sheet->getStyle("A34:A37")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	
	$sheet->getStyle("H34:H37")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$sheet->getStyle("B4:B35")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	
	$sheet->getPageMargins()->setBottom(0);
	
	$sheet->getStyle("A3:I33" )->applyFromArray(array(
			'borders' => array(
					'allborders'	=> array('style' => PHPExcel_Style_Border::BORDER_THIN),
			)
	));
	
	
	$i=4;
	foreach ($registers as $r){
		$meta = get_post_meta($r->ID);
		$sheet->setCellValue('A'.$i, $i-3)
			  ->setCellValue('B'.$i,$meta['name'][0])
			  ->setCellValue('C'.$i,$meta['bodhi_name'][0])
			  ->setCellValue('D'.$i,$meta['gender'][0])
			  ->setCellValue('E'.$i,$meta['mobile'][0])
			  ->setCellValue('G'.$i,$meta['from'][0]);
		
		$i=$i+1;
	}
	while($i<34){
		$sheet->setCellValue('A'.$i, $i-3);
		$i=$i+1;
	}
	
	
	header('Content-Type: application/vnd.ms-excel');
	header('Content-Disposition: attachment;filename="salon.xls"');
	header('Cache-Control: max-age=0');
	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
	return $objWriter->save('php://output');
}
function ajax_salon_export_all(){
	require 'PHPExcel/PHPExcel.php';
	
	$ret = array('success'=>false);
	$post_id = (int)trim($_REQUEST['id']);
	if (! ($post = get_post($post_id))){
		$ret['message'] ='参数错误！'  ;
		_json_die($ret);
	}
	$salon_meta = get_post_meta($post_id);
	$registers = _get_salon_registers($post_id);
	
	//create excel
	$objPHPExcel = new PHPExcel();
	$objPHPExcel->setActiveSheetIndex(0);
	$sheet = $objPHPExcel->getActiveSheet();
	$sheet->setTitle($post->post_title);
	$sheet->mergeCells( 'A1:E1' );
	$sheet->setCellValue('A1',$post->post_title);
	$sheet->mergeCells( 'A2:C2' );
	$sheet->setCellValue('A2','活动地址：');
	$sheet->mergeCells( 'D2:E2' );
	$sheet->setCellValue('D2','导出日期：' . date("Y-m-d"));
	

	$sheet->getRowDimension(1)->setRowHeight(35);
	$sheet->getRowDimension(2)->setRowHeight(28);
	$sheet->getRowDimension(3)->setRowHeight(30);
	
	
	
	$sheet->setCellValue('A3', '序号')
	->setCellValue('B3', '姓 名')
	->SetCellValue('C3', '法 名')
	->SetCellValue('D3', '性别')
	->SetCellValue('E3', '手 机');
	
	$max_rows = 3 + count($registers);
	$sheet->getStyle("A1:I".$max_rows)->getFont()->setName('宋体');
	$sheet->getStyle("A1:I".$max_rows)->getAlignment()->setWrapText(true);
	$sheet->getStyle("A1:I".$max_rows)->getFont()->setSize(11);
	$sheet->getStyle("A1")->getFont()->setSize(16);
	$sheet->getStyle("A2:I2")->getFont()->setSize(12);
	
	$sheet->getColumnDimension('A')->setWidth(10);
	$sheet->getColumnDimension('B')->setWidth(16);
	$sheet->getColumnDimension('C')->setWidth(14);
	$sheet->getColumnDimension('D')->setWidth(10);
	$sheet->getColumnDimension('E')->setWidth(16);


	
	
	$sheet->getStyle("A1:I".$max_rows)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$sheet->getStyle("A1:I".$max_rows)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
	
	$sheet->getStyle("A2")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$sheet->getStyle("D2")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	
	
	
	$sheet->getStyle("A3:E".$max_rows)->applyFromArray(array(
			'borders' => array(
					'allborders'	=> array('style' => PHPExcel_Style_Border::BORDER_THIN),
			)
	));
	
	
	$i=4;
	foreach ($registers as $r){
		$meta = get_post_meta($r->ID);
		$sheet->setCellValue('A'.$i, $i-3)
			  ->setCellValue('B'.$i,$meta['name'][0])
			  ->setCellValue('C'.$i,$meta['bodhi_name'][0])
			  ->setCellValue('D'.$i,$meta['gender'][0])
			  ->setCellValue('E'.$i,$meta['mobile'][0]);
		$sheet->getRowDimension($i)->setRowHeight(20);
		$i=$i+1;
	}
	
	
	header('Content-Type: application/vnd.ms-excel');
	header('Content-Disposition: attachment;filename="salon.xls"');
	header('Cache-Control: max-age=0');
	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
	return $objWriter->save('php://output');
}
//修改后台活动和报名列表页字段
add_action('manage_posts_custom_column', 'custom_admin_list_columns',10,2);
add_filter('manage_posts_columns', 'add_admin_list_columns',10,2);
//添加子菜单
add_action('admin_menu', 'salon_register_menu');
function salon_register_menu() {
	add_submenu_page( 'edit.php?post_type=salon-register', '短信发送状态', '短信发送状态', 'manage_categories', 'salon-sms-statistics', 'salon_sms_statistics_callback' );
	//设置选项页
	add_options_page('Salon Options', '活动插件','manage_options', __FILE__, 'salon_option_page');
	
}
function salon_sms_statistics_callback() {
	ob_start();
	include 'includes/sms-stat.php';
	ob_end_flush();
}
function salon_register_mail_callback() {
	ob_start();
	include  'includes/sendmail.php';
	ob_end_flush();
}

function salon_option_page(){
	ob_start();
	include  'includes/options.php';
	ob_end_flush();
}
		
function salon_init(){
    load_plugin_textdomain('salon', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    salon_register_post_type();
    salon_register_taxonomy_type();
    //注册js
    wp_deregister_script( 'jquery-form' );
    wp_register_script( 'jquery-form', plugins_url('js/jquery.form.js',__FILE__) ,
	    array( 'jquery' ), '3.51.0-2014.06.20', true );
    wp_register_script( 'salon-form', plugins_url('js/salon.form.js',__FILE__),
   	    array( 'jquery-form' ), '0.0.1', true );
   
}



function salon_register_post_type(){
    $labels = array(
        'name'          =>  '活动',
        'add_new'       =>  '新增活动',
        'add_new_item'  =>  '新增活动',
        'edit_item'     =>  '修改活动',
        'new_item'      =>  '新活动',
        'all_items'     =>  '所有活动',
        'view_item'     =>  '查看',
        'search_items'  =>  '搜索活动',
        'not_found'     =>  '没有发现活动',
        'not_found_in_trash'    =>  '回收站里没有发现活动'
	);

    $args = array(
        'labels'     => $labels,
        'public'    => true,
        'menu_position' => 4,
        'supports' => array('title','editor','thumbnail','comments')
	);
    register_post_type(POST_TYPE_SALON,$args);


    $labels = array(
        'name'          =>  '活动报名信息',
        'add_new'       =>  '新增活动报名信息',
        'add_new_item'  =>  '新增活动报名信息',
        'edit_item'     =>  '修改活动报名信息',
        'new_item'      =>  '新活动报名信息',
        'all_items'     =>  '所有活动报名信息',
        'view_item'     =>  '查看',
        'search_items'  =>  '搜索活动报名信息',
        'not_found'     =>  '没有发现活动报名信息',
        'not_found_in_trash'    =>  '回收站里没有发现活动报名信息'
	);

    $args = array(
        'labels'     => $labels,
        'show_ui'    => true,
        'show_in_menu' => true,
        'menu_position' => 5
	);
    register_post_type(POST_TYPE_SALON_REGISTER,$args);
}
function salon_register_taxonomy_type(){
    $labels = array(
        'name'          =>  '活动类型',
        'singular_name' =>  '活动类型',
        'search_items'  =>  '搜索活动类型',
        'popular_items' =>  '热门活动类型',
        'all_items'     =>  '所有活动类型',
        'edit_item'     =>  '修改活动类型',
        'update_item'   =>  '更新活动类型',
        'add_new_item'  =>  '新增活动类型',
        'new_item_name' =>  '新增活动类型',
        'menu_name'     =>  '活动类型',
        'add_or_remove_items'       =>  '添加或删除活动类型',
        'choose_from_most_used'       =>  '从热门活动类型中选择'
	);

    $args = array(
        'labels'     => $labels,
        'public'    => true,
        'hierarchical' => true
	);
    register_taxonomy('salon_type','salon',$args);
}



function salon_display_register_form($atts=array(), $content=null) {
	$form = WP_PLUGIN_DIR . '/' . basename(dirname(__FILE__)) . '/views/' ;
	if (isset($atts['form']))
		$form .= $atts['form'] . '.php';
	else
		$form .= 'salon-register-form.php' ;
	if (isset($atts['redirect']))
		$redirect_url = $atts['redirect'];
	
    ob_start();
    include $form;
    return ob_get_clean();
}



function add_admin_list_columns($columns,$post_type){
    switch($post_type){
        case POST_TYPE_SALON:
            $my_cols = array(
                'salon_type'    => __('Salon Type','salon'),
                'subject'    => __('Salon Subject','salon'),
                'time'    => __('Salon Time','salon'),
                'address' => __('Salon Address','salon'),
                'emcee' => __('Salon Emcee','salon'),
                'class' => __('Salon Class','salon'),
                'opening' => __('Salon Status','salon'),
                'export' => __('Salon Export','salon')
			);
            return array_merge($columns,$my_cols);
            break;
        case POST_TYPE_SALON_REGISTER:
	        	$my_cols = array(
		        	'name'    	=> __('Salon Register Name','salon'),
		        	'mobile' => __('Salon Register Mobile','salon'),
		        	'email' 		=> __('Salon Register Email','salon'),
		        	'send_sms'  => __('Salon Register Send SMS','salon'),
		        	'send_mail' => __('Salon Register Send Email','salon'),
		        	'sign_in' => __('Salon Sign In','salon')
				);
	        	return array_merge($columns,$my_cols);
       	 	break;
    }

    return $columns;

}
function custom_admin_list_columns($column,$post_id){
	$post = get_post($post_id);
	$post_meta = get_post_meta($post_id);
	
	switch($post->post_type){
		case POST_TYPE_SALON:
			switch ($column){
				case 'salon_type':
					$salon_terms  = wp_get_post_terms($post_id,'salon_type');
					if (count($salon_terms) == 1){
						echo  $salon_terms[0]->name ;
					}
					break;
				/*case 'opening' :
					$col = $post_meta[$column];
					if ($col)
						echo $col[0] == true ? __('Salon Status Opened','salon') :
						__('Salon Status Closed','salon') ;
					break;*/
				case 'opening':
					$col = $post_meta[$column];
					$html = '<input class="salon-toggle-opening" type="checkbox" data-target="%s"  data-id="%d" data-action="%s" ' . ($post_meta['opening'][0] ? 'checked="checked"' : '') . '>开放报名';
					echo sprintf($html,admin_url('admin-ajax.php'),$post_id,"salon_toggle_opening");
					break;
				case 'export':
					$html = '<a class="salon-export button" data-target="%s"  data-id="%d" data-action="%s">导出报名表</a>';
					echo sprintf($html,admin_url('admin-ajax.php'),$post_id,"salon_export_all");
					
					$html = '<br/><a class="salon-export button" data-target="%s"  data-id="%d" data-action="%s">导出签到表</a>';
					echo sprintf($html,admin_url('admin-ajax.php'),$post_id,"salon_export");
					break;
				default :
					$col = $post_meta[$column];
					if ($col)
						echo $col[0];
					break;
			}
			break;
		case POST_TYPE_SALON_REGISTER:
			$options = get_option("salon_options"); 
			switch ($column){
				case 'send_sms':
					if ($post_meta['send_sms'] && $post_meta['send_sms'][0])
						echo '<div id="sms-btn-'. $post_id . '">已发</div>';
					else{
						$sms =replace_sms_template($options['sms-template'],$post_meta);
						$html = '<div id="sms-btn-%d"><a class="open-window open-sms-window button" data-id="%d" data-mobile="%s" data-sms-text="%s" href="#sms-window">发送短信</a></div>';
						echo sprintf($html,$post_id,$post_id,$post_meta['mobile'][0],$sms);
					}
					break;
				case 'send_mail':
					if ($post_meta['email'] && $post_meta['email'][0]){
						if($post_meta['send_mail'] && $post_meta['send_mail'][0])
							echo '<div id="email-btn-'. $post_id . '">已发</div>';
						else {
							$title =replace_sms_template($options['email-title'],$post_meta);
							$content =replace_sms_template($options['email-template'],$post_meta);
							$html = '<div id="email-btn-%d"><a class="open-window open-email-window button" data-id="%d" data-to="%s" data-email-title="%s" data-email-text="%s" href="#email-window">发送邮件</a></div>';
							echo sprintf($html,$post_id,$post_id,$post_meta['email'][0],$title,$content);
							
							//$url = "edit.php?post_type=salon-register&page=salon-register-send-mail&register_id=" . $post_id;
							//echo '<button onclick="window.location.href=\'' . $url .  '\';return false;" >发送邮件</button>';
						}
					};
					break;
				case 'sign_in':
					$html = '<input class="sign_in" type="checkbox" data-target="%s"  data-id="%d" data-action="%s" ' . ($post_meta['sign_in'][0] ? 'checked="checked"' : '') . '>';
					echo sprintf($html,admin_url('admin-ajax.php'),$post_id,"sign_in");
					break;
				default :
					$col = $post_meta[$column];
					if ($col)
						echo $col[0];
					break;
			}
			break;

	}



}

/**
 * 后台页面添加需要的js和css
 */
function salon_screen($current_screen){
	
	if($current_screen->id == 'settings_page_salon/salon' || $current_screen->post_type == "salon-register"  ){
		wp_enqueue_style( 'salon-admin',  
			plugins_url('css/salon.admin.css',__FILE__),array(),'1.0.0');
	}
	if (($current_screen->post_type == "salon-register" || 
		 $current_screen->post_type == "salon")  &&
		$current_screen->base == "edit"  ){
			wp_enqueue_script( 'jquery-leanModal', plugins_url('js/jquery.leanModal.js',__FILE__),
				array( 'jquery' ), '1.1', true );
			wp_enqueue_script('salon-admin', plugins_url('js/salon.admin.js',__FILE__),
				array( 'jquery-form' ), '1.0', true );
	};
	
}
add_action( 'current_screen', 'salon_screen' );
/**
 * 插入发送email和短信的hmtl代码，方便js调用
 */
function insert_email_text_window(  ) {
	$current_screen = get_current_screen();
	//var_dump($current_screen);
	if ($current_screen->post_type == "salon-register" && 
		$current_screen->base == "edit" ){
		//短信窗口
		ob_start();
			require "includes/sms-email-temp.php";
		ob_end_flush();
	}
}
add_action( 'in_admin_header', 'insert_email_text_window' );

/**
 * 从meta数组获取活动id
 */
function get_salon_id_from_meta($salon_id){
	$salon_id = is_numeric($salon_id) ?   (int)$salon_id  :  unserialize($salon_id);
	if (is_array($salon_id) && count($salon_id) > 0)
		    	$salon_id = (int)$salon_id[0];
	else
		    if (!is_int($salon_id))  $salon_id = 0;
	return $salon_id;
}
/**
 * 替换短信和email模板 
 */
function replace_sms_template($template,$register_meta){
	$salon_id = get_salon_id_from_meta($register_meta['salon_id'][0]);
	$salon_meta = get_post_meta($salon_id);
	$template = str_replace('{姓名}', $register_meta['name'][0], $template);
	$template = str_replace('{活动时间}', $salon_meta['time'][0], $template);
	$template = str_replace('{活动地点}', $salon_meta['address'][0], $template);
	$template = str_replace('{活动主题}', $salon_meta['subject'][0], $template);
	return $template;
}
 
/**
 * 获取最新的可以报名的活动
 */
function get_latest_open_salon($type){
    $query = new WP_Query();
    $posts = $query->query(array(
        'post_type' => 'salon',
        'meta_query' => array(
            array('key'=>'opening','value'=>1)
		),
        'tax_query' => array(
          array('taxonomy'=>'salon_type','field'=>'slug','terms'=>array($type))
		),
        'posts_per_page' => 1
	));
    if (count($posts) == 1)
        return $posts[0];
    return null;
}

function _get_salon_registers($salon_id){
	$query = new WP_Query();
	$posts = $query->query(array(
			'post_type' => 'salon-register',
			'post_status' => 'publish',
			'posts_per_page'=> -1,
			'meta_query' => array(
				array('key'=>'salon_id','value'=>serialize(array((string)$salon_id)))
			)
	));
	return $posts;
}


