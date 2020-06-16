## Kuga-openapi-core
提供API网关系统基础功能框架，功能包话邮件发送、短信发送、OSS配置、日志服务、配置服务、多语言服务等，可根据需要在Init.php中修改进行启用或禁用。
##API网关使用说明

- API网关处理程序在src/Core/Api/ApiService.php中
- 不同的接口处理约定在api的json说明文件中约定了，具体见api-json-samle这个例子目录
- 处理API的程序是根据json文件的约定，在src/Api目录中
- api.json文件的规范及相关api的约定见[http://github.com/misnet/apidocs]
- API网关会根据json文件的约定，对json文件中request部分进行校验，也会根据文件约定是否
需要accessToken进行校验；同时json文件中约定了API处理接口的命名空间及处理的类和方法。
- config-sample中的内容为示例内容，是当前应用可能要读取用到的相关配置。
- 本应用不能单独使用。
- 配置中apiLogEnabled启用时，需要配置redis支持

## API参数
有两种类型，一种是业务参数，一种是公共参数，公共参数一般是每个基本都需要或可以传递的内容。业务参数是指和当前接口有关的参数。
在本应用中，公共参数一般有:
- appkey appkey值，需要系统之前有分配
- version 版本号
- locale  语言，值有en_US，zh_CN
- access-token-type token类型，值有KUGA、JWT
- access_token 用于KUGA类型的token传值
- Authorization  用于JWT的token传值，一般是"Bearer "再加token字串

## token类型
本应用支持两种类型的token，一种是KUGA，一种是JWT：

KUGA类型token特点：
- 所有传参全部通过\Kuga\Core\Api\Request\BaseRequest或\Kuga\Core\Api\Request\JWTRequest 的构造函数传递
- headers实际不传参数
- 传递的参数需要appkey， appkey是在配置文件中与appsecret配套的一对
- 传递的参数需要sign，sign是签名串，根据一定的规则生成的签名串。因为需要签名，客户端需要知道签名规则
- 生成sign值的参数包括业务参数以及公共参数

JWT特点：
- 业务参数通过\Kuga\Core\Api\Request\JWTRequest 的构造函数传递
- 公共参数通过headers传递
- 对参数不做签名验证
- 生成JWT需要配置在配置文件中 配置 jwtTokenSecret 参数，验证与生成JWT全部在API中进行，通过验证JWT可以得到JWT中的参数
## 初始化示例：
```
$customConfig = include('config-sample/config.default.php');
//不设置默认用/tmp
Kuga\Init::setTmpDir('/opt/tmp);
Kuga\Init::setup($customConfig);
```
## API网关调用示例：
```
$apiKeys = [
    ['1000']=>['secret'=>'abc'],
    ['1001']=>['secret'=>'def']
];
$requestObject = new \Kuga\Core\Api\Request\JWTRequest($_POST);
$requestObject->setOrigRequest($_POST);
$requestObject->setMethod('acc.app.list');
$requestObject->setHeaders($headers);//request header 数组
$requestObject->setDI($this->getDI());  //di对象是Phalcon的di对象

\Kuga\Core\Api\ApiService::setDi($this->getDI());
\Kuga\Core\Api\ApiService::initApiKeys($apiKeys);
\Kuga\Core\Api\ApiService::initApiJsonConfigFile('路径/api.json');
$result = \Kuga\Core\Api\ApiService::response($requestObject);
echo json_encode($result);
```

上面示例提到的api.json参见https://github.com/misnet/apidocs

## sign签名串生成规则
将所有参数按字母a-z顺序排序，以Key+Value的形式串起来，头尾再加上secret值，例现在有这些参数：
系统分配的appkey是1001，appsecret是abc

- method: member.register
- appkey: 1001
- access_token: 999
- uid: 123

按Key升序，将Key+Value的顺序排序来串，头尾加上appsecret的值就是：
```
abcaccess_token999appkey1001methodmember.registeruid123abc
```
然后将上面这个字串进行md5加密，再转为大写，就是sign的值

## API编写说明
```php
namespace Kuga\Api\Test;
use Kuga\Core\Api\AbstractApi;
class TestApi extends AbstractApi{
    public function create(){
        //获得业务参数对象
        $data = $this->_toParamObject($this->getParams());
        $dataArray = $data->toArray();

        //accessToken或JWT解密后得到了用户ID
        $currentUserId = $this->_userMemberId;
        return [
            'list'=>[],
            'userId'=>$currentUserId,
            'total'=>10
        ];
    }
}
```
数据返回的结构一般是
```json
{
  "data": {
    "list": [],
    "total": 10,
    "userId": 10,
  },
  "status": 0
}
```
当有错误发生时status值为非零，data值为错误信息