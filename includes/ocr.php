
<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once 'config.php';
require_once 'functions.php';
use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Ocr\V20181119\OcrClient;
use TencentCloud\Ocr\V20181119\Models\GeneralBasicOCRRequest;

function tencent_ocr($file_path)
{
    try {
        // 读取图片文件内容并进行 Base64 编码
        $file_content = file_get_contents($file_path);
        $base64_image = base64_encode($file_content);

        // 实例化一个认证对象，入参需要传入腾讯云账户 SecretId 和 SecretKey
        $cred = new Credential(TENCENT_OCR_SECRETID, TENCENT_OCR_SECRETKEY);

        // 实例化一个 http 选项，可选的，没有特殊需求可以跳过
        $httpProfile = new HttpProfile();
        $httpProfile->setEndpoint("ocr.tencentcloudapi.com");

        // 实例化一个 client 选项，可选的，没有特殊需求可以跳过
        $clientProfile = new ClientProfile();
        $clientProfile->setHttpProfile($httpProfile);

        // 实例化要请求产品的 client 对象,clientProfile 是可选的
        $client = new OcrClient($cred, "", $clientProfile);

        // 实例化一个请求对象,每个接口都会对应一个 request 对象
        $req = new GeneralBasicOCRRequest();

        $params = [
            "ImageBase64" => $base64_image
        ];
        $req->fromJsonString(json_encode($params));

        // 发送请求并获取响应
        $resp = $client->GeneralBasicOCR($req);

        // 将响应结果转换为数组
        $result = json_decode($resp->toJsonString(), true);

        // 提取识别出的文字
        $text = '';
        if (isset($result['TextDetections'])) {
            foreach ($result['TextDetections'] as $detection) {
                $text .= $detection['DetectedText'] . "\n";
            }
        }

        return $text;
    } catch (TencentCloudSDKException $e) {
        // 记录错误日志
        log_message("腾讯 OCR 识别出错: " . $e->getMessage());
        return false;
    }
}