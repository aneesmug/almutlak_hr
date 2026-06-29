<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نص عربي على الصورة | Arabic Text on Image</title>
    <style>
        /* Fallback font in case Scheherazade fails */
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }

        canvas {
            border: 2px solid #466699;
            max-width: 100%;
            background: white;
        }

        input {
            padding: 12px;
            font-size: 18px;
            width: 80%;
            max-width: 500px;
            margin: 15px 0;
            border: 1px solid #466699;
        }

        button {
            padding: 12px 25px;
            background: #466699;
            color: white;
            border: none;
            font-size: 18px;
            cursor: pointer;
            margin: 10px;
        }

        button:disabled {
            background: #cccccc;
        }

        .error {
            color: red;
            margin: 15px;
        }
    </style>
</head>
<body>
    <h1>أضف نصاً عربياً على الصورة</h1>
    <div>
        <input type="text" id="textInput" placeholder="اكتب اسمك هنا..." dir="rtl">
    </div>
    <button id="downloadBtn" disabled>حفظ الصورة</button>
    <div id="errorMessage" class="error"></div>
    <canvas id="imageCanvas" width="800" height="600"></canvas>

    <script>
        // ================== FONT LOADING ================== //
        const FONT_URL = 'https://fonts.gstatic.com/s/almarai/v18/tsstApxBaigK_hnnQ1iFo0C3.woff2';
        let fontLoaded = false;
        async function loadFont() {
            try {
                // Method 1: Using FontFace API (modern browsers)
                const font = new FontFace('Almarai', `url(${FONT_URL})`);
                await font.load();
                document.fonts.add(font);
                fontLoaded = true;
                console.log('Font loaded successfully using FontFace API');
            } catch (error) {
                console.warn('FontFace API failed, trying fallback method...');
                // Method 2: Fallback using @font-face
                const style = document.createElement('style');
                style.textContent = `
                    @font-face {
                        font-family: 'Almarai';
                        font-style: normal;
                        font-weight: 400;
                        src: url('${FONT_URL}');
                    }
                `;
                document.head.appendChild(style);
                // Verify font loaded
                await document.fonts.ready;
                fontLoaded = true;
                console.log('Font loaded using @font-face fallback');
            }
        }
        // ================== IMAGE HANDLING ================== //
        let baseImage = new Image();
        async function loadImage() {
            return new Promise((resolve) => {
                baseImage.crossOrigin = "anonymous";
                baseImage.src = "design.jpg"; // Replace with your image path
                baseImage.onload = () => {
                    const canvas = document.getElementById("imageCanvas");
                    canvas.width = baseImage.width;
                    canvas.height = baseImage.height;
                    canvas.getContext('2d').drawImage(baseImage, 0, 0);
                    resolve();
                };
                baseImage.onerror = () => {
                    console.warn('Image failed to load, using blank canvas');
                    const canvas = document.getElementById("imageCanvas");
                    const ctx = canvas.getContext('2d');
                    ctx.fillStyle = '#f0f0f0';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    resolve();
                };
            });
        }

        // ================== TEXT RENDERING ================== //
        function updateText() {
            const canvas = document.getElementById("imageCanvas");
            const ctx = canvas.getContext("2d");
            const text = document.getElementById("textInput").value.trim();
            // Redraw base image
            ctx.drawImage(baseImage, 0, 0);
            if (text && fontLoaded) {
                ctx.font = "bold 50px 'Almarai'";
                ctx.fillStyle = "#466699";
                ctx.textAlign = "center";
                ctx.textBaseline = "middle";
                // ctx.fillText(text, canvas.width/2, canvas.height/2);
                ctx.fillText(text, canvas.width/2, 650); // Adjust position as needed
                document.getElementById("downloadBtn").disabled = false;
            } else if (text) {
                showError("الخط العربي لم يحمل بعد. يرجى الانتظار...");
            }
        }

        // ================== DOWNLOAD ================== //
        function downloadImage() {
            const canvas = document.getElementById("imageCanvas");
            const fileName = document.getElementById("textInput").value.trim() || "صورة-نص";
            const link = document.createElement("a");
            link.download = `${fileName}.jpg`;
            link.href = canvas.toDataURL("image/jpg");
            link.click();
        }

        // ================== ERROR HANDLING ================== //
        function showError(message) {
            document.getElementById("errorMessage").textContent = message;
            setTimeout(() => {
                document.getElementById("errorMessage").textContent = "";
            }, 5000);
        }

        // ================== INITIALIZATION ================== //
        async function initialize() {
            try {
                await Promise.all([loadFont(), loadImage()]);
                document.getElementById("textInput").addEventListener("input", updateText);
                document.getElementById("downloadBtn").addEventListener("click", downloadImage);
                console.log("Application ready");
            } catch (error) {
                showError("حدث خطأ في تحميل التطبيق. يرجى تحديث الصفحة.");
                console.error("Initialization error:", error);
            }
        }

        // Start the application
        window.addEventListener('load', initialize);
    </script>
</body>
</html>