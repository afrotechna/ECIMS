# Installs Poppler (pdftotext) for Laragon on Windows.
# Target: C:\laragon\bin\poppler\Library\bin\pdftotext.exe (matches PdfTextExtractor defaults)
# Requires: PowerShell 5+, ~20 MB download from GitHub.

$ErrorActionPreference = 'Stop'
$zipUrl = 'https://github.com/oschwartz10612/poppler-windows/releases/download/v25.12.0-0/Release-25.12.0-0.zip'
$laragonBin = 'C:\laragon\bin'
$destRoot = Join-Path $laragonBin 'poppler'
$tempZip = Join-Path $env:TEMP 'poppler-windows-release.zip'
$tempExtract = Join-Path $env:TEMP ('poppler-extract-' + [Guid]::NewGuid().ToString('N'))

Write-Host 'Downloading Poppler...'
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
Invoke-WebRequest -Uri $zipUrl -OutFile $tempZip -UseBasicParsing

Write-Host 'Extracting...'
Expand-Archive -Path $tempZip -DestinationPath $tempExtract -Force

# Recent releases extract to poppler-x.y.z\ at zip root; older builds used Release-*\poppler-*\ .
$popplerDir = Get-ChildItem -Path $tempExtract -Directory -Filter 'poppler-*' | Select-Object -First 1
if (-not $popplerDir) {
    $releaseDir = Get-ChildItem -Path $tempExtract -Directory -Filter 'Release-*' | Select-Object -First 1
    if ($releaseDir) {
        $popplerDir = Get-ChildItem -Path $releaseDir.FullName -Directory -Filter 'poppler-*' | Select-Object -First 1
    }
}
if (-not $popplerDir) { throw 'Unexpected zip layout: no poppler-* folder (check release ZIP).' }
$library = Join-Path $popplerDir.FullName 'Library'
if (-not (Test-Path $library)) { throw 'Unexpected zip layout: missing Library folder.' }

if (-not (Test-Path $laragonBin)) {
    New-Item -ItemType Directory -Path $laragonBin -Force | Out-Null
}

if (Test-Path $destRoot) {
    Write-Host "Removing old $destRoot ..."
    Remove-Item -LiteralPath $destRoot -Recurse -Force
}

Write-Host "Installing to $destRoot ..."
New-Item -ItemType Directory -Path $destRoot -Force | Out-Null
Copy-Item -Path (Join-Path $library '*') -Destination $destRoot -Recurse -Force

$exe = Join-Path $destRoot 'bin\pdftotext.exe'
if (-not (Test-Path $exe)) {
    throw "pdftotext.exe not found at $exe"
}

Remove-Item -LiteralPath $tempZip -Force -ErrorAction SilentlyContinue
Remove-Item -LiteralPath $tempExtract -Recurse -Force -ErrorAction SilentlyContinue

Write-Host "Done. pdftotext: $exe"
Write-Host 'Optional: add to system PATH for CLI use:'
Write-Host "  $destRoot\bin"
