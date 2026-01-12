# Setup Task Scheduler for Automatic Database Backups
# Run this script as Administrator to create scheduled task

# Configuration
$TaskName = "PPUT-Ostrans-Database-Backup"
$ScriptPath = "$PSScriptRoot\backup-database.ps1"
$ScheduleTime = "02:00"  # 2:00 AM daily

Write-Host "=== Setting up automatic database backups ===" -ForegroundColor Green
Write-Host "Task name: $TaskName"
Write-Host "Script: $ScriptPath"
Write-Host "Schedule: Daily at $ScheduleTime"
Write-Host ""

# Check if running as Administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    Write-Host "ERROR: This script must be run as Administrator!" -ForegroundColor Red
    Write-Host "Please right-click PowerShell and select 'Run as Administrator'" -ForegroundColor Yellow
    exit 1
}

# Check if script exists
if (-not (Test-Path $ScriptPath)) {
    Write-Host "ERROR: Backup script not found at: $ScriptPath" -ForegroundColor Red
    exit 1
}

try {
    # Remove existing task if it exists
    $existingTask = Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
    if ($existingTask) {
        Write-Host "Removing existing task..." -ForegroundColor Yellow
        Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false
    }
    
    # Create action
    $action = New-ScheduledTaskAction -Execute "powershell.exe" `
        -Argument "-ExecutionPolicy Bypass -NoProfile -File `"$ScriptPath`""
    
    # Create trigger (daily at specified time)
    $trigger = New-ScheduledTaskTrigger -Daily -At $ScheduleTime
    
    # Create settings
    $settings = New-ScheduledTaskSettingsSet `
        -AllowStartIfOnBatteries `
        -DontStopIfGoingOnBatteries `
        -StartWhenAvailable `
        -RunOnlyIfNetworkAvailable:$false
    
    # Get current user
    $principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest
    
    # Register task
    Register-ScheduledTask -TaskName $TaskName `
        -Action $action `
        -Trigger $trigger `
        -Settings $settings `
        -Principal $principal `
        -Description "Automatic daily backup of PPUT Ostrans PostgreSQL database"
    
    Write-Host ""
    Write-Host "=== Task created successfully! ===" -ForegroundColor Green
    Write-Host ""
    Write-Host "The backup will run daily at $ScheduleTime" -ForegroundColor Cyan
    Write-Host "Backups will be stored in: $PSScriptRoot\..\backups" -ForegroundColor Cyan
    Write-Host "Logs will be stored in: $PSScriptRoot\..\logs" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "To run backup manually:" -ForegroundColor Yellow
    Write-Host "  Start-ScheduledTask -TaskName '$TaskName'" -ForegroundColor White
    Write-Host ""
    Write-Host "To view task:" -ForegroundColor Yellow
    Write-Host "  Get-ScheduledTask -TaskName '$TaskName'" -ForegroundColor White
    Write-Host ""
    Write-Host "To remove task:" -ForegroundColor Yellow
    Write-Host "  Unregister-ScheduledTask -TaskName '$TaskName' -Confirm:`$false" -ForegroundColor White
    Write-Host ""
    
    # Test run (optional)
    $response = Read-Host "Do you want to test the backup now? (Y/N)"
    if ($response -eq "Y" -or $response -eq "y") {
        Write-Host ""
        Write-Host "Starting test backup..." -ForegroundColor Cyan
        Start-ScheduledTask -TaskName $TaskName
        Write-Host "Backup task started. Check logs directory for results." -ForegroundColor Green
    }
    
} catch {
    Write-Host ""
    Write-Host "ERROR: Failed to create scheduled task" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    exit 1
}
