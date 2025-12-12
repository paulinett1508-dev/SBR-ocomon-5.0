<?php

/*                        Copyright 2023 Flávio Ribeiro

This file is part of OCOMON.

OCOMON is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

OCOMON is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Foobar; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * Checa se o usuário está logado, e se tem o nível necessário para acessar o script.
 * @param int $logged: se o usuário está autenticado
 * @param int $userLevel: nível de acesso do usuário
 * @param int $scriptLevel: nível de acesso necessário para acessar o script
 * @param int $pageModule: se a página é do módulo de ocorrências (1) ou de inventário(2)
 */
class AuthNew
{
    public $output;
	public $text;
    
    public function __construct($logged, $userLevel, $scriptLevel, $pageModule = '')
    {
        if (!isset($logged) || $logged == 0) {
            $_SESSION['session_expired'] = 1;
            echo "<script>top.window.location = '../../index.php'</script>";
            return;
        }

        if ($userLevel > $scriptLevel) 
        {
            header("Location: ./module_not_allowed.php");
            return;
            // header("Location: ../../index.php");
            // return;
        }

        if (!empty($pageModule)) {
            /* modulo 1: ocorrencias - modulo 2: inventario */
            if (!isIn($pageModule, $_SESSION['s_permissoes'])) {
                header("Location: ./module_not_allowed.php");
                return;
            }
        }
    }


	public function showHeader($help = '')
    {

        // return;
        
        if ($help != '') {
            $help = "&nbsp;<a onClick=\"return popupS('" . HELP_PATH . "" . $help . "')\"><i class='fas fa-question-circle text-success'></i></a>";
        }

        $this->text = TRANS('MENU_TTL_MOD_OCCO');
        if (is_file("./.invmon_dir")) {
            $this->text = TRANS('MENU_TTL_MOD_INV');
        } elseif (is_file("./.admin_dir")) {
            $this->text = TRANS('MENU_TTL_MOD_ADMIN');
        }

        $this->output = "<div class='container-fluid '>"; /* bg-light */
            $this->output .= "<div class='row border-bottom  border-light' style='border-width: 4px !important; '>"; //rounded
                $this->output .= "<div class='col-sm-4 small text-nowrap text-sm-left text-secondary font-weight-bold'>"; /* text-muted */
                    $this->output .= $this->text;
                    // $this->output .= $this->text . " <span class='badge badge-danger'>NOVA CLASSE</span>";
                $this->output .= "</div>";
                $this->output .= "<div class='col-4'>";
                $this->output .= "</div>";
                $this->output .= "<div class='col-sm-4 small text-nowrap text-md-right text-sm-left text-secondary font-weight-bold'>"; /* text-muted */
                    // $this->output .= TRANS(date("l")) . ",&nbsp;" . (dateScreen(date("Y/m/d H:i:s"))) . "</b>" . $help;
                    $this->output .= TRANS(date("l")) . ",&nbsp;" . (dateScreen(date("Y/m/d H:i:s"))) . "</b>";
                $this->output .= "</div>";

            $this->output .= "</div>";
        $this->output .= "</div>";
        
        echo $this->output;

        return;
        
    }
}