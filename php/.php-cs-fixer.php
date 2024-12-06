<?php
// Copyright © 2016-2024 Sesame Network Technology all rights reserved
// 版权所有 © 2016-2024 Sesame Network Technology 保留所有权利

// 创建一个 PhpCsFixer 的 Finder 实例，用于定位需要被代码格式化的文件
$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/modules', // 定义模块目录为代码格式化的目标目录
        __DIR__ . '/common',  // 定义公共目录为代码格式化的目标目录
        __DIR__ . '/config',  // 定义配置目录为代码格式化的目标目录
    ]);

// 创建一个 PhpCsFixer 配置实例
$config = new PhpCsFixer\Config();

// 配置代码格式化规则并返回配置对象
return $config->setRules([
    '@PSR2' => true, // 启用 PSR-2 代码风格规则
    'array_syntax' => ['syntax' => 'short'], // 使用短数组语法，例如 [] 替代 array()
    'ordered_imports' => ['sort_algorithm' => 'alpha'], // 按字母顺序排序 use 导入
    'no_unused_imports' => true, // 移除未使用的 use 导入
    'trailing_comma_in_multiline' => true, // 在多行数组或参数列表的最后一个元素后添加逗号
    'phpdoc_scalar' => true, // 将 PHPDoc 类型提示中的 `integer`、`boolean` 等转为更标准的 `int` 和 `bool`
    'unary_operator_spaces' => true, // 确保一元运算符后面有空格
    'binary_operator_spaces' => true, // 确保二元运算符前后有空格
    'phpdoc_single_line_var_spacing' => true, // 确保 PHPDoc 单行注释中的变量和描述之间有一个空格
    'phpdoc_var_without_name' => true, // 移除 PHPDoc 中变量声明时的变量名（如果存在）
    'method_argument_space' => [
        'on_multiline' => 'ensure_fully_multiline', // 确保多行方法参数是完全分行的
        'keep_multiple_spaces_after_comma' => true, // 保留逗号后的多个空格
    ],
    'cast_spaces' => ['space' => 'single'], // 类型转换操作符 (int) 等后面添加一个空格
    'lambda_not_used_import' => true, // 删除未使用的闭包导入变量
])
    ->setUsingCache(false) // 禁用缓存，以确保每次都重新格式化代码
    ->setFinder($finder); // 将 Finder 设置为格式化目标
