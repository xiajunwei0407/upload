<?php
/**
 * 文件上传
 * Created by PhpStorm.
 * User: xiajunwei
 * Date: 2019/4/29
 * Time: 9:37
 */
namespace Xjw\Upload;


class Upload
{
    // 上传的文件
    private $file;
    // 允许上传的文件类型
    private $allowExt = ['mp3', 'mp4'];
    // 允许上传的文件大小 "B", "K", M", or "G"
    private $allowSize = '2M';
    // 上传根目录
    private $uploadRoot;
    // 保存到哪个目录,末尾带/
    private $savePath;
    // 保存后的文件名
    private $saveName;
    // 自定义文件名
    private $customName = '';
    // 保存文件名的模式：random=随机,original=源文件名,custom=自定义
    private $saveNameMode = 'random';
    // 上传文件的后缀名
    private $ext;
    // input控件的name
    private $key;
    // 上传的文件大小
    private $size;
    // 临时文件名
    private $tmpName;
    // 上传的文件原名
    private $name;
    // 保存文件时，是否按天生成目录
    private $dayDir = true;
    // 是否覆盖同名文件
    private $isCover = false;
    // 上传成功后返回的完整文件名(不含$uploadRoot)
    private $returnName;


    /**
     * UploadController constructor.
     * @param array $config 上传的相关配置
     * $config = [
     *      'allowExt' => ['mp3', 'mp4'], // 允许上传的文件类型;非必传,默认['mp3', 'mp4']
     *      'allowSize' => '2M', // 允许上传的文件大小;非必传，默认2M；可选单位B,K,M,G
     *      'uploadRoot' => '../resources/uploads/', // 文件上传的根目录；必传,末尾带/
     *      'savePath' => 'path/to/xxx/', // 文件保存的子目录；必传,开头不带/,末尾带/
     *      'saveNameMode' => 'random', // 文件名生成模式；非必传，默认random，可选值random--随机,original--源文件名,custom--自定义，需指定saveName
     *      'saveName' => 'test.mp4', // 保存的文件名；saveNameMode=custom时必传，其他情况不用传
     *      'dayDir' => true, // 是否按 /年月/日 的形式生成目录；非必传，默认true
     *      'isCover' => false, // 是否覆盖同名文件；非必传，默认false，有同名文件时抛出异常
     * ];
     *
     * 调用方式 $upload = new Upload($config[, $key]); $upload->upload();
     */
    public function __construct(array $config = [], $key = 'file')
    {
        if(!$_FILES){
            throw new \Exception('没有上传文件');
        }
        $this->file = $_FILES;
        $this->key = $key ?: '';
        if(!$this->key){
            throw new \Exception('没有指定key');
        }
        if( array_key_exists( 'allowExt', $config ) && is_array( $config['allowExt'] ) && $config['allowExt'] ){
            $this->allowExt = $config['allowExt'];
        }
        if( array_key_exists( 'allowSize', $config ) && $config['allowSize'] ){
            $this->allowSize = $config['allowSize'];
        }
        if( array_key_exists( 'uploadRoot', $config ) && $config['uploadRoot'] ){
            $this->uploadRoot = $config['uploadRoot'];
        }else{
            throw new \Exception('必须设置uploadRoot');
        }
        if( array_key_exists( 'savePath', $config ) && $config['savePath'] ){
            $this->savePath = $config['savePath'];
        }else{
            throw new \Exception('必须设置savePath');
        }
        if( array_key_exists('saveNameMode', $config) && in_array($config['saveNameMode'], ['random', 'original', 'custom']) ){
            $this->saveNameMode = $config['saveNameMode'];
        }
        if( array_key_exists('saveName', $config) && $config['saveName'] ){
            $this->customName = $config['saveName'];
        }
        if( array_key_exists('dayDir', $config) && is_bool($config['dayDir']) ){
            $this->dayDir = $config['dayDir'];
        }
        if( array_key_exists('isCover', $config) && is_bool($config['isCover']) ){
            $this->isCover = $config['isCover'];
        }
        $this->init();
    }

    /**
     * 初始化一些参数
     */
    private function init()
    {
        $this->setSize();
        $this->setExt();
        $this->setName();
        $this->setTmpName();
        $this->setSaveName();
        $this->savePath = $this->handlePath($this->savePath);
        $this->uploadRoot = $this->handlePath($this->uploadRoot);
    }

    /**
     * 文件上传
     */
    public function upload()
    {
        set_time_limit(0);
        // 检查是否有错误
        $this->checkError();
        // 检查文件后缀是否合法
        $this->checkExt();
        // 检查文件大小是否合法
        $this->checkSize();
        // 保存文件
        $result = move_uploaded_file($this->tmpName, $this->generateFullName());
        return $result;
    }

    /**
     * 获取返回数据
     */
    public function getReturnData()
    {
        return [
            'fileName' => $this->returnName,
            'fileSize' => $this->size
        ];
    }

    /**
     * Path后面加/
     */
    private function handlePath($path)
    {
        if(substr($path, -1) !== '/'){
            $path .= '/';
        }
        return $path;
    }

    /**
     * 生成文件名，md5( 时间戳 + md5( mt_rand(100000, 999999) + uniqid() ) )
     */
    private function generateName()
    {
        return md5(time() . md5(mt_rand(100000, 999999) . uniqid())) . '.' . $this->ext;
    }

    /**
     * 设置上传文件的后缀名
     */
    private function setExt()
    {
        $this->ext = pathinfo($this->file[$this->key]['name'], PATHINFO_EXTENSION);
    }

    /**
     * 设置上传的文件大小
     */
    private function setSize()
    {
        $this->size = $this->file[$this->key]['size'];
    }

    /**
     * 设置上传的文件原名
     */
    private function setName()
    {
        $this->name = $this->file[$this->key]['name'];
    }

    /**
     * 设置临时文件名
     */
    private function setTmpName()
    {
        $this->tmpName = $this->file[$this->key]['tmp_name'];
    }

    /**
     * 根据文件名生成模式，生成文件名
     */
    private function setSaveName()
    {
        switch ($this->saveNameMode){
            case 'random':
                $this->saveName = $this->generateName();
                break;
            case 'original':
                $this->saveName = $this->name;
                break;
            case 'custom':
                if(!$this->customName){
                    throw new \Exception('没有指定saveName值');
                }
                $this->saveName = $this->customName;
                break;
            default:
                throw new \Exception('saveNameMode错误，可选值为:"random","original","custom"');
                break;
        }
    }

    /**
     * 生成完整的文件名
     */
    private function generateFullName()
    {
        if($this->dayDir){
            $fullName         = $this->uploadRoot . $this->savePath . date('Ym') . '/' . date('d') . '/' . $this->saveName;
            $this->returnName = $this->savePath . date('Ym') . '/' . date('d') . '/' . $this->saveName;
        }else{
            $fullName         = $this->uploadRoot . $this->savePath . $this->saveName;
            $this->returnName = $this->savePath . $this->saveName;
        }
        // 如果不覆盖同名文件且文件已存在，则抛出异常
        if(!$this->isCover && file_exists($fullName)){
            throw new \Exception('有同名文件存在，如需覆盖，请将isCover设置成true后，重新上传');
        }
        if(!is_dir(dirname($fullName))){
            @mkdir(dirname($fullName), 0777, true);
        }
        return $fullName;
    }

    /**
     * 检查文件后缀名是否允许
     * @return bool
     */
    private function checkExt()
    {
        if(in_array($this->ext, $this->allowExt)){
            return true;
        }
        throw new \Exception('上传的文件类型不合法，允许上传的类型：' . implode(',', $this->allowExt));
    }

    /**
     * 检查上传的文件大小是否符合
     */
    private function checkSize()
    {
        $allowSize = $this->unitConvert($this->allowSize);
        if($this->size <= $allowSize){
            return true;
        }
        throw new \Exception('文件大小不能超过' . $this->allowSize);
    }

    /**
     * 把单位换算成字节
     * B => KB => MB => GB
     */
    private function unitConvert($str)
    {
        $size = (int)substr($str, 0, -1);
        $unit = strtoupper(substr($str, -1));
        switch ($unit){
            case 'K':
                $result = $size * 1024;
                break;
            case 'M':
                $result = $size * 1024 * 1024;
                break;
            case 'G':
                $result = $size * 1024 * 1024 * 1024;
                break;
            case 'B':
                $result = $size;
                break;
            default:
                throw new \Exception('allowSize单位错误，请使用 "B","K","M","G"');
                break;
        }
        return $result;
    }

    /**
     * 检查错误码
     * @return bool
     * @throws \Exception
     */
    private function checkError()
    {
        $errorCode = $this->file[$this->key]['error'];
        if($errorCode == 0) return true;
        $errorMessage = '';
        switch ($errorCode){
            case 1:
                $errorMessage = '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值。';
                break;
            case 2:
                $errorMessage = '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值。';
                break;
            case 3:
                $errorMessage = '文件只有部分被上传。';
                break;
            case 4:
                $errorMessage = '没有文件被上传。';
                break;
            case 6:
                $errorMessage = '找不到临时文件夹。';
                break;
            case 7:
                $errorMessage = '文件写入失败。';
                break;
        }
        throw new \Exception($errorMessage);
    }
}