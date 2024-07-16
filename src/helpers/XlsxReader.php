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
    protected $_xlsxFilePath;
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
    public function __construct(string $filePath = null)
    {
        set_time_limit(0);
        $filePath && $this->setXlsxFile($filePath);
    }


    /**
     * 读取
     * @param array $colParseRule
     * @param int   $startRowNo
     * @return array
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @date   2021/6/8 18:13
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function readByRule(array $colParseRule, int $startRowNo = 1): array
    {
        $data  = [];
        $sheet = $this->getSheet();
        $total = $sheet->getHighestRow();
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
        $value = $this->getCell($col, $row)->getValue();
        if(is_string($value)) $value = trim($value);
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
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    public function setXlsxFile(string $filePath, bool $checkExt = true): XlsxReader
    {
        if (!is_file($filePath)) {
            throw new Exception('文件不存在 ' . $filePath);
        }
        if ($checkExt && strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) !== 'xlsx') {
            throw new Exception('文件类型不符，只接受 xlsx 格式');
        }
        $this->reset();
        $this->_xlsxFilePath = $filePath;
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
        $this->_xlsxFilePath = $filePath;
        return $this;
    }

    /**
     * reset
     * @author Yuanjun.Liu <6879391@qq.com>
     */
    protected function reset()
    {
        $this->_xlsxFilePath = null;
        $this->_spreadsheet  = null;
        $this->_tmpPath      = null;
        $this->_sheetIndex   = 0;
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
                $this->_reader = IOFactory::createReader('Xlsx');
//                $this->_reader->setReadDataOnly(TRUE); //hyperlinks are not loaded when setReadDataOnly(true)
            }
            $this->_spreadsheet = $this->_reader->load($this->_xlsxFilePath);
        }
        return $this->_spreadsheet;
    }

    protected $_reader;

}
