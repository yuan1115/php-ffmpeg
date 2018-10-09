### 视频分段剪辑程序
* 首先打开classify.txt配置文件，填写分类配置（格式：目录名;满足剪切要求的的视频时间0
    （a）;剪切片头时间(b);剪切片尾时间(c);分段视频时间(d)。参数a,b,c,d不填默认分别是
    600，4，3，300。若视频不想分段参数a则写0。）
* 点击 “生成分类目录.bat” 生成对应目录
* 将对应分类的片头（e），片尾(f)，logo(g)，背景音乐(h)对号入座，注意：若分类中的e，f
  ，g，h中无文件，则默认取通用common目录里面文件
* 点击 “视频处理.bat” 运行脚本

#### 目录结构

    |--footer                片尾
        |--common            通用片尾
        |--分类名       
    |--header                片头  
        |--common            通用片头
        |--分类名
    |--logo                  logo  
        |--common            通用logo
        |--分类名
    |--mp3                   背景音乐
        |--common            通用背景音乐
        |--分类名
    |--video                 需要裁剪的目录 
    |--video_out             裁剪完成后的视频目录
    |--baidu_transapi.php    百度翻译api
    |--classify.txt          视频分类 
    |--ffmpeg.exe            必要软件  
    |--ffplay.exe            必要软件 
    |--ffprobe.exe           必要软件
    |--yunxin.php            主程序代码 
    |--生成分类目录.bat       每次classify.txt添加新目录时执行
    |--视频处理.bat           处理视频时执行

