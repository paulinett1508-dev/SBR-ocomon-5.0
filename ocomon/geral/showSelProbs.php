<?php /*Copyright 2023 Flávio Ribeiro

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
  */session_start();

exit; /* Descontinuado */

if (!isset($_SESSION['s_logado']) || $_SESSION['s_logado'] == 0) {
	$_SESSION['session_expired'] = 1;
    echo "<script>top.window.location = '../../index.php'</script>";
	exit;
}
require_once __DIR__ . "/" . "../../includes/include_basics_only.php";
require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";

use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 3, 1);

	/* A div "divProblema" deve ser utilizada para a exibição das categorias de tipos de problemas */
	
	if (isset($_GET['pathAdmin'])) {
		/* se o script estiver sendo chamado a partir do path do módulo de administração */
		print "<input type='hidden' name='pathAdmin' id='idPathAdmin' value='fromPathAdmin'>";
		print "<SELECT class='form-control' name='problema' id='idProblema' onChange=\"ajaxFunction('divProblema', '../../ocomon/geral/showProbs.php', 'idLoad', 'prob=idProblema', 'area_cod=idArea', 'pathAdmin=idPathAdmin');   \">";

		/* ajaxFunction('divInformacaoProblema', '../../ocomon/geral/showInformacaoProb.php', 'idLoad', 'prob=idProblema', 'area_cod=idArea'); */

		/* ajaxFunction('divSla', 'sla_standalone.php', 'idLoad', 'numero=idSlaNumero', 'popup=idSlaNumero', 'SCHEDULED=idScheduled', 'new_prob=idProblema'); */
	} else {
		print "<SELECT class='form-control' name='problema' id='idProblema' 
		onChange=\"
		ajaxFunction('divProblema', 'showProbs.php', 'idLoad', 'prob=idProblema', 'area_cod=idArea'); 
		ajaxFunction('divInformacaoProblema', 'showInformacaoProb.php', 'idLoad', 'prob=idProblema', 'area_cod=idArea'); 

		\">";
	}
	
	$query = "";

	/* Se o campo área está habilitado no perfil de tela de quem está abrindo o chamado */
	if($_GET['area_habilitada'] == 'sim'){
		if($_GET['area_cod']=="" || $_GET['area_cod']==-1){
			print "<option value='-1'>".TRANS('SEL_AREA')."</option>";
		} else {
			$query = "
                SELECT prob_id, problema 
                FROM problemas 
                WHERE 
                	prob_area = '".$_GET['area_cod']."' OR 
                	prob_area IS NULL OR 
                	prob_area = -1
                GROUP BY problema, prob_id  
                ORDER BY problema
				";
			$exec_prob = $conn->query($query);
			print "<option value='-1'>".TRANS('SEL_PROB')."</option>";
			foreach ($exec_prob->fetchAll(PDO::FETCH_ASSOC) as $row_prob) {
				print "<option value=".$row_prob['prob_id']."";
				if ($row_prob['prob_id'] == $_GET['prob']) {
					print " selected";
				}
				print " >".$row_prob['problema']." </option>";
			}
		}
	} elseif ($_GET['area_habilitada'] == 'needless_area'){ /* Utilizado quando chamado pelos roteiros de atendimento
		scripts_documentation pois não precisa considerar o perfil de tela de abertura para o usuário */
		
			$query = "
                SELECT prob_id, problema 
                FROM problemas 
                WHERE 
                	prob_area = '".$_GET['area_cod']."' OR 
                	prob_area IS NULL OR 
                	prob_area = -1
                GROUP BY prob_id, problema 
                ORDER BY problema
				";
			$exec_prob = $conn->query($query);
			print "<option value='-1'>".TRANS('SEL_PROB')."</option>";
			foreach ($exec_prob->fetchAll(PDO::FETCH_ASSOC) as $row_prob) {
				print "<option value=".$row_prob['prob_id']."";
				if ($row_prob['prob_id'] == $_GET['prob']) {
					print " selected";
				}
				print " >".$row_prob['problema']." </option>";
			}
		
	} else {
		/* Para os casos onde o campo área não está habilitado - Busca tipos de problemas da área destino definida no perfil de tela de abertura e também os tipos de problemas que não possuem vínculo com qualquer área específica */
		$terms = (isset($_GET['area_destino']) ? " WHERE prob_area = '" . $_GET['area_destino'] . "' OR prob_area = '-1' OR prob_area IS NULL" : "");

		$query = "
                SELECT prob_id, problema 
				FROM problemas 
				{$terms} 
                GROUP BY prob_id, problema 
                ORDER BY problema
                ";
		$exec_prob = $conn->query($query);
		print "<option value='-1'>".TRANS('SEL_PROB')."</option>";
		foreach ($exec_prob->fetchAll(PDO::FETCH_ASSOC) as $row_prob) {
			print "<option value=".$row_prob['prob_id']."";
				if ($row_prob['prob_id'] == $_GET['prob']) {
					print " selected";
				}
			print " >".$row_prob['problema']." </option>";
		}
	}
	print "</select>";

?>