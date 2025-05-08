@echo off
echo ===================================================
echo      THIẾT LẬP ĐĂNG NHẬP GOOGLE CHO COPECUTE
echo ===================================================
echo.

echo Bước 1: Lấy SHA-1 certificate fingerprint...
echo.

cd android
call gradlew signingReport

echo.
echo Bước 2: Yêu cầu thông tin từ Google Cloud Console
echo.
echo Vui lòng mở Google Cloud Console và tạo OAuth 2.0 Client ID
echo https://console.cloud.google.com/apis/credentials
echo.

set /p webClientId=Nhập Web Client ID (dạng xxx.apps.googleusercontent.com): 
set /p androidClientId=Nhập Android Client ID (dạng xxx.apps.googleusercontent.com): 
set /p projectNumber=Nhập Project Number: 
set /p apiKey=Nhập API Key: 
set /p sha1=Nhập SHA-1 fingerprint: 

echo.
echo Bước 3: Cập nhật file strings.xml...
echo.

cd ..
mkdir -p .\android\app\src\main\res\values
echo ^<?xml version="1.0" encoding="utf-8"?^> > .\android\app\src\main\res\values\strings.xml
echo ^<resources^> >> .\android\app\src\main\res\values\strings.xml
echo     ^<string name="app_name"^>Copecute Chatbot^</string^> >> .\android\app\src\main\res\values\strings.xml
echo     ^<string name="server_client_id"^>%webClientId%^</string^> >> .\android\app\src\main\res\values\strings.xml
echo ^</resources^> >> .\android\app\src\main\res\values\strings.xml

echo Đã cập nhật file strings.xml!
echo.

echo Bước 4: Cập nhật file auth_service.dart...
echo.

powershell -Command "(Get-Content lib\services\auth_service.dart) -replace '// serverClientId: ''YOUR_WEB_CLIENT_ID.apps.googleusercontent.com''', 'serverClientId: ''%webClientId%''' | Set-Content lib\services\auth_service.dart"

echo Đã cập nhật file auth_service.dart!
echo.

echo Bước 5: Tạo file google-services.json...
echo.

echo { > .\android\app\google-services.json
echo   "project_info": { >> .\android\app\google-services.json
echo     "project_number": "%projectNumber%", >> .\android\app\google-services.json
echo     "project_id": "copecute-app", >> .\android\app\google-services.json
echo     "storage_bucket": "copecute-app.appspot.com" >> .\android\app\google-services.json
echo   }, >> .\android\app\google-services.json
echo   "client": [ >> .\android\app\google-services.json
echo     { >> .\android\app\google-services.json
echo       "client_info": { >> .\android\app\google-services.json
echo         "mobilesdk_app_id": "1:%projectNumber%:android:0000000000000000", >> .\android\app\google-services.json
echo         "android_client_info": { >> .\android\app\google-services.json
echo           "package_name": "com.copecute.bot" >> .\android\app\google-services.json
echo         } >> .\android\app\google-services.json
echo       }, >> .\android\app\google-services.json
echo       "oauth_client": [ >> .\android\app\google-services.json
echo         { >> .\android\app\google-services.json
echo           "client_id": "%webClientId%", >> .\android\app\google-services.json
echo           "client_type": 3 >> .\android\app\google-services.json
echo         }, >> .\android\app\google-services.json
echo         { >> .\android\app\google-services.json
echo           "client_id": "%androidClientId%", >> .\android\app\google-services.json
echo           "client_type": 1, >> .\android\app\google-services.json
echo           "android_info": { >> .\android\app\google-services.json
echo             "package_name": "com.copecute.bot", >> .\android\app\google-services.json
echo             "certificate_hash": "%sha1%" >> .\android\app\google-services.json
echo           } >> .\android\app\google-services.json
echo         } >> .\android\app\google-services.json
echo       ], >> .\android\app\google-services.json
echo       "api_key": [ >> .\android\app\google-services.json
echo         { >> .\android\app\google-services.json
echo           "current_key": "%apiKey%" >> .\android\app\google-services.json
echo         } >> .\android\app\google-services.json
echo       ], >> .\android\app\google-services.json
echo       "services": { >> .\android\app\google-services.json
echo         "appinvite_service": { >> .\android\app\google-services.json
echo           "other_platform_oauth_client": [] >> .\android\app\google-services.json
echo         } >> .\android\app\google-services.json
echo       } >> .\android\app\google-services.json
echo     } >> .\android\app\google-services.json
echo   ], >> .\android\app\google-services.json
echo   "configuration_version": "1" >> .\android\app\google-services.json
echo } >> .\android\app\google-services.json

echo Đã tạo file google-services.json!
echo.

echo Bước 6: Clean và chạy ứng dụng...
echo.

set /p runApp=Bạn có muốn clean và build lại ứng dụng không? (y/n): 

if /i "%runApp%"=="y" (
    call flutter clean
    call flutter pub get
    echo.
    echo Có thể chạy ứng dụng bằng lệnh:
    echo flutter run
)

echo.
echo ===================================================
echo          THIẾT LẬP HOÀN TẤT!
echo ===================================================
echo.
echo Vui lòng xem file README_GOOGLE_LOGIN.md để biết thêm thông tin
echo và cách khắc phục các lỗi thường gặp.
echo. 