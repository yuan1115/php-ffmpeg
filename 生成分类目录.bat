@echo off
title ���ɷ���Ŀ¼.bat
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
     echo common�Ѵ���
)
if not exist header/common ( 
     cd header
     md common
     cd ../ 
) else ( 
     echo common�Ѵ���
)
if not exist footer/common ( 
     cd footer
     md common
     cd ../ 
) else ( 
     echo common�Ѵ���
)
if not exist logo/common ( 
     cd  logo
     md common
     cd ../ 
) else ( 
     echo common�Ѵ���
)
for /f "tokens=*" %%a in (classify.txt) do (
for /f "delims=;,tokens=1,*" %%i in ("%%a") do (
echo %%i
 if not exist ./video/%%i ( 
         cd video
         md %%i
         echo video����%%i�ɹ�
         cd ../
   ) else (
         echo video%%i�Ѵ��� 
   )
  if not exist ./mp3/%%i ( 
         cd mp3  
         md %%i
         echo mp3����%%i�ɹ�
         cd ../ 
   ) else (
         echo mp3%%i�Ѵ��� 
   )
   if not exist ./video_out/%%i ( 
         cd video_out
         md %%i
         echo video_out����%%i�ɹ�
         cd ../ 
   ) else (
         echo video_out%%i�Ѵ��� 
   )
    if not exist ./header/%%i ( 
         cd header
         md %%i
         echo header����%%i�ɹ�
         cd ../ 
   ) else (
         echo header%%i�Ѵ��� 
   )
 if not exist ./footer/%%i ( 
         cd footer
         md %%i
         echo footer����%%i�ɹ�
         cd ../ 
   ) else (
         echo footer%%i�Ѵ��� 
   )
if not exist ./logo/%%i ( 
         cd logo
         md %%i
         echo logo����%%i�ɹ�
         cd ../ 
   ) else (
         echo logo%%i�Ѵ��� 
   )
)
)	
pause
