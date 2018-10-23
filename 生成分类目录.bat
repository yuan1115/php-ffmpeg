@echo off
title 生成分类目录.bat
if not exist video md video
if not exist mp3 md mp3
if not exist video_out md video_out
if not exist header md header
if not exist footer md footer
if not exist logo md logo
if not exist cache md cache

if not exist mp3/common ( 
     cd mp3
     md common
     cd ../ 
) else ( 
     echo common已存在
)
if not exist header/common ( 
     cd header
     md common
     cd ../ 
) else ( 
     echo common已存在
)
if not exist footer/common ( 
     cd footer
     md common
     cd ../ 
) else ( 
     echo common已存在
)
if not exist logo/common ( 
     cd  logo
     md common
     cd ../ 
) else ( 
     echo common已存在
)
for /f "tokens=*" %%a in (classify.txt) do (
for /f "delims=;,tokens=1,*" %%i in ("%%a") do (
echo %%i
 if not exist ./video/%%i ( 
         cd video
         md %%i
         echo video创建%%i成功
         cd ../
   ) else (
         echo video%%i已存在 
   )
  if not exist ./mp3/%%i ( 
         cd mp3  
         md %%i
         echo mp3创建%%i成功
         cd ../ 
   ) else (
         echo mp3%%i已存在 
   )
   if not exist ./video_out/%%i ( 
         cd video_out
         md %%i
         echo video_out创建%%i成功
         cd ../ 
   ) else (
         echo video_out%%i已存在 
   )
    if not exist ./header/%%i ( 
         cd header
         md %%i
         echo header创建%%i成功
         cd ../ 
   ) else (
         echo header%%i已存在 
   )
 if not exist ./footer/%%i ( 
         cd footer
         md %%i
         echo footer创建%%i成功
         cd ../ 
   ) else (
         echo footer%%i已存在 
   )
if not exist ./logo/%%i ( 
         cd logo
         md %%i
         echo logo创建%%i成功
         cd ../ 
   ) else (
         echo logo%%i已存在 
   )
)
)	
pause
