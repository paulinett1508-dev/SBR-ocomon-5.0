<?php
/*
 * oauth_callback.php
 *
 * Handler do callback OAuth do Supabase/Google.
 * Esta URL deve ser registrada em:
 *   - Supabase Dashboard → Authentication → URL Configuration → Redirect URLs
 *
 * Fluxo:
 *   1. Recebe ?code= da Supabase após autenticação Google
 *   2. Troca o code pelo access_token via Supabase API (PKCE)
 *   3. Valida email (@workspace autorizado)
 *   4. Cria/atualiza usuário na tabela `usuarios`
 *   5. Popula sessão PHP e redireciona para index.php
 */

session_start();

require_once __DIR__ . '/../../includes/include_geral_new.inc.php';
require_once __DIR__ . '/../../includes/classes/ConnectPDO.php';
require_once __DIR__ . '/../../includes/classes/SupabaseAuth.php';

use includes\classes\ConnectPDO;
use includes\classes\SupabaseAuth;

$conn = ConnectPDO::getInstance();

// ------------------------------------------------------------------
// 1. Verificar parâmetros do callback
// ------------------------------------------------------------------

$code  = filter_input(INPUT_GET, 'code',  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$error = filter_input(INPUT_GET, 'error', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

if ($error) {
    error_log("OAuth callback error: {$error}");
    $_SESSION['flash'] = message('danger', 'Ooops!', TRANS('OAUTH_ERROR') . ': ' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8'), '');
    redirect('../../login.php');
    exit;
}

if (empty($code)) {
    $_SESSION['flash'] = message('warning', 'Ooops!', TRANS('OAUTH_MISSING_CODE'), '');
    redirect('../../login.php');
    exit;
}

// ------------------------------------------------------------------
// 2. Trocar code por token (PKCE)
// ------------------------------------------------------------------

$auth    = new SupabaseAuth();
$session = $auth->exchangeCodeForSession($code);

if (!$session || empty($session['access_token'])) {
    $_SESSION['flash'] = message('danger', 'Ooops!', TRANS('OAUTH_TOKEN_EXCHANGE_FAILED'), '');
    redirect('../../login.php');
    exit;
}

// ------------------------------------------------------------------
// 3. Validar token e verificar domínio Workspace
// ------------------------------------------------------------------

$googleUser = $auth->validateAndGetUser($session['access_token']);

if (!$googleUser) {
    $_SESSION['flash'] = message('danger', 'Ooops!', TRANS('OAUTH_INVALID_TOKEN_OR_DOMAIN'), '');
    redirect('../../login.php');
    exit;
}

$email      = $googleUser['email'];
$name       = $googleUser['name'];
$supabaseId = $googleUser['sub'];

// ------------------------------------------------------------------
// 4. Criar ou atualizar usuário local
// ------------------------------------------------------------------

$now = date('Y-m-d H:i:s');

// Verificar se usuário já existe pelo email
$sql = "SELECT user_id, login, nome, nivel, AREA as area_id, user_admin,
               can_route, can_get_routed, opening_mode, sis_screen, language,
               last_logon, user_client
        FROM usuarios
        WHERE login = :email
        LIMIT 1";
$res = $conn->prepare($sql);
$res->bindParam(':email', $email);
$res->execute();
$userInfo = $res->fetch(\PDO::FETCH_ASSOC);

if (!$userInfo) {
    // Primeiro login — provisionar usuário automaticamente com nível 3 (usuário final)
    $config = getConfig($conn);

    $sql = "INSERT INTO usuarios
                (login, nome, email, nivel, AREA, user_admin, data_inc, can_route, can_get_routed, opening_mode)
            VALUES
                (:login, :nome, :email, 3, 1, 0, :data_inc, 0, 0, 1)";
    $res = $conn->prepare($sql);
    $res->bindParam(':login',    $email);
    $res->bindParam(':nome',     $name);
    $res->bindParam(':email',    $email);
    $res->bindParam(':data_inc', $now);

    try {
        $res->execute();
    } catch (\Exception $e) {
        error_log('OAuth callback — falha ao criar usuário: ' . $e->getMessage());
        $_SESSION['flash'] = message('danger', 'Ooops!', TRANS('OAUTH_USER_CREATE_FAILED'), '');
        redirect('../../login.php');
        exit;
    }

    // Recarregar usuário recém-criado
    $sql = "SELECT user_id, login, nome, nivel, AREA as area_id, user_admin,
                   can_route, can_get_routed, opening_mode, sis_screen, language,
                   last_logon, user_client
            FROM usuarios
            WHERE login = :email
            LIMIT 1";
    $res = $conn->prepare($sql);
    $res->bindParam(':email', $email);
    $res->execute();
    $userInfo = $res->fetch(\PDO::FETCH_ASSOC);
}

if (!$userInfo) {
    $_SESSION['flash'] = message('danger', 'Ooops!', TRANS('OAUTH_USER_NOT_FOUND'), '');
    redirect('../../login.php');
    exit;
}

// Verificar nível de acesso
if ($userInfo['nivel'] > 3) {
    $_SESSION['flash'] = message('warning', 'Ooops!', TRANS('ERR_LOGON'), '');
    redirect('../../login.php');
    exit;
}

// ------------------------------------------------------------------
// 5. Atualizar último login
// ------------------------------------------------------------------

updateLastLogon($conn, $userInfo['user_id']);

// ------------------------------------------------------------------
// 6. Regenerar ID de sessão (previne session fixation)
// ------------------------------------------------------------------

session_regenerate_id(true);

// ------------------------------------------------------------------
// 7. Popular sessão PHP (mantém compatibilidade com o restante do sistema)
// ------------------------------------------------------------------

$area           = $userInfo['area_id'];
$secondaryAreas = getUserAreas($conn, $userInfo['user_id']);
$allAreas       = (!empty($secondaryAreas) ? $area . ',' . $secondaryAreas : $area);

$mod_tickets   = getModuleAccess($conn, 1, $allAreas);
$mod_inventory = getModuleAccess($conn, 2, $allAreas);

$modulos = '';
if ($mod_tickets)   $modulos = '1';
if ($mod_inventory) $modulos .= (strlen((string)$modulos) ? ',' : '') . '2';

$config = getConfig($conn);

$_SESSION['s_logado']         = 1;
$_SESSION['s_auth_method']    = 'GOOGLE_OAUTH'; // identifica o método de auth
$_SESSION['csrf_token']       = '';
$_SESSION['s_usuario']        = $email;
$_SESSION['s_usuario_nome']   = $name;
$_SESSION['s_uid']            = $userInfo['user_id'];
$_SESSION['s_nivel_real']     = $userInfo['nivel'];
$_SESSION['s_nivel']          = $userInfo['nivel'];
$_SESSION['s_nivel_desc']     = $userInfo['nivel'];
$_SESSION['s_area']           = $userInfo['area_id'];
$_SESSION['s_uareas']         = $allAreas;
$_SESSION['s_opening_mode']   = $userInfo['opening_mode'];
$_SESSION['s_area_admin']     = $userInfo['user_admin'];
$_SESSION['s_can_route']      = $userInfo['can_route'];
$_SESSION['s_can_get_routed'] = $userInfo['can_get_routed'];
$_SESSION['s_ocomon']         = $mod_tickets;
$_SESSION['s_invmon']         = $mod_inventory;
$_SESSION['s_permissoes']     = $modulos;

$allowedUnits = getAreaAllowedUnits($conn, $userInfo['area_id']);
$_SESSION['s_allowed_units'] = ($userInfo['nivel'] != 1 && !empty($allowedUnits))
    ? implode(',', array_column($allowedUnits, 'unit_id'))
    : '';

$allowedClients = implode(',', array_column(getAreaAllowedClients($conn, $_SESSION['s_area']), 'inst_client'));
$_SESSION['s_allowed_clients'] = ($userInfo['nivel'] != 1 && !empty($allowedClients))
    ? $allowedClients
    : '';

$defaultScreenProfile       = getDefaultScreenProfile($conn);
$_SESSION['s_screen']       = $userInfo['sis_screen'] ?? $defaultScreenProfile;
$_SESSION['s_wt_areas']     = $config['conf_wt_areas'];
$_SESSION['s_language']     = !empty($userInfo['language']) ? $userInfo['language'] : $config['conf_language'];
$_SESSION['s_date_format']  = $config['conf_date_format'];
$_SESSION['s_page_size']    = $config['conf_page_size'];
$_SESSION['s_allow_reopen'] = $config['conf_allow_reopen'];
$_SESSION['s_ocomon_site']  = $config['conf_ocomon_site'];
$_SESSION['s_paging_full']  = 0;
$_SESSION['s_formatBarOco']   = (strpos($config['conf_formatBar'] ?? '', '%oco%')   !== false ? 1 : 0);
$_SESSION['s_formatBarMural'] = (strpos($config['conf_formatBar'] ?? '', '%mural%') !== false ? 1 : 0);

$_SESSION['s_rep_filters'] = [
    'client' => '',
    'area'   => $userInfo['area_id'],
    'd_ini'  => date('01/m/Y'),
    'd_fim'  => date('d/m/Y'),
    'state'  => 1,
    'cat1'   => -1,
    'cat2'   => -1,
    'cat3'   => -1,
];

$_SESSION['s_colorDestaca']   = '#CCCCCC';
$_SESSION['s_colorMarca']     = '#FFFFCC';
$_SESSION['s_colorLinPar']    = '#E3E1E1';
$_SESSION['s_colorLinImpar']  = '#F6F6F6';

$_SESSION['attempt']['try'] = 0;

$isFirstLogon = (empty($userInfo['last_logon']));
$message      = $isFirstLogon ? TRANS('MSG_WELCOME') : TRANS('MSG_WELCOME_BACK');
$_SESSION['flash'] = message('success', TRANS('MSG_HELLO') . ' ' . firstLetterUp(firstWord($name)) . '!', $message, '');

// ------------------------------------------------------------------
// 8. Redirecionar para a aplicação
// ------------------------------------------------------------------

redirect('../../index.php');
exit;
