$javaHome = 'C:\Program Files\Microsoft\jdk-17.0.18.8-hotspot'
if (Test-Path $javaHome) {
  $env:JAVA_HOME = $javaHome
  $env:Path = "$javaHome\bin;$env:Path"
}

Set-Location $PSScriptRoot

if (Test-Path '.\target\e-perpustakaan-1.0.0.jar') {
  & "$env:JAVA_HOME\bin\java.exe" -jar '.\target\e-perpustakaan-1.0.0.jar'
} else {
  .\mvnw.cmd -DskipTests package
  & "$env:JAVA_HOME\bin\java.exe" -jar '.\target\e-perpustakaan-1.0.0.jar'
}
