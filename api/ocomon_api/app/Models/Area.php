<?php

namespace OcomonApi\Models;

use CoffeeCode\DataLayer\DataLayer;
use OcomonApi\Models\WorktimeProfile;

/**
 * OcoMon Api | Class Area | Active Record Pattern
 *
 * @author Flávio Ribeiro <flaviorib@gmail.com>
 * @package OcomonApi\Models
 */
class Area extends DataLayer
{
    /**
     * Area constructor.
     */
    public function __construct()
    {
        parent::__construct("sistemas", ["sistema", "sis_status"], "sis_id", false);
    }

    public function users()
    {
        return (new User())->find("AREA = :sis_id", "sis_id={$this->sis_id}")->fetch(true);
    }

    /**
     * Retorna o perfil de jornada associado
     */
    public function worktimeProfile(): ?WorktimeProfile
    {
        return (new WorktimeProfile())->findById($this->sis_wt_profile);
    }

    
}