function Utils(errorOutputId) {
  // eslint-disable-line no-unused-vars
  let self = this;
  this.errorOutput = document.getElementById(errorOutputId);
  this.apiKey = "168508101488d7JV8yvF32yRGRti0hyatBHFwev0KInaUAgtGv";

  const OPENCV_URL = "opencv.js";
  this.loadOpenCv = function (onloadCallback) {
    let script = document.createElement("script");
    script.setAttribute("async", "");
    script.setAttribute("type", "text/javascript");
    script.addEventListener("load", () => {
      if (cv.getBuildInformation) {
        console.log(cv.getBuildInformation());
        onloadCallback();
      } else {
        // WASM
        cv["onRuntimeInitialized"] = () => {
          console.log(cv.getBuildInformation());
          onloadCallback();
        };
      }
    });
    script.addEventListener("error", () => {
      self.printError("Failed to load " + OPENCV_URL);
    });
    script.src = OPENCV_URL;
    let node = document.getElementsByTagName("script")[0];
    node.parentNode.insertBefore(script, node);
  };

  this.createFileFromUrl = function (path, url, callback) {
    let request = new XMLHttpRequest();
    request.open("GET", url, true);
    request.responseType = "arraybuffer";
    request.onload = function (ev) {
      if (request.readyState === 4) {
        if (request.status === 200) {
          let data = new Uint8Array(request.response);
          cv.FS_createDataFile("/", path, data, true, false, false);
          callback();
        } else {
          self.printError(
            "Failed to load " + url + " status: " + request.status
          );
        }
      }
    };
    request.send();
  };

  this.loadImageToCanvas = function (url, cavansId) {
    let canvas = document.getElementById(cavansId);
    let ctx = canvas.getContext("2d");
    let img = new Image();
    img.crossOrigin = "anonymous";
    img.onload = function () {
      canvas.width = img.width;
      canvas.height = img.height;
      ctx.drawImage(img, 0, 0, img.width, img.height);
    };
    img.src = url;
  };

  this.executeCode = function (textAreaId) {
    try {
      this.clearError();
      let code = document.getElementById(textAreaId).value;
      eval(code);
    } catch (err) {
      this.printError(err);
    }
  };

  this.clearError = function () {
    this.errorOutput.innerHTML = "";
  };

  this.printError = function (err) {
    if (typeof err === "undefined") {
      err = "";
    } else if (typeof err === "number") {
      if (!isNaN(err)) {
        if (typeof cv !== "undefined") {
          err = "Exception: " + cv.exceptionFromPtr(err).msg;
        }
      }
    } else if (typeof err === "string") {
      let ptr = Number(err.split(" ")[0]);
      if (!isNaN(ptr)) {
        if (typeof cv !== "undefined") {
          err = "Exception: " + cv.exceptionFromPtr(ptr).msg;
        }
      }
    } else if (err instanceof Error) {
      err = err.stack.replace(/\n/g, "<br>");
    }
    this.errorOutput.innerHTML = err;
  };

  this.loadCode = function (scriptId, textAreaId) {
    let scriptNode = document.getElementById(scriptId);
    let textArea = document.getElementById(textAreaId);
    if (scriptNode.type !== "text/code-snippet") {
      throw Error("Unknown code snippet type");
    }
    textArea.value = scriptNode.text.replace(/^\n/, "");
  };

  this.addFileInputHandler = function (fileInputId, canvasId) {
    let inputElement = document.getElementById(fileInputId);
    inputElement.addEventListener(
      "change",
      (e) => {
        let files = e.target.files;
        if (files.length > 0) {
          let imgUrl = URL.createObjectURL(files[0]);
          self.loadImageToCanvas(imgUrl, canvasId);
        }
      },
      false
    );
  };

  function onVideoCanPlay() {
    if (self.onCameraStartedCallback) {
      self.onCameraStartedCallback(self.stream, self.video);
    }
  }

  this.startCamera = function (resolution, callback, videoId) {
    const constraints = {
      qvga: { width: { exact: 320 }, height: { exact: 240 } },
      vga: { width: { exact: 640 }, height: { exact: 480 } },
    };
    let video = document.getElementById(videoId);
    if (!video) {
      video = document.createElement("video");
    }

    let videoConstraint = constraints[resolution];
    if (!videoConstraint) {
      videoConstraint = true;
    }

    navigator.mediaDevices
      .getUserMedia({ video: videoConstraint, audio: false })
      .then(function (stream) {
        video.srcObject = stream;
        video.play();
        self.video = video;
        self.stream = stream;
        self.onCameraStartedCallback = callback;
        video.addEventListener("canplay", onVideoCanPlay, false);
      })
      .catch(function (err) {
        self.printError("Camera Error: " + err.name + " " + err.message);
      });
  };

  this.stopCamera = function () {
    if (this.video) {
      this.video.pause();
      this.video.srcObject = null;
      this.video.removeEventListener("canplay", onVideoCanPlay);
    }
    if (this.stream) {
      this.stream.getVideoTracks()[0].stop();
    }
  };

  this.captureImage = function (canvasId) {
    let canvas = document.getElementById(canvasId);
    let dataUrl = canvas.toDataURL("image/jpeg");
    return dataUrl;
  };

  this.stringFunction = function (dynamicString) {
    console.log("Dynamic string:", dynamicString);

    document.getElementById("status-text").innerText = dynamicString;
  };

  this.captureAndSend = function () {
    let video = document.getElementById("cam_input");
    let canvas = document.getElementById("canvas_output");

    // Hide the video and show the canvas after capturing the photo
    video.style.display = "none";
    canvas.style.display = "block";
    isCanvasVisible = true;

    // Create a canvas element to temporarily hold the image data
    let tempCanvas = document.createElement("canvas");
    tempCanvas.width = video.videoWidth;
    tempCanvas.height = video.videoHeight;
    let ctx = tempCanvas.getContext("2d");
    ctx.drawImage(video, 0, 0, tempCanvas.width, tempCanvas.height);

    // Draw the oval shape on the temporary canvas
    ctx.save();
    ctx.beginPath();
    ctx.moveTo(tempCanvas.width / 2, 0);
    ctx.bezierCurveTo(
      tempCanvas.width,
      0,
      tempCanvas.width,
      tempCanvas.height,
      tempCanvas.width / 2,
      tempCanvas.height
    );
    ctx.bezierCurveTo(0, tempCanvas.height, 0, 0, tempCanvas.width / 2, 0);
    ctx.clip();
    ctx.drawImage(tempCanvas, 0, 0);
    ctx.restore();

    let imageDataUrl = tempCanvas.toDataURL("image/jpeg");
    
//     // Check if imageDataUrl is valid
//     if (!imageDataUrl) {
//       console.error("Invalid imageDataUrl");
//       return;
//     }

//     // Send the captured image to the API
//     fetch("https://accurascan.com/v2/api/liveness", {
//       method: "POST",
//       headers: {
//         "Api-Key": self.apiKey,
//         "Content-Type": "application/json",
//       },
//       body: JSON.stringify({
//         liveness_image: imageDataUrl,
//       }),
//     })
//       .then((response) => {
//         // Check if the response is successful (status code 2xx)
//         if (!response.ok) {
//           throw new Error(
//             `Network response was not ok, status: ${response.status}`
//           );
//         }
//         return response.json(); // Parse response body as JSON
//       })
//       .then(function (data) {
//         console.log("API response:", data);

//         // Check the "status" property in the data object and display appropriate messages
//         if (data.status === true) {
//           self.stringFunction(
//             "Face detected! Quality: " +
//               data.data.quality +
//               ", Score: " +
//               data.data.score
//           );
//           //When face is detected
//         } else {
//           self.stringFunction("No face detected or other status");
//           // When no face is detected or handle other status scenarios if needed
//         }
//       })
//       .catch((error) => {
//         console.error("Error sending image to API:", error);
//         self.stringFunction("Error sending image to API");
//       });
  };
}
