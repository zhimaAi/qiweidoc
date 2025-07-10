# 接入流程

## 自建应用接入

### 准备

1. 需要corpid和密钥，用来兑换token

2. 了获取调用权限，需要配置可信域名和可信IP。

3. 安装应用后扫码登录需要配置企业微信授权登录的授权回调域名

4. 为了获取调用客户联系相关的权限，需要在客户--api内配置为可调用的应用。



### 步骤

1 登录企业微信后台，我的企业，企业信息中复制企业ID，填写到对应字段中

![image1.png](integration+20920e16-9a22-4e91-898e-9249682231a0/image1.png)



![image2.png](integration+20920e16-9a22-4e91-898e-9249682231a0/image2.png)



2 应用管理-应用-自建应用中，创建自建应用，获取AgentID和secret

![image3.png](integration+20920e16-9a22-4e91-898e-9249682231a0/image3.png)



![image4.png](integration+20920e16-9a22-4e91-898e-9249682231a0/image4.png)



![image5.png](integration+20920e16-9a22-4e91-898e-9249682231a0/image5.png)



![image6.png](integration+20920e16-9a22-4e91-898e-9249682231a0/image6.png)



将以上获取的信息填写到对应的页面中

![image7.png](integration+20920e16-9a22-4e91-898e-9249682231a0/image7.png)



3 应用管理-应用-自建应用进入详情，网页授权及JS-SDK配置可信域名，企微登录回调域名以及企业可信IP

![image8.png](integration+20920e16-9a22-4e91-898e-9249682231a0/image8.png)



3.1 设置可信域名，填写域名，并在页面中完成归属认证，认证前需要下载文件，并复制文件信息在接入页面完成验证

![image9.png](integration+20920e16-9a22-4e91-898e-9249682231a0/image9.png)



![image10.png](integration+20920e16-9a22-4e91-898e-9249682231a0/image10.png)



![image11.png](integration+20920e16-9a22-4e91-898e-9249682231a0/image11.png)



![image12.png](integration+20920e16-9a22-4e91-898e-9249682231a0/image12.png)



![image13.png](integration+20920e16-9a22-4e91-898e-9249682231a0/image13.png)



![image14.png](integration+20920e16-9a22-4e91-898e-9249682231a0/image14.png)

3.2 设置可信IP

![image15.png](integration+20920e16-9a22-4e91-898e-9249682231a0/image15.png)



![image16.png](integration+20920e16-9a22-4e91-898e-9249682231a0/image16.png)



3.3 企微微信授权登录的授权回调地址



![image17.png](integration+20920e16-9a22-4e91-898e-9249682231a0/image17.png)



![image18.png](integration+20920e16-9a22-4e91-898e-9249682231a0/image18.png)



4 客户与上下游-客户-客户联系中的API中，展开后，可调用接口应用中配置调取的自建应用



![image19.png](integration+20920e16-9a22-4e91-898e-9249682231a0/image19.png)



![image20.png](integration+20920e16-9a22-4e91-898e-9249682231a0/image20.png)



## 前置条件-开通/购买会话存档

1.开启会话存档前需开通和购买企微会话存档接口，请联系客服协助开通和购买

![dc37e9d6783b9b93822e3b96a81121f5.png](integration+20920e16-9a22-4e91-898e-9249682231a0/dc37e9d6783b9b93822e3b96a81121f5.png)

## 会话存档的配置流程


1. 获取密钥，用来兑换token
2. 获取调用权限，需要配置可信IP
3. 消息解密，需要在会话存档内配置加密的公钥。
4. 需要在我们后台配置公钥填写后显示的版本号。

5.配置接收事件服务器，自动同步客户/员工/群信息。

### 步骤

1 登录到企微后台，高级功能，会话内容存档，secret中点击查看获取验证码后，发送secret到企微中，复制secret中，并填写到对应的字段中。

![image21.png](integration+20920e16-9a22-4e91-898e-9249682231a0/image21.png)



![image22.png](integration+20920e16-9a22-4e91-898e-9249682231a0/image22.png)



![image23.png](integration+20920e16-9a22-4e91-898e-9249682231a0/image23.png)



![image24.png](integration+20920e16-9a22-4e91-898e-9249682231a0/image24.png)



![image25.png](integration+20920e16-9a22-4e91-898e-9249682231a0/image25.png)



2 会话内容存档页面中，设置可信IP



![image26.png](integration+20920e16-9a22-4e91-898e-9249682231a0/image26.png)



![image27.png](integration+20920e16-9a22-4e91-898e-9249682231a0/image27.png)



3 消息加密公钥中设置后台页面的中的公钥，点击设置，填写

![image.png](integration+20920e16-9a22-4e91-898e-9249682231a0/image.png)



![image22.png](integration+20920e16-9a22-4e91-898e-9249682231a0/image22__1.png)



![image28.png](integration+20920e16-9a22-4e91-898e-9249682231a0/image28.png)



4 设置公钥后，复制版本号，填写到对应的位子



![image22.png](integration+20920e16-9a22-4e91-898e-9249682231a0/image22__2.png)



![image29.png](integration+20920e16-9a22-4e91-898e-9249682231a0/image29.png)

5.复制页面的URL，token，encodingAESSKey，填写到企微后台-应用管理-接收消息服务器配置中

![image.png](integration+20920e16-9a22-4e91-898e-9249682231a0/image__1.png)

![2dd7ba5d8b6f090466d6b3d9a5097bc5.png](integration+20920e16-9a22-4e91-898e-9249682231a0/2dd7ba5d8b6f090466d6b3d9a5097bc5.png)

![8114c3941eb01b6d5a321ef21b6b39c5.png](integration+20920e16-9a22-4e91-898e-9249682231a0/8114c3941eb01b6d5a321ef21b6b39c5.png)

![eaf98f5a59639228f5d5e16ba8685ee9.png](integration+20920e16-9a22-4e91-898e-9249682231a0/eaf98f5a59639228f5d5e16ba8685ee9.png)

![b7aff48f726156fcc5b742632737032c.png](integration+20920e16-9a22-4e91-898e-9249682231a0/b7aff48f726156fcc5b742632737032c.png)



