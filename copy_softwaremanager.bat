@echo off
setlocal enabledelayedexpansion

echo ��ʼִ��Ŀ¼����...

:: ɾ��Y���µ�softwaremanagerĿ¼��������ڣ�
echo ��鲢ɾ��Y:\softwaremanagerĿ¼...
if exist "Y:\softwaremanager" (
    rd /s /q "Y:\softwaremanager"
    if !errorlevel! equ 0 (
        echo Y:\softwaremanagerĿ¼�ѳɹ�ɾ����
    ) else (
        echo ɾ��Y:\softwaremanagerĿ¼ʱ����������: !errorlevel!
        goto :error
    )
) else (
    echo Y:\softwaremanagerĿ¼�����ڣ�����ɾ��������
)

:: ���������ϵ�softwaremanagerĿ¼��Y�̣��ų�.gitĿ¼
echo ��ʼ����C:\Users\ND\Desktop\GLPI_Project\softwaremanagerĿ¼��Y��...
echo ע�⣺.gitĿ¼�������ݽ����ų�...

robocopy "C:\Users\ND\Desktop\GLPI_Project\softwaremanager" "Y:\softwaremanager" /E /NFL /NDL /NJH /NJS /XD .git /XF .gitattributes .gitignore
if !errorlevel! leq 1 (
    echo Ŀ¼���Ƴɹ���ɡ�
) else (
    echo ���ƹ����з������󣬴�����: !errorlevel!
    goto :error
)

echo ���в����ѳɹ���ɡ�
goto :end

:error
echo ���������з���������������Ĵ�����Ϣ��

:end
endlocal
pause    