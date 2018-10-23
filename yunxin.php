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
 * [getNeed 随机获取对应分类的视频需要的部分]
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
		$filename = $reArr[$k];
	}else{
		$reArr = scandir($GLOBALS[$para].'/common');
		unset($reArr[0],$reArr[1]);
		$count = count($reArr);
		if($count>0){
			$k = rand(2,$count+1);
			$path = $GLOBALS[$para]."/common/".$reArr[$k];
			$filename = $reArr[$k];
		}else{
			$path = '';
			$filename = '';
		}
	}
	$type = pathinfo($path,PATHINFO_EXTENSION);
	if($hf==1&&$type=='mp4'){
		$cmd = 'ffmpeg -i '.$path.' -q 0 '.str_replace('.mp4', '.mpg', $path);
		shell_exec($cmd);
		$res['path'] = isset($filename)?str_replace('.mp4', '.mpg', $path):'';
		$res['filename'] = str_replace('.mp4', '.mpg', $filename);
		unlink($path);
	}else{
		$res['path'] = isset($filename)?$path:'';
		$res['filename'] = $filename;
	}
	return $res;
}

/**
 * [getfooter 获取片头对应的片尾或随即片尾]
 * @param  [type] $classify   [分类]
 * @param  string $headerName [视频片头文件名]
 * @param  string $headerPath [视频片头路径]
 * @return [type]             [description]
 */
function getfooter($classify,$headerName='',$headerPath=''){
	if($headerName){
		if(is_file(str_replace('header', 'footer', $headerPath))){
			$res['path'] = str_replace('header', 'footer', $headerPath);
		}else if(is_file(str_replace('.mpg', '.mp4', str_replace('header', 'footer', $headerPath)))){
			$cmd = 'ffmpeg -i '.str_replace('.mpg', '.mp4', str_replace('header', 'footer', $headerPath)).' -q 0 '.str_replace('.mp4', '.mpg', str_replace('.mpg', '.mp4', str_replace('header', 'footer', $headerPath)));
			shell_exec($cmd);
			unlink(str_replace('.mpg', '.mp4', str_replace('header', 'footer', $headerPath)));
			$res['path'] = str_replace('header', 'footer', $headerPath);
		}else{
			$res = getNeed($classify,'footer',1);
		}
	}else{
		$res = getNeed($classify,'footer',1);
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
		$config[$v[0]]['cut_max_time'] = isset($v[1])&&((int)$v[1]>0||isset($v[1])&&$v[1]=='0')?$v[1]:$GLOBALS['cut_max_time'];
		$config[$v[0]]['header_time'] = isset($v[2])&&((int)$v[2]>0||isset($v[2])&&$v[2]=='0')?$v[2]:$GLOBALS['header_time'];
		$config[$v[0]]['footer_time'] = isset($v[3])&&((int)$v[3]>0||isset($v[3])&&$v[3]=='0')?$v[3]:$GLOBALS['footer_time'];
		$config[$v[0]]['cut_video_time'] = isset($v[4])&&((int)$v[4]>0||isset($v[4])&&$v[4]=='0')?$v[4]:$GLOBALS['cut_video_time'];
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
	$result['duration'] = $v['streams'][0]['duration'];
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
	$type = pathinfo($GLOBALS['video'].'/'.$classify.'/'.$fileName,PATHINFO_EXTENSION);
	$filename = explode(".".$type, $filename);
	$filename = $filename[0];
	$str = array('/','?','\\',':','"','|','<','>','*','“','”','！','？',' ','-','——','&','，');
	// foreach ($str as $k => $v) {
	// 	$filename = str_replace($v, '', $filename);
	// }
	$res = translate($filename,'auto','zh');
	$dstName = iconv('utf-8', 'gbk', $res['trans_result'][0]['dst']);
	foreach ($str as $k => $v) {
		$dstName = str_replace(strTo($v), '', $dstName);
	}
	if($dstName){
		$f = '"'.getcwd().'\\'.$GLOBALS['video'].'\\'.$classify.'\\'.$fileName.'"';
		$of ='"'.getcwd().'\\'.$GLOBALS['video'].'\\'.$classify.'\\'.$dstName.'.'.$type.'"';
		$cmd = 'move '.$f." $of";
		shell_exec($cmd);
	}else{
		$dstName = $dstName?$dstName:$fileName;	
	}
	return $dstName.".".$type;
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
	//获取配置文件
	$config = getConfig();
	//获取视频信息
	$videoInfo = getV($vpath);
	$start_time = $config[$classify]['header_time'];
	$end_time = $videoInfo['duration']-$config[$classify]['header_time']-$config[$classify]['footer_time']; 

	//获取音乐
	echo "*********************".PHP_EOL.PHP_EOL.strTo("开始获取音乐").PHP_EOL.PHP_EOL."*********************".PHP_EOL;
	$mp3Arr = getNeed($classify,$GLOBALS['mp3']);
	$mp3 = $mp3Arr['path'];
	echo $mp3.PHP_EOL;
	//获取片头
	echo "*********************".PHP_EOL.PHP_EOL.strTo("开始获取片头").PHP_EOL.PHP_EOL."*********************".PHP_EOL;
	$headerArr = getNeed($classify,$GLOBALS['header'],1);
	if($headerArr['path']){
		$header = $headerArr['path'].'|';
	}else{
		$header = '';
	}
	echo $header.PHP_EOL;
	//获取片尾
	echo "*********************".PHP_EOL.PHP_EOL.strTo("开始获取片尾").PHP_EOL.PHP_EOL."*********************".PHP_EOL;
	$footerArr = getfooter($classify,$headerArr['filename'],$headerArr['path']);
	if($footerArr['path']){
		$footer = '|'.$footerArr['path'];
	}else{
		$footer = '';
	}
	echo $footer.PHP_EOL;

	//获取logo
	echo "*********************".PHP_EOL.PHP_EOL.strTo("开始获取logo").PHP_EOL.PHP_EOL."*********************".PHP_EOL;
	$logoArr = getNeed($classify,$GLOBALS['logo']);
	$logo = $logoArr['path'];
	echo $logo.PHP_EOL;
	// print_r($config);die;
	//判断是否需要分段
	if($videoInfo['duration'] >= $config[$classify]['cut_max_time'] && $config[$classify]['cut_max_time']!=0){
		$count = floor($videoInfo['duration']/$config[$classify]['cut_video_time']);
		for($i=1;$i<=$count;$i++){
			if($i==$count){
				$cmd = 'ffmpeg -ss '.$config[$classify]['cut_video_time']*($i-1).' -t '.$config[$classify]['cut_video_time'].' -i '.$vpath.' -vcodec copy -acodec copy '.str_replace('.mp4', '' , $vpath).$i.'.mp4';
			}else{
				$cmd = 'ffmpeg -ss '.$config[$classify]['cut_video_time']*($i-1).' -t '.$config[$classify]['cut_video_time']*$i.' -i '.$vpath.' -vcodec copy -acodec copy '.str_replace('.mp4', '' , $vpath).$i.'.mp4';
			}
			shell_exec($cmd);
			cutVideo($classify,str_replace('.mp4','',$filename).$i.'.mp4');
		}
		unlink($vpath);
	}else{
		//判断开始时间，结束时间
		if((int)$start_time+(int)$end_time<$videoInfo['duration']){
			$start_time = $start_time>0?' -ss '.$start_time:'';
			$end_time = $end_time>0?' -t '.$end_time:'';
		}else{
			$start_time = '';
			$end_time = '';
		}

		//判断MP3是否存在
		if($mp3){
			//去除片头片尾加音乐
			echo "*********************".PHP_EOL.PHP_EOL.strTo("开始去除片头片尾加背景音乐").PHP_EOL.PHP_EOL."*********************".PHP_EOL;
			$cmd = 'ffmpeg -i '.$vpath.' -stream_loop -1 -i '.$mp3.' -map 0:v '.$start_time.$end_time.' -c:v h264 -map 1:a -c:a copy  cache/'.$filename;
			shell_exec($cmd);
		}else{
			if($start_time&&$end_time){
				$cmd = 'ffmpeg '.$start_time.$end_time.' -i '.$vpath.'  cache/'.$filename;
				shell_exec($cmd);
			}else{
				rename($vpath, 'cache/'.$filename);
			}
		}

		//判断是否有片头片尾
		if(!$footer&&!$header){
			echo strTo('没有可用的片头片尾').PHP_EOL;
			$cmd2 = rename('cache/'.$filename, 'cache/out'.$filename);
		}else{
			//转换成mpg
			echo "*********************".PHP_EOL.PHP_EOL.strTo("开始转换成mpg").PHP_EOL.PHP_EOL."*********************".PHP_EOL;
			$cmd1 = 'ffmpeg -i '.'cache/'.$filename.' -q 0  cache/'.$filename.'.mpg'; 
			shell_exec($cmd1);

			//添加片头片尾（合成视频）
			echo "*********************".PHP_EOL.PHP_EOL.strTo("开始添加片头片尾（合成视频）").PHP_EOL.PHP_EOL."*********************".PHP_EOL;
			$cmd2 = 'ffmpeg -i "concat:'.$header.'cache/'.$filename.'.mpg'.$footer.'" -q:a 10  cache/out'.$filename;
			shell_exec($cmd2);
		}

		//判断是否有logo
		if($logo){
			$cmd3 = 'ffmpeg -i "cache/out'.$filename.'" -i '.$logo.' -filter_complex "[1:v][0:v]scale2ref=150:150 [wm][base];[base][wm]overlay=main_w-overlay_w-10:main_h-overlay_h-10" '.$GLOBALS['video_out'].'/'.$classify.'/'.$filename;
			shell_exec($cmd3);
		}else{
			$cmd3 = rename('cache/out'.$filename, $GLOBALS['video_out'].'/'.$classify.'/'.$filename);
		}

	}
	$cache = scandir('cache');
	foreach ($cache as $k => $v) {
		if($v!='.'&&$v!='..'){
			unlink('cache/'.$v);
		}
	}
	if(is_file($GLOBALS['video_out'].'/'.$classify.'/'.$filename)&&is_file($GLOBALS['video'].'/'.$classify.'/'.$filename)){
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
//shell_exec('shutdown -s -t 60');
?>