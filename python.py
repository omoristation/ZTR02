#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Speed Wi-Fi HOME 5G L13 实时信号监控工具

"""

import requests
import hashlib
import time
import sys
from datetime import datetime

# ================== 请在这里修改 ==================
ROUTER_IP = "192.168.10.1"          # 大多数是这个，少数是 192.168.0.1
PASSWORD  = "明文密码"         # 直接写明文密码
# 如果从 shell 传了参数
if len(sys.argv) > 1:
    ROUTER_IP = sys.argv[1]
if len(sys.argv) > 2:
    PASSWORD = sys.argv[2]
# ================================================

session = requests.Session()
session.headers.update({
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
    "Referer": f"http://{ROUTER_IP}/index.html",
    "Origin": f"http://{ROUTER_IP}",
})

def sha256(text):
    """返回大写的 SHA256 值"""
    return hashlib.sha256(text.encode("utf-8")).hexdigest().upper()

def login():
    print("正在获取动态盐 LD ...")
    url = f"http://{ROUTER_IP}/goform/goform_get_cmd_process"
    r = session.get(url, params={"cmd": "LD", "multi_data": "1", "_": int(time.time()*1000)})
    ld = r.json()["LD"]

    # 双重 SHA256 + 盐
    step1 = sha256(PASSWORD)
    final_password = sha256(step1 + ld)

    print("正在登录...")
    login_payload = {
        "isTest": "false",
        "goformId": "LOGIN",
        "password": final_password
    }
    resp = session.post(f"http://{ROUTER_IP}/goform/goform_set_cmd_process", data=login_payload, timeout=10)
    result = resp.json()
    if result.get("result") in ["0", "success"]:
        print("登录成功！\n")
        return True
    else:
        print("登录失败，请检查密码是否正确")
        return False

def get_signal():
    url = f"http://{ROUTER_IP}/goform/goform_get_cmd_process"
    params = {
        "cmd": "lte_rssi,lte_rsrp,Z5g_rsrp,Z5g_SINR,signalbar,network_type,wan_active_band,Z5g_snr,lte_ca_pcell_band,lte_ca_scell_band",
        "multi_data": "1",
        "_": int(time.time()*1000)
    }
    try:
        r = session.get(url, params=params, timeout=8)
        return r.json()
    except:
        return None

if __name__ == "__main__":
    print("=== Speed Wi-Fi HOME 5G L13 信号实时监控 ===\n")
    if not login():
        exit(1)

    print("开始监控，按 Ctrl+C 退出\n")
    empty_5g_count = 0  # 连续 5G RSRP 为空的次数
    try:
        while True:
            data = get_signal()
            if not data.get('Z5g_rsrp','?'):
                print("获取数据失败，网络异常或已掉线")
                empty_5g_count += 1
                if empty_5g_count >= 5:
                    print(f"\n连续 {empty_5g_count} 次未检测到 Z5g_rsrp 信号，自动退出脚本。")
                    print("建议：重新运行脚本即可重新登录并继续监控。")
                    break
                time.sleep(3)
                continue

            now = datetime.now().strftime("%H:%M:%S")
            print(f"[{now}]  "
                  f"{data.get('network_type','?'):5}  "
                  f"信号格 {data.get('signalbar','?')}｜ "
                  f"LTE {data.get('lte_rsrp','?')} dBm (RSSI {data.get('lte_rssi','?')} dBm)  │  "
                  f"5G {data.get('Z5g_rsrp','?'):>4} dBm  SINR {data.get('Z5g_SINR','?'):>5} dB  │  "
                  f"频段 {data.get('wan_active_band','?')}",
                  end="\r" if data.get('Z5g_rsrp') else "\n")
            time.sleep(3)
    except KeyboardInterrupt:
        print("\n\n拜拜！")
