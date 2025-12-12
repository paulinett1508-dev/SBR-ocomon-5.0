<?php

namespace OcomonApi\Models;

use CoffeeCode\DataLayer\DataLayer;

/**
 * OcoMon Api | Class InputTag | Active Record Pattern
 *
 * @author Flávio Ribeiro <flaviorib@gmail.com>
 * @package OcomonApi\Models
 */
class InputTag extends DataLayer
{
    /**
     * InputTag constructor.
     */
    public function __construct()
    {
        parent::__construct("input_tags", ["tag_name"], "id", false);
    }

    
    public function findByTagName(string $tagName, string $columns = "*"): ?InputTag
    {
        $find = $this->find("tag_name = :tag_name", "tag_name={$tagName}", $columns);
        return $find->fetch();
    }

}