# PostgreSQL Database Backup Script for PPUT Ostrans
# This script creates automated backups of the PostgreSQL database
# 
# Usage: 
#   .\backup-database.ps1
# 
# Schedule with Windows Task Scheduler:
#   powershell.exe -ExecutionPolicy Bypass -File "C:\path\to\backup-database.ps1"

# Configuration
$DB_HOST = $env:DB_HOST ?? "localhost"
$DB_PORT = $env:DB_PORT ?? "5432"
$DB_NAME = $env:DB_NAME ?? "ostrans"
$DB_USER = $env:DB_USER ?? "postgres"
$DB_PASSWORD = $env:DB_PASSWORD ?? ""

# Backup directory
$BACKUP_DIR = "$PSScriptRoot\..\backups"
$LOG_DIR = "$PSScriptRoot\..\logs"

# Create directories if they don't exist
if (-not (Test-Path $BACKUP_DIR)) {
    New-Item -ItemType Directory -Path $BACKUP_DIR -Force | Out-Null
}
if (-not (Test-Path $LOG_DIR)) {
    New-Item -ItemType Directory -Path $LOG_DIR -Force | Out-Null
}

# Timestamp for backup file
$TIMESTAMP = Get-Date -Format "yyyyMMdd_HHmmss"
$BACKUP_FILE = "$BACKUP_DIR\ostrans_backup_$TIMESTAMP.sql"
$BACKUP_FILE_GZ = "$BACKUP_FILE.gz"
$LOG_FILE = "$LOG_DIR\backup_$TIMESTAMP.log"

Write-Host "=== PPUT Ostrans Database Backup ===" | Tee-Object -FilePath $LOG_FILE
Write-Host "Start time: $(Get-Date)" | Tee-Object -FilePath $LOG_FILE -Append
Write-Host "Database: $DB_NAME on $DB_HOST:$DB_PORT" | Tee-Object -FilePath $LOG_FILE -Append
Write-Host "" | Tee-Object -FilePath $LOG_FILE -Append

# Set PostgreSQL password environment variable
$env:PGPASSWORD = $DB_PASSWORD

try {
    # Check if pg_dump is available
    $pgDumpPath = Get-Command pg_dump -ErrorAction SilentlyContinue
    if (-not $pgDumpPath) {
        throw "pg_dump not found. Please ensure PostgreSQL client tools are installed and in PATH."
    }
    
    Write-Host "Starting backup..." | Tee-Object -FilePath $LOG_FILE -Append
    
    # Run pg_dump
    $dumpArgs = @(
        "-h", $DB_HOST,
        "-p", $DB_PORT,
        "-U", $DB_USER,
        "-F", "p",  # Plain text format
        "-f", $BACKUP_FILE,
        $DB_NAME
    )
    
    $process = Start-Process -FilePath "pg_dump" -ArgumentList $dumpArgs -Wait -PassThru -NoNewWindow
    
    if ($process.ExitCode -ne 0) {
        throw "pg_dump failed with exit code $($process.ExitCode)"
    }
    
    Write-Host "Backup created: $BACKUP_FILE" | Tee-Object -FilePath $LOG_FILE -Append
    
    # Get file size
    $fileSize = (Get-Item $BACKUP_FILE).Length
    $fileSizeMB = [math]::Round($fileSize / 1MB, 2)
    Write-Host "Backup size: $fileSizeMB MB" | Tee-Object -FilePath $LOG_FILE -Append
    
    # Compress backup (optional)
    Write-Host "Compressing backup..." | Tee-Object -FilePath $LOG_FILE -Append
    
    # Using .NET compression
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    $compressionLevel = [System.IO.Compression.CompressionLevel]::Optimal
    
    $sourceStream = [System.IO.File]::OpenRead($BACKUP_FILE)
    $targetStream = [System.IO.File]::Create($BACKUP_FILE_GZ)
    $gzipStream = New-Object System.IO.Compression.GZipStream($targetStream, $compressionLevel)
    
    $sourceStream.CopyTo($gzipStream)
    
    $gzipStream.Close()
    $targetStream.Close()
    $sourceStream.Close()
    
    # Remove uncompressed file
    Remove-Item $BACKUP_FILE
    
    $compressedSize = (Get-Item $BACKUP_FILE_GZ).Length
    $compressedSizeMB = [math]::Round($compressedSize / 1MB, 2)
    $compressionRatio = [math]::Round(($compressedSize / $fileSize) * 100, 2)
    
    Write-Host "Compressed backup: $BACKUP_FILE_GZ" | Tee-Object -FilePath $LOG_FILE -Append
    Write-Host "Compressed size: $compressedSizeMB MB ($compressionRatio% of original)" | Tee-Object -FilePath $LOG_FILE -Append
    
    # Cleanup old backups (keep last 30 days)
    Write-Host "" | Tee-Object -FilePath $LOG_FILE -Append
    Write-Host "Cleaning up old backups (keeping last 30 days)..." | Tee-Object -FilePath $LOG_FILE -Append
    
    $cutoffDate = (Get-Date).AddDays(-30)
    $oldBackups = Get-ChildItem -Path $BACKUP_DIR -Filter "ostrans_backup_*.sql.gz" | 
                  Where-Object { $_.LastWriteTime -lt $cutoffDate }
    
    foreach ($oldBackup in $oldBackups) {
        Write-Host "Removing old backup: $($oldBackup.Name)" | Tee-Object -FilePath $LOG_FILE -Append
        Remove-Item $oldBackup.FullName -Force
    }
    
    $remainingBackups = (Get-ChildItem -Path $BACKUP_DIR -Filter "ostrans_backup_*.sql.gz").Count
    Write-Host "Total backups: $remainingBackups" | Tee-Object -FilePath $LOG_FILE -Append
    
    Write-Host "" | Tee-Object -FilePath $LOG_FILE -Append
    Write-Host "=== Backup completed successfully ===" | Tee-Object -FilePath $LOG_FILE -Append
    Write-Host "End time: $(Get-Date)" | Tee-Object -FilePath $LOG_FILE -Append
    
    exit 0
    
} catch {
    Write-Host "" | Tee-Object -FilePath $LOG_FILE -Append
    Write-Host "ERROR: $($_.Exception.Message)" -ForegroundColor Red | Tee-Object -FilePath $LOG_FILE -Append
    Write-Host "Backup failed!" -ForegroundColor Red | Tee-Object -FilePath $LOG_FILE -Append
    exit 1
} finally {
    # Clear password from environment
    $env:PGPASSWORD = $null
}
