<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Text on Image</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
</head>
<body>
    <div class="container">
    <!-- <label for="formFileLg" class="form-label">Select File for text</label> -->
    <input class="form-control form-control-lg mt-5" type="file" id="upload" accept="image/*">
    <br>
    <div class="input-group input-group-lg">
        <span class="input-group-text" id="inputGroup-sizing-lg">Enter your name</span>
        <input type="text" id="text" class="form-control" aria-label="Sizing example input" aria-describedby="inputGroup-sizing-lg">
    </div>
    <br>
    <div class="form-check">
    <input class="form-check-input" type="radio" id="design1" name="design" value="1">
        <label class="form-check-label" for="design1">
            Small Image
        </label>
    </div>
    <div class="form-check">
        <input class="form-check-input" type="radio" id="design2" name="design" value="0">
        <label class="form-check-label" for="design2">
            Larg Image
        </label>
    </div>
    <br>
    <button onclick="drawText()" class="btn btn-primary my-3">Draw Text</button>
    <button onclick="downloadImage()" class="btn btn-danger">Download Image</button>
    <br>
    <canvas id="canvas"></canvas>
    </div>
    <script>
        let canvas = document.getElementById('canvas');
        let ctx = canvas.getContext('2d');
        let img = new Image();

        document.getElementById('upload').addEventListener('change', function (e) {
            let reader = new FileReader();
            reader.onload = function (event) {
                img.onload = function () {
                    canvas.width = img.width;
                    canvas.height = img.height;
                    ctx.drawImage(img, 0, 0);
                }
                img.src = event.target.result;
            }
            reader.readAsDataURL(e.target.files[0]);
        });
        function drawText() {
            let text = document.getElementById('text').value;
            let design = document.querySelector('input[name="design"]:checked').value;
            console.log(design);
            ctx.drawImage(img, 0, 0);  // Redraw the image to clear previous text
            ctx.font = (design == 1)?"40px DecoType Thuluth":"90px Calibri";
            ctx.fillStyle = (design == 1)?"black":"#213E4E";
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            let x = canvas.width / 2; // let y = canvas.height / 2;
            let y = (design == 1)?"430":"2400";
            ctx.fillText(text, x, y);
            // ctx.fillText(text, 320, 430);  // Change coordinates as needed
        }
        function downloadImage() {
            let text = document.getElementById('text').value;
            let link = document.createElement('a');
            link.download = text + '-image.png';
            link.href = canvas.toDataURL();
            link.click();
        }
    </script>
</body>
</html>
