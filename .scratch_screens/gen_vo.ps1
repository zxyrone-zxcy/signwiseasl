Add-Type -AssemblyName System.Speech
$outDir = $args[0]
New-Item -ItemType Directory -Force -Path $outDir | Out-Null

$lines = @(
    @{ name = "l1"; text = "What if sign language felt this natural?" },
    @{ name = "l2"; text = "Meet SignWise. Your webcam, turned sign language coach." },
    @{ name = "l3"; text = "Watch the sign. Follow the motion. Practice in real time." },
    @{ name = "l4"; text = "Get instant feedback on every gesture." },
    @{ name = "l5"; text = "Build your streak. Track your progress. Keep getting better." },
    @{ name = "l6"; text = "SignWise. Learn sign language, with confidence." }
)

$synth = New-Object System.Speech.Synthesis.SpeechSynthesizer
$voice = $synth.GetInstalledVoices() | Where-Object { $_.VoiceInfo.Name -eq "Microsoft Zira Desktop" }
if ($voice) { $synth.SelectVoice("Microsoft Zira Desktop") }
$synth.Rate = 2

foreach ($l in $lines) {
    $path = Join-Path $outDir ($l.name + ".wav")
    $synth.SetOutputToWaveFile($path, (New-Object System.Speech.AudioFormat.SpeechAudioFormatInfo(44100, [System.Speech.AudioFormat.AudioBitsPerSample]::Sixteen, [System.Speech.AudioFormat.AudioChannel]::Mono)))
    $synth.Speak($l.text)
    $synth.SetOutputToNull()
    Write-Output ("Wrote " + $path)
}
