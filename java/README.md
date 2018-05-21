## 获取 SDK

[Java SDK 下载>>](https://github.com/tencentyun/tac-storage-sts-sdk)

### 查看示例

请查看 `src/test` 下的 java 文件，里面描述了如何调用SDK。

### 使用方法

#### 1. 在 java 工程的 pom.xml 文件中集成依赖：

```
<dependency>
	<groupId>com.qcloud</groupId>
   <artifactId>qcloud-java-sdk</artifactId>
   <version>2.0.6</version>
   <scope>compile</scope>
</dependency>
```

#### 2. 拷贝 `src/main` 下的 `StorageSts.java` 到您的工程中，调用代码如下：

```
TreeMap<String, Object> config = new TreeMap<String, Object>();

// 您的 SecretID
config.put("SecretId", "xxx");
// 您的 SecretKey
config.put("SecretKey", "xxx");
// 临时密钥有效时长，单位是秒，如果没有设置，默认是30分钟
config.put("durationInSeconds", 1800);

JSONObject credential = StorageSts.getCredential(config);
```

### 返回结果

成功的话，可以拿到包含密钥的 JSON 文本：

```
{"code":0,"message":"","codeDesc":"Success","data":{"credentials":{"sessionToken":"2a0c0ead3e6b8eed9608899eb74f2458812208ab30001","tmpSecretId":"AKIDBSrMaeFD0ZAECKuBzohnjAhJ53XNCE2F","tmpSecretKey":"UC7YjMrIlcuFgoWGwnrHwsMBrQrpUwYI"},"expiredTime":1526288317}}
```


