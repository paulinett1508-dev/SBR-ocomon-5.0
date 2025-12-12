<?php

namespace OcomonApi\WebControllers;

use OcomonApi\Core\OcomonWeb;
use OcomonApi\Models\FormField;

class FormFields extends OcomonWeb
{

    private ?string $entity;

    private ?string $action;
    
    public function __construct(?string $entity = null, ?string $action = null)
    {
        parent::__construct();

        $this->entity = $entity;
        $this->action = $action;
    }

    // public static function getInstance(string $entity, string $action): static
    public static function getInstance(string $entity, string $action)
    {
        $new = new static($entity, $action);
        return $new;
    }

    public function listAll(): Object
    {
        $formFields = (new FormField())->find()->fetch(true);

        $return = [];

        /** @var FormField $formField */
        foreach ($formFields as $formField) {

            $return[] = $formField->data();
        }

        $return = (Object)$return;
        return $return;
    }


    public function listEntity(): ?Object
    {
        $formFields = (new FormField())->findByEntity($this->entity)->fetch(true);

        if (!$formFields) {
            return null;
        }

        $return = [];

        /** @var FormField $formField */
        foreach ($formFields as $formField) {

            $return[] = $formField->data();
        }

        $return = (Object)$return;
        return $return;
    }



    /**
     * @param string $field
     * @param string $entity
     * @param string $action
     * @return null|bool
     */
    public function isRequired(string $field, ?string $entity = null, ?string $action = null): ?bool
    {
        if (!$action) {
            $action = $this->action;
        }

        $entity = ($entity ? $entity : ($this->entity ? $this->entity : null));
        $action = ($action ? $action : ($this->action ? $this->action : null));

        if (empty($entity) || empty($action)) {
            return null;
        }
        
        $actions = array("new", "edit", "close");
        if (!in_array($action, $actions)) {
            echo "Invalid action";
            return null;
        }

        $formField = (new FormField())->findByField($entity, $field, $action);
        if ($formField) {
            if ($formField->not_empty)
                return true;
            return false;
        }
        return false;
    }


    /**
     * save
     *
     * @param array $data
     * 
     * @return bool
     */
    public function save(array $data): bool
    {

        $data = filter_var_array($data, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $entity = (isset($data['entity']) ? $data['entity'] : "");
        $field = (isset($data['field']) ? $data['field'] : "");
        $action = (isset($data['action']) ? $data['action'] : "");

        $actions = array("new", "edit", "close");
        if (!in_array($action, $actions)) {
            echo "Invalid action";
            return false;
        }

        if (empty($entity) || empty($field) || empty ($action)) {
            echo "Dados incompletos";
            return false;
        }

        $not_empty = (isset($data['not_empty']) && !empty($data['not_empty']) ? 1 : 0);

        $find = (new FormField())->findByField($entity, $field, $action);

        if ($find) {
            $find->not_empty = $not_empty;
            if (!$find->save()) {
                echo "problemas em atualizar o registro";
                return false;
            }
            return true;
        }
            
        $formField = new FormField();
        
        $formField->entity_name = $entity;
        $formField->field_name = $field;
        $formField->action_name = $action;
        $formField->not_empty = $not_empty;

        // var_dump($formField);

        if (!$formField->save()) {
            echo "Problema na tentativa de gravar o registro";
            return false;
        }

        return true;
    }


    /**
     * delete
     *
     * @param array $data
     * 
     * @return bool
     */
    public function delete(array $data): bool
    {
        $data = filter_var_array($data, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $id = (isset($data['id']) ? $data['id'] : "");

        if ($id && filter_var($id, FILTER_VALIDATE_INT)) {

            $formField = (new FormField())->findById($id);
            
            if (!$formField) {
                // $this->message("O registro não existe");
                echo "O registro não existe";
                return false;
            }

            if (!$formField->destroy()){
                echo "problemas em remover o registro";
                return false;
            }

            return true;
        }

        $entity = (isset($data['entity']) ? $data['entity'] : "");
        $field = (isset($data['field']) ? $data['field'] : "");
        $action = (isset($data['action']) ? $data['action'] : "");

        $actions = array("new", "edit", "close");
        if (!in_array($action, $actions)) {
            echo "Invalid action";
            return false;
        }

        if (empty($entity) || empty($field) || empty ($action)) {
            echo "Dados incompletos";
            return false;
        }


        $find = (new FormField())->findByField($entity, $field, $action);

        if ($find) {
            if (!$find->destroy()) {
                echo "problemas em remover o registro";
                return false;
            }
            return true;
        }
        return false;
    }


}