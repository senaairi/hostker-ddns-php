<?php
require 'HostkerDnsAPI.php';
require 'config.php';

/// 主机壳(ké) dns解析api实现的ddns客户端
/// 主要是给路由器跑的
/// 如果需设置解析的域名不存在，会自动创建
/// 脚本启动时，会强制更新一次解析

try {
    $api = new HostKerDnsAPI();
    $api->email = Email;
    $api->token = Token;
    # 读取dns解析列表
    $dnsList = $api->dnsList(Domain);

    # 查找是否已设置解析
    $v4_dns_id = 0;
    $v4_dns_data = '';
    $v6_dns_id = 0;
    $v6_dns_data = '';
    echo '检测是否已存在 '.DomainHeader.'.'.Domain." 解析记录...\r\n";
    foreach ($dnsList as $item) {
        if(DDNS_IPV4 && $item['type']=='A'){
            if($item['header'] == DomainHeader) {
                $v4_dns_id = $item['id'];
                $v4_dns_data = $item['data'];
            }
        }
        if(DDNS_IPV6 && $item['type']=='AAAA'){
            if($item['header'] == DomainHeader) {
                $v6_dns_id = $item['id'];
                $v6_dns_data = $item['data'];
            }
        }
    }

    # 未找打记录，自动创建
    $myip = GetMyip_openwrt(); // 获取本机ip
    $v4_first_created = false;
    $v6_first_created = false;
    if(DDNS_IPV4 && !$v4_dns_id) {
        echo "创建A解析\r\n";
        $Clash = false;
        # v4解析需要检查解析冲突
        foreach ($dnsList as $item) {
            if($item['header'] == DomainHeader && in_array($item['type'], ['CDN', 'A', 'CNAME'])){
                $Clash = true;
                echo '检测到冲突，前缀：'.$item['header'].' 类型：'.$item['type'].' 解析值：'.$item['type']['data'],"\r\n";
            }
        }
        if($Clash) throw new Exception('解析记录冲突，退出执行', 0);
        if(!$myip[0]) throw new Exception('解析本机ip失败？', 0);
        # 创建解析
        $v4_dns_id = $api->dnsAddRecord(Domain, DomainHeader, 'A', $myip[0], 60);
        $v4_first_created = true;
        $v4_dns_data = $myip[0];
        echo "创建成功\r\n";
    }
    if(DDNS_IPV6 && !$v6_dns_id) {
        echo "创建AAAA解析\r\n";
        $Clash = false;
        #检查v6解析冲突
        foreach ($dnsList as $item) {
            if($item['header'] == DomainHeader && in_array($item['type'], ['CDN', 'AAAA'])){
                $Clash = true;
                echo '检测到冲突，前缀：'.$item['header'].' 类型：'.$item['type'].' 解析值：'.$item['type']['data'],"\r\n";
            }
        }
        if($Clash) throw new Exception('解析记录冲突，退出执行', 0);
        if(!$myip[1]) throw new Exception('解析本机ip失败？', 0);
        # 创建解析
        $v6_dns_id = $api->dnsAddRecord(Domain, DomainHeader, 'AAAA', $myip[1], 60);
        $v6_first_created = true;
        $v6_dns_data = $myip[1];
        echo "创建成功\r\n";
    }

    echo "开始定时检测...\r\n";
    while (true){
        # 如果解析是首次创建的跳过第一次ip更新检测
        if($myip[0] && !$v4_first_created && DDNS_IPV4){
            if($myip[0] != $v4_dns_data) {
                echo "更新记录，{$v4_dns_data} 变更为 {$myip[0]} \r\n";
                $api->dnsEditRecord($v4_dns_id, $myip[0], DomainTTL);
                $v4_dns_data = $myip[0];
            }
        }else
            $v4_first_created = false;

        if($myip[1] && !$v6_first_created && DDNS_IPV6){
            if($myip[1] != $v6_dns_data) {
                echo "更新记录，{$v6_dns_data} 变更为 {$myip[1]} \r\n";
                $api->dnsEditRecord($v6_dns_id, $myip[1], DomainTTL);
                $v6_dns_data = $myip[1];
            }
        }else
            $v6_first_created = false;

        sleep(IPCheckSleep);
        # 休眠结束，下一次检测前先读取一下本机ip
        $ipcheck = GetMyip_openwrt(); // 获取本机ip
        if(DDNS_IPV4 && !$ipcheck[0])
            echo "ipv4检测失败！！！\r\n";
        else
            $myip[0] = $ipcheck[0];
        if(DDNS_IPV6 && !$ipcheck[1])
            echo "ipv6检测失败！！！\r\n";
        else
            $myip[1] = $ipcheck[1];
    }

} catch (Exception $e) {
    echo date('Y-m-d H:i:s'),' ',$e->getCode(),'  ',$e->getMessage(),"\r\n";
}
/**
 * 获取本机ip的方法，可根据需求自行实现
 */
function GetMyIP(){
    $return = ['',''];
    if(DDNS_IPV4) {
        $return[0] = file_get_contents('https://api-ipv4.ip.sb/ip');
        //这货返回值带\r\n就很烦
        $return[0] = str_replace("\r", '', $return[0]);
        $return[0] = str_replace("\n", '', $return[0]);
    }
    if(DDNS_IPV6){
        $return[1] = file_get_contents('https://api-ipv6.ip.sb/ip');
        $return[1] = str_replace("\r", '', $return[1]);
        $return[1] = str_replace("\n", '', $return[1]);
    }
    return $return;
}

/**
 * 在openwrt下，获取本机ip
 */
function GetMyip_openwrt() {
    $ipv6 = '';
    $ipv4 = '';
    // 获取pppoe-wan的ipv4地址
    exec('ip addr show pppoe-wan', $result, $status);
    if(preg_match('/inet\ ([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}){1}\ /', $result[2], $matches)) {
        $ipv4 = $matches[1];
    }
    // 获取lan口的ipv6
    exec('ip addr show br-lan', $result, $status);
    if(preg_match('/inet6\ ([0-9a-f\:]+){1}\//', $result[4], $matches)) {
        $ipv6 = $matches[1];
    }
    return [$ipv4, $ipv6];
}
?>