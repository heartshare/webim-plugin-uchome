WebIM For UChome
================================================================

为UCHome开发的站内聊天插件(Webim)，更新内容请查看 CHANGELOG.md


需求
-----------------------------

*	MySQL版本不低于4.1.2
*	需要PHP版本不低于5.1
*	PHP访问外部网络，WebIM连接时需要访问WebIM服务器, 请确保您的php环境是否可连接外部网络, 设置php.ini中`allow_url_fopen=ON`.


升级
-----------------------------

1.	覆盖新版内容到webim目录，浏览器打开webim管理地址( uchome地址/webim/ )会自动执行升级脚本


安装
-----------------------------

1. 解压下载的安装包文件UChome根目录

	.
	|-- webim
	|   |-- README.md
	|   |-- static

给与安装目录可写权限

	chmod 777 webim

2. 访问http://UCHome访问URL/webim/admin/install.php

3. 根据安装向导，配置域名、APIKEY等参数

4. 安装完成


