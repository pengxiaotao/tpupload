<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>上传图片并识别</title>
</head>

<body>
    <h1>上传图片并识别</h1>
    <input type="file" id="image" accept="image/*">
    <button onclick="uploadImage()">上传并识别</button>
    <div id="result"></div>

    <script>
        function uploadImage() {
            const fileInput = document.getElementById('image');
            const file = fileInput.files[0];

            if (file) {
                const formData = new FormData();
                formData.append('image', file);

                fetch('api_upload.php', {
                    method: 'POST',
                    body: formData
                })
                  .then(response => response.json())
                  .then(data => {
                        if (data.result) {
                            document.getElementById('result').innerHTML = `识别结果：${data.result}`;
                        } else {
                            document.getElementById('result').innerHTML = `错误：${data.error}`;
                        }
                    })
                  .catch(error => {
                        document.getElementById('result').innerHTML = `错误：${error.message}`;
                    });
            } else {
                alert('请选择要上传的图片');
            }
        }
    </script>
</body>

</html>