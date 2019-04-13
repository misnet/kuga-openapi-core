# Aliyun OSS Configure Example
阿里云OSS的配置文件 config.json 示例：
```
{
    "AccessKeyId": "xxx",
    "AccessKeySecret": "xxxxx",
    "RoleArn": "acs:ram::1091890318479917:role/app-oss",
    "TokenExpireTime": "3600",
    "PolicyFile": "policy-all.txt",

    "RoleSessionName": "kuga",
    "Bucket": {
        "Region": "cn-shenzhen",
        "Name": "product",
        "Endpoint": "oss-cn-shenzhen.aliyuncs.com",
        "Host": "https://product.oss-cn-shenzhen.aliyuncs.com"
    },
    "TestBucket": {
        "Region": "cn-shenzhen",
        "Name": "test",
        "Endpoint": "oss-cn-shenzhen.aliyuncs.com",
        "Host": "https://test.oss-cn-shenzhen.aliyuncs.com"
    }
}
```

policy-all.txt的格式示例
```
{
  "Statement": [
    {
      "Action": [
        "oss:*"
      ],
      "Effect": "Allow",
      "Resource": ["acs:oss:*:*:*"]
    }
  ],
  "Version": "1"
}
```

调用示例：
```
$option = [
    'configFile'=>QING_ROOT_PATH.'/config/aliunoss/config.json',
    'policyFile'=>QING_ROOT_PATH.'/config/aliunoss/policy-all.txt',
];

\Kuga\Core\Service\FileService::factory('aliyun',$option,$di);
```