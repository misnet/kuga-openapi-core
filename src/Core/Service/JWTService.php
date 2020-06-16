<?php
/**
 * API Request Object
 *
 * @author Donny
 */

namespace Kuga\Core\Service;


class JWTService
{

    private $_secret;
    private $_header = ['alg'=>'HS256','typ'=>'JWT'];

    public function setSecret($s){
        $this->_secret = $s;
    }
    /**
     * 验证请求是否有效
     * @param string $token
     * @return boolean|String
     */
    public function validate($token)
    {
        $tokens = explode('.', $token);
        if (count($tokens) != 3)
            return false;

        list($base64header, $base64payload, $sign) = $tokens;

        //获取jwt算法
        $base64decodeheader = json_decode(self::base64UrlDecode($base64header), JSON_OBJECT_AS_ARRAY);
        if (empty($base64decodeheader['alg']))
            return false;
        //签名验证
        if (self::signature($base64header . '.' . $base64payload, $this->_secret, $base64decodeheader['alg']) !== $sign)
            return false;

        $payload = json_decode(self::base64UrlDecode($base64payload), JSON_OBJECT_AS_ARRAY);

        //签发时间大于当前服务器时间验证失败
        if (isset($payload['iat']) && $payload['iat'] > time())
            return false;

        //过期时间小宇当前服务器时间验证失败
        if (isset($payload['exp']) && $payload['exp'] < time())
            return false;

        //该nbf时间之前不接收处理该Token
        if (isset($payload['nbf']) && $payload['nbf'] > time())
            return false;

        return $payload;
    }
    /**
     * base64UrlEncode   https://jwt.io/  中base64UrlEncode编码实现
     * @param string $input 需要编码的字符串
     * @return string
     */
    private static function base64UrlEncode(string $input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }
    /**
     * HMACSHA256签名   https://jwt.io/  中HMACSHA256签名实现
     * @param string $input 为base64UrlEncode(header).".".base64UrlEncode(payload)
     * @param string $key
     * @param string $alg   算法方式
     * @return mixed
     */
    private static function signature(string $input, string $key, string $alg = 'HS256')
    {
        $alg_config=array(
            'HS256'=>'sha256'
        );
        return self::base64UrlEncode(hash_hmac($alg_config[$alg], $input, $key,true));
    }
    /**
     * base64UrlEncode  https://jwt.io/  中base64UrlEncode解码实现
     * @param string $input 需要解码的字符串                                                      ,
     * @return bool|string
     */
    private static function base64UrlDecode(string $input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $addlen = 4 - $remainder;
            $input .= str_repeat('=', $addlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }
    /**
     * 创建token字串
     * @param array $params 要加密的对象
     * @param integer $lifetime
     * @return string
     */
    public  function createToken($params=[],$lifetime=0)
    {
        if ( ! is_array($params)) {
            $params = [];
        }
        if($lifetime>0){
            $params['exp']  = time() + $lifetime;
        }
        $base64Header = self::base64UrlEncode( json_encode($this->_header,JSON_UNESCAPED_UNICODE));
        $base64Payload= self::base64UrlEncode(json_encode($params,JSON_UNESCAPED_UNICODE));
        $token  =  $base64Header.'.'.$base64Payload.'.'.self::signature($base64Header.'.'.$base64Payload,$this->_secret,$this->_header['alg']);
        return $token;
    }

}
