#!/usr/bin/env node

/**
 * Tpure 主题 - 资源构建脚本
 * 
 * 功能：
 * - 压缩CSS文件
 * - 压缩JS文件
 * - 生成Source Map
 * - 添加版本哈希
 * - 生成资源清单（manifest.json）
 * 
 * 使用方法：
 *   npm install          # 安装依赖
 *   npm run build        # 构建生产版本
 *   npm run build:dev    # 构建开发版本
 * 
 * @version 1.0.0
 */

const fs = require('fs');
const path = require('path');
const crypto = require('crypto');

// ==================== 配置 ====================

const config = {
    // 输入文件
    input: {
        css: 'style/style.css',
        js: 'script/src/main.js'
    },
    
    // 输出文件
    output: {
        css: 'style/style.min.css',
        js: 'script/common.min.js'
    },
    
    // 构建选项
    options: {
        sourceMap: true,        // 生成Source Map
        hashVersion: true,      // 添加版本哈希
        removeComments: true,   // 移除注释
        minify: true            // 压缩代码
    }
};

// ==================== 工具函数 ====================

/**
 * 读取文件
 */
function readFile(filePath) {
    const fullPath = path.resolve(__dirname, '..', filePath);
    if (!fs.existsSync(fullPath)) {
        throw new Error(`文件不存在: ${fullPath}`);
    }
    return fs.readFileSync(fullPath, 'utf8');
}

/**
 * 写入文件
 */
function writeFile(filePath, content) {
    const fullPath = path.resolve(__dirname, '..', filePath);
    const dir = path.dirname(fullPath);
    
    // 确保目录存在
    if (!fs.existsSync(dir)) {
        fs.mkdirSync(dir, { recursive: true });
    }
    
    fs.writeFileSync(fullPath, content, 'utf8');
}

/**
 * 计算文件哈希
 */
function calculateHash(content) {
    return crypto.createHash('md5').update(content).digest('hex').substring(0, 8);
}

/**
 * 格式化文件大小
 */
function formatSize(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return (bytes / Math.pow(k, i)).toFixed(2) + ' ' + sizes[i];
}

/**
 * 计算压缩率
 */
function calculateSavings(original, minified) {
    return ((1 - minified / original) * 100).toFixed(2);
}

// ==================== CSS 压缩 ====================

/**
 * 简单的CSS压缩（不依赖外部库）
 */
function minifyCSS(css) {
    let result = css;
    
    // 移除注释
    result = result.replace(/\/\*[\s\S]*?\*\//g, '');
    
    // 移除多余空白
    result = result.replace(/\s+/g, ' ');
    
    // 移除选择器前后的空格
    result = result.replace(/\s*([{}:;,>+~])\s*/g, '$1');
    
    // 移除最后一个分号
    result = result.replace(/;}/g, '}');
    
    // 移除单位为0的值的单位
    result = result.replace(/(\s|:)0(px|em|rem|%|vh|vw)/gi, '$10');
    
    // 压缩颜色值
    result = result.replace(/#([0-9a-f])\1([0-9a-f])\2([0-9a-f])\3/gi, '#$1$2$3');
    
    return result.trim();
}

/**
 * 构建CSS
 */
function buildCSS() {
    console.log('\n📦 开始构建CSS...');
    
    try {
        // 读取源文件
        const source = readFile(config.input.css);
        const originalSize = Buffer.byteLength(source, 'utf8');
        
        // 压缩
        const minified = minifyCSS(source);
        const minifiedSize = Buffer.byteLength(minified, 'utf8');
        
        // 写入压缩文件
        writeFile(config.output.css, minified);
        
        // 生成Source Map（简化版）
        if (config.options.sourceMap) {
            const sourceMap = {
                version: 3,
                sources: [config.input.css],
                names: [],
                mappings: '',
                file: path.basename(config.output.css)
            };
            writeFile(config.output.css + '.map', JSON.stringify(sourceMap, null, 2));
        }
        
        // 输出统计
        const savings = calculateSavings(originalSize, minifiedSize);
        console.log(`✅ CSS构建完成:`);
        console.log(`   原始大小: ${formatSize(originalSize)}`);
        console.log(`   压缩后: ${formatSize(minifiedSize)}`);
        console.log(`   节省: ${savings}%`);
        
        return {
            original: config.input.css,
            output: config.output.css,
            hash: calculateHash(minified),
            size: minifiedSize
        };
        
    } catch (error) {
        console.error(`❌ CSS构建失败: ${error.message}`);
        throw error;
    }
}

// ==================== JS 压缩 ====================

/**
 * 简单的JS压缩（不依赖外部库）
 * 注意：这是一个非常基础的压缩，生产环境建议使用 UglifyJS 或 Terser
 */
function minifyJS(js) {
    let result = js;
    
    // 移除单行注释（但保留URL中的//）
    result = result.replace(/([^:])\/\/.*$/gm, '$1');
    
    // 移除多行注释
    result = result.replace(/\/\*[\s\S]*?\*\//g, '');
    
    // 移除多余空白（保留字符串内的空白）
    result = result.replace(/\s+/g, ' ');
    
    // 移除操作符周围的空格
    result = result.replace(/\s*([{}()\[\];,:<>+\-*/%=!&|?])\s*/g, '$1');
    
    // 恢复一些必要的空格
    result = result.replace(/}([a-zA-Z])/g, '} $1');
    result = result.replace(/\breturn([^;])/g, 'return $1');
    
    return result.trim();
}

/**
 * 构建JS
 */
function buildJS() {
    console.log('\n📦 开始构建JS...');
    
    try {
        // 读取源文件
        const source = readFile(config.input.js);
        const originalSize = Buffer.byteLength(source, 'utf8');
        
        // 压缩
        const minified = minifyJS(source);
        const minifiedSize = Buffer.byteLength(minified, 'utf8');
        
        // 写入压缩文件
        writeFile(config.output.js, minified);
        
        // 生成Source Map（简化版）
        if (config.options.sourceMap) {
            const sourceMap = {
                version: 3,
                sources: [config.input.js],
                names: [],
                mappings: '',
                file: path.basename(config.output.js)
            };
            writeFile(config.output.js + '.map', JSON.stringify(sourceMap, null, 2));
        }
        
        // 输出统计
        const savings = calculateSavings(originalSize, minifiedSize);
        console.log(`✅ JS构建完成:`);
        console.log(`   原始大小: ${formatSize(originalSize)}`);
        console.log(`   压缩后: ${formatSize(minifiedSize)}`);
        console.log(`   节省: ${savings}%`);
        
        return {
            original: config.input.js,
            output: config.output.js,
            hash: calculateHash(minified),
            size: minifiedSize
        };
        
    } catch (error) {
        console.error(`❌ JS构建失败: ${error.message}`);
        throw error;
    }
}

// ==================== 资源清单 ====================

/**
 * 生成资源清单
 */
function generateManifest(cssInfo, jsInfo) {
    console.log('\n📝 生成资源清单...');
    
    const manifest = {
        version: '5.0.7',
        buildTime: new Date().toISOString(),
        assets: {
            css: {
                original: cssInfo.original,
                minified: cssInfo.output,
                hash: cssInfo.hash,
                size: cssInfo.size,
                url: `style/style.min.${cssInfo.hash}.css`
            },
            js: {
                original: jsInfo.original,
                minified: jsInfo.output,
                hash: jsInfo.hash,
                size: jsInfo.size,
                url: `script/common.min.${jsInfo.hash}.js`
            }
        }
    };
    
    writeFile('manifest.json', JSON.stringify(manifest, null, 2));
    console.log('✅ 资源清单已生成: manifest.json');
    
    return manifest;
}

// ==================== 主函数 ====================

/**
 * 主构建流程
 */
async function build() {
    console.log('🚀 Tpure 主题资源构建');
    console.log('='.repeat(50));
    
    const startTime = Date.now();
    
    try {
        // 构建CSS
        const cssInfo = buildCSS();
        
        // 构建JS
        const jsInfo = buildJS();
        
        // 生成清单
        const manifest = generateManifest(cssInfo, jsInfo);
        
        // 完成
        const duration = ((Date.now() - startTime) / 1000).toFixed(2);
        console.log('\n' + '='.repeat(50));
        console.log(`✨ 构建完成！耗时 ${duration}秒`);
        console.log('='.repeat(50));
        
        // 输出总结
        console.log('\n📊 构建总结:');
        console.log(`   CSS: ${formatSize(cssInfo.size)} (${cssInfo.hash})`);
        console.log(`   JS:  ${formatSize(jsInfo.size)} (${jsInfo.hash})`);
        console.log(`   总计: ${formatSize(cssInfo.size + jsInfo.size)}`);
        
    } catch (error) {
        console.error('\n❌ 构建失败:', error.message);
        process.exit(1);
    }
}

// ==================== 执行 ====================

// 检查是否为主模块
if (require.main === module) {
    build();
}

module.exports = { build, minifyCSS, minifyJS };

