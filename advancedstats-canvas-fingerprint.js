/**
 * AdvancedStats - Canvas 指纹收集脚本
 * 
 * 功能：在前台页面加载时自动生成并发送 Canvas 指纹到服务器
 * 
 * 安装方法：
 * 1. 上传到：zb_users/plugin/AdvancedStats/canvas-fingerprint.js
 * 2. 在 include.php 中加载此脚本
 * 
 * @version 1.0.0
 * @author Tpure Theme
 */

(function() {
    'use strict';
    
    // ===== 配置 =====
    const CONFIG = {
        // AJAX 端点
        endpoint: window.location.origin + '/zb_system/cmd.php?act=AdvancedStats_SaveFingerprint',
        
        // 是否在控制台输出调试信息
        debug: false,
        
        // Canvas 尺寸
        canvasWidth: 200,
        canvasHeight: 50
    };
    
    /**
     * 日志输出
     */
    function log(message, data) {
        if (CONFIG.debug) {
            console.log('[AdvancedStats]', message, data || '');
        }
    }
    
    /**
     * 生成 Canvas 指纹
     * @returns {string|null} 指纹哈希值或 null
     */
    function generateCanvasFingerprint() {
        try {
            // 创建隐藏的 Canvas
            const canvas = document.createElement('canvas');
            canvas.width = CONFIG.canvasWidth;
            canvas.height = CONFIG.canvasHeight;
            canvas.style.display = 'none';
            
            const ctx = canvas.getContext('2d');
            
            if (!ctx) {
                log('Canvas context not available');
                return null;
            }
            
            // 绘制复杂的文字和图形（增加唯一性）
            ctx.textBaseline = 'top';
            ctx.font = '14px Arial, sans-serif';
            
            // 彩色矩形
            ctx.fillStyle = '#f60';
            ctx.fillRect(125, 1, 62, 20);
            
            // 英文文字
            ctx.fillStyle = '#069';
            ctx.fillText('Browser Fingerprint 🔒', 2, 15);
            
            // 半透明叠加文字
            ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
            ctx.fillText('Canvas Test 12345', 4, 17);
            
            // 添加更多复杂度：渐变
            const gradient = ctx.createLinearGradient(0, 0, canvas.width, 0);
            gradient.addColorStop(0, 'red');
            gradient.addColorStop(0.5, 'green');
            gradient.addColorStop(1, 'blue');
            ctx.fillStyle = gradient;
            ctx.fillRect(0, 40, canvas.width, 10);
            
            // 转换为数据 URL
            const dataURL = canvas.toDataURL();
            
            log('Canvas data generated', {
                length: dataURL.length,
                preview: dataURL.substring(0, 100) + '...'
            });
            
            // 生成哈希
            const hash = simpleHash(dataURL);
            
            log('Canvas fingerprint generated', hash);
            
            return hash;
            
        } catch (error) {
            console.error('[AdvancedStats] Canvas fingerprint generation failed:', error);
            return null;
        }
    }
    
    /**
     * 简单哈希函数（类似 Java 的 String.hashCode()）
     * @param {string} str 要哈希的字符串
     * @returns {string} 16进制哈希值
     */
    function simpleHash(str) {
        let hash = 0;
        
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // 转换为32位整数
        }
        
        // 转换为正数的16进制
        return Math.abs(hash).toString(16).padStart(8, '0');
    }
    
    /**
     * 发送指纹到服务器
     * @param {string} fingerprint Canvas 指纹哈希
     */
    function sendFingerprint(fingerprint) {
        if (!fingerprint) {
            log('No fingerprint to send');
            return;
        }
        
        log('Sending fingerprint to server', fingerprint);
        
        // 使用 Fetch API（现代浏览器）
        if (window.fetch) {
            sendViaFetch(fingerprint);
        } 
        // 降级到 XMLHttpRequest（旧浏览器）
        else {
            sendViaXHR(fingerprint);
        }
    }
    
    /**
     * 通过 Fetch API 发送
     */
    function sendViaFetch(fingerprint) {
        fetch(CONFIG.endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'canvas_fingerprint=' + encodeURIComponent(fingerprint),
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                log('Canvas fingerprint saved successfully', data);
            } else {
                console.warn('[AdvancedStats] Failed to save fingerprint:', data.message);
            }
        })
        .catch(error => {
            console.error('[AdvancedStats] Network error:', error);
        });
    }
    
    /**
     * 通过 XMLHttpRequest 发送（兼容旧浏览器）
     */
    function sendViaXHR(fingerprint) {
        const xhr = new XMLHttpRequest();
        
        xhr.open('POST', CONFIG.endpoint, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        if (data.success) {
                            log('Canvas fingerprint saved successfully (XHR)', data);
                        } else {
                            console.warn('[AdvancedStats] Failed to save fingerprint:', data.message);
                        }
                    } catch (e) {
                        console.error('[AdvancedStats] Parse error:', e);
                    }
                } else {
                    console.error('[AdvancedStats] HTTP error:', xhr.status);
                }
            }
        };
        
        xhr.send('canvas_fingerprint=' + encodeURIComponent(fingerprint));
    }
    
    /**
     * 主函数：生成并发送指纹
     */
    function init() {
        // 检查是否已发送（避免重复）
        if (sessionStorage.getItem('canvas_fingerprint_sent')) {
            log('Fingerprint already sent in this session');
            return;
        }
        
        // 生成指纹
        const fingerprint = generateCanvasFingerprint();
        
        if (!fingerprint) {
            log('Failed to generate fingerprint');
            return;
        }
        
        // 发送到服务器
        sendFingerprint(fingerprint);
        
        // 标记已发送（会话期间只发送一次）
        sessionStorage.setItem('canvas_fingerprint_sent', '1');
        
        // 可选：也存储指纹本身供调试
        if (CONFIG.debug) {
            sessionStorage.setItem('canvas_fingerprint_value', fingerprint);
        }
    }
    
    // ===== 自动执行 =====
    
    // 等待 DOM 加载完成
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        // DOM 已加载，立即执行
        // 添加小延迟，避免阻塞页面渲染
        setTimeout(init, 100);
    }
    
    // 导出到全局（可选，供调试使用）
    window.AdvancedStatsFingerprint = {
        generate: generateCanvasFingerprint,
        send: sendFingerprint,
        version: '1.0.0'
    };
    
    log('Canvas fingerprint script loaded');
    
})();

