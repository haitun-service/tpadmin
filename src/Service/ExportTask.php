<?php

namespace Haitun\Service\TpAdmin\Service;

use Haitun\Service\TpAdmin\System\Be;
use Haitun\Service\TpAdmin\System\Service;

/**
 * 导出任务功能
 *
 * Class Export
 * @package Haitun\Service\TpAdmin\Service
 */
class ExportTask extends Service
{

    public function getUserId() {

        $userId = 0;
        switch(Be::getRuntime()->getFramework())
        {
            case 'tp5-report':
                $userService = new \app\home\service\User();
                $user = $userService->getUser();
                $userId = $user['user_id'];
                break;
        }

        return $userId;
    }

    /**
     * 更新 数据库行记灵对象
     *
     * @param string $namespace 命名空间
     * @param string $name 名称
     * @param array $condition 查询条件
     * @return string 任务ID
     * @throws |Exception
     */
    public function create($namespace, $name, $condition)
    {
        $pathIndex = Be::getRuntime()->getPathData() . '/ExportTask/' . $namespace . '/index';
        $dir = dirname($pathIndex);
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $taskId = uniqid();
        $pathData = Be::getRuntime()->getPathData() . '/ExportTask/' . $namespace . '/' . $taskId . '/data';
        $dir = dirname($pathData);
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $bootstrapUrl = \Haitun\Service\TpAdmin\Util\Url::encode('exportTaskRun', array('taskId' => $taskId));
        $data = [
            'taskId' => $taskId,
            'userId' => $this->getUserId(),
            'name' => $name,
            'createTime' => date('Y-m-d H:i:s'),
            'completeTime' => '-',
            'error' => '-',
            'condition' => $condition,
            'bootstrapUrl' => $bootstrapUrl
        ];
        file_put_contents($pathData, serialize($data), LOCK_EX);

        file_put_contents($pathIndex, $taskId . PHP_EOL, FILE_APPEND | LOCK_EX);

        return $taskId;
    }

    /**
     * 任务列表
     *
     * @param string $namespace 命名空间
     * @return array 任务列表
     */
    public function getTasks($namespace)
    {
        $pathIndex = Be::getRuntime()->getPathData() . '/ExportTask/' . $namespace . '/index';
        if (!file_exists($pathIndex)) {
            return array();
        }

        $tasks = array();

        $fIndex = fopen($pathIndex, 'r');
        if ($fIndex) {
            while (($taskId = fgets($fIndex)) !== false) {
                $taskId = trim($taskId);
                $task = $this->getTask($namespace,$taskId);
                if ($task && isset($task['userId']) && $task['userId'] == $this->getUserId()) $tasks[] = $task;
            }
            fclose($fIndex);
        }

        $tasks = array_reverse($tasks);

        return $tasks;
    }

    /**
     * 清除 $days 天前创建的下载任务
     *
     * @param int $days 天数
     * @param string $namespace 命名空间，未设置时遍历所有的
     * @return bool
     */
    public function cleanTasks($days = 30, $namespace = null) {
        if ($namespace) {

            $pathIndex = Be::getRuntime()->getPathData() . '/ExportTask/' . $namespace . '/index';

            if (!file_exists($pathIndex)) {
                return true;
            }

            $fIndex = fopen($pathIndex, 'r');
            flock($fIndex, LOCK_EX);

            if ($fIndex) {

                $pathIndex0 = Be::getRuntime()->getPathData() . '/ExportTask/' . $namespace . '/index0';
                $fIndex0 = fopen($pathIndex0, 'w');
                flock($fIndex0, LOCK_EX);

                while (($taskId = fgets($fIndex)) !== false) {
                    $taskId = trim($taskId);

                    $pathData = Be::getRuntime()->getPathData() . '/ExportTask/' . $namespace . '/' . $taskId . '/data';
                    if (file_exists($pathData)) {
                        $task = unserialize(file_get_contents($pathData));
                        if ($task['createTime'] < (time() - $days * 86400)) {

                            $path = Be::getRuntime()->getPathData() . '/ExportTask/' . $namespace . '/' . $taskId;
                            $this->rmDir($path);

                        } else {
                            fwrite($fIndex0, $taskId . PHP_EOL);
                        }
                    }
                }

                flock($fIndex, LOCK_UN);
                fclose($fIndex);

                flock($fIndex0, LOCK_UN);
                fclose($fIndex0);

                unlink($pathIndex);
                rename($pathIndex0, $pathIndex);
            }

        } else {
            $path = Be::getRuntime()->getPathData() . '/ExportTask';
            $handle = opendir($path);
            while (($file = readdir($handle)) !== false) {
                if ($file != '.' && $file != '..') {
                    $this->cleanTasks($days, $file);
                }
            }
            closedir($handle);

        }

        return true;
    }

    /**
     * 获取任务
     *
     * @param string $namespace 命名空间
     * @return array 任务
     */
    public function getTask($namespace, $taskId)
    {
        $task = [];
        $pathData = Be::getRuntime()->getPathData() . '/ExportTask/' . $namespace . '/' . $taskId . '/data';
        $pathCsvData = Be::getRuntime()->getPathData() . '/ExportTask/' . $namespace . '/' . $taskId . '/csvData';
        if (file_exists($pathData)) {
            $task = unserialize(file_get_contents($pathData));
            $task['progress'] = $this->getProgress($namespace, $task['taskId']);
            $size = 0;
            if (file_exists($pathCsvData)) {
                $size = filesize($pathCsvData);
                if ($size > 1073741824) {
                    $size = number_format($size / 1073741824, 2, '.', '').' GB';
                } elseif ($size > 1048576) {
                    $size = number_format($size / 1048576, 2, '.', '').' MB';
                } elseif ($size > 1024) {
                    $size = number_format($size / 1024, 0, '.', '').' KB';
                } else {
                    $size = $size.' B';
                }
            }
            $task['size'] = $size;

            if (!isset($task['completeTime'])) $task['completeTime'] = '-';

            $executeTime = '-';
            if ($task['completeTime'] != '-') {
                $executeTimeStr = '';
                $executeTimeS = strtotime($task['completeTime']) - strtotime($task['createTime']);
                if ($executeTimeS > 3600) {
                    $executeTimeStr .= intval($executeTimeS / 3600);
                    $executeTimeS = $executeTimeS % 3600;
                } else {
                    $executeTimeStr .= '0';
                }
                $executeTimeStr .= ':';

                if ($executeTimeS > 60) {
                    $m = intval($executeTimeS / 60);
                    if ($m < 10) $executeTimeStr .= '0';
                    $executeTimeStr .= $m;
                    $executeTimeS = $executeTimeS % 60;
                } else {

                    $executeTimeStr .= '00';
                }
                $executeTimeStr .= ':';

                if ($executeTimeS > 0) {
                    if ($executeTimeS < 10) $executeTimeStr .= '0';
                    $executeTimeStr .= $executeTimeS;
                } else {
                    $executeTimeStr .= '00';
                }

                $executeTime = $executeTimeStr;
            }

            $task['executeTime'] = $executeTime;

            $task['createTime'] = date('Y-m-d H:i', strtotime($task['createTime']));
            if ($task['completeTime'] != '-') $task['completeTime'] = date('Y-m-d H:i', strtotime($task['completeTime']));

            if (!isset($task['error'])) {
                $task['error'] = '-';
            } else {
                if ($task['error'] != '-') {
                    $path = Be::getRuntime()->getPathData() . '/ExportTask/' . $namespace . '/' . $taskId . '/error';  // 错误
                    $task['errorDetails'] = nl2br(file_get_contents($path));
                }
            }
        }
        return $task;
    }

    /**
     * 输出CSV数据
     *
     * @param string $namespace 命名空间
     * @param string $taskId 任务ID
     * @param array $csvData CSV 数据
     */
    public function addCsvData($namespace, $taskId, $csvData = array())
    {
        $path = Be::getRuntime()->getPathData() . '/ExportTask/' . $namespace . '/' . $taskId . '/csvData';  // 数据

        $dir = dirname($path);
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        file_put_contents($path, json_encode($csvData) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /**
     * 获取 CSV 数据
     *
     * @param $namespace
     * @param $taskId
     * @return \Generator
     */
    public function getCsvData($namespace, $taskId)
    {
        $path = Be::getRuntime()->getPathData() . '/ExportTask/' . $namespace . '/' . $taskId . '/csvData';  // 数据

        $fCsvData = fopen($path, 'r');
        flock($fCsvData, LOCK_EX);

        while (($csvData = fgets($fCsvData, 40960)) !== false) {
            $csvData = trim($csvData);
            if ($csvData) {
                yield json_decode($csvData);
            }
        }

        flock($fCsvData, LOCK_UN);
        fclose($fCsvData);
    }

    /**
     * 设置指定任务的进度
     *
     * @param string $namespace 命名空间
     * @param string $taskId 任务ID
     * @param $progress
     */
    public function setProgress($namespace, $taskId, $progress)
    {
        $path = Be::getRuntime()->getPathData() . '/ExportTask/' . $namespace . '/' . $taskId . '/progress';  // 进度

        $dir = dirname($path);
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        if ($progress > 100) $progress = 100;

        file_put_contents($path, $progress, LOCK_EX);

        if ($progress == 100) {
            $task = $this->getTask($namespace, $taskId);
            $task['completeTime'] = date('Y-m-d H:i:s');

            $pathData = Be::getRuntime()->getPathData() . '/ExportTask/' . $namespace . '/' . $taskId . '/data';
            file_put_contents($pathData, serialize($task), LOCK_EX);
        }
    }

    /**
     * 设置指定任务的进度
     *
     * @param string $namespace 命名空间
     * @param string $taskId 任务ID
     * @param \Exception $e 异常
     */
    public function error($namespace, $taskId, $e)
    {
        $task = $this->getTask($namespace, $taskId);
        $task['error'] = $e->getMessage();

        $pathData = Be::getRuntime()->getPathData() . '/ExportTask/' . $namespace . '/' . $taskId . '/data';
        file_put_contents($pathData, serialize($task), LOCK_EX);

        $path = Be::getRuntime()->getPathData() . '/ExportTask/' . $namespace . '/' . $taskId . '/error';  // 错误

        $dir = dirname($path);
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $error = '错误信息: '.$e->getMessage() . PHP_EOL;
        $error .= '错误码: '.$e->getCode() . PHP_EOL;
        $error .= '文件: '.$e->getFile() . PHP_EOL;
        $error .= '行号: '.$e->getLine() . PHP_EOL;
        $error .= '跟踪: ' . PHP_EOL;
        $error .= $e->getTraceAsString() . PHP_EOL;

        file_put_contents($path, $error, LOCK_EX);
    }

    /**
     * 获取指定任务的进度
     *
     * @param string $namespace 命名空间
     * @param string $taskId 任务ID
     * @return int
     */
    public function getProgress($namespace, $taskId)
    {
        $path = Be::getRuntime()->getPathData() . '/ExportTask/' . $namespace . '/' . $taskId . '/progress';  // 进度
        if (!file_exists($path)) {
            return 0;
        }

        return intval(file_get_contents($path));
    }


    /**
     * 删除任务记录
     *
     * @param string $namespace 命名空间
     * @param string $taskId 任务ID
     * @return bool 是否删除成功
     */
    public function delete($namespace, $taskId)
    {
        $path = Be::getRuntime()->getPathData() . '/ExportTask/' . $namespace . '/' . $taskId;
        $this->rmDir($path);

        $pathIndex = Be::getRuntime()->getPathData() . '/ExportTask/' . $namespace . '/index';
        if (!file_exists($pathIndex)) {
            return true;
        }

        $pathIndex0 = Be::getRuntime()->getPathData() . '/ExportTask/' . $namespace . '/index0';

        $fIndex = fopen($pathIndex, 'r');
        flock($fIndex, LOCK_EX);

        $fIndex0 = fopen($pathIndex0, 'w');
        flock($fIndex0, LOCK_EX);

        while (($tTaskId = fgets($fIndex)) !== false) {
            $tTaskId = trim($tTaskId);
            if ($tTaskId != $taskId) {
                fwrite($fIndex0, $tTaskId . PHP_EOL);
            }
        }

        flock($fIndex, LOCK_UN);
        fclose($fIndex);

        flock($fIndex0, LOCK_UN);
        fclose($fIndex0);

        unlink($pathIndex);
        rename($pathIndex0, $pathIndex);

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
