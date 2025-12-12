<?php

namespace OcomonApi\Models;

use CoffeeCode\DataLayer\DataLayer;

/**
 * OcoMon Api | Class FormField Active Record Pattern
 *
 * @author Flávio Ribeiro <flaviorib@gmail.com>
 * @package OcomonApi\Models
 */
class FormField extends DataLayer
{

    /**
     * FormField constructor.
     */
    public function __construct()
    {
        parent::__construct("form_fields", ["entity_name", "field_name", "action_name"], "id", false);
    }


    /**
     * @param string $entity
     * @param string $field
     * @param string $action
     * @param string $columns
     * @return null|FormField
     */
    public function findByField(string $entity, string $field, string $action, string $columns = "*"): ?FormField
    {
        $find = $this->find("entity_name = :entity AND field_name = :field AND action_name = :action", "entity={$entity}&field={$field}&action={$action}", $columns);
        
        if (!$find)
            return null;
        
        return $find->fetch();
    }


    /**
     * findByEntity
     *
     * @param string $entity
     * @param string $columns
     * 
     * @return FormField|null
     */
    public function findByEntity(string $entity, string $columns = "*"): ?FormField
    {
        $find = $this->find("entity_name = :entity", "entity={$entity}", $columns);

        if (!$find)
            return null;
        return $find;
    }


    
}