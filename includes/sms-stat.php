<?php
$options = get_option("salon_options");
$url = 'http://120.132.132.102/WS/GetReportSMS.aspx?CorpID=' . $options['sms-username'] . '&Pwd=' . $options['sms-password'];
//var_dump($url);
$request = new WP_Http;
$response = $request->request( $url);
//$response = array('response'=>array('code'=>200));
?>
<div class="wrap">
	<h2>短信发送统计报告</h2>
	<div class="info">
		如果遇到问题，可<a target="_blank" href="http://120.132.132.102">直接登录短信平台查看</a><br/>
		用户名: blindflier 密码: xfsl6688
	</div>
<?php if ((int)$response['response']['code'] != 200) :?>
	<h2>获取短信状态错误！</h2>
<?php else :?>
	<table class="sms">
		<thead>
		<tr>
			<th>编号</th><th>手机号码</th><th>发送时间</th><th>状态</th><th>状态报告</th><th>报告时间</th>
		</tr>
		</thead>
		<tbody>
<?php 
	$text=trim($response['body'],'|') ;
	if (strlen($text) > 0 ){
		$reports= array_slice(explode('|||',trim($text,'|')),0,30);
	    foreach($reports as $row){
			$data = explode('$$$$$',$row); ?>
			<tr class="<?php echo ($data[3] == 1 ? "success" : 'failed') ?>">	
			<?php foreach ($data as $d) :?>				 
				<td><?php echo $d?></td>
			<?php endforeach;?>
			</tr>
	<?php }}?>
	</tbody>
	</table>
<?php endif ;?>
</div>	
