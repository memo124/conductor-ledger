# Descarga dependencias frontend a public/vendor (ejecutar desde la raíz del proyecto)
$ErrorActionPreference = "Stop"
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $root

function Save-RemoteFile($url, $dest) {
    $dir = Split-Path -Parent $dest
    if (!(Test-Path $dir)) { New-Item -ItemType Directory -Force -Path $dir | Out-Null }
    Write-Host "Downloading $url"
    Invoke-WebRequest -Uri $url -OutFile $dest -UseBasicParsing
}

$files = @{
    "public/vendor/jquery/3.7.1/jquery.min.js" = "https://code.jquery.com/jquery-3.7.1.min.js"
    "public/vendor/bootstrap/5.3.3/css/bootstrap.min.css" = "https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    "public/vendor/bootstrap/5.3.3/js/bootstrap.bundle.min.js" = "https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    "public/vendor/datatables/1.13.8/css/dataTables.bootstrap5.min.css" = "https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css"
    "public/vendor/datatables/1.13.8/js/jquery.dataTables.min.js" = "https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"
    "public/vendor/datatables/1.13.8/js/dataTables.bootstrap5.min.js" = "https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"
    "public/vendor/select2/4.1.0-rc.0/css/select2.min.css" = "https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"
    "public/vendor/select2/4.1.0-rc.0/js/select2.min.js" = "https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"
    "public/vendor/fontawesome/6.5.1/css/all.min.css" = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
}

foreach ($entry in $files.GetEnumerator()) {
    Save-RemoteFile $entry.Value $entry.Key
}

# Font Awesome webfonts
$webfonts = @(
    "fa-solid-900.woff2",
    "fa-solid-900.ttf",
    "fa-regular-400.woff2",
    "fa-regular-400.ttf",
    "fa-brands-400.woff2",
    "fa-brands-400.ttf"
)

foreach ($font in $webfonts) {
    Save-RemoteFile "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/webfonts/$font" "public/vendor/fontawesome/6.5.1/webfonts/$font"
}

Write-Host "Vendor assets downloaded."
