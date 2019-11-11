<?php
/**
 * Copyright © 2018 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
class Wyomind_Datafeedmanager_Block_Adminhtml_Configurations_Renderer_Process extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    const _SUCCEED = 'SUCCEED';
    const _PENDING = 'PENDING';
    const _PROCESSING = 'PROCESSING';
    const _HOLD = 'HOLD';
    const _COLLECTING = "COLLECTING";
    const _FAILED = 'FAILED';

    public function render(Varien_Object $row) {
        $dir = Mage::getBaseDir() . DS . 'var' . DS . 'tmp' . DS;
        $file = $dir . 'dfm_' . $row->getId() . '.flag';

        $flag = new Varien_Io_File();
        $flag->open(array('path' => $dir));

        if ($flag->fileExists($file, false)) {
            $flag->streamOpen($file, 'r');
            $line = $flag->streamReadCsv(";");
            $stats = $flag->streamStat();

            if ($line[0] == $this::_SUCCEED) {
                $line[0] = $this->checkCronTasks($line[0], $row, $stats["mtime"]);
            }

            switch ($line[0]) {
                case $this::_SUCCEED:
                    $severity = 'notice';
                    $status = Mage::helper('datafeedmanager')->__($line[0]);
                    break;
                case $this::_PENDING:
                    $severity = 'minor';
                    $status = Mage::helper('datafeedmanager')->__($line[0]);
                    break;
                case $this::_COLLECTING:
                    $percent = $line[2];
                    $severity = 'minor';
                    $status = Mage::helper('datafeedmanager')->__($line[0]) . " [" . $percent . "%]";
                    break;
                case $this::_PROCESSING:
                    $percent = $line[2];
                    $severity = 'minor';
                    $status = Mage::helper('datafeedmanager')->__($line[0]) . " [" . $percent . "%]";
                    break;
                case $this::_HOLD:
                    $severity = 'major';
                    $status = Mage::helper('datafeedmanager')->__($line[0]);
                    break;
                case $this::_FAILED:
                    $severity = 'critical';
                    $status = Mage::helper('datafeedmanager')->__($line[0]);
                    break;
                default :
                    $severity = 'critical';
                    $status = Mage::helper('datafeedmanager')->__("ERROR");
                    break;
            }
        } else {
            $severity = 'minor';
            $line[1] = "no message";
            $status = Mage::helper('datafeedmanager')->__($this::_PENDING);
        }
        $script = "<script language='javascript' type='text/javascript'>var updater_url='"
                . $this->getUrl('/configurations/updater') . "'</script>";

        return $script . "<span title=\"" . htmlentities($line[1]) . "\" class='grid-severity-$severity updater' cron='" . $row->getCronExpr()
                . "' id='feed_" . $row->getId() . "'><span>" . ($status) . "</span></span>";
    }

    protected function getStatus($status, $updatedAt, $taskTime) {
        if (Mage::getSingleton('core/date')->gmtTimestamp() > $updatedAt + ($taskTime * 10)) {
            $status = 'FAILED';
        } elseif (Mage::getSingleton('core/date')->gmtTimestamp() > $updatedAt + ($taskTime * 2)) {
            $status = 'HOLD';
        }

        return $status;
    }

    protected function checkCronTasks($status, Varien_Object $row, $mtime) {
        $cron = array();
        $cron['current']['localTime'] = Mage::getSingleton('core/date')->timestamp();
        $cron['file']['localTime'] = Mage::getSingleton('core/date')->timestamp($mtime);
        $cronExpr = json_decode($row->getCronExpr());
        $i = 0;
        if ($cronExpr != null) {
            foreach ($cronExpr->days as $day) {
                foreach ($cronExpr->hours as $hour) {
                    $time = explode(':', $hour);

                    if (Mage::getSingleton('core/date')->date('l') == $day) {
                        $cron['tasks'][$i]['localTime'] = strtotime(Mage::getSingleton('core/date')->date('Y-m-d')) + ($time[0] * 60 * 60) + ($time[1] * 60);
                    } else {
                        $cron['tasks'][$i]['localTime'] = strtotime("last " . $day, $cron['current']['localTime']) + ($time[0] * 60 * 60) + ($time[1] * 60);
                    }

                    if ($cron['tasks'][$i]['localTime'] >= $cron['file']['localTime'] && $cron['tasks'][$i]['localTime'] <= $cron['current']['localTime']
                    ) {
                        $status = $this::_PENDING;
                        continue 2;
                    }
                    $i++;
                }
            }
        }

        return $status;
    }
}