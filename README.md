# yhlicense

use illmy\Yhlicense\Yhlicense;

$license = new Yhlicense('哈哈，我的车牌号是月A一二哈哈西尔克司s,桂阳的贵',['pattern' => ['西','尔克司'],'replacement' => ['C','X']],['哈','逗']);
$data = $license->filterLicense();

var_dump($data);


构造函数接受4个参数  
1 需要提取的字符串
2 需要替换的字符
3 需要过滤掉的字符
4 查找字符  权重高
