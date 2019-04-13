## Kuga-openapi-core
提供系统基础功能框架，功能包话邮件发送、短信发送、OSS配置、日志服务、配置服务、多语言服务等，可根据需要在Init.php中修改进行启用或禁用。
##API网关使用说明

- API网关处理程序在src/Core/Api/ApiService.php中
- 不同的接口处理约定在api的json说明文件中约定了，具体见api-json-samle这个例子目录
- 处理API的程序是根据json文件的约定，在src/Api目录中
- api.json文件的规范及相关api的约定见[http://github.com/misnet/apidocs]
- API网关会根据json文件的约定，对json文件中request部分进行校验，也会根据文件约定是否
需要accessToken进行校验；同时json文件中约定了API处理接口的命名空间及处理的类和方法。
- config-sample中的内容为示例内容，是当前应用可能要读取用到的相关配置。
- 本应用不能单独使用。

初始化示例：
```
$customConfig = include('config-sample/config.default.php');
//不设置默认用/tmp
Kuga\Init::setTmpDir('/opt/tmp);
Kuga\Init::setup($customConfig);
```

API网关调用示例：
```
$requestObject = new \Kuga\Core\Api\Request($_POST);
$requestObject->setOrigRequest($_POST);
\Kuga\Core\Api\ApiService::setDi($this->getDI());
\Kuga\Core\Api\ApiService::initApiJsonConfigFile('路径/api.json');
$result = ApiService::response($requestObject);
echo json_encode($result);
```