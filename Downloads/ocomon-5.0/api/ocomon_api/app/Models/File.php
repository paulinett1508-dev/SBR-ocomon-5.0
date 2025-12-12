<?php

namespace OcomonApi\Models;

use CoffeeCode\DataLayer\DataLayer;

/**
 * OcoMon Api | Class xxx Active Record Pattern
 *
 * @author Flávio Ribeiro <flaviorib@gmail.com>
 * @package OcomonApi\Models
 */
class File extends DataLayer
{
    /**
     * User constructor.
     */
    public function __construct()
    {
        parent::__construct("imagens", ["img_nome", "img_tipo", "img_bin"], "img_cod", false);
    }

    public function findByTicket(int $ticket): ?array
    {
        if (empty($ticket)) {
            return null;
        }
        
        $find = $this->find("img_oco = :img_oco", "img_oco={$ticket}", "*");

        if ($find)
            return $find->fetch(true);

        return null;
    }


    public function isImage(): bool
    {
        return in_array($this->data()->img_tipo, ["image/jpeg", "image/png", "image/gif"]);
    }

    public function isPdf(): bool
    {
        return $this->data()->img_tipo === "application/pdf";
    }

    public function imageSize(): ?int
    {
        return $this->data()->img_size;
    }
   

    
}