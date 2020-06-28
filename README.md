# Easyswoole Hyperf Database 插件

该插件允许在Easyswoole框架中使用Hyperf的Database组件

## 配置方法

见 `example/dev.php`

配置项和Hyperf内一致，详见Hyperf文档

## 代码配置

见 `example/EasySwooleEvent.php`

如需配置Model生成命令，见 `example/bootstrap.php`

代码生成命令暂时支持不完全，仅支持全局批量生成和生成单独表

```shell
# 生成全部Model
php easyswoole gen:model

# 生成单一表
php easyswoole gen:model t_user 
```

如需扩展，可修改 `src/Command/ModelCommand.php` 文件
