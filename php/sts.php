<?php
// 临时密钥计算样例

// 配置参数
$config = array(
    'Url' => 'https://sts.api.qcloud.com/v2/index.php',
    'Domain' => 'sts.api.qcloud.com',
    'Proxy' => '', 
    'SecretId' => 'AKIDxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', // 固定密钥
    'SecretKey' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', // 固定密钥
    'Bucket' => 'test-1250000000', // 换成你的 bucket
    'Region' => 'ap-guangzhou', // 换成 bucket 所在地区
    'DurationSeconds' => 1800, // 密钥有效期
    'AllowPrefix' => '*', // 这里改成允许的路径前缀，可以根据自己网站的用户登录态判断允许上传的目录，例子：* 或者 a/* 或者 a.jpg
    // 密钥的权限列表。简单上传和分片需要以下的权限，其他权限列表请看 https://cloud.tencent.com/document/product/436/14048
    'allowActions' => array (
        // 简单上传
        'name/cos:PutObject',
        // 分片上传
        'name/cos:InitiateMultipartUpload',
        'name/cos:ListMultipartUploads',
        'name/cos:ListParts',
        'name/cos:UploadPart',
        'name/cos:CompleteMultipartUpload'
    )
);

function _hex2bin($data) {
    $len = strlen($data);
    return pack("H" . $len, $data);
}

// obj 转 query string
function json2str($obj, $notEncode = false) {
    ksort($obj);
    $arr = array();
    foreach ($obj as $key => $val) {
        array_push($arr, $key . '=' . ($notEncode ? $val : rawurlencode($val)));
    }
    return join('&', $arr);
}

// 计算临时密钥用的签名
function getSignature($opt, $key, $method) {
    global $config;
    $formatString = $method . $config['Domain'] . '/v2/index.php?' . json2str($opt, 1);
    $sign = hash_hmac('sha1', $formatString, $key);
    $sign = base64_encode(_hex2bin($sign));
    return $sign;
}

// 获取临时密钥
function getTempKeys() {

    global $config;

    $ShortBucketName = substr($config['Bucket'],0, strripos($config['Bucket'], '-'));
    $AppId = substr($config['Bucket'], 1 + strripos($config['Bucket'], '-'));
    $policy = array(
        'version'=> '2.0',
        'statement'=> array(
            array(
                'action'=> $config['allowActions'],
                'effect'=> 'allow',
                'principal'=> array('qcs'=> array('*')),
                'resource'=> array(
                    'qcs::cos:' . $config['Region'] . ':uid/' . $AppId . ':prefix//' . $AppId . '/' . $ShortBucketName . '/' . $config['AllowPrefix']
                )
            )
        )
    );

    $policyStr = str_replace('\\/', '/', json_encode($policy));
    $Action = 'GetFederationToken';
    $Nonce = rand(10000, 20000);
    $Timestamp = time() - 1;
    $Method = 'POST';

    $params = array(
        'Region'=> $config['Region'],
        'SecretId'=> $config['SecretId'],
        'Timestamp'=> $Timestamp,
        'Nonce'=> $Nonce,
        'Action'=> $Action,
        'durationSeconds'=> $config['DurationSeconds'],
        'name'=> 'cos',
        'policy'=> urlencode($policyStr)
    );
    $params['Signature'] = getSignature($params, $config['SecretKey'], $Method);

    $url = $config['Url'];
    $ch = curl_init($url);
    $config['Proxy'] && curl_setopt($ch, CURLOPT_PROXY, $config['Proxy']);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json2str($params));
    $result = curl_exec($ch);
    if(curl_errno($ch)) $result = curl_error($ch);
    curl_close($ch);

    $result = json_decode($result, 1);
    if (isset($result['data'])) $result = $result['data'];
    $result['startTime'] = $result['expiredTime'] - $config['DurationSeconds'];

    return $result;
}

// 获取临时密钥，计算签名
$tempKeys = getTempKeys();

// 返回数据给前端
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://127.0.0.1'); // 这里修改允许跨域访问的网站
header('Access-Control-Allow-Headers: origin,accept,content-type');
echo json_encode($tempKeys);
