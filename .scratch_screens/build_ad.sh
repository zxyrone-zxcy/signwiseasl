#!/bin/bash
set -e
FF="/c/Users/My PC/AppData/Local/Microsoft/WinGet/Packages/Gyan.FFmpeg_Microsoft.Winget.Source_8wekyb3d8bbwe/ffmpeg-9.0.2-full_build/bin/ffmpeg.exe"
D="/c/Users/My PC/signwiseasl/signwiseasl/.scratch_screens"
VO="$D/vo"
V="/c/Users/My PC/Downloads/Marketing Ads for SignWise/MarketingAdsForSignWise.mp4"
TOTAL=31.0
ORIG_DUR=26.467
PAD=$(awk "BEGIN{printf \"%.3f\", $TOTAL-$ORIG_DUR}")

echo "=== 1. Music bed: 4 chord segments ==="
CHORD_DUR=$(awk "BEGIN{printf \"%.3f\", $TOTAL/4}")
mkdir -p "$D/music"

gen_chord () {
  idx=$1; f1=$2; f2=$3; f4=$4
  "$FF" -y -f lavfi -i "sine=frequency=$f1:duration=$CHORD_DUR" \
        -f lavfi -i "sine=frequency=$f2:duration=$CHORD_DUR" \
        -f lavfi -i "sine=frequency=$f4:duration=$CHORD_DUR" \
        -filter_complex "[0:a][1:a][2:a]amix=inputs=3:weights='1 0.8 0.6':normalize=0[mx];[mx]volume=0.16,afade=t=in:d=0.8,afade=t=out:st=$(awk "BEGIN{printf \"%.3f\", $CHORD_DUR-0.8}"):d=0.8,tremolo=f=0.2:d=0.3[out]" \
        -map "[out]" -ar 44100 -ac 2 "$D/music/chord$idx.wav" -loglevel error
}

# Warm optimistic progression: Am9 - Fmaj7 - Cmaj - Gmaj
gen_chord 1 220.00 261.63 329.63   # A3 C4 E4 (Am)
gen_chord 2 174.61 220.00 261.63   # F3 A3 C4 (F)
gen_chord 3 261.63 329.63 392.00   # C4 E4 G4 (C)
gen_chord 4 196.00 246.94 293.66   # G3 B3 D4 (G)

echo "file 'chord1.wav'" > "$D/music/list.txt"
echo "file 'chord2.wav'" >> "$D/music/list.txt"
echo "file 'chord3.wav'" >> "$D/music/list.txt"
echo "file 'chord4.wav'" >> "$D/music/list.txt"

"$FF" -y -f concat -safe 0 -i "$D/music/list.txt" -c copy "$D/music/pad_raw.wav" -loglevel error

echo "=== 2. Shaker/percussion layer ==="
"$FF" -y -f lavfi -i "anoisesrc=color=white:duration=$TOTAL:amplitude=1" \
  -af "bandpass=f=8000:width_type=h:w=4000,tremolo=f=1.83:d=0.9,volume=0.045" \
  -ar 44100 -ac 2 "$D/music/shaker.wav" -loglevel error

echo "=== 3. Combine pad + shaker, pad to TOTAL ==="
"$FF" -y -i "$D/music/pad_raw.wav" -i "$D/music/shaker.wav" \
  -filter_complex "[0:a][1:a]amix=inputs=2:weights='1 1':normalize=0,apad,atrim=0:$TOTAL[out]" \
  -map "[out]" -ar 44100 -ac 2 "$D/music/music_bed.wav" -loglevel error

echo "=== 4. Voiceover mix (delayed + summed) ==="
# start times in ms: l1=400 l2=9000 l3=14300 l4=19200 l5=21800 l6=26500
"$FF" -y -i "$VO/l1.wav" -i "$VO/l2.wav" -i "$VO/l3.wav" -i "$VO/l4.wav" -i "$VO/l5.wav" -i "$VO/l6.wav" \
  -filter_complex "\
[0:a]adelay=400|400,volume=1.0[a0];\
[1:a]adelay=9000|9000,volume=1.0[a1];\
[2:a]adelay=14300|14300,volume=1.0[a2];\
[3:a]adelay=19200|19200,volume=1.0[a3];\
[4:a]adelay=21800|21800,volume=1.0[a4];\
[5:a]adelay=26500|26500,volume=1.0[a5];\
[a0][a1][a2][a3][a4][a5]amix=inputs=6:normalize=0,apad,atrim=0:$TOTAL[out]" \
  -map "[out]" -ar 44100 -ac 2 "$D/music/vo_mix.wav" -loglevel error

echo "=== 5. Duck music under VO (sidechain compress) ==="
"$FF" -y -i "$D/music/music_bed.wav" -i "$D/music/vo_mix.wav" \
  -filter_complex "[0:a][1:a]sidechaincompress=threshold=0.02:ratio=10:attack=15:release=400:makeup=1[out]" \
  -map "[out]" -ar 44100 -ac 2 "$D/music/music_ducked.wav" -loglevel error

echo "=== 6. Final audio mix ==="
"$FF" -y -i "$D/music/music_ducked.wav" -i "$D/music/vo_mix.wav" \
  -filter_complex "[0:a][1:a]amix=inputs=2:weights='1 1.3':normalize=0,volume=1.6,alimiter=limit=0.95[out]" \
  -map "[out]" -ar 44100 -ac 2 "$D/music/final_audio.wav" -loglevel error

echo "=== 7. Extend video with frozen last frame ==="
"$FF" -y -i "$V" -vf "tpad=stop_mode=clone:stop_duration=$PAD" -an -c:v libx264 -crf 18 -preset medium -pix_fmt yuv420p "$D/music/extended_video.mp4" -loglevel error

echo "=== 8. Mux final ad ==="
"$FF" -y -i "$D/music/extended_video.mp4" -i "$D/music/final_audio.wav" -c:v copy -c:a aac -b:a 192k -shortest "$D/SignWise_Ad_30s.mp4" -loglevel error

echo "DONE"
"$FF" -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 "$D/SignWise_Ad_30s.mp4"
