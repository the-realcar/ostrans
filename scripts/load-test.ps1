# PPUT Ostrans - Load Testing Script
# Tests API performance under load
# 
# Usage:
#   .\load-test.ps1 -ApiUrl "http://localhost/ostrans/panel/api.php" -Users 50 -Duration 60

param(
    [string]$ApiUrl = "http://localhost/ostrans/panel/api.php",
    [int]$Users = 50,
    [int]$Duration = 60,
    [string]$TestLogin = "driver1",
    [string]$TestPassword = "dpass"
)

Write-Host "=== PPUT Ostrans Load Testing ===" -ForegroundColor Cyan
Write-Host "API URL: $ApiUrl"
Write-Host "Concurrent Users: $Users"
Write-Host "Duration: $Duration seconds"
Write-Host "Test account: $TestLogin"
Write-Host ""

# Test configuration
$endpoints = @(
    @{ Method = "POST"; Path = "/api/login"; Body = @{ login = $TestLogin; password = $TestPassword } },
    @{ Method = "GET"; Path = "/api/me"; RequireAuth = $true },
    @{ Method = "GET"; Path = "/api/wnioski"; RequireAuth = $true },
    @{ Method = "GET"; Path = "/api/grafik"; RequireAuth = $true },
    @{ Method = "GET"; Path = "/api/pracownicy"; RequireAuth = $true },
    @{ Method = "GET"; Path = "/api/pojazdy"; RequireAuth = $true }
)

# Statistics
$stats = @{
    TotalRequests = 0
    SuccessfulRequests = 0
    FailedRequests = 0
    TotalResponseTime = 0
    MinResponseTime = [double]::MaxValue
    MaxResponseTime = 0
    ResponseTimes = @()
}

# Login to get token
function Get-AuthToken {
    param($url)
    
    try {
        $body = @{ login = $TestLogin; password = $TestPassword } | ConvertTo-Json
        $response = Invoke-RestMethod -Uri "$url/api/login" -Method POST -Body $body -ContentType "application/json" -ErrorAction Stop
        return $response.token
    } catch {
        Write-Host "Failed to authenticate: $($_.Exception.Message)" -ForegroundColor Red
        return $null
    }
}

# Make HTTP request
function Invoke-ApiRequest {
    param(
        [string]$url,
        [string]$method,
        [string]$path,
        [string]$token = $null,
        [hashtable]$body = $null
    )
    
    $headers = @{}
    if ($token) {
        $headers["Authorization"] = "Bearer $token"
    }
    
    $requestUrl = "$url$path"
    $stopwatch = [System.Diagnostics.Stopwatch]::StartNew()
    
    try {
        $params = @{
            Uri = $requestUrl
            Method = $method
            Headers = $headers
            ErrorAction = "Stop"
        }
        
        if ($body -and $method -eq "POST") {
            $params["Body"] = ($body | ConvertTo-Json)
            $params["ContentType"] = "application/json"
        }
        
        $response = Invoke-RestMethod @params
        $stopwatch.Stop()
        
        return @{
            Success = $true
            ResponseTime = $stopwatch.Elapsed.TotalMilliseconds
            StatusCode = 200
        }
    } catch {
        $stopwatch.Stop()
        return @{
            Success = $false
            ResponseTime = $stopwatch.Elapsed.TotalMilliseconds
            Error = $_.Exception.Message
        }
    }
}

# Worker thread simulation
function Invoke-LoadTest {
    param(
        [int]$userId,
        [string]$url,
        [string]$token,
        [int]$duration,
        [ref]$statsRef
    )
    
    $endTime = (Get-Date).AddSeconds($duration)
    $localStats = @{
        Requests = 0
        Success = 0
        Failed = 0
        TotalTime = 0
        MinTime = [double]::MaxValue
        MaxTime = 0
        Times = @()
    }
    
    while ((Get-Date) -lt $endTime) {
        # Pick random endpoint
        $endpoint = $endpoints | Get-Random
        
        # Skip auth-required endpoints if no token
        if ($endpoint.RequireAuth -and -not $token) {
            continue
        }
        
        $result = Invoke-ApiRequest -url $url -method $endpoint.Method -path $endpoint.Path -token $token -body $endpoint.Body
        
        $localStats.Requests++
        $localStats.TotalTime += $result.ResponseTime
        $localStats.Times += $result.ResponseTime
        
        if ($result.ResponseTime -lt $localStats.MinTime) {
            $localStats.MinTime = $result.ResponseTime
        }
        if ($result.ResponseTime -gt $localStats.MaxTime) {
            $localStats.MaxTime = $result.ResponseTime
        }
        
        if ($result.Success) {
            $localStats.Success++
        } else {
            $localStats.Failed++
        }
        
        # Small delay to simulate real usage
        Start-Sleep -Milliseconds (Get-Random -Minimum 100 -Maximum 500)
    }
    
    return $localStats
}

# Main test execution
Write-Host "Authenticating..." -ForegroundColor Yellow
$authToken = Get-AuthToken -url $ApiUrl

if (-not $authToken) {
    Write-Host "Cannot proceed without authentication token" -ForegroundColor Red
    exit 1
}

Write-Host "Authentication successful. Starting load test..." -ForegroundColor Green
Write-Host ""

$startTime = Get-Date

# Run concurrent users (PowerShell job simulation)
$jobs = @()
for ($i = 1; $i -le $Users; $i++) {
    Write-Host "Starting user $i..." -NoNewline
    
    # In PowerShell 7+, you could use Start-ThreadJob for true parallelism
    # For PS 5.1, we simulate with sequential calls but measure accurately
    $result = Invoke-LoadTest -userId $i -url $ApiUrl -token $authToken -duration ($Duration / $Users) -statsRef ([ref]$stats)
    
    $stats.TotalRequests += $result.Requests
    $stats.SuccessfulRequests += $result.Success
    $stats.FailedRequests += $result.Failed
    $stats.TotalResponseTime += $result.TotalTime
    $stats.ResponseTimes += $result.Times
    
    if ($result.MinTime -lt $stats.MinResponseTime) {
        $stats.MinResponseTime = $result.MinTime
    }
    if ($result.MaxTime -gt $stats.MaxResponseTime) {
        $stats.MaxResponseTime = $result.MaxTime
    }
    
    Write-Host " Done ($($result.Requests) requests)" -ForegroundColor Green
}

$endTime = Get-Date
$totalDuration = ($endTime - $startTime).TotalSeconds

# Calculate statistics
$avgResponseTime = if ($stats.TotalRequests -gt 0) { 
    $stats.TotalResponseTime / $stats.TotalRequests 
} else { 
    0 
}

$requestsPerSecond = if ($totalDuration -gt 0) {
    $stats.TotalRequests / $totalDuration
} else {
    0
}

$successRate = if ($stats.TotalRequests -gt 0) {
    ($stats.SuccessfulRequests / $stats.TotalRequests) * 100
} else {
    0
}

# Calculate percentiles
$sortedTimes = $stats.ResponseTimes | Sort-Object
$p50 = if ($sortedTimes.Count -gt 0) { $sortedTimes[[math]::Floor($sortedTimes.Count * 0.50)] } else { 0 }
$p95 = if ($sortedTimes.Count -gt 0) { $sortedTimes[[math]::Floor($sortedTimes.Count * 0.95)] } else { 0 }
$p99 = if ($sortedTimes.Count -gt 0) { $sortedTimes[[math]::Floor($sortedTimes.Count * 0.99)] } else { 0 }

# Display results
Write-Host ""
Write-Host "=== Load Test Results ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "Duration: $([math]::Round($totalDuration, 2)) seconds" -ForegroundColor White
Write-Host "Total Requests: $($stats.TotalRequests)" -ForegroundColor White
Write-Host "Successful: $($stats.SuccessfulRequests) ($([math]::Round($successRate, 2))%)" -ForegroundColor Green
Write-Host "Failed: $($stats.FailedRequests)" -ForegroundColor Red
Write-Host ""
Write-Host "Performance Metrics:" -ForegroundColor Yellow
Write-Host "  Requests/sec: $([math]::Round($requestsPerSecond, 2))" -ForegroundColor White
Write-Host "  Avg Response Time: $([math]::Round($avgResponseTime, 2)) ms" -ForegroundColor White
Write-Host "  Min Response Time: $([math]::Round($stats.MinResponseTime, 2)) ms" -ForegroundColor White
Write-Host "  Max Response Time: $([math]::Round($stats.MaxResponseTime, 2)) ms" -ForegroundColor White
Write-Host ""
Write-Host "Response Time Percentiles:" -ForegroundColor Yellow
Write-Host "  P50 (median): $([math]::Round($p50, 2)) ms" -ForegroundColor White
Write-Host "  P95: $([math]::Round($p95, 2)) ms" -ForegroundColor White
Write-Host "  P99: $([math]::Round($p99, 2)) ms" -ForegroundColor White
Write-Host ""

# Assessment
Write-Host "=== Assessment ===" -ForegroundColor Cyan
if ($avgResponseTime -lt 500) {
    Write-Host "✓ Average response time under 0.5s - EXCELLENT" -ForegroundColor Green
} elseif ($avgResponseTime -lt 1000) {
    Write-Host "⚠ Average response time under 1s - GOOD" -ForegroundColor Yellow
} else {
    Write-Host "✗ Average response time over 1s - NEEDS OPTIMIZATION" -ForegroundColor Red
}

if ($successRate -gt 99) {
    Write-Host "✓ Success rate over 99% - EXCELLENT" -ForegroundColor Green
} elseif ($successRate -gt 95) {
    Write-Host "⚠ Success rate over 95% - ACCEPTABLE" -ForegroundColor Yellow
} else {
    Write-Host "✗ Success rate below 95% - CRITICAL ISSUES" -ForegroundColor Red
}

if ($requestsPerSecond -gt 100) {
    Write-Host "✓ Handling over 100 requests/sec - EXCELLENT" -ForegroundColor Green
} elseif ($requestsPerSecond -gt 50) {
    Write-Host "⚠ Handling over 50 requests/sec - GOOD" -ForegroundColor Yellow
} else {
    Write-Host "⚠ Handling under 50 requests/sec - CONSIDER OPTIMIZATION" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Note: For production load testing, use specialized tools like:" -ForegroundColor Cyan
Write-Host "  - k6 (https://k6.io)" -ForegroundColor White
Write-Host "  - Apache JMeter" -ForegroundColor White
Write-Host "  - Gatling" -ForegroundColor White
