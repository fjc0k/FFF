<?php namespace Funch;

use Funch\CookieJarWriter;

class Curl{
    public static function request($a, $return_raw = false){
        $url = $a['url'];
        $referer = isset($a['referer']) ? $a['referer'] : $url;
        $headers = isset($a['headers']) ? (is_array($a['headers']) ? $a['headers'] : explode("\r\n", $a['headers'])) : [];
        $post = isset($a['post']) ? $a['post'] : false;

        // 2016-7-31 支持文件上传
        if(isset($a['files'])){
            array_walk($a['files'], function(&$file) {
                $file = new \CURLFile($file, null, basename($file));
            });
            $post = $post ? array_merge($post, $a['files']) : $a['files'];
        }

        $host = parse_url($url, PHP_URL_HOST);
        $path = str_replace(DIRECTORY_SEPARATOR, '/', dirname(parse_url($url, PHP_URL_PATH)));
        
        $headers[] = 'Host: ' . $host;
        $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:32.0) Gecko/20100101 Firefox/32.0';
        $headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
        $headers[] = 'Accept-Language: zh-cn,zh;q=0.8,en-us;q=0.5,en;q=0.3';
        $headers[] = 'Referer: ' . $referer;
        $headers[] = 'Connection: close';
        
        $ch       = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, $return_raw);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts

        /* Cookie 相关 */
        // 自定义cookies
        if(isset($a['cookies']) && !isset($a['cookie_jar'])){
            $headers[] = 'Cookie: '.$a['cookies'];
        }
        if(isset($a['cookie_jar'])){
            if(!file_exists($a['cookie_jar'])){
                file_put_contents($a['cookie_jar'], '');
            }
            if(isset($a['cookies'])){
                $_cookies = explode(';', $a['cookies']);
                $cookie_jar_writer = new CookieJarWriter($a['cookie_jar']);
                $cookie_jar_writer->setPrefix($host, null, $path, null);
                foreach ($_cookies as $_cookie) {
                    $temp = explode('=', $_cookie, 2);
                    $cookie_jar_writer->addCookie($temp[0], $temp[1], 1);
                }
            }
            curl_setopt($ch, CURLOPT_COOKIEFILE, $a['cookie_jar']);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $a['cookie_jar']);
        }

        if(!isset($a['follow_location']) || $a['follow_location'] !== false){
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        }

        // 2016-8-4 支持代理
        if(isset($a['proxy'])){
            curl_setopt($ch, CURLOPT_PROXY, $a['proxy']);
        }

        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        
        if ($post !== false) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        
        $response = curl_exec($ch);

        curl_close($ch);
        
        return $response;
    }
}