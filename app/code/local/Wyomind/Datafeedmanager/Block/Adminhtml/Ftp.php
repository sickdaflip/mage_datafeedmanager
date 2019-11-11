<?php
/**
 * Copyright © 2018 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
class Wyomind_Datafeedmanager_Block_Adminhtml_Ftp extends Mage_Adminhtml_Block_Template
{
    public function _ToHtml()
    {
        $ftpHost = $this->getRequest()->getParam('ftp_host');
        $ftpLogin = $this->getRequest()->getParam('ftp_login');
        $ftpPassword = $this->getRequest()->getParam('ftp_password');
        $ftpDir = $this->getRequest()->getParam('ftp_dir');
        $useSftp = $this->getRequest()->getParam('use_sftp');
        $ftpActive = $this->getRequest()->getParam('ftp_active');

        if ($useSftp) {
            $ftp = new Varien_Io_Sftp();
        } else {
            $ftp = new Varien_Io_Ftp();
        }

        try {
            $ftp->open(
                array(
                    'host' => $ftpHost,
                    'user' => $ftpLogin, //ftp
                    'username' => $ftpLogin, //sftp
                    'password' => $ftpPassword,
                    'timeout' => '120',
                    'path' => $ftpDir,
                    'passive' => !($ftpActive)
                )
            );

            $ftp->write(null, null);
            $ftp->close();

            return 'Connection succeeded';
        } catch (Exception $e) {
            return Mage::helper('datafeedmanager')->__('Ftp error : ') . $e->getMessage();
        }
    }
}