<?php

namespace OcomonApi\Controllers;

use OcomonApi\Core\OcomonApi;
use OcomonApi\Models\InputTag;

class InputTags extends OcomonApi
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * List Stages data
     */
    public function index(): void
    {
        $inputTags = (new InputTag())->find()->fetch(true);

        /** @var InputTag $inputTag */
        foreach ($inputTags as $inputTag) {
            $response[]['inputTag'] = $inputTag->data();
        }
        $this->back($response);
        return;
    }


    /**
     * Cadastra uma nova tag caso ainda não exista.
     * 
    * @param string $tagName
    */
    public function create(string $tagName): bool
    {

        // $tagName = filter_var($tagName, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $tagName = filter_var($tagName, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        // if (strlen(trim($tagName) < 4 )) {
        //     $this->call(
        //         400,
        //         "invalid_data",
        //         "Os rótulos precisam ter no mínimo 4 caracteres"
        //     )->back();
        //     return false;
        // }
        
        $find = (new InputTag())->findByTagName($tagName);

        if (!$find) {
            $newTag = new InputTag();
            $newTag->tag_name = $tagName;

            if (!$newTag->save()) {
                // echo "Problema na tentativa de gravar o registro";
                return false;
            }
        }

        return true;
    }
}