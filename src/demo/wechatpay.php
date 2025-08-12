<?php
require_once 'pay/Pay.php';
// require_once 'pay/Pay/Wechat.php';

# 微信支付配置
$wechatConfig = [
    'xcxid'         => '', // 小程序 appid
    'appid'         => '', // 微信支付 appid
    'mchid'         => '', // 微信支付 mch_id 商户收款账号
    'key'           => '', // 微信支付 apiV3key（尽量包含大小写字母，否则验签不通过，服务商模式使用服务商key）
    'appsecret'     => '', // 公众帐号 secert (公众号支付获取 code 和 openid 使用)

    'sp_appid'      => '', // 服务商应用 ID
    'sp_mchid'      => '', // 服务商户号

    'notify_url'    => 'http://www.xxx.cn/pay/notify.php', // 接收支付状态的连接  改成自己的回调地址

    'redirect_url'  => 'http://www.xxx.cn/pay/redirect.php', // 公众号支付，调起支付页面


    // 服务商模式下，使用服务商证书
    'serial_no'     => '', // 商户API证书序列号（可不传，默认根据证书直接获取）
    'cert_client'   => './cert/apiclient_cert.pem', // 证书（退款，红包时使用）
    'cert_key'      => './cert/apiclient_key.pem', // 商户API证书私钥（Api安全中下载）

    'public_key_id' => '', // 平台证书序列号或支付公钥ID
    // （支付公钥ID请带：PUB_KEY_ID_ 前缀，默认根据证书直接获取，不带前缀）
    'public_key'    => './cert/public_key.pem', // 平台证书或支付公钥（Api安全中下载）
    // （微信支付新申请的，已不支持平台证书，老版调用证书列表，自动生成平台证书，注意目录权限）

    'log_path' => './logs/wechat.log', // 日志文件路径
];
// $pay = new \jincon\Tinypay\Wechat($wechatConfig); // 微信
$order = [
    'body'      => 'subject-测试', // 商品描述
    'order_sn'  => time(), // 商户订单号
    'total_amount' => 1, // 订单金额
];
// $code_url = $pay->scan($order);
$pay = new \jincon\Tinypay();
$code_url = $pay::Wechat($wechatConfig)->scan($order);


var_dump($code_url);