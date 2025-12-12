<?php session_start();

/*      Copyright 2023 Flávio Ribeiro

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

if (!isset($_SESSION['s_logado']) || $_SESSION['s_logado'] == 0) {
    $_SESSION['session_expired'] = 1;
    echo "<script>top.window.location = '../../index.php'</script>";
    exit;
}

require_once __DIR__ . "/" . "../../includes/include_basics_only.php";
// require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";
use OcomonApi\WebControllers\FormFields;


// use includes\classes\ConnectPDO;

$entity = "ocorrencias";
$formfield = new FormFields($entity);

$post = $_POST;

$erro = false;
$exception = "";
$data = [];
$data['success'] = true;
$data['message'] = "";
$data['cod'] = (isset($post['cod']) ? intval($post['cod']) : "");
$data['numero'] = (isset($post['numero']) ? intval($post['numero']) : "");
$data['action'] = $post['action'];
$data['field_id'] = "";


/* Verificação de CSRF */
if (!csrf_verify($post)) {
    $data['success'] = false;
    $data['message'] = message('warning', 'Ooops!', TRANS('FORM_ALREADY_SENT'), '');
    echo json_encode($data);
    return false;
}


/* Cliente */
$data['client_new'] = (isset($post['client_new']) ? ($post['client_new'] == "yes" ? 1 : 0) : 0);
$data['client_edit'] = (isset($post['client_edit']) ? ($post['client_edit'] == "yes" ? 1 : 0) : 0);
$data['client_close'] = (isset($post['client_close']) ? ($post['client_close'] == "yes" ? 1 : 0) : 0);


$fieldPrepare['entity'] = $entity;
$fieldPrepare['field'] = "client";
$fieldPrepare['action'] = "new";
$fieldPrepare['not_empty'] = $data['client_new'];
$formfield->save($fieldPrepare);
unset($fieldPrepare);

$fieldPrepare['entity'] = $entity;
$fieldPrepare['field'] = "client";
$fieldPrepare['action'] = "edit";
$fieldPrepare['not_empty'] = $data['client_edit'];
$formfield->save($fieldPrepare);
unset($fieldPrepare);

$fieldPrepare['entity'] = $entity;
$fieldPrepare['field'] = "client";
$fieldPrepare['action'] = "close";
$fieldPrepare['not_empty'] = $data['client_close'];
$formfield->save($fieldPrepare);
unset($fieldPrepare);


/* Area */
$data['area_new'] = (isset($post['area_new']) ? ($post['area_new'] == "yes" ? 1 : 0) : 0);
$data['area_edit'] = (isset($post['area_edit']) ? ($post['area_edit'] == "yes" ? 1 : 0) : 0);
$data['area_close'] = (isset($post['area_close']) ? ($post['area_close'] == "yes" ? 1 : 0) : 0);


$fieldPrepare['entity'] = $entity;
$fieldPrepare['field'] = "area";
$fieldPrepare['action'] = "new";
$fieldPrepare['not_empty'] = $data['area_new'];
$formfield->save($fieldPrepare);
unset($fieldPrepare);

$fieldPrepare['entity'] = $entity;
$fieldPrepare['field'] = "area";
$fieldPrepare['action'] = "edit";
$fieldPrepare['not_empty'] = $data['area_edit'];
$formfield->save($fieldPrepare);
unset($fieldPrepare);

$fieldPrepare['entity'] = $entity;
$fieldPrepare['field'] = "area";
$fieldPrepare['action'] = "close";
$fieldPrepare['not_empty'] = $data['area_close'];
$formfield->save($fieldPrepare);
unset($fieldPrepare);


/* Tipo de problema */
$data['issue_new'] = (isset($post['issue_new']) ? ($post['issue_new'] == "yes" ? 1 : 0) : 0);
$data['issue_edit'] = (isset($post['issue_edit']) ? ($post['issue_edit'] == "yes" ? 1 : 0) : 0);
$data['issue_close'] = (isset($post['issue_close']) ? ($post['issue_close'] == "yes" ? 1 : 0) : 0);

$fieldPrepare['entity'] = $entity;
$fieldPrepare['field'] = "issue";
$fieldPrepare['action'] = "new";
$fieldPrepare['not_empty'] = $data['issue_new'];
$formfield->save($fieldPrepare);
unset($fieldPrepare);

$fieldPrepare['entity'] = $entity;
$fieldPrepare['field'] = "issue";
$fieldPrepare['action'] = "edit";
$fieldPrepare['not_empty'] = $data['issue_edit'];
$formfield->save($fieldPrepare);
unset($fieldPrepare);

$fieldPrepare['entity'] = $entity;
$fieldPrepare['field'] = "issue";
$fieldPrepare['action'] = "close";
$fieldPrepare['not_empty'] = $data['issue_close'];
$formfield->save($fieldPrepare);
unset($fieldPrepare);


/* Unidade */
$data['unit_new'] = (isset($post['unit_new']) ? ($post['unit_new'] == "yes" ? 1 : 0) : 0);
$data['unit_edit'] = (isset($post['unit_edit']) ? ($post['unit_edit'] == "yes" ? 1 : 0) : 0);
$data['unit_close'] = (isset($post['unit_close']) ? ($post['unit_close'] == "yes" ? 1 : 0) : 0);

$fieldPrepare['entity'] = $entity;
$fieldPrepare['field'] = "unit";
$fieldPrepare['action'] = "new";
$fieldPrepare['not_empty'] = $data['unit_new'];
$formfield->save($fieldPrepare);
unset($fieldPrepare);

$fieldPrepare['entity'] = $entity;
$fieldPrepare['field'] = "unit";
$fieldPrepare['action'] = "edit";
$fieldPrepare['not_empty'] = $data['unit_edit'];
$formfield->save($fieldPrepare);
unset($fieldPrepare);

$fieldPrepare['entity'] = $entity;
$fieldPrepare['field'] = "unit";
$fieldPrepare['action'] = "close";
$fieldPrepare['not_empty'] = $data['unit_close'];
$formfield->save($fieldPrepare);
unset($fieldPrepare);



/* Etiqueta */
$data['asset_tag_new'] = (isset($post['asset_tag_new']) ? ($post['asset_tag_new'] == "yes" ? 1 : 0) : 0);
$data['asset_tag_edit'] = (isset($post['asset_tag_edit']) ? ($post['asset_tag_edit'] == "yes" ? 1 : 0) : 0);
$data['asset_tag_close'] = (isset($post['asset_tag_close']) ? ($post['asset_tag_close'] == "yes" ? 1 : 0) : 0);

$fieldPrepare['entity'] = $entity;
$fieldPrepare['field'] = "asset_tag";
$fieldPrepare['action'] = "new";
$fieldPrepare['not_empty'] = $data['asset_tag_new'];
$formfield->save($fieldPrepare);
unset($fieldPrepare);

$fieldPrepare['entity'] = $entity;
$fieldPrepare['field'] = "asset_tag";
$fieldPrepare['action'] = "edit";
$fieldPrepare['not_empty'] = $data['asset_tag_edit'];
$formfield->save($fieldPrepare);
unset($fieldPrepare);

$fieldPrepare['entity'] = $entity;
$fieldPrepare['field'] = "asset_tag";
$fieldPrepare['action'] = "close";
$fieldPrepare['not_empty'] = $data['asset_tag_close'];
$formfield->save($fieldPrepare);
unset($fieldPrepare);



/* Contato */
$data['contact_new'] = (isset($post['contact_new']) ? ($post['contact_new'] == "yes" ? 1 : 0) : 0);
$data['contact_edit'] = (isset($post['contact_edit']) ? ($post['contact_edit'] == "yes" ? 1 : 0) : 0);
$data['contact_close'] = (isset($post['contact_close']) ? ($post['contact_close'] == "yes" ? 1 : 0) : 0);

$fieldPrepare['entity'] = $entity;
$fieldPrepare['field'] = "contact";
$fieldPrepare['action'] = "new";
$fieldPrepare['not_empty'] = $data['contact_new'];
$formfield->save($fieldPrepare);
unset($fieldPrepare);

$fieldPrepare['entity'] = $entity;
$fieldPrepare['field'] = "contact";
$fieldPrepare['action'] = "edit";
$fieldPrepare['not_empty'] = $data['contact_edit'];
$formfield->save($fieldPrepare);
unset($fieldPrepare);

$fieldPrepare['entity'] = $entity;
$fieldPrepare['field'] = "contact";
$fieldPrepare['action'] = "close";
$fieldPrepare['not_empty'] = $data['contact_close'];
$formfield->save($fieldPrepare);
unset($fieldPrepare);



/* Contato email */
$data['contact_email_new'] = (isset($post['contact_email_new']) ? ($post['contact_email_new'] == "yes" ? 1 : 0) : 0);
$data['contact_email_edit'] = (isset($post['contact_email_edit']) ? ($post['contact_email_edit'] == "yes" ? 1 : 0) : 0);
$data['contact_email_close'] = (isset($post['contact_email_close']) ? ($post['contact_email_close'] == "yes" ? 1 : 0) : 0);

$fieldPrepare['entity'] = $entity;
$fieldPrepare['field'] = "contact_email";
$fieldPrepare['action'] = "new";
$fieldPrepare['not_empty'] = $data['contact_email_new'];
$formfield->save($fieldPrepare);
unset($fieldPrepare);

$fieldPrepare['entity'] = $entity;
$fieldPrepare['field'] = "contact_email";
$fieldPrepare['action'] = "edit";
$fieldPrepare['not_empty'] = $data['contact_email_edit'];
$formfield->save($fieldPrepare);
unset($fieldPrepare);

$fieldPrepare['entity'] = $entity;
$fieldPrepare['field'] = "contact_email";
$fieldPrepare['action'] = "close";
$fieldPrepare['not_empty'] = $data['contact_email_close'];
$formfield->save($fieldPrepare);
unset($fieldPrepare);



/* Telefone */
$data['phone_new'] = (isset($post['phone_new']) ? ($post['phone_new'] == "yes" ? 1 : 0) : 0);
$data['phone_edit'] = (isset($post['phone_edit']) ? ($post['phone_edit'] == "yes" ? 1 : 0) : 0);
$data['phone_close'] = (isset($post['phone_close']) ? ($post['phone_close'] == "yes" ? 1 : 0) : 0);

$fieldPrepare['entity'] = $entity;
$fieldPrepare['field'] = "phone";
$fieldPrepare['action'] = "new";
$fieldPrepare['not_empty'] = $data['phone_new'];
$formfield->save($fieldPrepare);
unset($fieldPrepare);

$fieldPrepare['entity'] = $entity;
$fieldPrepare['field'] = "phone";
$fieldPrepare['action'] = "edit";
$fieldPrepare['not_empty'] = $data['phone_edit'];
$formfield->save($fieldPrepare);
unset($fieldPrepare);

$fieldPrepare['entity'] = $entity;
$fieldPrepare['field'] = "phone";
$fieldPrepare['action'] = "close";
$fieldPrepare['not_empty'] = $data['phone_close'];
$formfield->save($fieldPrepare);
unset($fieldPrepare);



/* Department */
$data['department_new'] = (isset($post['department_new']) ? ($post['department_new'] == "yes" ? 1 : 0) : 0);
$data['department_edit'] = (isset($post['department_edit']) ? ($post['department_edit'] == "yes" ? 1 : 0) : 0);
$data['department_close'] = (isset($post['department_close']) ? ($post['department_close'] == "yes" ? 1 : 0) : 0);

$fieldPrepare['entity'] = $entity;
$fieldPrepare['field'] = "department";
$fieldPrepare['action'] = "new";
$fieldPrepare['not_empty'] = $data['department_new'];
$formfield->save($fieldPrepare);
unset($fieldPrepare);

$fieldPrepare['entity'] = $entity;
$fieldPrepare['field'] = "department";
$fieldPrepare['action'] = "edit";
$fieldPrepare['not_empty'] = $data['department_edit'];
$formfield->save($fieldPrepare);
unset($fieldPrepare);

$fieldPrepare['entity'] = $entity;
$fieldPrepare['field'] = "department";
$fieldPrepare['action'] = "close";
$fieldPrepare['not_empty'] = $data['department_close'];
$formfield->save($fieldPrepare);
unset($fieldPrepare);



// if (!empty($exception)) {
//     $data['message'] = $data['message'] . "<hr>" . $exception;
// }

$data['message'] = TRANS('MSG_SUCCESS_EDIT');

$_SESSION['flash'] = message('success', '', $data['message'], '');
echo json_encode($data);
return false;

echo json_encode($data);
