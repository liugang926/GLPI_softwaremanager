@echo off
setlocal enabledelayedexpansion

echo 开始执行目录操作...

:: 删除Y盘下的softwaremanager目录（如果存在）
echo 检查并删除Y:\softwaremanager目录...
if exist "Y:\softwaremanager" (
    rd /s /q "Y:\softwaremanager"
    if !errorlevel! equ 0 (
        echo Y:\softwaremanager目录已成功删除。
    ) else (
        echo 删除Y:\softwaremanager目录时出错，错误码: !errorlevel!
        goto :error
    )
) else (
    echo Y:\softwaremanager目录不存在，跳过删除操作。
)

:: 复制桌面上的softwaremanager目录到Y盘，排除.git目录
echo 开始复制C:\Users\ND\Desktop\GLPI_Project\softwaremanager目录到Y盘...
echo 注意：.git目录及其内容将被排除...

robocopy "C:\Users\ND\Desktop\GLPI_Project\softwaremanager" "Y:\softwaremanager" /E /NFL /NDL /NJH /NJS /XD .git /XF .gitattributes .gitignore
if !errorlevel! leq 1 (
    echo 目录复制成功完成。
) else (
    echo 复制过程中发生错误，错误码: !errorlevel!
    goto :error
)

echo 所有操作已成功完成。
goto :end

:error
echo 操作过程中发生错误，请检查上面的错误信息。

:end
endlocal
pause    