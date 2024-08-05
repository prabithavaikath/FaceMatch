<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" 
/>
    <title>Face Scanning App</title>
    <script src="/plugins/ocvjs/js/utils.js"></script>
    <script async src="/plugins/ocvjs/js/opencv.js" onload="openCvReady();"></script>
  
    <style>
      body {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
        background-color: rgb(225, 225, 225);
        font-family: Arial, sans-serif;
        flex-direction: column;
      }

      #container {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 20px;
        background-color: transparent;
        border-radius: 10px;
      }

      #description {
        text-align: center;
        margin-bottom: 10px;
        font-size: 16px;
        color: #666;
      }

      #instruction-box {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 10px;
        background-color: #fff;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        border-radius: 5px;
        margin-bottom: 20px;
      }

      #instruction-text {
        font-size: 20px;
        font-weight: bold;
        color: #333;
      }

      #cam_input {
        width: 700px;
        height: 700px;
        border-radius: 100%;
        -webkit-mask-image: -webkit-radial-gradient(circle, white 100%, 
black 100%);
        -webkit-transform: rotate(0.000001deg);
        -webkit-border-radius: 100%;
        -moz-border-radius: 100%;
        display: none; 
      }

       #canvas_output {
        height: 370px;
        width: 250px;
        border-radius: 50%;
        object-fit: cover;
      }

      #status-text {
        font-size: 18px;
        color: #333;
        margin-top: 10px;
      }

      #powered-by {
        text-align: center;
        font-size: 12px;
        color: #000000;
      }

      #powered-logo {
        width: 200px;
        margin: 0 auto;
        display: inline-block;
        margin-bottom: 20px;
      }
      #IBETA_logo {
        width: 130px;
        margin: 0 5px;
        display: inline-block;
      }

      #loader {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 9999;
      }

      .loader {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #3498db;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        animation: spin 2s linear infinite;
      }

      @keyframes spin {
        0% {
          transform: rotate(0deg);
        }
        100% {
          transform: rotate(360deg);
        }
      }
    </style>
  </head>

  <body>
    <div id="container">
      <div id="powered-by">
        <img id="powered-logo" src="/plugins/ocvjs/purple_logo_accura.png" alt="Powered 
by Your Logo" />
      </div>
      <div id="instruction-box">
        <div id="instruction-text"></div>
      </div>
      <video id="cam_input" height="480" width="640"></video>
      <div id="loader" style="display: none;">
        <div class="loader"></div>
      </div>
      <canvas id="canvas_output" width="640" height="480"></canvas>
      <div id="response-text"></div>
      <div id="powered-by">
        <a 
href="https://ibeta.com/wp-content/uploads/2022/08/220816-Accura-Scan-PAD-Level-1-Confirmation-Letter.pdf" 
target="_blank">
          <img id="IBETA_logo" src="/plugins/ocvjs/images/IBETA-Level-1.webp" 
alt="IBETA-Level-1" />
        </a>
        <a 
href="https://www.ibeta.com/wp-content/uploads/2023/07/230712-Accura-Scan-PAD-Level-2-APCER-Confirmation-Letter.pdf" 
target="_blank">
          <img style="padding-bottom: 3px;" id="IBETA_logo" 
src="/plugins/ocvjs/images/IBETA-Level-2.webp" alt="IBETA-Level-2" />
        </a>
      </div>
    </div>

    <script type="text/JavaScript">
      let utils;
      let isCanvasVisible = false;

      function openCvReady() {
        cv['onRuntimeInitialized'] = () => {
          let video = document.getElementById("cam_input");
          let canvas = document.getElementById("canvas_output");
          let canvasContainer = document.getElementById("container");
          let instructionText = 
document.getElementById("instruction-text");
          navigator.mediaDevices.getUserMedia({
            video: true,
            audio: false
          })
          .then(function(stream) {
            video.srcObject = stream;
            video.play();
            isCanvasVisible = true;

            let videoAspect = video.videoWidth / video.videoHeight;
            let canvasWidth = canvasContainer.clientWidth;
            let canvasHeight = canvasWidth / videoAspect;

            canvas.width = canvasWidth;
            canvas.height = canvasHeight;
          })
          .catch(function(err) {
            console.log("An error occurred! " + err);
          });

          let src = new cv.Mat(video.height, video.width, cv.CV_8UC4);
          let dst = new cv.Mat(video.height, video.width, cv.CV_8UC4);
          let gray = new cv.Mat();
          let cap = new cv.VideoCapture(video);
          let faces = new cv.RectVector();
          let classifier = new cv.CascadeClassifier();
          utils = new Utils('errorMessage');
          let faceCascadeFile = 'haarcascade_frontalface_default.xml';
          utils.createFileFromUrl(faceCascadeFile, faceCascadeFile, () => 
{
            classifier.load(faceCascadeFile);
          });

          const FPS = 1080;
          let faceDetectionCount = 0;

          const ZOOM_FACTOR = 1.5;

          function isFaceInsideOval(face, canvas) {
            const faceCenterX = face.x + face.width / 3;
            const faceCenterY = face.y + face.height / 3;

            const ovalCenterX = canvas.width / 3;
            const ovalCenterY = canvas.height / 3;

            const a = canvas.width / 3;
            const b = canvas.height / 3;

            const distanceSquared = Math.pow((faceCenterX - ovalCenterX) / 
a, 2) +
                                    Math.pow((faceCenterY - ovalCenterY) / 
b, 2);

            return distanceSquared <= 1;
          }



         function processVideo() {
            let begin = Date.now();
            cap.read(src);

            // Zoom the video frame
            let zoomedSrc = new cv.Mat();
            let zoomRect = new cv.Rect(
              src.cols / 2 - (src.cols / (2 * ZOOM_FACTOR)),
              src.rows / 2 - (src.rows / (2 * ZOOM_FACTOR)),
              src.cols / ZOOM_FACTOR,
              src.rows / ZOOM_FACTOR
            );
            zoomedSrc = src.roi(zoomRect);
            cv.resize(zoomedSrc, dst, new cv.Size(src.cols, src.rows), 0, 0, cv.INTER_LINEAR);

            let clonedst = new cv.Mat();
	    dst.copyTo(clonedst);

            instructionText.textContent = "Keep Your Face In The Frame";

            // Perform face detection on the zoomed image
            let faces1 = new cv.RectVector();
            let faces = new cv.RectVector();
            try {
              classifier.detectMultiScale(src, faces, 1.5, 3, 0);
              classifier.detectMultiScale(dst, faces1, 1.5, 3, 0);

                           console.log("Face size is " + faces.size() + faces1.size());

              if (faces1.size() === 1) {
                const face = faces.get(0);
                const face1 = faces1.get(0);
                const rect = new cv.Rect(face.x, face.y, face.width, face.height);
 
               // cv.rectangle(dst, new cv.Point(rect.x, rect.y), new cv.Point(rect.x + rect.width, rect.y + rect.height), [255, 0, 0, 255], 2);

                         
	    const bufferRatio = 0.14; // 18% buffer
            let bufferedX = Math.max(0, face.x - face.width * bufferRatio);
            let bufferedY = Math.max(0, face.y - face.height * bufferRatio);
            let bufferedWidth = Math.min(src.cols - bufferedX, face.width * (1 + 2 * bufferRatio));
            let bufferedHeight = Math.min(src.rows - bufferedY, face.height * (1 + 2 * bufferRatio));

            const bufferedRect = new cv.Rect(bufferedX, bufferedY, bufferedWidth, bufferedHeight);


                
                // Draw the buffered rectangle on the canvas
                cv.rectangle(src, new cv.Point(bufferedRect.x, 
bufferedRect.y), new cv.Point(bufferedRect.x + bufferedRect.width, 
bufferedRect.y + bufferedRect.height), [5, 255, 0, 255], 2);

                const faceArea = face1.width * face1.height;
                const ovalArea = Math.PI * (canvas.width / 2) * (canvas.height / 2);
                const faceAreaPercentage = (faceArea / ovalArea) * 100;

                if (!isFaceInsideOval(face1, canvas)) {
                  instructionText.textContent = "Move inside the oval window"; 
                  faceDetectionCount = 0;
                } else if (faceAreaPercentage < 40) {
                  instructionText.textContent = "Stay closer to the window";
                  faceDetectionCount = 0;
                } else if (faceAreaPercentage >= 40 && faceAreaPercentage 
<= 100) {
                  instructionText.textContent = "Processing....";
                  faceDetectionCount++;
                  if (faceDetectionCount === 3) {
                    uploadBufferedFaceImage(bufferedRect);
                    showLoader();
                    return false;
                  }
                }
              }
            } catch (err) {
              console.log(err);
            } finally {
                          faces1.delete();
            }

            // Show the result on the canvas
            if (isCanvasVisible) {
              cv.flip(dst, dst, 1);
             // cv.imshow("canvas_output", dst);
              cv.imshow("canvas_output", dst);
            }

            let delay = 1000 / FPS - (Date.now() - begin);
            setTimeout(processVideo, delay);
          }

function uploadBufferedFaceImage(rect) {
  // Create a temporary canvas to draw the entire video frame
  const videoCanvas = document.createElement('canvas');
  videoCanvas.width = video.videoWidth;
  videoCanvas.height = video.videoHeight;
  const videoCtx = videoCanvas.getContext('2d');
  videoCtx.drawImage(video, 0, 0, videoCanvas.width, videoCanvas.height);

  // Create another canvas to draw the buffered rectangle
  const cropCanvas = document.createElement('canvas');
  cropCanvas.width = rect.width;
  cropCanvas.height = rect.height;
  const cropCtx = cropCanvas.getContext('2d');

  // Get the image data from the video canvas and draw it onto the crop canvas
  const imageData = videoCtx.getImageData(rect.x, rect.y, rect.width, rect.height);
  cropCtx.putImageData(imageData, 0, 0);

  // Convert the crop canvas to a blob and upload it
  cropCanvas.toBlob((blob) => {
    const formData = new FormData();
    formData.append('image', blob, 'buffered_face_image.jpg');
    
    fetch('http://192.168.1.57:8012/upload.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        alert('Please try again. Something went wrong.');
        console.log(data);
        window.location.reload();
      } else {
        console.log('Image uploaded successfully');
        const score = data.score;
        if (score >= 50) {
          window.location.href = 'verified.html';
        } else {
          window.location.href = 'failed.html';
        }
      }
    })
    .catch(error => {
      console.error('Error uploading image:', error);
      alert('Please try again. Something went wrong.');
      window.location.reload();
    });

    // Create a download link for the cropped image
    const downloadLink = document.createElement('a');
    downloadLink.href = URL.createObjectURL(blob);
    downloadLink.download = 'buffered_face_image.jpg';
    downloadLink.style.display = 'none'; // Hide the link
    document.body.appendChild(downloadLink);
    downloadLink.click(); // Trigger the download
    document.body.removeChild(downloadLink); // Clean up*
  }, 'image/jpeg');
}

          function showLoader() {
            const loader = document.getElementById('loader');
            loader.style.display = 'inline-flex';
          }

          setTimeout(processVideo, 0);
        };
      }
    </script>
  </body>
</html>
