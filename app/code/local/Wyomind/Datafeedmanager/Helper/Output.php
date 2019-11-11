<?php
/**
 * Copyright © 2018 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
class Wyomind_Datafeedmanager_Helper_Output extends Mage_Core_Helper_Data
{
    /**
     * Convert json to html
     * 
     * @param string $pattern
     * @param string $header
     * @return string
     */
    public function jsonToTable($pattern, $header = false)
    {
        $pattern = preg_replace(array('/(\r\n|\n|\r|\r\n)/s', '/[\x00-\x1f]/'), '', $pattern);
        $styleTd = 'padding:2px; border:1px solid grey; text-align:center;padding:5px; min-width:10px;min-height:10px;';

        $data = json_decode($pattern);

        if (!is_array($data)) {
            $d[] = $data;
        } else {
            $d = $data;
        }

        $tr = null;

        foreach ($d as $data) {
            $br = 0;
            
            if (isset($data->header)) {
                $data = $data->header;
            } else {
                $data = $data->product;
            }
            
            if ($header) {
                $tr = "<tr style='background-color:grey; color:white; font-weight:bold'>";
            } else {
                $tr .= "<tr>";
            }

            foreach ($data as $key => $value) {
                $value = (($value));
                if ($br) {
                    $br++;
                }
                
                if (strstr($value, "/breakline/")) {
                    $value = str_replace("/breakline/", "</tr><tr>", $value);
                    $br = 1;
                }
                
                $v = ($value != null) ? ($value) : "<span style='font-size:10px;color:grey'>(empty)</span>";
                $tr .= "<td style='" . $styleTd . "'>" . $v . "</td>";
            }
            $tr .= "</tr>";
        }

        return $tr;
    }
    
    /**
     * Convert json to a csv sting
     * 
     * @param string  $jsonPattern
     * @param string  $delimiter
     * @param string  $enclosure
     * @param string $escaper
     * @return string
     */
    public function jsonToStr($jsonPattern, $delimiter, $enclosure, $escaper)
    {
        $pattern = preg_replace(array('/(\r\n|\n|\r|\r\n)/s', '/[\x00-\x1f]/'), '', $jsonPattern);
        $data = json_decode($pattern);
        if (!is_array($data)) {
            $d[] = $data;
        } else {
            $d = $data;
        }
        
        $line = '';
        if ($delimiter == '\t') {
            $delimiter = "\t";
        }

        foreach ($d as $data) {
            $br = 0;
            if (isset($data->header)) {
                $data = $data->header;
            } else {
                if (!json_decode($pattern)) {
                    return "";
                }
                $data = $data->product;
            }

            $u = 0;
            foreach ($data as $key => $value) {
                if ($br > 0) {
                    $br = 2;
                }
                
                if (strstr($value, "/breakline/")) {
                    $br++;
                }

                if ($u > 0 && $br < 2) {
                    $line .= $delimiter;
                }

                if (!strstr($value, "/breakline/")) {
                    $br = 0;
                }

                if ($enclosure != "") {
                    $line .= $enclosure . $this->escapeStr(str_replace("/breakline/", '', $value), $enclosure, $escaper)
                            . $enclosure;
                    if (strstr($value, "/breakline/")) {
                        $line .= "\r\n";
                    }
                } else {
                    $value = str_replace("/breakline/", "\r\n", $value);
                    $line .= $this->escapeStr($value, $delimiter, $escaper);
                }

                $u++;
            }

            if ($delimiter == "[|]") {
                $line.="[:]";
            }
            
            if (!$br) {
                $line .= "\r\n";
            }
        }
        
        return $line;
    }
    
    /**
     * Escape a string with a given character
     * 
     * @param string $pattern
     * @param string $escapedChar
     * @param string $escaper
     * @return string
     */
    public function escapeStr($pattern, $escapedChar = '"', $escaper = "\\")
    {
        $escapeString = str_replace($escapedChar, $escaper . $escapedChar, $pattern);
        
        return $escapeString;
    }
    
    /**
     * Render a xml string with CDATA
     * @param string  $productPattern
     * @param boolean $enclose
     * @param boolean $clean
     * @return string
     */
    public function xmlEncloseData($productPattern, $enclose = true, $clean = true)
    {
        $pattern = '/(<[^>^\/]+>)([^<]*)(<\/[^>]+>)/s';
        preg_match_all($pattern, $productPattern, $matches);

        foreach ($matches[1] as $key => $value) {
            $tagContent = trim($matches[2][$key]);
            if (empty($tagContent) && !is_numeric($tagContent) && $clean) {
                $productPattern = str_replace($matches[0][$key], '', $productPattern);
            } else {
                if ($enclose) {
                    $productPattern = str_replace(
                        $matches[0][$key], 
                        ($matches[1][$key]) . '<![CDATA[' . $tagContent . ']]>' . ($matches[3][$key]),
                        $productPattern
                    );
                } else {
                    $productPattern = str_replace(
                        $matches[0][$key], 
                        ($matches[1][$key]) . $tagContent . ($matches[3][$key]), 
                        $productPattern
                    );
                }
            }
        }
        
        $a = preg_split("/\n/s", $productPattern);
        $o = '';
        
        foreach ($a as $line) {
            (strlen(trim($line)) > 0) ? $o .= $line . "\n" : false;
        }
        
        $productPattern = $o;

        return $productPattern;
    }

    /**
     * Encode string in a given charset
     * 
     * @param string $var
     * @param string $encoding
     * @return string
     */
    public function encode($var, $encoding)
    {
        if ($encoding != 'UTF-8') {
            $var = htmlentities($var, ENT_NOQUOTES, 'UTF-8');
            $var = html_entity_decode($var, ENT_NOQUOTES, $encoding);
        }
        
        return $var;
    }
}