<?php

/**
 * Copyright © 2018 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
class Wyomind_Datafeedmanager_Adminhtml_ConfigurationsController extends Mage_Adminhtml_Controller_Action
{
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('catalog/datafeedmanager')
            ->_addBreadcrumb($this->__('Data feed Manager'), ('Data feed Manager'));

        return $this;
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('catalog/datafeedmanager/datafeedmanagerconfigurations');
    }

    public function indexAction()
    {
        $this->_initAction()->renderLayout();
    }

    public function editAction()
    {
        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('datafeedmanager/configurations')->load($id);

        if ($model->getId() || $id == 0) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
            if (!empty($data)) {
                $model->setData($data);
            }

            Mage::register('datafeedmanager_data', $model);

            $this->loadLayout();
            $this->_setActiveMenu('catalog/datafeedmanager')->_addBreadcrumb(
                Mage::helper('datafeedmanager')->__('Data Feed Manager'), ('Data Feed Manager')
            );
            $this->_addBreadcrumb(Mage::helper('datafeedmanager')->__('Data Feed Manager'), ('Data Feed Manager'));

            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

            $this->_addContent(
                $this->getLayout()
                    ->createBlock('datafeedmanager/adminhtml_configurations_edit')
            )
                ->_addLeft($this->getLayout()->createBlock('datafeedmanager/adminhtml_configurations_edit_tabs'));

            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('datafeedmanager')->__('Item does not exist')
            );
            $this->_redirect('*/*/');
        }
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function saveAction()
    {
        if ($this->getRequest()->getPost()) {
            $data = $this->getRequest()->getPost();
            $model = Mage::getModel('datafeedmanager/configurations');

            if ($this->getRequest()->getParam('id')) {
                $model->load($this->getRequest()->getParam('id'));
            }

            $model->setData($data);

            try {
                $model->save();

                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('datafeedmanager')->__('The data feed configuration has been saved.')
                );
                Mage::getSingleton('adminhtml/session')->setFormData(false);

                if ($this->getRequest()->getParam('continue')) {
                    $this->getRequest()->setParam('id', $model->getId());
                    $this->_forward('edit');
                    return;
                }

                if ($this->getRequest()->getParam('generate')) {
                    $this->getRequest()->setParam('feed_id', $model->getId());
                    $this->_forward('generate');
                    return;
                }

                $this->_redirect('*/*/');
                return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        $this->_redirect('*/*/');
    }

    /**
     * Delete action
     */
    public function deleteAction()
    {
        if ($this->getRequest()->getParam('id')) {
            $id = $this->getRequest()->getParam('id');
            try {
                $model = Mage::getModel('datafeedmanager/configurations');
                $model->setId($id);
                $model->load($id);

                $io = new Varien_Io_File();
                $fileName = $model->getPreparedFilename();
                $filePath = $io->getCleanPath(BP . DS . $fileName);

                if ($io->fileExists($filePath)) {
                    $io->rm($filePath);
                }

                $model->delete();

                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('datafeedmanager')->__('The data feed configuration has been deleted.')
                );

                $this->_redirect('*/*/');
                return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/');
                return;
            }
        }

        Mage::getSingleton('adminhtml/session')->addError(
            Mage::helper('datafeedmanager')->__('Unable to find the data feed configuration to delete.')
        );

        $this->_redirect('*/*/');
    }

    /**
     * Delete several templates
     */
    public function massDeleteAction()
    {
        $ids = $this->getRequest()->getParam('datafeedmanager_massaction');
        if ($ids) {
            $model = Mage::getModel('datafeedmanager/configurations');

            try {
                foreach ($ids as $id) {
                    $model->setId($id);
                    $model->load($id);

                    $io = new Varien_Io_File();
                    $fileName = $model->getPreparedFilename();
                    $filePath = $io->getCleanPath(BP . DS . $fileName);

                    if ($io->fileExists($filePath)) {
                        $io->rm($filePath);
                    }

                    $model->delete();
                }

                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('datafeedmanager')->__('%s feed(s) successfully deleted', count($ids))
                );

            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/');
    }

    public function importAction()
    {
        if ($this->getRequest()->getPost()) {
            $file = $_FILES['file'];
            $explodedFileName = explode('.', $file['name']);
            $fileType = array_pop($explodedFileName);

            if (strtolower($fileType) != 'dfm') {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('datafeedmanager')->__('Wrong file type (' . $file['type'] . ').')
                );
            } else {
                // récuperer le contenu
                $filename = $file['tmp_name'];
                $info = pathinfo($filename);
                $fileSize = $file['size'];

                $io = new Varien_Io_File();
                $io->open(array('path' => $info['dirname']));
                $io->streamOpen($filename, 'r');
                $fileContent = $io->streamRead($fileSize);

                if (Mage::getStoreConfig('datafeedmanager/system/trans_domain_export')) {
                    $key = 'dfm-empty-key';
                } else {
                    $key = Mage::getStoreConfig('datafeedmanager/license/activation_code');
                }

                $template = rtrim(
                    mcrypt_decrypt(
                        MCRYPT_RIJNDAEL_256,
                        md5($key),
                        base64_decode($fileContent),
                        MCRYPT_MODE_CBC, md5(md5($key))
                    ), "\0"
                );

                $io->streamClose();

                Mage::getResourceModel('datafeedmanager/datafeedmanager')->importConfiguration($template);
            }
        }

        $this->loadLayout();
        $this->_setActiveMenu('datafeedmanager/configurations');

        $this->_addContent($this->getLayout()->createBlock('datafeedmanager/adminhtml_import'))
            ->_addLeft($this->getLayout()->createBlock('datafeedmanager/adminhtml_import_edit_tabs'));

        $this->renderLayout();
    }

    public function exportAction()
    {
        $id = $this->getRequest()->getParam('feed_id');
        $feed = Mage::getModel('datafeedmanager/configurations')->load($id);

        $this->getResponse()
            ->setHttpResponseCode(200)
            ->setHeader('Pragma', 'public', true)
            ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
            ->setHeader('Content-type', 'application/force-download')
            ->setHeader('Content-Disposition', 'inline' . '; filename=' . $feed->getFeedName() . ".dfm");
        $this->getResponse()->clearBody();
        $this->getResponse()->sendHeaders();

        foreach ($feed->getData() as $field => $value) {
            $fields[] = $field;
            if ($field == 'feed_id') {
                $values[] = 'NULL';
            } else {
                $values[] = "'" . str_replace(array("'", "\\"), array("\'", "\\\\"), $value) . "'";
            }
        }
        $sql = "INSERT INTO {{datafeedmanager_configurations}}(" . implode(',', $fields) . ") "
            . "VALUES (" . implode(',', $values) . ");";
        if (Mage::getStoreConfig('datafeedmanager/system/trans_domain_export')) {
            $key = "dfm-empty-key";
        } else {
            $key = Mage::getStoreConfig('datafeedmanager/license/activation_code');
        }
        $sql = utf8_encode($sql);

        $body = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $sql, MCRYPT_MODE_CBC, md5(md5($key))));

        $this->getResponse()->setBody($body);
    }

    public function sampleAction()
    {
        try {
            $this->loadLayout()->renderLayout();
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            $this->_forward('index');
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            $this->_getSession()->addException(
                $e, Mage::helper('datafeedmanager')->__('Unable to generate the data feed.')
            );
            $this->_forward('index');
        }
    }

    public function generateAction()
    {
        try {
            // init and load datafeedmanager model
            $id = $this->getRequest()->getParam('feed_id');

            $datafeedmanager = Mage::getModel('datafeedmanager/configurations');
            $datafeedmanager->setId($id);
            $limit = $this->getRequest()->getParam('limit');
            $datafeedmanager->limit = $limit;

            // if datafeedmanager record exists
            if ($datafeedmanager->load($id)) {
                $timeStart = Mage::getSingleton('core/date')->gmtTimestamp();
                $datafeedmanager->generateFile();
                $timeEnd = Mage::getSingleton('core/date')->gmtTimestamp();
                $time = $timeEnd - $timeStart;

                if ($time < 60) {
                    $time = ceil($time) . ' sec. ';
                } else {
                    $time = floor($time / 60) . ' min. ' . ($time % 60) . ' sec.';
                }

                $unit = array('b', 'Kb', 'Mb', 'Gb', 'Tb', 'Pb');

                $memory = 0;
                $exponential = pow(1024, ($i = floor(log(memory_get_usage(), 1024))));

                if ($exponential !== 0) {
                    $memory = round(memory_get_usage() / $exponential, 2);
                }

                $memory .= ' ' . $unit[$i];

                $fileName = preg_replace('/^\//', '', $datafeedmanager->getFeed_path() . $datafeedmanager->_filename);
                $types = array(1 => 'xml', 2 => 'txt', 3 => 'csv', 4 => 'tsv');
                $ext = $types[$datafeedmanager->getFeed_type()];

                $url = (Mage::app()->getStore($datafeedmanager->getStoreId())
                        ->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . $fileName);
                $report = "
                    <table>
                    <tr><td align='right' width='150'>Processing time &#8614; </td><td>$time</td></tr>
                    <tr><td align='right'>Memory usage &#8614; </td><td>$memory</td></tr>
                    <tr><td align='right'>Product inserted &#8614; </td><td>$datafeedmanager->_inc</td></tr>
                    <tr>
                    <td align='right'>Generated file &#8614; </td>
                    <td><a href='$url' target='_blank'>$url</a></td>
                    </tr>
                    </table>";

                $this->_getSession()->addSuccess(
                    Mage::helper('datafeedmanager')->__(
                        'The data feed "%s" has been generated.', $datafeedmanager->getFeedName() . '.' . $ext
                    )
                );

                $this->_getSession()->addSuccess($report);

                if ($this->getRequest()->getParam('generate')) {
                    $this->_forward('edit', null, null, array('id' => $id));
                } else {
                    $this->_forward('index');
                }
            } else {
                $this->_getSession()->addError(
                    Mage::helper('datafeedmanager')->__('Unable to find a data feed to generate.')
                );
            }
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            $this->_getSession()->addException(
                $e, Mage::helper('datafeedmanager')->__('Unable to generate the data feed.')
            );
        }
    }

    /**
     * Enable/disable feeds
     */
    public function massChangeStatusAction()
    {
        $ids = $this->getRequest()->getParam('datafeedmanager_massaction');
        $status = $this->getRequest()->getParam('status');

        if ($ids && in_array($status, array(0, 1))) {
            $model = Mage::getModel('datafeedmanager/configurations');

            try {
                foreach ($ids as $id) {
                    $model->load($id);

                    if (!$model->getId()) {
                        continue;
                    }

                    $model->setFeedStatus($status);
                    $model->save();
                }

                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('datafeedmanager')->__('%s feed(s) successfully updated', count($ids))
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/');
    }

    public function ftpAction()
    {
        $this->loadLayout()->renderLayout();
    }

    public function categoriesAction()
    {
        $this->loadLayout()->renderLayout();
    }

    public function libraryAction()
    {
        $this->loadLayout()->renderLayout();
    }

    public function updaterAction()
    {
        $this->loadLayout()->renderLayout();
    }
}