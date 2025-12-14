Speed Wi-Fi HOME 5G L13 实时信号监控工具

注意修改为路由器的ip地址和密码

postman脚本导入后需要设置环境变量encrypted_pwd和ld都为空，密码写在集合变量 password  还有路由器IP地址 router_ip

1.postman.json 调试版导入你的Postman,成功后会输出

{"lte_rssi":"-40","lte_rsrp":"-73","Z5g_rsrp":"-101","Z5g_SINR":"5.0","signalbar":"4","network_type":"ENDC","wan_active_band":"LTE
BAND 41","lte_ca_pcell_band":"41","lte_ca_scell_band":"41"}

2.python.py python版运行成功后会输出

=== Speed Wi-Fi HOME 5G L13 信号实时监控 ===

正在获取动态盐 LD ...
正在登录...
登录成功！

开始监控，按 Ctrl+C 退出

[13:47:02]  ENDC   信号格 4｜ LTE -74 dBm (RSSI -46 dBm)  │  5G -100 dBm  SINR   9.5 dB  │  频段 LTE BAND 41

3.L13.php php版需要在局域网内运行,成功后会输出

更新时间：15:11:47
网络模式：ENDC
信号格子：4 格
4G 主信号：RSRP -82 dBm　RSSI -50 dBm
5G 辅信号：RSRP -90 dBm　SINR 13.0 dB
当前频段：LTE BAND 41
载波聚合：主 B41 | 辅 B41
页面每3秒自动刷新 · 会话已保持
