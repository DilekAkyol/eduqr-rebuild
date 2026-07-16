@echo off
echo ==============================================================
echo eduQR - ngrok Sabit Tünel Başlatılıyor...
echo Domain: dotty-unexperimental-ling.ngrok-free.dev
echo ==============================================================
echo.
ngrok http --domain=dotty-unexperimental-ling.ngrok-free.dev 80
pause
