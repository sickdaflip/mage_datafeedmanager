<?php

/**
 * @category    Wyomind
 * @package     Wyomind_SimpleGoogleShopping
 * @version     9.5.0
 * @copyright   Copyright (c) 2016 Wyomind (https://www.wyomind.com/)
 */
require_once 'abstract.php';

class Wyomind_Datafeedmanager_Shell extends Mage_Shell_Abstract
{
    public function run()
    {
        try {
            $this->_validate();

            if ($this->getArg('generate')) {
                if (is_string($this->getArg('generate'))) {
                    $this->_generate($this->getArg('generate'));
                } else {
                    $this->_generateAll();
                }
            } elseif ($this->getArg('list')) {
                $this->_list();
            } else {
                $this->_echo($this->usageHelp());
            }
        } catch (Exception $e) {
            $this->_echo('');
            Mage::logException($e);
            $this->_fault($e->getMessage());
        }
    }

    protected function _generateAll()
    {
        $this->_generate();
    }

    protected function _generate($ids = null)
    {
        $collection = Mage::getModel('datafeedmanager/configurations')->getCollection()->addFieldToFilter('feed_status', 1);
        if ($ids != null) {
            $ids = explode(",", $ids);
            $collection->addFieldToFilter("feed_id", array("in", $ids));
        }

        if (count($collection) == 0) {
            $this->_fault("No data feed found!");
        }

        foreach ($collection as $feed) {
            $this->_echo("\n\033[1mProcessing data feed #" . $feed->getFeedId() . " : " . $feed->getFeedName() . "\033[0m\n\n");
            $feed->generateFile();
            $this->_echo("\n\n");
        }
    }

    protected function _list()
    {
        $collection = Mage::getModel('datafeedmanager/configurations')->getCollection()->addFieldToFilter('feed_status', 1);
        $this->_echo('');
        $row = sprintf(" %-6s | %-45s | %-22s", "#", "File", "Last Update");
        $this->_echo($row);
        $this->_echo("--------+-----------------------------------------------+---------------------");
        foreach ($collection as $feed) {
            $row = sprintf(
                    " %-6d | %-45s | %-22s", $feed->getFeedId(), $feed->getFeedPath() . $feed->getFeedName(), $feed->getFeedUpdatedAt()
            );
            $this->_echo($row);
            $this->_echo("--------+-----------------------------------------------+---------------------");
        }
    }

    protected function _fault($str)
    {
        $this->_echo($str);
        exit;
    }

    protected function _echo($str)
    {
        fwrite(STDOUT, $str . PHP_EOL);
        return $this;
    }

    protected function _validate()
    {
        if (!Mage::isInstalled()) {
            exit('Please install magento before running this script.');
        }

        if (!Mage::helper('core')->isDevAllowed()) {
            exit('You are not allowed to run this script.');
        }

        if (!Mage::helper('core')->isModuleEnabled('Wyomind_Datafeedmanager')) {
            exit('Please enable Woymind_Datafeedmanager module before running this script.');
        }

        return true;
    }

    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f shell/wyomind_datafeedmanager.php -- [options]

  -h                            Short alias for help
  --generate                    Generate all data feeds
  --generate id1,id2,...,idN    Generate data feeds from the given list
  --list                        List all data feeds
  help                          This help

USAGE;
    }

}

$shell = new Wyomind_Datafeedmanager_Shell();
$shell->run();
