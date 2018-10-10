<?php 
header('Content-type:text/html;charset=utf-8');
include './baidu_transapi.php';
$GLOBALS['header'] = "header";           //片头
$GLOBALS['footer'] = "footer";           //片尾
$GLOBALS['logo'] = "logo";               //logo
$GLOBALS['video'] = "video";             //需要处理的视频目录
$GLOBALS['mp3'] = "mp3";                 //背景音乐  
$GLOBALS['video_out'] = "video_out";     //输出目录
$GLOBALS['cut_max_time'] = 600;            //满足需要分段的视频时长
$GLOBALS['header_time'] = 4;               //需要剪切掉视频前4秒
$GLOBALS['footer_time'] = 3;               //需要剪切掉视频后3秒
$GLOBALS['cut_video_time'] = 300;          //视频分段时间

/**
 * 防止在cmd下乱码，转换字符编码
 * @Author   junmoxiao
 * @DateTime 2018-10-03T14:38:23+0800
 * @param    [string]                   $str [description]
 * @return   [type]                        [description]
 */
function strTo($str){
	return iconv("utf-8", 'gbk', $str);
} 

/**
 * [getNeed 获取视频需要的背景音乐/片头/片尾/logo]
 * @param  [type] $classify [分类名]
 * @param  [type] $para [全局变量参数如footer]
 * @param  [type] $hf   [是否是片头片尾]
 * @return [type]       [description]
 */
function getNeed($classify,$para,$hf=0){
	$reArr = scandir($GLOBALS[$para].'/'.$classify);
	unset($reArr[0],$reArr[1]);
	if(count($reArr)>0){
		$count = count($reArr);
		$k = rand(2,$count+1);
		$path = $GLOBALS[$para]."/".$classify.'/'.$reArr[$k];
		$filename = $reArr[$classify][$k];
	}else{
		$reArr = scandir($GLOBALS[$para].'/common');
		unset($reArr[0],$reArr[1]);
		$count = count($reArr);
		$k = rand(2,$count+1);
		$path = $GLOBALS[$para]."/common/".$reArr[$k];
		$filename = $reArr[$k];
	}
	if($hf==1&&strpos($filename, '.mp4')){
		$cmd = 'ffmpeg -i '.$path.' -q 0 '.str_replace('.mp4', '.mpg', $path);
		shell_exec($cmd);
		$res['path'] = str_replace('.mp4', '.mpg', $path);
		$res['filename'] = str_replace('.mp4', '.mpg', $reArr[$k]);
		unlink($path);
	}else{
		$res['path'] = $path;
		$res['filename'] = $reArr[$k];
	}
	return $res;
}

/**
 * 获取配置信息
 * @Author   junmoxiao
 * @DateTime 2018-10-03T14:38:05+0800
 * @return   [type]                   [返回视频分类对应的配置信息]
 */
function getConfig(){
	$cof = explode(PHP_EOL, file_get_contents('classify.txt'));
	$config = array();
	foreach ($cof as $k => $v) {
		$v = explode(";", $v);
		$config[$v[0]]['classify'] = $v[0];
		$config[$v[0]]['cut_max_time'] = $v[1]?$v[1]:$GLOBALS['cut_max_time'];
		$config[$v[0]]['header_time'] = $v[2]?$v[2]:$GLOBALS['header_time'];
		$config[$v[0]]['footer_time'] = $v[3]?$v[3]:$GLOBALS['footer_time'];
		$config[$v[0]]['cut_video_time'] = $v[4]?$v[4]:$GLOBALS['cut_video_time'];
	}
	return $config;
}

/**
 * [getV 获取视频信息]
 * @Author   junmoxiao
 * @DateTime 2018-10-03T14:40:02+0800
 * @param    [string]                   $path [视频地址]
 * @return   [array]                         [视频信息（视频大小，尺寸，时长等]
 */
function getV($path){
	echo strTo('开始获取').$path.strTo('视频信息').PHP_EOL;
	$cmd = "ffprobe -v quiet -print_format json -show_format -show_streams -i ".$path;
	$v = json_decode(shell_exec($cmd),true);
	$result['duration'] = $v['format']['duration'];
	$result['size'] = $v['format']['size'];
	$result['width'] = $v['streams'][0]['width'];
	$result['height'] = $v['streams'][0]['height'];
	return $result;
}

/**
 * [file_name_format 文件名格式，转中文]
 * @Author   junmoxiao
 * @DateTime 2018-10-04T13:55:34+0800
 * @param    [type]                   $classify [目录名]
 * @param    [type]                   $fileName [文件名]
 * @return   [type]                             [description]
 */
function file_name_format($classify,$fileName){
	$filename = iconv('gbk', 'utf-8', $fileName);
	$filename = explode(".mp4", $filename);
	$filename = $filename[0];
	$str = array('/','?','\\',':','"','|','<','>','*','“','”','！','？',' ','-','——');
	foreach ($str as $k => $v) {
		$filename = str_replace($v, '', $filename);
	}
	$res = translate($filename,'auto','zh');
	$dstName = iconv('utf-8', 'gbk', $res['trans_result'][0]['dst']);
	foreach ($str as $k => $v) {
		$dstName = str_replace($v, '', $dstName);
	}
	if($dstName){
		$f = '"'.getcwd().'\\'.$GLOBALS['video'].'\\'.$classify.'\\'.$fileName.'"';
		$of ='"'.getcwd().'\\'.$GLOBALS['video'].'\\'.$classify.'\\'.$dstName.'.mp4"';
		$cmd = 'move '.$f." $of";
		shell_exec($cmd);
	}else{
		$dstName = $dstName?$dstName:$fileName;	
	}
	return $dstName.".mp4";
}

/**
 * [cutVideo 视频剪切]
 * @param  [type] $classify [分类名]
 * @param  [type] $filename [文件民]
 * @return [type]           [description]
 */
function cutVideo($classify,$filename){
	if(!is_dir('cache')){mkdir('cache');}
	$vpath = $GLOBALS['video']."/".$classify."/".$filename;         //视频路径
	$outpath = $GLOBALS['video_out']."/".$classify."/".$filename;   //输出路径 
	$config = getConfig();
	$videoInfo = getV($vpath);
	$start_time = $config[$classify]['header_time'];
	$end_time = $videoInfo['duration']-$config[$classify]['header_time']-$config[$classify]['footer_time']; 
	$mp3Arr = getNeed($classify,$GLOBALS['mp3']);
	$mp3 = $mp3Arr['path'];
	//判断是否需要分段
	if($videoInfo['duration'] >= $config[$classify]['cut_max_time'] && $config[$classify]['cut_max_time']!=0){

	}else{
		//去除片头片尾加音乐
		echo "*********************".PHP_EOL.PHP_EOL.strTo("开始去除片头片尾").PHP_EOL.PHP_EOL."*********************".PHP_EOL;
		$cmd = 'ffmpeg -i '.$vpath.' -stream_loop -1 -i '.$mp3.' -map 0:v -ss '.$start_time.' -t '.$end_time.' -c:v h264 -map 1:a -c:a copy cache/'.$filename;
		shell_exec($cmd);
		//转换成mpg
		echo "*********************".PHP_EOL.PHP_EOL.strTo("转换成mpg").PHP_EOL.PHP_EOL."*********************".PHP_EOL;
		$cmd1 = 'ffmpeg -i '.'cache/'.$filename.' -q 0 cache/'.$filename.'.mpg'; 
		shell_exec($cmd1);
		//获取片头
		echo "*********************".PHP_EOL.PHP_EOL.strTo("开始获取片头").PHP_EOL.PHP_EOL."*********************".PHP_EOL;
		$headerArr = getNeed($classify,$GLOBALS['header'],1);
		$header = $headerArr['path'];
		//获取片尾
		echo "*********************".PHP_EOL.PHP_EOL.strTo("开始获取片尾").PHP_EOL.PHP_EOL."*********************".PHP_EOL;
		$footerArr = getNeed($classify,$GLOBALS['footer'],1);
		$footer = $footerArr['path'];
		//添加片头片尾（合成视频）
		echo "*********************".PHP_EOL.PHP_EOL.strTo("开始添加片头片尾（合成视频）").PHP_EOL.PHP_EOL."*********************".PHP_EOL;
		$cmd2 = 'ffmpeg -i "concat:'.$header.'|cache/'.$filename.'.mpg|'.$footer.'" -q:a 10 cache/out'.$filename;
		shell_exec($cmd2);
		//获取logo
		echo "*********************".PHP_EOL.PHP_EOL.strTo("开始添加logo").PHP_EOL.PHP_EOL."*********************".PHP_EOL;
		$logoArr = getNeed($classify,$GLOBALS['logo']);
		$logo = $logoArr['path'];
		$cmd2 = 'ffmpeg -i "cache/out'.$filename.'" -i '.$logo.' -filter_complex "[1:v][0:v]scale2ref=150:150 [wm][base];[base][wm]overlay=main_w-overlay_w-10:main_h-overlay_h-10" '.$GLOBALS['video_out'].'/'.$classify.'/'.$filename;
		shell_exec($cmd2);
	}
	$cache = scandir('cache');
	foreach ($cache as $k => $v) {
		if($v!='.'&&$v!='..'){
			unlink('cache/'.$v);
		}
	}
	if(is_file($GLOBALS['video_out'].'/'.$classify.'/'.$filename)){
		unlink($vpath);
	}
}
$path = scandir($GLOBALS['video']);
foreach ($path as $k => $v) {
	if($v!='.'&&$v!='..'){
		$file = scandir($GLOBALS['video'].'/'.$v);
		echo strTo("开始处理分类------->").$v.PHP_EOL;
		foreach ($file as $key => $value) {
			if($value!='.'&&$value!='..'){
				$dest = file_name_format($v,$value);
				cutVideo($v,$dest);
			}
		}
	}
}
?>