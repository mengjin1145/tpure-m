<?php
/**
 * Tpure 主题 - 安全函数测试
 * 
 * @package Tpure\Tests
 * @version 5.0.6
 * @author TOYEAN
 */

require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/../lib/security.php';

/**
 * 安全函数测试类
 */
class SecurityTest extends TestCase {
    
    public function __construct() {
        parent::__construct('安全函数测试');
    }
    
    /**
     * 测试HTML转义
     */
    public function testEscHtml() {
        // 测试普通文本
        $result = tpure_esc_html('Hello World');
        $this->assertEquals('Hello World', $result);
        
        // 测试HTML标签
        $result = tpure_esc_html('<script>alert("XSS")</script>');
        $this->assertEquals('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', $result);
        
        // 测试单引号
        $result = tpure_esc_html("It's a test");
        $this->assertContains('&#039;', $result);
        
        // 测试空字符串
        $result = tpure_esc_html('');
        $this->assertEquals('', $result);
        
        // 测试null
        $result = tpure_esc_html(null);
        $this->assertEquals('', $result);
    }
    
    /**
     * 测试属性转义
     */
    public function testEscAttr() {
        // 测试普通文本
        $result = tpure_esc_attr('test-value');
        $this->assertEquals('test-value', $result);
        
        // 测试HTML标签
        $result = tpure_esc_attr('<img src=x onerror=alert(1)>');
        $this->assertNotContains('<img', $result);
        
        // 测试换行符（应该被移除）
        $result = tpure_esc_attr("line1\nline2");
        $this->assertNotContains("\n", $result);
        
        // 测试回车符（应该被移除）
        $result = tpure_esc_attr("line1\rline2");
        $this->assertNotContains("\r", $result);
    }
    
    /**
     * 测试URL转义
     */
    public function testEscUrl() {
        // 测试HTTP URL
        $url = 'http://example.com/page?id=1&name=test';
        $result = tpure_esc_url($url);
        $this->assertContains('http://example.com', $result);
        
        // 测试HTTPS URL
        $url = 'https://example.com';
        $result = tpure_esc_url($url);
        $this->assertEquals($url, $result);
        
        // 测试JavaScript伪协议（应该被拒绝）
        $url = 'javascript:alert(1)';
        $result = tpure_esc_url($url);
        $this->assertEquals('', $result);
        
        // 测试data伪协议（应该被拒绝）
        $url = 'data:text/html,<script>alert(1)</script>';
        $result = tpure_esc_url($url);
        $this->assertEquals('', $result);
        
        // 测试空字符串
        $result = tpure_esc_url('');
        $this->assertEquals('', $result);
    }
    
    /**
     * 测试文本净化
     */
    public function testSanitizeText() {
        // 测试HTML标签移除
        $result = tpure_sanitize_text('<p>Hello <b>World</b></p>');
        $this->assertEquals('Hello World', $result);
        
        // 测试脚本移除
        $result = tpure_sanitize_text('<script>alert(1)</script>Text');
        $this->assertEquals('Text', $result);
        
        // 测试多余空白
        $result = tpure_sanitize_text("  Hello  \n  World  ");
        $this->assertEquals('Hello World', $result);
        
        // 测试制表符
        $result = tpure_sanitize_text("Hello\tWorld");
        $this->assertEquals('Hello World', $result);
    }
    
    /**
     * 测试ID验证
     */
    public function testValidateId() {
        // 测试有效ID
        $result = tpure_validate_id(123);
        $this->assertEquals(123, $result);
        
        $result = tpure_validate_id('456');
        $this->assertEquals(456, $result);
        
        // 测试无效ID
        $result = tpure_validate_id('abc');
        $this->assertFalse($result);
        
        $result = tpure_validate_id(0);
        $this->assertFalse($result);
        
        $result = tpure_validate_id(-1);
        $this->assertFalse($result);
        
        $result = tpure_validate_id('123abc');
        $this->assertFalse($result);
    }
    
    /**
     * 测试邮箱验证
     */
    public function testValidateEmail() {
        // 测试有效邮箱
        $this->assertTrue(tpure_validate_email('user@example.com'));
        $this->assertTrue(tpure_validate_email('test.user@example.com'));
        $this->assertTrue(tpure_validate_email('user+tag@example.co.uk'));
        
        // 测试无效邮箱
        $this->assertFalse(tpure_validate_email('invalid'));
        $this->assertFalse(tpure_validate_email('@example.com'));
        $this->assertFalse(tpure_validate_email('user@'));
        $this->assertFalse(tpure_validate_email(''));
    }
    
    /**
     * 测试邮件头净化
     */
    public function testSanitizeEmailHeader() {
        // 测试普通文本
        $result = tpure_sanitize_email_header('Test Subject');
        $this->assertEquals('Test Subject', $result);
        
        // 测试换行符注入
        $result = tpure_sanitize_email_header("Subject\nBcc: attacker@evil.com");
        $this->assertNotContains("\n", $result);
        $this->assertNotContains('Bcc:', $result);
        
        // 测试URL编码的换行符
        $result = tpure_sanitize_email_header("Subject%0ABcc: attacker@evil.com");
        $this->assertNotContains('%0A', $result);
        
        // 测试邮件头关键字
        $result = tpure_sanitize_email_header("To: attacker@evil.com");
        $this->assertNotContains('To:', $result);
    }
    
    /**
     * 测试文件上传验证
     */
    public function testValidateUpload() {
        // 测试空文件
        $result = tpure_validate_upload(null);
        $this->assertNotEquals(true, $result);
        
        // 测试上传错误
        $file = [
            'error' => UPLOAD_ERR_NO_FILE,
            'size' => 0
        ];
        $result = tpure_validate_upload($file);
        $this->assertNotEquals(true, $result);
        
        // 测试文件过大（模拟）
        $file = [
            'error' => UPLOAD_ERR_OK,
            'size' => 10 * 1024 * 1024, // 10MB
            'type' => 'image/jpeg',
            'name' => 'test.jpg',
            'tmp_name' => '/tmp/phptest'
        ];
        $result = tpure_validate_upload($file, 1024 * 1024); // 限制1MB
        $this->assertContains('大小', $result);
    }
    
    /**
     * 测试CSRF令牌生成
     */
    public function testGenerateCsrfToken() {
        // 清除session
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['csrf_token'] = '';
        
        $token1 = tpure_generate_csrf_token();
        $this->assertNotEmpty($token1);
        $this->assertEquals(64, strlen($token1)); // 32字节 = 64个十六进制字符
        
        // 同一session应返回相同token
        $token2 = tpure_generate_csrf_token();
        $this->assertEquals($token1, $token2);
    }
    
    /**
     * 测试CSRF令牌验证
     */
    public function testVerifyCsrfToken() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        // 生成令牌
        $token = tpure_generate_csrf_token();
        
        // 验证正确的令牌
        $this->assertTrue(tpure_verify_csrf_token($token));
        
        // 验证错误的令牌
        $this->assertFalse(tpure_verify_csrf_token('invalid_token'));
        
        // 验证空令牌
        $this->assertFalse(tpure_verify_csrf_token(''));
    }
    
    /**
     * 测试XSS防护组合
     */
    public function testXssProtection() {
        $xssAttempts = [
            '<script>alert("XSS")</script>',
            '<img src=x onerror=alert(1)>',
            'javascript:alert(1)',
            '<iframe src="evil.com"></iframe>',
            '<svg onload=alert(1)>',
            '<body onload=alert(1)>',
        ];
        
        foreach ($xssAttempts as $xss) {
            $escaped = tpure_esc_html($xss);
            // 确保没有可执行的标签
            $this->assertNotContains('<script', strtolower($escaped));
            $this->assertNotContains('javascript:', strtolower($escaped));
            $this->assertNotContains('onerror=', strtolower($escaped));
            $this->assertNotContains('onload=', strtolower($escaped));
        }
    }
}

