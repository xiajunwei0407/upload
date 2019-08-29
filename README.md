### 文件上传类
#### 使用方法

###### HTML部分的代码示例：
```html
<!--这里的name="file"，无特殊情况不用更改-->
<!--如需更改，则实例化upload时传入第二个参数进行指定: $upload = new Xjw\Upload($config, 'your input name');-->
<input type="file" name="file" value="上传文件">
```

###### php部分的代码示例：
```php
$config = [
           'allowExt' => ['mp3', 'mp4'], // 允许上传的文件类型;非必传,默认['mp3', 'mp4']
           'allowSize' => '2M', // 允许上传的文件大小;非必传，默认2M；可选单位B,K,M,G
           'uploadRoot' => '../resources/uploads/', // 文件上传的根目录；必传,末尾带/
           'savePath' => 'path/to/xxx/', // 文件保存的子目录；必传,开头不带/,末尾带/
           'saveNameMode' => 'random', // 文件名生成模式；非必传，默认random，可选值random--随机,original--源文件名,custom--自定义，需指定saveName
           'saveName' => 'test.mp4', // 保存的文件名；saveNameMode=custom时必传，其他情况不用传
           'dayDir' => true, // 是否按 /年月/日 的形式生成目录；非必传，默认true
           'isCover' => false, // 是否覆盖同名文件；非必传，默认false，有同名文件时抛出异常
      ];
$upload = new Xjw\Upload($config);
$result = $upload->upload();
//var_dump($result);
```
    

