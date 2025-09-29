#Requires -RunAsAdministrator

param(
	[string]$ProjectRoot = 'C:\xampp\htdocs\TempEmpMng',
	[string]$PhpPath = 'C:\xampp\php\php.exe',
	[string]$TaskPrefix = 'TempEmpMng'
)

function Invoke-Schtasks {
	param(
		[string[]]$Arguments,
		[string]$ErrorMessage
	)

	& schtasks.exe @Arguments | Out-Null
	if ($LASTEXITCODE -ne 0) {
		throw "$ErrorMessage (exit code: $LASTEXITCODE)"
	}
}

function Remove-TaskIfExists {
	param([string]$Name)

	& schtasks.exe /Query /TN $Name | Out-Null
	if ($LASTEXITCODE -eq 0) {
		& schtasks.exe /Delete /TN $Name /F | Out-Null
	}
}

if (-not (Test-Path $PhpPath)) {
	throw "PHP 実行ファイルが見つかりません: $PhpPath"
}

if (-not (Test-Path $ProjectRoot)) {
	throw "プロジェクトパスが見つかりません: $ProjectRoot"
}

$scheduleTaskName = "$TaskPrefix-ScheduleRun"
$workerStartupTaskName = "$TaskPrefix-QueueWorker-Startup"
$workerHourlyTaskName = "$TaskPrefix-QueueWorker-Hourly"

$taskScriptRoot = Join-Path $env:ProgramData $TaskPrefix
$taskScriptDir = Join-Path $taskScriptRoot 'tasks'
New-Item -ItemType Directory -Path $taskScriptDir -Force | Out-Null

$scheduleScriptPath = Join-Path $taskScriptDir 'schedule-run.cmd'
$workerScriptPath = Join-Path $taskScriptDir 'queue-worker.cmd'
$scheduleLogPath = Join-Path $taskScriptDir 'schedule-run.log'
$workerLogPath = Join-Path $taskScriptDir 'queue-worker.log'

$cmdHeader = '@echo off'
$changeDirLine = 'cd /d "{0}"' -f $ProjectRoot
$scheduleBody = '"{0}" artisan schedule:run' -f $PhpPath
$workerBody = '"{0}" artisan queue:work database --queue=reminders,default --sleep=5 --tries=3 --max-time=3600' -f $PhpPath

$scheduleLogStart = 'powershell -NoProfile -Command "Add-Content -Path ''{0}'' -Value ((Get-Date -Format ''yyyy-MM-dd HH:mm:ss'') + '' [schedule:run] triggered'')"' -f $scheduleLogPath
$scheduleLogEnd = 'powershell -NoProfile -Command "Add-Content -Path ''{0}'' -Value ((Get-Date -Format ''yyyy-MM-dd HH:mm:ss'') + '' [schedule:run] exited with code %EXITCODE%'')"' -f $scheduleLogPath
$workerLogStart = 'powershell -NoProfile -Command "Add-Content -Path ''{0}'' -Value ((Get-Date -Format ''yyyy-MM-dd HH:mm:ss'') + '' [queue:work] started'')"' -f $workerLogPath
$workerLogEnd = 'powershell -NoProfile -Command "Add-Content -Path ''{0}'' -Value ((Get-Date -Format ''yyyy-MM-dd HH:mm:ss'') + '' [queue:work] exited with code %EXITCODE%'')"' -f $workerLogPath

$scheduleScriptLines = @(
	$cmdHeader,
	'setlocal',
	('set "LOG_FILE={0}"' -f $scheduleLogPath),
	'if not exist "%LOG_FILE%" (type nul > "%LOG_FILE%")',
	$scheduleLogStart,
	$changeDirLine,
	($scheduleBody + ' >> "%LOG_FILE%" 2>&1'),
	'set "EXITCODE=%ERRORLEVEL%"',
	$scheduleLogEnd,
	'exit /b %EXITCODE%'
)

$workerScriptLines = @(
	$cmdHeader,
	'setlocal',
	('set "LOG_FILE={0}"' -f $workerLogPath),
	'if not exist "%LOG_FILE%" (type nul > "%LOG_FILE%")',
	$workerLogStart,
	$changeDirLine,
	($workerBody + ' >> "%LOG_FILE%" 2>&1'),
	'set "EXITCODE=%ERRORLEVEL%"',
	$workerLogEnd,
	'exit /b %EXITCODE%'
)

Set-Content -Path $scheduleScriptPath -Value $scheduleScriptLines -Encoding ASCII
Set-Content -Path $workerScriptPath -Value $workerScriptLines -Encoding ASCII
$scheduleCommand = '"' + ($scheduleScriptPath -replace '"', '""') + '"'
$workerCommand = '"' + ($workerScriptPath -replace '"', '""') + '"'

Remove-TaskIfExists -Name $scheduleTaskName
Remove-TaskIfExists -Name $workerStartupTaskName
Remove-TaskIfExists -Name $workerHourlyTaskName

$scheduleStart = (Get-Date).AddMinutes(1)
$scheduleStartTime = $scheduleStart.ToString('HH:mm')
$scheduleStartDate = $scheduleStart.ToString('yyyy/MM/dd')

$workerStart = (Get-Date).AddMinutes(1)
$workerStartTime = $workerStart.ToString('HH:mm')
$workerStartDate = $workerStart.ToString('yyyy/MM/dd')

Invoke-Schtasks -Arguments @(
	'/Create',
	'/TN', $scheduleTaskName,
	'/TR', $scheduleCommand,
	'/SC', 'MINUTE',
	'/MO', '1',
	'/ST', $scheduleStartTime,
	'/SD', $scheduleStartDate,
	'/RU', 'SYSTEM',
	'/RL', 'HIGHEST',
	'/F'
) -ErrorMessage "[$scheduleTaskName] の登録に失敗しました。"

Invoke-Schtasks -Arguments @(
	'/Create',
	'/TN', $workerStartupTaskName,
	'/TR', $workerCommand,
	'/SC', 'ONSTART',
	'/RU', 'SYSTEM',
	'/RL', 'HIGHEST',
	'/F'
) -ErrorMessage "[$workerStartupTaskName] の登録に失敗しました。"

Invoke-Schtasks -Arguments @(
	'/Create',
	'/TN', $workerHourlyTaskName,
	'/TR', $workerCommand,
	'/SC', 'HOURLY',
	'/MO', '1',
	'/ST', $workerStartTime,
	'/SD', $workerStartDate,
	'/RU', 'SYSTEM',
	'/RL', 'HIGHEST',
	'/F'
) -ErrorMessage "[$workerHourlyTaskName] の登録に失敗しました。"

Write-Host "[$scheduleTaskName] を登録しました。毎分 schedule:run を実行します。"
Write-Host "[$workerStartupTaskName] を登録しました。再起動時に queue:work (reminders) を起動します。"
Write-Host "[$workerHourlyTaskName] を登録しました。毎時 queue:work (reminders) を再起動します。"

