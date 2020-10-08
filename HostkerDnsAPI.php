<?php
class HostKerDnsAPI {
    const APIURL = 'https://i.hostker.com/api/';
    public $email = '';
    public $token = '';

    /**
     * @param string $domain 主域名
     * @return array 自己var_dump看
     * @throws Exception
     */
    public function dnsList($domain){
        $data = $this->api_request('dnsGetRecords', ['domain'=>$domain]);
        return $data['records'];
    }
    /**
     * 修改解析记录
     * @param int $id 解析记录编号
     * @param string $data 解析记录值，当记录类型为 CDN 时可不传此字段
     * @param int $ttl 记录生存周期，最小 60 最大 339384，当记录类型为 CDN 时可不传此字段
     * @param int $priority 优先级，最小 1 最大 50000，当记录类型非 MX 时可不传此字段
     * @return array
     * @throws Exception
     */
    public function dnsEditRecord($id, $data, $ttl=60, $priority=5){
        if($ttl < 60) throw new Exception('ttl值不可小于60', 0);
        $r_data = ['id'=>$id, 'ttl'=>$ttl];
        if($data) $r_data['data'] = $data;
        if($priority) $r_data['priority'] = $priority;
        return $this->api_request('dnsEditRecord', $r_data);
    }
    /**
     * @param string $domain 主域名
     * @param string $header 主机头，如果为空可以是空或者@，末尾无需带.（点）
     * @param string $type 解析记录类型，可选 CDN A CNAME TXT MX AAAA
     * @param string $data 解析记录值，当记录类型为 CDN 时可不传此字段
     * @param int $ttl 记录生存周期，最小 60 最大 339384，当记录类型为 CDN 时可不传此字段
     * @param int $priority 优先级，最小 1 最大 50000，当记录类型非 MX 时可不传此字段
     * @return int 记录值编号，修改和删除时需要传入
     * @throws Exception
     */
    public function dnsAddRecord($domain, $header, $type, $data='', $ttl=300, $priority=0){
        if($ttl < 60) throw new Exception('ttl值不可小于60', 0);
        $r_data = [
            'domain'=>$domain,
            'header'=>$header,
            'type'=>$type
        ];
        if($type != 'CDN') $r_data['ttl'] = $ttl;
        if($data) $r_data['data'] = $data;
        if($priority) $r_data['priority'] = $priority;
        $data = $this->api_request('dnsAddRecord', $r_data);
        return $data['id'];
    }
    /**
     * 删除解析记录
     * @param int $id 解析记录编号
     * @return ???不晓得，没调用过
     */
    public function dnsDeleteRecord($id) {
        return $this->api_request('dnsDeleteRecord', ['id'=>$id]);
    }
    public function api_request($url, $post_data, $header=[]) {
        $post_data['email'] = $this->email;
        $post_data['token'] = $this->token;

        $curl = curl_init();
        $header[] = 'Connection: close';
        $header[] = 'Cache-Control: no-cache';
        $header[] = 'Content-Type: application/x-www-form-urlencoded';
        $header[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8';
        $header[] = 'Accept-Language: zh-CN,zh;q=0.8,en;q=0.6,ja;q=0.4,zh-TW;q=0.2,sv;q=0.2';
        curl_setopt($curl, CURLOPT_URL, self::APIURL.$url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_ENCODING, 'deflate');
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.101 Safari/537.36');
        curl_setopt($curl , CURLOPT_FRESH_CONNECT ,true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_CAINFO, 'cacert.pem');
        $http_data = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if($http_data === false || $http_code != 200)
            throw new Exception("http响应状态错误！\r\nhttp_data:".$http_data, $http_code);

        $return_data = json_decode($http_data, true);
        if(!is_array($return_data))
            throw new Exception("解析http数据错误！\r\nhttp_data:".$http_data, $http_code);

        if($return_data['success'] != 1)
            throw new Exception($return_data['errorMessage'], $http_code);

        return $return_data;
    }
}
?>