<?php

namespace OcomonApi\Models;


/**
 * OcoMon Api | Class Helper | Active Record Pattern
 *
 * @author Flávio Ribeiro <flaviorib@gmail.com>
 * @package OcomonApi\Models
 */
class Helper
{
    /**
     * Helper constructor.
     */
    public function __construct()
    {
        //
    }

    
    /**
     * string_contains
     *
     * @param string $haystack
     * @param string $needle
     * 
     * @return bool
     */
    public function string_contains(string $haystack, string $needle): bool
    {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }


    
    
    
}
