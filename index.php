<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Whisper API Demo by Ryan Decker</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.4.min.js" integrity="sha256-oP6HI9z1XaZNBrJURtCoUT5SUnxFr8s3BzRl+cbzUq8=" crossorigin="anonymous"></script>
    <script src="https://cdn.WebRTC-Experiment.com/RecordRTC.js"></script>
    <script src="
https://cdn.jsdelivr.net/npm/lamejs@1.2.1/lame.min.js
"></script>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .mic {
            font-size: 10rem;
        }

        .recording {
            color: red;
        }
    </style>
</head>

<body>
    <i class="fa fa-microphone mic" aria-hidden="true"></i>

    <script>
        $(document).ready(function() {
            var mic = $(".mic");
            var isRecording = false;
            var recorder;
            var audioContext, stream;
            var maxRecordingTime = 30 * 1000;

            function convertToMP3(blob) {
                let reader = new FileReader();
                reader.onloadend = function() {
                    let wavBuffer = new Uint8Array(reader.result);
                    let wav = lamejs.WavHeader.readHeader(new DataView(wavBuffer.buffer));

                    let samples = wavBuffer.subarray(lamejs.WavHeader.HEADER_LENGTH);
                    let samples16Bit = new Int16Array(samples.buffer);
                    let mp3encoder = new lamejs.Mp3Encoder(wav.channels, wav.sampleRate, 128);
                    let mp3Data = [];

                    let remaining = samples16Bit.length;
                    let maxSamples = 1152;
                    for (let i = 0; i < remaining; i += maxSamples) {
                        let mono = samples16Bit.subarray(i, i + maxSamples);
                        let buffer = mp3encoder.encodeBuffer(mono);
                        if (buffer.length > 0) {
                            mp3Data.push(buffer);
                        }
                    }

                    let tail = mp3encoder.flush();
                    if (tail.length > 0) {
                        mp3Data.push(tail);
                    }

                    let mp3Blob = new Blob(mp3Data, {
                        type: 'audio/mpeg'
                    });
                    saveMP3(mp3Blob);
                };

                reader.readAsArrayBuffer(blob);
            }

            async function startRecording() {
                audioContext = new(window.AudioContext || window.webkitAudioContext)();

                const getUserMedia = navigator.mediaDevices.getUserMedia ||
                    navigator.getUserMedia ||
                    navigator.webkitGetUserMedia ||
                    navigator.mozGetUserMedia;

                const constraints = {
                    audio: true
                };

                try {
                    stream = await navigator.mediaDevices.getUserMedia(constraints);

                    recorder = new RecordRTC(stream, {
                        type: 'audio',
                        mimeType: 'audio/wav',
                        recorderType: RecordRTC.StereoAudioRecorder,
                        numberOfAudioChannels: 1
                    });
                    recorder.startRecording();

                    setTimeout(stopRecording, maxRecordingTime);
                } catch (error) {
                    console.error("Error getting audio stream:", error);
                }
            }

            function stopRecording() {
                recorder.stopRecording(function() {
                    stream.getAudioTracks()[0].stop();

                    let blob = recorder.getBlob();
                    convertToMP3(blob);
                });
            }

            function saveMP3(blob) {
                let link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = "recording.mp3";
                link.click();
            }

            mic.on("click", function() {
                if (!isRecording) {
                    mic.addClass("recording");
                    isRecording = true;
                    startRecording();
                } else {
                    mic.removeClass("recording");
                    isRecording = false;
                    stopRecording();
                }
            });
        });
    </script>
</body>

</html>
