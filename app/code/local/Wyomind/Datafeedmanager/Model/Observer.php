<?php
/**
 * Copyright © 2018 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
class Wyomind_Datafeedmanager_Model_Observer
{
    public function scheduledGenerateFeeds()
    {
        $errors = array();
        $log = array();
        $log[] = "-------------------- CRON PROCESS --------------------";

        $collection = Mage::getModel('datafeedmanager/configurations')->getCollection();
        $cnt = 0;

        foreach ($collection as $feed) {
            try {
                $log[] = "--> Running profile : " . $feed->getFeedName() . ' [#' . $feed->getFeedId() . '] <--';

                $cron['current']['localDate'] = Mage::getSingleton('core/date')->date('l Y-m-d H:i:s');
                $cron['current']['gmtDate'] = Mage::getSingleton('core/date')->gmtDate('l Y-m-d H:i:s');
                $cron['current']['localTime'] = Mage::getSingleton('core/date')->timestamp();
                $cron['current']['gmtTime'] = Mage::getSingleton('core/date')->gmtTimestamp();

                $cron['file']['localDate'] = Mage::getSingleton('core/date')
                                                ->date('l Y-m-d H:i:s', $feed->getFeedUpdatedAt());
                $cron['file']['gmtDate'] = $feed->getFeedUpdatedAt();
                $cron['file']['localTime'] = Mage::getSingleton('core/date')->timestamp($feed->getFeedUpdatedAt());
                $cron['file']['gmtTime'] = strtotime($feed->getFeedUpdatedAt());

                /**
                 * Magento getGmtOffset() is bugged and doesn't include daylight saving time, 
                 * the following workaround is used 
                 */
                // date_default_timezone_set(Mage::app()->getStore()->getConfig('general/locale/timezone'));
                // $date = new DateTime();
                //$cron['offset'] = $date->getOffset() / 3600;
                $cron['offset'] = Mage::getSingleton('core/date')->getGmtOffset("hours");

                $log[] = '   * Last update : ' . $cron['file']['gmtDate'] . " GMT / " 
                        . $cron['file']['localDate'] . ' GMT' . $cron['offset'];
                $log[] = '   * Current date : ' . $cron['current']['gmtDate'] . " GMT / "
                        . $cron['current']['localDate'] . ' GMT' . $cron['offset'];

                $cronExpr = json_decode($feed->getCronExpr());
                $i = 0;
                $done = false;

                foreach ($cronExpr->days as $d) {
                    foreach ($cronExpr->hours as $h) {
                        $time = explode(':', $h);
                        if (date('l', $cron['current']['gmtTime']) == $d) {
                            $cron['tasks'][$i]['localTime'] = strtotime(Mage::getSingleton('core/date')->date('Y-m-d')) + ($time[0] * 60 * 60) + ($time[1] * 60);
                            $cron['tasks'][$i]['localDate'] = date('l Y-m-d H:i:s', $cron['tasks'][$i]['localTime']);
                        } else {
                            $cron['tasks'][$i]['localTime'] = strtotime("last " . $d, $cron['current']['localTime']) + ($time[0] * 60 * 60) + ($time[1] * 60);
                            $cron['tasks'][$i]['localDate'] = date('l Y-m-d H:i:s', $cron['tasks'][$i]['localTime']);
                        }

                        if ($cron['tasks'][$i]['localTime'] >= $cron['file']['localTime'] 
                                && $cron['tasks'][$i]['localTime'] <= $cron['current']['localTime'] && $done != true
                        ) {
                            $log[] = '   * Scheduled : ' . ($cron['tasks'][$i]['localDate'] . " GMT" . $cron['offset']);

                            if ($feed->generateFile()) {
                                $done = true;
                                $cnt++;
                                $log[] = '   * EXECUTED!';
                            }
                        }

                        $i++;
                    }
                }
            } catch (Exception $e) {
                $log[] = '   * ERROR! ' . ($e->getMessage());
            }
            
            if (!$done) {
                $log[] = '   * SKIPPED!';
            }
        }

        if (Mage::getStoreConfig('datafeedmanager/setting/enable_report')) {
            foreach (explode(',', Mage::getStoreConfig('datafeedmanager/setting/emails')) as $email) {
                try {
                    if ($cnt) {
                        // start test code
                        $mail = Mage::getSingleton('core/email');
                        $mail->setToEmail($email);
                        $mail->setSubject(Mage::getStoreConfig('datafeedmanager/setting/report_title'));
                        $mail->setBody("\n" . implode($log, "\n"));
                        $mail->setFromEmail(Mage::getStoreConfig('trans_email/ident_general/email'));
                        $mail->setFromName(Mage::getStoreConfig('trans_email/ident_general/name'));
                        $mail->send();
                    }
                } catch (Exception $e) {
                    $log[] = '   * EMAIL ERROR! ' . ($e->getMessage());
                }
            }
        }
        
        if (Mage::getStoreConfig('datafeedmanager/system/log_enabled')) {
            Mage::log("\n" . implode($log, "\n"), null, 'DataFeedManager-cron.log');
        }
    }
}