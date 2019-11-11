<?php
/**
 * Copyright © 2018 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
class Wyomind_Datafeedmanager_DatafeedmanagerController extends Mage_Core_Controller_Front_Action
{
    public function generateAction()
    {
        // http://www.example.com/index.php/datafeedmanager/datafeedmanager/generate/id/{data_feed_id}/ak/{YOUR_ACTIVATION_KEY}
        
        $id = $this->getRequest()->getParam('id');
        $ak = $this->getRequest()->getParam('ak');
        
        $activationKey = Mage::getStoreConfig('datafeedmanager/license/activation_key');
        
        if ($activationKey == $ak) {
            $datafeedmanager = Mage::getModel('datafeedmanager/configurations');
            $datafeedmanager->setId($id);
            if ($datafeedmanager->load($id)) {
                try {
                    $datafeedmanager->generateFile();
                    $this->getResponse()->setBody(
                        Mage::helper('datafeedmanager')->__(
                            'The data feed "%s" has been generated.', $datafeedmanager->getFeedName()
                        )
                    );
                } catch (Mage_Core_Exception $e) {
                    $this->getResponse()->setBody($e->getMessage());
                } catch (Exception $e) {
                    $this->getResponse()->setBody($e->getMessage());
                }
            } else {
                $this->getResponse()->setBody(
                    Mage::helper('datafeedmanager')->__('Unable to find a data feed to generate.')
                );
            }
        } else {
            $this->getResponse()->setBody('Invalid activation key');
        }
    }
}
