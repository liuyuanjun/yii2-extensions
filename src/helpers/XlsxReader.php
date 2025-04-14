<?php

namespace liuyuanjun\yii2\helpers;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use yii\base\Exception;
use yii\web\UploadedFile;
use Yii;

/**
 * Class XlsxReader
 *
 * $xlsx = new XlsxReader();
 * $xlsx->setUploadedFile('file');
 * $data = $xlsx->readByRule([
 * 'ent_name'           => ['type' => 'value', 'col' => 1],
 * 'real_capital'       => ['type' => 'value', 'col' => 2],//实缴资本
 * 'annual_date'        => ['type' => 'value', 'col' => 3],//核准日期
 * 'open_time'          => ['type' => 'value', 'col' => 4],//营业期限
 * 'taxpayer'           => ['type' => 'value', 'col' => 5],//纳税人资质
 * 'authority'          => ['type' => 'value', 'col' => 6],//登记机关
 * 'staff_num'          => ['type' => 'value', 'col' => 7],//人员规模
 * 'insured_num'        => ['type' => 'value', 'col' => 8],//参保人数
 * 'prev_ent_name'      => ['type' => 'value', 'col' => 9],//曾用名
 * 'eng_ent_name'       => ['type' => 'value', 'col' => 10],//英文名
 * 'import_export_code' => ['type' => 'value', 'col' => 11],//进出口企业代码
 * 'custom_test'        => function ($xlsx, $row) {
 * static $totalInsuredNum = 0;
 * $totalInsuredNum += (int)$xlsx->getCellValue(8, $row);
 * return $totalInsuredNum;
 * }
 * ], 2);
 *
 * @author  Yuanjun.Liu <6879391@qq.com>
 */
class XlsxReader
{
    /**  @var string file path */
    protected $_filePath;
    protected $_fileExt;
    /** @var null|UploadedFile */
    protected $_uploadedFile;
    protected $_spreadsheet;
    protected $_tmpPath;
    protected $_sheetIndex = 0;
    public    $ignoreValue = ['-'];

    /**
     * XlsxReader constructor.
     *
     * @param string|null $filePath
     * @throws Exception
     */
    public function __construct($filePath = null)
    {
        set_time_limit(0);
        $filePath && $this->setXlsxFile($filePath);
    }


    /**
     * 读取
     * @param array $colParseRule
     * @param int   $startRowNo
     * @param int   $endRowNo
     * @return array
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @date   2021/6/8 18:13
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function readByRule(array $colParseRule, int $startRowNo = 1, int $endRowNo = -1): array
    {
        $data  = [];
        $sheet = $this->getSheet();
        $total = $endRowNo > 0 ? $endRowNo : $sheet->getHighestRow();
        if ($total < 1) throw new Exception('没有记录可导出');
        for ($row = $startRowNo; $row <= $total; $row++) { //读取内容
            $array = [];
            foreach ($colParseRule as $key => $rule) {
                if (is_callable($rule)) {
                    $array[$key] = call_user_func($rule, $this, $row);
                } elseif ($rule['type'] == 'value') {
                    $array[$key] = $this->getCellValue($rule['col'], $row);
                } elseif ($rule['type'] == 'link') {
                    $array[$key] = $this->getCellLink($rule['col'], $row);
                } elseif ($rule['type'] == 'calculatedValue') {
                    $array[$key] = $this->getCell($rule['col'], $row)->getCalculatedValue();
                } elseif ($rule['type'] == 'formattedValue') {
                    $array[$key] = $this->getCell($rule['col'], $row)->getFormattedValue();
                } elseif ($rule['type'] == 'date') {
                    $cellVal = $this->getCellValue($rule['col'], $row);
                    if ($cellVal) {
                        $toTimestamp = Date::excelToTimestamp($cellVal);
                        $array[$key] = date("Y-m-d", $toTimestamp);
                    } else {
                        $array[$key] = $cellVal;
                    }
                }
            }
            $data[] = $array;
        }
        if (empty($data)) throw new Exception('数据无法识别！');
        return $data;
    }

    /**
     * 读取
     * 数据规则Map的 key 为列的编号，value 为规则，
     * 规则（rule） 是一个 Map
     * rule.data 为数据类型，根据不同类型读取方法不一样  value link  calculated  formatted  date 或可调用函数，不指定则默认读取Cell值
     * rule.key 为行数据的列key，如果没有 rule.key 则以 rule.titleRow 为 key，如果没有 rule.titleRow 则以列号（A、B、C）为 key
     * rule.primary 为主字段，如果指定了，则读取到主字段为空结束，否则读取到最后一行，主字段必须为直读的值，为空则停止读取
     * @param array $dataRules      数据规则 ['1' => ['data' => 'link', 'key' => 'id', 'titleRow' => 3, 'primary' => true, 'ignore' => false, 'isLast' => false]]
     * @param int   $dataStartRowNo 数据起始行
     * @return array
     * @date   2025/4/13 18:13
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function read($dataRules = [], $dataStartRowNo = 2, $defaultTitleRowNo = -1): array
    {
        $data       = [];
        $sheet      = $this->getSheet();
        $highestRow = $sheet->getHighestRow();
        $highestCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        if ($highestRow < 1) return $data;
        $titles      = [];
        $primaryCol  = null;
        $parsedRules = [];
        for ($col = 1; $col <= $highestCol; $col++) {
            $colLetter = Coordinate::stringFromColumnIndex($col);
            $colRule   = $dataRules[$colLetter] ?? $dataRules[$col . ''] ?? $dataRules[$col] ?? null;
            if (!empty($colRule['ignore'])) continue;
            $rule = ['letter' => $colLetter, 'col' => $col];
            if (!empty($colRule['key'])) {
                $rule['key'] = $colRule['key'];
            } elseif (!empty($colRule['titleRow'])) {
                $rule['key'] = $this->getCellValue($col, $colRule['titleRow']);
            } elseif ($defaultTitleRowNo > 0) {
                $rule['key'] = $this->getCellValue($col, $defaultTitleRowNo);
            } else {
                $rule['key'] = Coordinate::stringFromColumnIndex($col);
            }
            $rule['data']      = $colRule['data'] ?? 'value';
            $parsedRules[$col] = $rule;
            if (!empty($colRule['primary'])) {
                $primaryCol = $col;
            }
            if (!empty($colRule['isLast'])) {
                break;
            }
        }
        for ($row = $dataStartRowNo; $row <= $highestRow; $row++) {
            $rowData = [];
            foreach ($parsedRules as $col => $rule) {
                if ($primaryCol == $col && $this->getCellValue($col, $row) == '') {
                    break 2;
                }
                $rowData[$rule['key']] = $this->getCellValueByRule($col, $row, $rule['data'] ?? null);
            }
            $data[] = $rowData;
        }
        return $data;
    }

    /**
     * 按规则读取 Cell 值
     * @param $col
     * @param $row
     * @param $dataRule
     * @return false|float|int|mixed|string
     * @throws \PhpOffice\PhpSpreadsheet\Calculation\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    private function getCellValueByRule($col, $row, $dataRule = null)
    {
        $dataRule = $dataRule ?? 'value';
        if ($dataRule === 'value') {
            return $this->getCellValue($col, $row);
        } elseif ($dataRule === 'link') {
            return $this->getCellLink($col, $row);
        } elseif ($dataRule === 'calculated') {
            return $this->getCell($col, $row)->getCalculatedValue();
        } elseif ($dataRule === 'formatted') {
            return $this->getCell($col, $row)->getFormattedValue();
        } elseif ($dataRule === 'date') {
            $cellVal = $this->getCellValue($col, $row);
            if ($cellVal) {
                $toTimestamp = Date::excelToTimestamp($cellVal);
                return date("Y-m-d", $toTimestamp);
            } else {
                return $cellVal;
            }
        } elseif ($dataRule && is_callable($dataRule)) {
            return call_user_func($dataRule, $this, $col, $row);
        } else {
            return $this->getCellValue($col, $row);
        }
    }

    /**
     * @param int $startRowNo
     * @return array
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @date   2021/6/8 17:57
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function readAll(int $startRowNo = 1): array
    {
        $sheet      = $this->getSheet();
        $highestRow = $sheet->getHighestRow();
        $highestCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        if ($highestRow < 1) throw new Exception('没有记录可导出');
        $data = [];
        for ($row = $startRowNo; $row <= $highestRow; $row++) { //读取内容
            $rowData = [];
            for ($col = 1; $col <= $highestCol; $col++) {
                $rowData[$col] = $this->getCellValue($col, $row);
            }
            $data[] = $rowData;
        }
        return $data;
    }

    /**
     * @param int $row
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @date   2021/6/8 16:21
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function readRow(int $row): array
    {
        $highestCol = Coordinate::columnIndexFromString($this->getSheet()->getHighestColumn());
        $data       = [];
        for ($col = 1; $col <= $highestCol; $col++) {
            $data[$col] = $this->getCellValue($col, $row);
        }
        return $data;
    }

    /**
     * @param int $col
     * @param int $row
     * @return string
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @date   2021/6/8 16:26
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function getCellLink(int $col, int $row): string
    {
        return trim($this->getCell($col, $row)->getHyperlink()->getUrl());
    }

    /**
     * @param int $col
     * @param int $row
     * @return string
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @date   2021/6/8 13:37
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function getCellValue(int $col, int $row): string
    {
        $value = (string)$this->getCell($col, $row)->getValue();
        $value = trim($value);
        return in_array($value, $this->ignoreValue) ? '' : $value;
    }

    /**
     * @param int $col
     * @param int $row
     * @return Cell|null
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @date   2021/6/8 13:56
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function getCell(int $col, int $row): ?Cell
    {
        return $this->getSheet()->getCellByColumnAndRow($col, $row);
    }

    /**
     * @return Worksheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @date   2021/6/8 13:46
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function getSheet(): Worksheet
    {
        return $this->getSpreadsheet()->getActiveSheet();
    }

    /**
     * 设置Sheet
     * @param int $pIndex
     * @return $this
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @date   2021/6/8 13:46
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function setSheet(int $pIndex): XlsxReader
    {
        $this->_sheetIndex = $pIndex;
        $this->getSpreadsheet()->setActiveSheetIndex($this->_sheetIndex);
        return $this;
    }

    /**
     * 设置文件
     * @param string $filePath
     * @param bool   $checkExt
     * @return XlsxReader
     * @throws Exception
     * @author     Yuanjun.Liu <6879391@qq.com>
     * @deprecated 改为使用 setFile() 方法
     */
    public function setXlsxFile(string $filePath, bool $checkExt = true): XlsxReader
    {
        if (!is_file($filePath)) {
            throw new Exception('文件不存在 ' . $filePath);
        }
        $this->_fileExt = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($checkExt && !in_array($this->_fileExt, ['xlsx', 'xls'], true)) {
            throw new Exception('文件类型不符，只接受 xlsx, xls 格式');
        }
        $this->reset();
        $this->_filePath = $filePath;
        return $this;
    }

    /**
     * 设置文件
     * @param string $filePath
     * @return $this
     * @throws Exception
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function setFile(string $filePath): XlsxReader
    {
        if (!is_file($filePath)) {
            throw new Exception('文件不存在 ' . $filePath);
        }
        $this->_fileExt = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($this->_fileExt, ['xlsx', 'xls'], true)) {
            throw new Exception('文件类型不符，只接受 xlsx, xls 格式');
        }
        $this->reset();
        $this->_filePath = $filePath;
        return $this;
    }

    /**
     * 设置上传文件
     * @param string $fileInputName
     * @return XlsxReader
     * @throws Exception
     */
    public function setUploadedFile(string $fileInputName): XlsxReader
    {
        if (!$this->_uploadedFile = UploadedFile::getInstanceByName($fileInputName)) {
            throw new Exception('没有找到上传文件');
        }
        if ($this->_uploadedFile->getExtension() !== 'xlsx') {
            throw new Exception('文件类型不符，只接受 xlsx 格式');
        }
        $filePath = $this->getTmpPath() . '/import.xlsx';
        if (!$this->_uploadedFile->saveAs($filePath)) {
            throw new Exception('保存文件失败');
        }
        $this->reset();
        $this->_filePath = $filePath;
        return $this;
    }

    /**
     * reset
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    protected function reset()
    {
        $this->_filePath    = null;
        $this->_spreadsheet = null;
        $this->_tmpPath     = null;
        $this->_sheetIndex  = 0;
    }

    /**
     * 清理临时文件
     */
    public function removeTmpFiles()
    {
        exec('rm -rf ' . $this->getTmpPath());
    }

    /**
     * 临时目录
     * @return string
     * @date   2021/6/8 19:21
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    protected function getTmpPath(): string
    {
        if (!$this->_tmpPath) {
            $this->_tmpPath = Yii::getAlias('@runtime/xlsx') . '/' . uniqid();
            is_dir($this->_tmpPath) || mkdir($this->_tmpPath, 0777, true);
        }
        return $this->_tmpPath;
    }

    /**
     * 获取实例
     * @return Spreadsheet
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function getSpreadsheet(): Spreadsheet
    {
        if (!$this->_spreadsheet) {
            if ($this->_reader === null) {
                $this->_reader = IOFactory::createReader($this->_fileExt === 'xlsx' ? IOFactory::READER_XLSX : IOFactory::READER_XLS);
//                $this->_reader->setReadDataOnly(TRUE); //hyperlinks are not loaded when setReadDataOnly(true)
            }
            $this->_spreadsheet = $this->_reader->load($this->_filePath);
        }
        return $this->_spreadsheet;
    }

    protected $_reader;

}
