param(
  [string]$RepoRoot = (Get-Location).Path
)
Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"
Write-Host "=> Repo: $RepoRoot"

# ensure php-cs-fixer finder has in(['src','tests'])
$csFixer = Join-Path $RepoRoot "php-cs-fixer.dist.php"
if (Test-Path $csFixer) {
  $content = Get-Content $csFixer -Raw
  if ($content -notmatch "Finder::create") {
    $code = @"
<?php
$finder = PhpCsFixer\Finder::create()->in(['src','tests'])->exclude(['var','vendor','node_modules','archives','.tmp.driveupload']);
return (new PhpCsFixer\Config())->setRiskyAllowed(true)->setFinder($finder);
"@
    Set-Content -Path $csFixer -Value $code -NoNewline
  } elseif ($content -notmatch "in\(\['src','tests'\]\)") {
    $content = $content -replace "PhpCsFixer\\Finder::create\(\)", "PhpCsFixer\Finder::create()->in(['src','tests'])->exclude(['var','vendor','node_modules','archives','.tmp.driveupload'])"
    Set-Content -Path $csFixer -Value $content -NoNewline
  }
}

# fix Console alias (Command -> BaseCommand)
Get-ChildItem -Path (Join-Path $RepoRoot "src/Command") -Recurse -Filter *.php -ErrorAction SilentlyContinue | ForEach-Object {
  $p = $_.FullName
  $c = Get-Content $p -Raw
  if ($c -match "use\s+Symfony\\Component\\Console\\Command\\Command;") {
    $c = $c -replace "use\s+Symfony\\Component\\Console\\Command\\Command;", "use Symfony\Component\Console\Command\Command as BaseCommand;"
    $c = $c -replace "extends\s+Command", "extends BaseCommand"
    Set-Content -Path $p -Value $c -NoNewline
    Write-Host "  + Patched Console alias: $($_.Name)"
  }
}

# remove cached tmp artifacts
if (Test-Path (Join-Path $RepoRoot ".tmp.driveupload")) {
  git -C $RepoRoot rm -r --cached .tmp.driveupload 2>$null | Out-Null
}

# autoload refresh
composer -d $RepoRoot dump-autoload -o

# analysis
if (Test-Path (Join-Path $RepoRoot "vendor\bin\phpstan.bat")) {
  & "$RepoRoot\vendor\bin\phpstan.bat" analyse "$RepoRoot\src" --level=max --no-progress --error-format=table | Tee-Object -FilePath "$RepoRoot\audit_phpstan.txt"
} elseif (Test-Path (Join-Path $RepoRoot "vendor\bin\phpstan")) {
  & "$RepoRoot\vendor\bin\phpstan" analyse "$RepoRoot\src" --level=max --no-progress --error-format=table | Tee-Object -FilePath "$RepoRoot\audit_phpstan.txt"
}
if (Test-Path (Join-Path $RepoRoot "vendor\bin\php-cs-fixer.bat")) {
  & "$RepoRoot\vendor\bin\php-cs-fixer.bat" fix --dry-run --allow-risky=yes --format=txt | Tee-Object -FilePath "$RepoRoot\audit_csfixer.txt"
} elseif (Test-Path (Join-Path $RepoRoot "vendor\bin\php-cs-fixer")) {
  & "$RepoRoot\vendor\bin\php-cs-fixer" fix --dry-run --allow-risky=yes --format=txt | Tee-Object -FilePath "$RepoRoot\audit_csfixer.txt"
}

# syntax pass
$files = git -C $RepoRoot ls-files *.php
$files | Where-Object {$_ -notmatch '^(vendor/|var/|node_modules/|archives/|\.tmp\.driveupload/)'} | ForEach-Object {
  php -l (Join-Path $RepoRoot $_)
} | Tee-Object -FilePath "$RepoRoot\audit_php_syntax.txt"

Write-Host "=> Critical Fix Pass complete."
