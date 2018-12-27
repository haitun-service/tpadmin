<?php
namespace Haitun\Service\TpAdmin\Service;


use Haitun\Service\TpAdmin\Util\String;
use Haitun\Service\TpAdmin\System\Be;
use Haitun\Service\TpAdmin\System\Db;
use Haitun\Service\TpAdmin\System\db\DbException;
use Haitun\Service\TpAdmin\System\Service;

class Cache extends Service
{

    /**
     * 清除缓存
     *
     * @param string $dir 缓存文件夹名，为 null 时清空所有缓存
     * @param string $file 指定缓存文件夹下的文件名，为 null 时清空整个文件夹
     * @return bool 是否清除成功
     */
    public function clear($dir = null, $file = null)
    {
        if ($dir === null) {
            return $this->clear('File')
                && $this->clear('Row')
                && $this->clear('Table')
                && $this->clear('Template');
        }

        if ($file === null) return $this->rmDir(Be::getRuntime()->getPathCache() . '/' . $dir);
        return $this->rmDir(Be::getRuntime()->getPathCache() . '/' . $dir . '/' . $file);
    }

    /**
     * 更新 数据库行记灵对象
     *
     * @param string $name 数据库行记灵对象名称
     * @return bool 是否更新成功
     * @throws |Exception
     */
    public function updateRow($name)
    {
        $tableName = String::snakeCase($name);
        $db = Be::getDb();
        if (!$db->getValue('SHOW TABLES LIKE \'' . $tableName . '\'')) {
            throw new \Exception('未找到名称为 ' . $tableName . ' 的数据库表！');
        }

        $fields = $db->getObjects('SHOW FULL FIELDS FROM ' . $tableName);

        $primaryKey = 'id';
        foreach ($fields as $field) {
            if ($field->Key == 'PRI') {
                $primaryKey = $field->Field;
            }
        }

        $formattedFields = $this->formatTableFields($name, $fields);

        $code = '<?php' . "\n";
        $code .= 'namespace Cache\\Row;' . "\n";
        $code .= "\n";
        $code .= 'class ' . $name . ' extends \\Haitun\\Service\\M\\System\\Row' . "\n";
        $code .= '{' . "\n";
        $code .= '    protected $tableName = \'' . $tableName . '\'; // 表名' . "\n";
        $code .= '    protected $primaryKey = \'' . $primaryKey . '\'; // 主键' . "\n";
        $code .= '    protected $fields = ' . var_export($formattedFields, true) . '; // 字段列表' . "\n";

        foreach ($formattedFields as $key => $field) {
            $code .= '    public $' . $field['field'] . ' = ' . ($field['isNumber'] ? $field['default'] : ('\'' . $field['default'] . '\'')) . ';';
            if ($field->comment) $code .= ' // ' . $field['comment'];
            $code .= "\n";
        }
        $code .= '}' . "\n";
        $code .= "\n";

        $path = Be::getRuntime()->getPathCache() . '/Row/' . $name . '.php';
        $dir = dirname($path);
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        file_put_contents($path, $code, LOCK_EX);
        chmod($path, 0755);

        return true;
    }

    /**
     * 更新表
     *
     * @param string $name 要表新的表名
     * @return bool 是否更新成功
     */
    public function updateTable($name)
    {
        $tableName = String::snakeCase($name);
        $db = Be::getDb();
        $fields = $db->getObjects('SHOW FULL FIELDS FROM ' . $tableName);

        $primaryKey = 'id';
        foreach ($fields as $field) {
            if ($field->Key == 'PRI') {
                $primaryKey = $field->Field;
            }
        }

        $formattedFields = $this->formatTableFields($name, $fields);

        $code = '<?php' . "\n";
        $code .= 'namespace Cache\\Table;' . "\n";
        $code .= "\n";
        $code .= 'class ' . $name . ' extends \\Haitun\\Service\\M\\System\\Table' . "\n";
        $code .= '{' . "\n";
        $code .= '    protected $tableName = \'' . $tableName . '\'; // 表名' . "\n";
        $code .= '    protected $primaryKey = \'' . $primaryKey . '\'; // 主键' . "\n";
        $code .= '    protected $fields = ' . var_export($formattedFields, true) . '; // 字段列表' . "\n";

        $code .= '}' . "\n";
        $code .= "\n";

        $path = Be::getRuntime()->getPathCache() . '/Table/' . $name . '.php';
        $dir = dirname($path);
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        file_put_contents($path, $code, LOCK_EX);
        chmod($path, 0755);

        return true;
    }

    /**
     * 更新表
     *
     * @param string $name 要表新的表名
     * @param string $fields 字段配置
     * @return bool 是否更新成功
     */
    public function updateTableConfig($name, $fields)
    {
        $tableName = String::snakeCase($name);

        $code = '<?php' . "\n";
        $code .= 'namespace Data\\TableConfig;' . "\n";
        $code .= "\n";
        $code .= 'class ' . $name . ' extends \\Haitun\\Service\\M\\System\\TableConfig' . "\n";
        $code .= '{' . "\n";
        $code .= '    protected $tableName = \'' . $tableName . '\'; // 表名' . "\n";
        $code .= '    protected $fields = ' . var_export($fields, true) . '; // 字段列表' . "\n";
        $code .= '}' . "\n";
        $code .= "\n";

        $path = Be::getRuntime()->getPathData() . '/TableConfig/' . $name . '.php';
        $dir = dirname($path);
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        file_put_contents($path, $code, LOCK_EX);
        chmod($path, 0755);

        $this->updateTable($name);

        $this->updateRow($name);

        return true;
    }


    private function formatTableFields($name, $fields) {

        $tableConfig = Be::getTableConfig($name);


        $formattedFields = array();

        foreach ($fields as $field) {

            $type = $field->Type;
            $typeLength = 0;
            $unsigned = strpos($field->Type, 'unsigned') !== false;

            $pos = strpos($field->Type, '(');
            if ($pos !== false) {
                $type = substr($field->Type, 0,  $pos);
                $typeLength = substr($field->Type, $pos + 1, strpos($field->Type, ')') - $pos - 1 );
            }

            //if (!is_numeric($typeLength)) $typeLength = -1;

            $numberTypes = array('int', 'mediumint', 'tinyint', 'smallint', 'bigint', 'decimal', 'float', 'double', 'real', 'bit', 'boolean', 'serial');
            $isNumber = in_array($type, $numberTypes);

            $default = null;
            if ($isNumber) {
                $default = $field->Default ? $field->Default : 0;
            } else {
                $default = $field->Default ? addslashes($field->Default) : '';
            }

            $extra = $field->Extra;

            $comment = addslashes($field->Comment);

            $optionType = 'null';
            $optionData = '';

            if ($type == 'enum') {
                $optionType = 'array';
                $optionData = str_replace(',', "\n", $typeLength);
            }

            $name = $field->Field;
            $disable = false;
            $show = true;
            $editable = true;
            $create = true;
            $format = '';


            $configField = $tableConfig->getField($field->Field);
            if ($configField) {
                $name = $configField['name'];

                if (isset($configField['optionType']) &&
                    in_array($configField['optionType'], array('null', 'array', 'sql')) &&
                    isset($configField['optionData'])
                ) {

                    $optionType = $configField['optionType'];
                    $optionData = $configField['optionData'];
                }

                if (isset($configField['disable'])) {
                    $disable = $configField['disable'] ? true : false;
                }

                if (isset($configField['show'])) {
                    $show = $configField['show'] ? true : false;
                }

                if (isset($configField['editable'])) {
                    $editable = $configField['editable'] ? true : false;
                }

                if (isset($configField['format']) && $configField['format']) {
                    $format = $configField['format'];
                }

                if (isset($configField['create']) && $configField['create']) {
                    $create = $configField['create'];
                }
            }

            $formattedFields[$field->Field] = array(
                'name' => $name, // 字段名
                'field' => $field->Field, // 字段名
                'type' => $type, // 类型
                'typeLength' => $typeLength, // 类型长度
                'optionType' => $optionType, // 枚举类型取值范围
                'optionData' => $optionData, // 枚举类型取值范围
                'isNumber' => $isNumber,  // 是否数字
                'unsigned' => $unsigned, // 是否非负，数字类型时有效
                'default' => $default, // 默认值
                'extra' => $extra, // 附加内容
                'comment' => $comment, // 注释
                'disable' => $disable, // 是否禁用
                'show' => $show, // 是否默认展示
                'editable' => $editable, // 是否可编辑
                'create' => $create, // 是否可新建
                'format' => $format, // 格式化
            );
        }

        return $formattedFields;
    }

    /**
     * 更新模板
     *
     * @param string $template 模析名
     * @param string $theme 主题名
     * @return bool 是否更新成功
     * @throws \Exception
     */
    public function updateTemplate($template, $theme)
    {
        $fileTheme = Be::getRuntime()->getPathRoot() . '/vendor/haitun/service/src/M/Theme/' . $theme . '/' . $theme . '.php';
        if (!file_exists($fileTheme)) {
            throw new \Exception('主题 ' . $theme . ' 不存在！');
        }

        $fileTemplate = Be::getRuntime()->getPathRoot() . '/vendor/haitun/service//src/M/Template/' .  $template . '.php';
        if (!file_exists($fileTemplate)) {
            throw new \Exception('模板 ' . $template . ' 不存在！');
        }

        $path = Be::getRuntime()->getPathCache() .  '/Template/' . $theme . '/' . $template . '.php';
        $dir = dirname($path);
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $contentTheme = file_get_contents($fileTheme);
        $contentTemplate = file_get_contents($fileTemplate);

        $codePre = '';
        $codeUse = '';
        $codeHtml = '';
        $pattern = '/<!--{html}-->(.*?)<!--{\/html}-->/s';
        if (preg_match($pattern, $contentTemplate, $matches)) { // 查找替换 html
            $codeHtml = trim($matches[1]);

            if (preg_match_all('/use\s(.+);/', $contentTemplate, $matches)) {
                foreach ($matches[1] as $m) {
                    $codeUse .= 'use ' . $m . ';' . "\n";
                }
            }

            $pattern = '/<\?php(.*?)\?>\s+<!--{html}-->/s';
            if (preg_match($pattern, $contentTemplate, $matches)) {
                $codePre = trim($matches[1]);
                $codePre = preg_replace('/use\s(.+);/', '', $codePre);
                $codePre = preg_replace('/\s+$/m', '', $codePre);
            }

        } else {

            if (preg_match($pattern, $contentTheme, $matches)) {
                $codeHtml = trim($matches[1]);

                $pattern = '/<!--{head}-->(.*?)<!--{\/head}-->/s';
                if (preg_match($pattern, $contentTemplate, $matches)) { // 查找替换 head
                    $codeHead = $matches[1];
                    $codeHtml = preg_replace($pattern, $codeHead, $codeHtml);
                }

                $pattern = '/<!--{body}-->(.*?)<!--{\/body}-->/s';
                if (preg_match($pattern, $contentTemplate, $matches)) { // 查找替换 body
                    $codeBody = $matches[1];
                    $codeHtml = preg_replace($pattern, $codeBody, $codeHtml);
                } else {

                    $pattern = '/<!--{north}-->(.*?)<!--{\/north}-->/s';
                    if (preg_match($pattern, $contentTemplate, $matches)) { // 查找替换 north
                        $codeNorth = $matches[1];
                        $codeHtml = preg_replace($pattern, $codeNorth, $codeHtml);
                    }

                    $pattern = '/<!--{middle}-->(.*?)<!--{\/middle}-->/s';
                    if (preg_match($pattern, $contentTemplate, $matches)) { // 查找替换 north
                        $codeMiddle = $matches[1];
                        $codeHtml = preg_replace($pattern, $codeMiddle, $codeHtml);
                    } else {
                        $pattern = '/<!--{west}-->(.*?)<!--{\/west}-->/s';
                        if (preg_match($pattern, $contentTemplate, $matches)) { // 查找替换 west
                            $codeWest = $matches[1];
                            $codeHtml = preg_replace($pattern, $codeWest, $codeHtml);
                        }

                        $pattern = '/<!--{center}-->(.*?)<!--{\/center}-->/s';
                        if (preg_match($pattern, $contentTemplate, $matches)) { // 查找替换 center
                            $codeCenter = $matches[1];
                            $codeHtml = preg_replace($pattern, $codeCenter, $codeHtml);
                        }

                        $pattern = '/<!--{east}-->(.*?)<!--{\/east}-->/s';
                        if (preg_match($pattern, $contentTemplate, $matches)) { // 查找替换 east
                            $codeEast = $matches[1];
                            $codeHtml = preg_replace($pattern, $codeEast, $codeHtml);
                        }
                    }

                    $pattern = '/<!--{message}-->(.*?)<!--{\/message}-->/s';
                    if (preg_match($pattern, $contentTemplate, $matches)) { // 查找替换 message
                        $codeMessage = $matches[1];
                        $codeHtml = preg_replace($pattern, $codeMessage, $codeHtml);
                    }

                    $pattern = '/<!--{south}-->(.*?)<!--{\/south}-->/s';
                    if (preg_match($pattern, $contentTemplate, $matches)) { // 查找替换 north
                        $codeSouth = $matches[1];
                        $codeHtml = preg_replace($pattern, $codeSouth, $codeHtml);
                    }
                }
            }

            $pattern = '/use\s(.+);/';
            $uses = null;
            if (preg_match_all($pattern, $contentTheme, $matches)) {
                $uses = $matches[1];
                foreach ($matches[1] as $m) {
                    $codeUse .= 'use ' . $m . ';' . "\n";
                }
            }

            if (preg_match_all($pattern, $contentTemplate, $matches)) {
                foreach ($matches[1] as $m) {
                    if ($uses !== null && !in_array($m, $uses)) {
                        $codeUse .= 'use ' . $m . ';' . "\n";
                    }
                }
            }

            $pattern = '/<\?php(.*?)\?>\s+<!--{html}-->/s';
            if (preg_match($pattern, $contentTheme, $matches)) {
                $codePreTheme = trim($matches[1]);
                $codePreTheme = preg_replace('/use\s(.+);/', '', $codePreTheme);
                $codePreTheme = preg_replace('/\s+$/m', '', $codePreTheme);
                $codePre = $codePreTheme . "\n";
            }

            $pattern = '/<\?php(.*?)\?>\s+<!--{(?:html|head|body|north|middle|west|center|east|south|message)}-->/s';
            if (preg_match($pattern, $contentTemplate, $matches)) {
                $codePreTemplate = trim($matches[1]);
                $codePreTemplate = preg_replace('/use\s(.+);/', '', $codePreTemplate);
                $codePreTemplate = preg_replace('/\s+$/m', '', $codePreTemplate);

                $codePre .= $codePreTemplate . "\n";
            }
        }

        $codePhp = '<?php' . "\n";
        $codePhp .= 'namespace Cache\\Template\\' . $theme . ';' . "\n";
        $codePhp .= "\n";
        $codePhp .= $codeUse;
        $codePhp .= "\n";
        $codePhp .= 'class ' . $template . ' extends \\Haitun\\Service\\M\\System\\Template' . "\n";
        $codePhp .= '{' . "\n";
        $codePhp .= "\n";
        $codePhp .= '  public function display()' . "\n";
        $codePhp .= '  {' . "\n";
        $codePhp .= $codePre;
        $codePhp .= '    ?>' . "\n";
        $codePhp .= $codeHtml . "\n";
        $codePhp .= '    <?php' . "\n";
        $codePhp .= '  }' . "\n";
        $codePhp .= '}' . "\n";
        $codePhp .= "\n";

        file_put_contents($path, $codePhp, LOCK_EX);
        chmod($path, 0755);

        return true;
    }

    /**
     * 删除文件夹, 同时删除文件夹下的所有文件
     *
     * @param string $path 文件路径
     * @return bool
     */
    public function rmDir($path)
    {
        if (!file_exists($path)) {
            return true;
        }

        if (is_dir($path)) {
            $handle = opendir($path);
            while (($file = readdir($handle)) !== false) {
                if ($file != '.' && $file != '..') {
                    $this->rmDir($path . '/' . $file);
                }
            }
            closedir($handle);

            rmdir($path);
        } else {
            unlink($path);
        }

        return true;
    }


}
