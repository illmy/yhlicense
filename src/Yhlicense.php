<?php
namespace illmy\Yhlicense;
use Yurun\Util\Chinese;
use Yurun\Util\Chinese\Pinyin;

class Yhlicense 
{
    //选中省份
    protected $selectProvice;

    //选中号码 
    protected $selectNumber;

    protected $filterStr = '';

    //初始串
    protected $initStr;
    //替换词
    protected $replaceSpeech = [
        'pattern' => ['零','一','二','三','四' ,'五','六','七','八','九'],
        'replacement' => ['0','1','2','3','4','5','6','7','8','9']
    ];

    //过滤词
    protected $filterSpeech = [];

    //查找词 替换对应词
    protected $findSpeech = [
        ['find' => '贵州|昂贵|贵阳','replace' => '贵'],
        ['find' => '桂圆','replace' => '桂']
    ];

    //匹配到的省份汉字
    protected $pcode = '';

    protected $provinceCode = [
        'jing' => '京',
        'jin' => ['1' => '津','4' => '晋'],
        'hu' => '沪',
        'yu' => ['2' => '渝','4' => '豫'],
        'ji' => ['4' => '冀','2' => '吉'],
        'yun' => '云',
        'liao' => '辽',
        'hei' => '黑',
        'xiang' => '湘',
        'wan' => '皖',
        'lu' => '鲁',
        'xin' => '新',
        'su' => '苏',
        'zhe' => '浙',
        'gan' => ['4' => '赣','1' => '甘'],
        'e' => '鄂',
        'gui' => ['4' => ['桂','贵']],
        'meng' => '蒙',
        'shan' => '陕',
        'min' => '闵',
        'yue' => '粤',
        'qing' => '清',
        'zang' => '藏',
        'chuan' => '川',
        'ning' => '宁',
        'qiong' => '琼',
        'gang' => '港',
        'ao' => '澳',
        'tai' => '台'
    ];

    public function __construct($str,$replaceSpeech = [],$filterSpeech = [],$findSpeech = [])
    {
        $this->initStr = $this->filterStr = $str;
        if (isset($replaceSpeech['pattern'])) {
            $this->replaceSpeech['pattern'] = array_merge($this->replaceSpeech['pattern'],$replaceSpeech['pattern']);
        }
        if (isset($replaceSpeech['replacement'])) {
            $this->replaceSpeech['replacement'] = array_merge($this->replaceSpeech['replacement'],$replaceSpeech['replacement']);
        }
        $this->filterSpeech = $filterSpeech;
        $this->findSpeech = $findSpeech ? : $this->findSpeech;
    }

    public function filterLicense()
    {
        $this->filterSpeech();
        $this->replaceSpeech();
        $this->pregLicense();
        $this->toPinyin();
        $this->findSpeech();
        $this->lowerToUpper();
        return $this->selectProvice.$this->selectNumber;
    }

    protected function replaceSpeech()
    {
        $this->replaceSpeech['pattern'] = array_map(function($v){ return '/'.$v.'/'; },$this->replaceSpeech['pattern']);
        $this->filterStr = preg_replace($this->replaceSpeech['pattern'],$this->replaceSpeech['replacement'],$this->filterStr);
    }

    protected function filterSpeech()
    {
        $filterSpeech = array_map(function($v){ return '/'.$v.'/'; },$this->filterSpeech);
        $this->filterStr = preg_replace($filterSpeech,'',$this->filterStr);
    }

    protected function lowerToUpper()
    {
        $this->selectNumber = strtoupper($this->selectNumber);
    }

    protected function pregLicense()
    {
        $pattern = '/(([\x{4e00}-\x{9fa5}])([A-Za-z0-9]{6,7}))/u';
        if (preg_match($pattern,$this->filterStr,$match)) {
            $this->filterStr = $match[1];
            $this->pcode = $match[2];
            $this->selectNumber = $match[3];
        }
    }

    protected function toPinyin()
    {
        if (empty($this->pcode)) {
            return true;
        }
        //匹配汉字
        $hanzi = array_values($this->provinceCode);
        $hanziarray = [];
        foreach ($hanzi as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $k => $val) {
                    if (is_array($val)) {
                        foreach ($val as $v) {
                            if ($v == $this->pcode) {
                                $this->selectProvice = $this->pcode;
                                break;
                            }
                        }
                    } else {
                        if ($val == $this->pcode) {
                            $this->selectProvice = $this->pcode;
                            break;
                        }
                    }    
                }
            } else {
                if ($value == $this->pcode) {
                    $this->selectProvice = $this->pcode;
                    break;
                }
            }   
        }
        if (!empty($this->selectProvice)) {
            return true;
        }
        $pinyin = Chinese::toPinyin($this->pcode, Pinyin::CONVERT_MODE_PINYIN)['pinyin'][0][0];
        if (isset($this->provinceCode[$pinyin])) {
            $code = $this->provinceCode[$pinyin];
            if (is_array($code)) {
                $sound = substr(Chinese::toPinyin($this->pcode, Pinyin::CONVERT_MODE_PINYIN_SOUND_NUMBER)['pinyinSoundNumber'][0][0],-1);
                if (isset($code[$sound])) {
                    $this->selectProvice = is_array($code[$sound]) ? $code[$sound][0] : $code[$sound];
                }
            } else {
                $this->selectProvice = $code;
            }
        }
    }

    /**
     * 关键字匹配  权重高
     *
     * @return void
     */
    protected function findSpeech()
    {
        if (empty($this->initStr)) return true;
        $pinyin = Chinese::toPinyin($this->initStr, Pinyin::CONVERT_MODE_PINYIN,'');
        if (empty($pinyin['pinyin'][0])) return true;
        $pinyin = $pinyin['pinyin'][0];
        foreach ($this->findSpeech as $value) {
            if (empty($value['find'])) continue;
            if (preg_match('/'.$value['find'].'/',$this->initStr)) {
                $this->selectProvice = $value['replace'];
                break;
            } else {
                $yin = Chinese::toPinyin($value['find'], Pinyin::CONVERT_MODE_PINYIN,'');
                if (empty($yin['pinyin'][0])) {
                    continue;
                }
                $pattern = '/'.$yin['pinyin'][0].'/';
                if (preg_match($pattern,$pinyin)) {
                    $this->selectProvice = $value['replace'];
                    break;
                }    
            }
        }
    }
}