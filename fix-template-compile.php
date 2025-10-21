<?php
/**
 * 模板编译文件修复工具
 * 解决"主题模板的编译文件不存在"问题
 */

require '../../../zb_system/function/c_system_base.php';
$zbp->Load();

$isLoggedIn = $zbp->CheckRights('root');

echo '<meta charset="utf-8">';
echo '<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
.box { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
.success { color: #28a745; font-weight: bold; }
.error { color: #dc3545; font-weight: bold; }
.warning { color: #ffc107; font-weight: bold; }
.btn { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
.btn-success { background: #28a745; }
.btn-danger { background: #dc3545; }
table { width: 100%; border-collapse: collapse; margin: 15px 0; }
th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #f8f9fa; }
pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
</style>';

echo '<h1>🔧 模板编译文件修复工具</h1>';

// 显示登录状态
if (!$isLoggedIn) {
    echo '<div class="box" style="background: #fff3cd; border-left: 4px solid #ffc107;">';
    echo '<p><strong>⚠️ 当前未登录</strong></p>';
    echo '<p>您可以查看诊断信息，但无法执行修复操作。<a href="' . $zbp->host . 'zb_system/login.php">点击登录</a></p>';
    echo '</div>';
}

// 步骤 1: 检查当前状态
echo '<div class="box">';
echo '<h3>步骤 1: 检查模板编译状态</h3>';

$theme = $zbp->theme;
$templateDir = $zbp->path . 'zb_users/theme/' . $theme . '/template/';
$compileDir = $zbp->path . 'zb_users/cache/compiled/';

echo '<table>';
echo '<tr><th>项目</th><th>值</th></tr>';
echo '<tr><td>当前主题</td><td>' . $theme . '</td></tr>';
echo '<tr><td>模板目录</td><td>' . $templateDir . '</td></tr>';
echo '<tr><td>编译目录</td><td>' . $compileDir . '</td></tr>';
echo '<tr><td>编译目录存在</td><td>' . (is_dir($compileDir) ? '<span class="success">✓ 是</span>' : '<span class="error">✗ 否</span>') . '</td></tr>';

if (is_dir($compileDir)) {
    echo '<tr><td>编译目录可写</td><td>' . (is_writable($compileDir) ? '<span class="success">✓ 是</span>' : '<span class="error">✗ 否</span>') . '</td></tr>';
}

echo '</table>';
echo '</div>';

// 步骤 2: 检查模板文件
echo '<div class="box">';
echo '<h3>步骤 2: 检查模板文件</h3>';

$templateFiles = [];
if (is_dir($templateDir)) {
    $files = glob($templateDir . '*.php');
    
    echo '<table>';
    echo '<tr><th>模板文件</th><th>大小</th><th>编译文件</th><th>状态</th></tr>';
    
    foreach ($files as $file) {
        $filename = basename($file);
        $compiledFile = $compileDir . $theme . '_' . str_replace('.php', '.php', $filename);
        
        $hasCompiled = file_exists($compiledFile);
        $templateFiles[$filename] = [
            'source' => $file,
            'compiled' => $compiledFile,
            'exists' => $hasCompiled
        ];
        
        echo '<tr>';
        echo '<td>' . $filename . '</td>';
        echo '<td>' . filesize($file) . ' 字节</td>';
        echo '<td>' . basename($compiledFile) . '</td>';
        echo '<td>' . ($hasCompiled ? '<span class="success">✓ 已编译</span>' : '<span class="error">✗ 未编译</span>') . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    
    $missingCount = count(array_filter($templateFiles, function($f) { return !$f['exists']; }));
    echo '<p>共 ' . count($templateFiles) . ' 个模板文件，其中 <strong>' . $missingCount . '</strong> 个未编译</p>';
} else {
    echo '<p class="error">✗ 模板目录不存在</p>';
}

echo '</div>';

// 步骤 3: 检查编译目录权限
echo '<div class="box">';
echo '<h3>步骤 3: 检查目录权限</h3>';

$directories = [
    'zb_users/cache/' => $zbp->path . 'zb_users/cache/',
    'zb_users/cache/compiled/' => $compileDir,
];

echo '<table>';
echo '<tr><th>目录</th><th>存在</th><th>可读</th><th>可写</th><th>权限</th></tr>';

foreach ($directories as $desc => $dir) {
    $exists = is_dir($dir);
    echo '<tr>';
    echo '<td>' . $desc . '</td>';
    echo '<td>' . ($exists ? '<span class="success">✓</span>' : '<span class="error">✗</span>') . '</td>';
    
    if ($exists) {
        $readable = is_readable($dir);
        $writable = is_writable($dir);
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        
        echo '<td>' . ($readable ? '<span class="success">✓</span>' : '<span class="error">✗</span>') . '</td>';
        echo '<td>' . ($writable ? '<span class="success">✓</span>' : '<span class="error">✗</span>') . '</td>';
        echo '<td>' . $perms . '</td>';
    } else {
        echo '<td colspan="3"><span class="error">目录不存在</span></td>';
    }
    
    echo '</tr>';
}

echo '</table>';
echo '</div>';

// 步骤 4: 修复操作
echo '<div class="box">';
echo '<h3>步骤 4: 模板编译修复</h3>';

if (isset($_POST['rebuild_templates'])) {
    if (!$isLoggedIn) {
        echo '<div style="background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 15px 0;">';
        echo '<p class="error"><strong>✗ 权限不足</strong></p>';
        echo '<p>修复操作需要登录后台管理。<a href="' . $zbp->host . 'zb_system/login.php">点击登录</a></p>';
        echo '</div>';
    } else {
        echo '<div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #007bff; margin: 15px 0;">';
        echo '<h4>修复过程：</h4>';
        
        // 1. 检查并创建编译目录
        if (!is_dir($compileDir)) {
            echo '<p>1. 创建编译目录...</p>';
            if (mkdir($compileDir, 0755, true)) {
                echo '<p class="success">   ✓ 编译目录创建成功</p>';
            } else {
                echo '<p class="error">   ✗ 编译目录创建失败</p>';
            }
        } else {
            echo '<p>1. 编译目录已存在</p>';
        }
        
        // 2. 清空现有编译文件
        echo '<p>2. 清空旧的编译文件...</p>';
        $cleared = 0;
        if (is_dir($compileDir)) {
            $oldFiles = glob($compileDir . $theme . '_*.php');
            foreach ($oldFiles as $oldFile) {
                if (unlink($oldFile)) {
                    $cleared++;
                }
            }
            echo '<p>   ✓ 清理了 ' . $cleared . ' 个旧编译文件</p>';
        }
        
        // 3. 重新编译模板
        echo '<p>3. 重新编译模板...</p>';
        
        try {
            // 方法 1: 使用 BuildTemplate
            $buildResult = $zbp->BuildTemplate();
            
            if ($buildResult) {
                echo '<p class="success">   ✓ BuildTemplate() 执行成功</p>';
            } else {
                echo '<p class="warning">   ⚠ BuildTemplate() 返回 false，但可能已生成文件</p>';
            }
            
            // 验证编译结果
            clearstatcache();
            $newFiles = glob($compileDir . $theme . '_*.php');
            echo '<p>   生成了 ' . count($newFiles) . ' 个编译文件</p>';
            
            if (count($newFiles) > 0) {
                echo '<p class="success"><strong>✓ 模板编译成功！</strong></p>';
                
                echo '<details><summary>点击查看生成的文件列表</summary>';
                echo '<ul>';
                foreach ($newFiles as $file) {
                    echo '<li>' . basename($file) . ' (' . filesize($file) . ' 字节)</li>';
                }
                echo '</ul>';
                echo '</details>';
            } else {
                echo '<p class="error"><strong>✗ 模板编译失败，没有生成编译文件</strong></p>';
                echo '<p>可能的原因：</p>';
                echo '<ul>';
                echo '<li>编译目录权限不足</li>';
                echo '<li>模板文件有语法错误</li>';
                echo '<li>PHP 内存不足</li>';
                echo '</ul>';
            }
            
        } catch (Exception $e) {
            echo '<p class="error">✗ 编译过程出错: ' . $e->getMessage() . '</p>';
            echo '<pre>' . $e->getTraceAsString() . '</pre>';
        }
        
        echo '<p style="margin-top: 20px;">';
        echo '<a href="' . $_SERVER['PHP_SELF'] . '" class="btn">刷新页面查看结果</a>';
        echo '<a href="' . $zbp->host . '" class="btn btn-success">访问网站首页验证</a>';
        echo '</p>';
        
        echo '</div>';
    }
}

echo '<form method="post" onsubmit="return confirm(\'确认要重新编译所有模板吗？\');">';
echo '<p><strong>修复说明：</strong></p>';
echo '<ol>';
echo '<li>创建编译目录（如果不存在）</li>';
echo '<li>清空旧的编译文件</li>';
echo '<li>重新编译所有模板文件</li>';
echo '<li>验证编译结果</li>';
echo '</ol>';

if ($isLoggedIn) {
    echo '<p><button type="submit" name="rebuild_templates" class="btn btn-success">开始修复模板编译</button></p>';
} else {
    echo '<p><button type="button" disabled class="btn" style="background: #6c757d; cursor: not-allowed;">开始修复模板编译 (需要登录)</button></p>';
    echo '<p class="error">⚠️ 此操作需要登录后台管理</p>';
}

echo '</form>';
echo '</div>';

// 步骤 5: 手动修复方法
echo '<div class="box" style="background: #fff3cd;">';
echo '<h3>步骤 5: 手动修复方法（备用）</h3>';

echo '<h4>方法 1: 通过 Z-BlogPHP 后台</h4>';
echo '<ol>';
echo '<li>登录后台管理</li>';
echo '<li>进入"主题管理"</li>';
echo '<li>找到当前主题，点击"重新编译模板"</li>';
echo '</ol>';

echo '<h4>方法 2: 通过 FTP/文件管理</h4>';
echo '<pre>';
echo '1. 检查目录权限
   chmod 755 zb_users/cache/
   chmod 755 zb_users/cache/compiled/

2. 如果编译目录不存在，手动创建
   mkdir zb_users/cache/compiled/

3. 访问后台清空缓存
   后台 → 网站设置 → 清空缓存';
echo '</pre>';

echo '<h4>方法 3: 通过 PHP 代码</h4>';
echo '<pre>';
echo '在网站根目录创建 fix.php 文件：
&lt;?php
require \'zb_system/function/c_system_base.php\';
$zbp-&gt;Load();
$zbp-&gt;BuildTemplate();
echo \'模板编译完成\';
?&gt;

然后访问: http://你的域名/fix.php
完成后删除 fix.php 文件';
echo '</pre>';

echo '</div>';

// 步骤 6: 系统信息
echo '<div class="box">';
echo '<h3>步骤 6: 系统信息</h3>';

echo '<table>';
echo '<tr><th>项目</th><th>值</th></tr>';
echo '<tr><td>PHP 版本</td><td>' . PHP_VERSION . '</td></tr>';
echo '<tr><td>Z-BlogPHP 版本</td><td>' . $zbp->version . '</td></tr>';
echo '<tr><td>当前主题</td><td>' . $zbp->theme . '</td></tr>';
echo '<tr><td>主题版本</td><td>' . ($zbp->themeapp->version ?? '未知') . '</td></tr>';
echo '<tr><td>服务器类型</td><td>' . $_SERVER['SERVER_SOFTWARE'] . '</td></tr>';
echo '<tr><td>操作系统</td><td>' . PHP_OS . '</td></tr>';
echo '</table>';

echo '</div>';

echo '<div style="margin-top: 20px; text-align: center;">';
echo '<a href="cache-diagnostic.php">← 返回缓存诊断</a> | ';
echo '<a href="find-config-location.php">配置文件检查</a>';
echo '</div>';
?>

