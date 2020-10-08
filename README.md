# 主机壳(ké) ddns客户端
主要是给路由器跑的...openwrt适用的启动脚本
```sh
screen -dmS ddns /bin/ash -c "php-cli /root/ddns/main.php"
```
### 安装
手动创建config.php，内容如下
```php
define('Email', 'aaaaa'); //账号
define('Token', 'balabalabalablalbalbal'); //e
define('Domain', '666.66'); //根域名
define('DomainHeader', '886'); //需要设置解析的域名前缀
define('DomainTTL', 60); //解析缓存时间，最小值60s
define('DDNS_IPV4', true); //是否设置A解析
define('DDNS_IPV6', false); //是否设置AAAA解析
define('IPCheckSleep', 300); //ip检测间隔时间，推荐不小于60s
```