<?php
/* 
Copyright 2023 Flávio Ribeiro

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
 * getConfig
 * Retorna o array com as informações de configuração do sistema
 * @param PDO $conn
 * @return array
 */
function getConfig ($conn): array
{
    $sql = "SELECT * FROM config ";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount())
            return $res->fetch();
        return [];
    }
    catch (Exception $e) {
        return [];
    }
}

/**
 * getConfigValue
 * Retorna o valor da chave de configuração informada - Configurações estendidas
 * @param \PDO $conn
 * @param string $key
 * @return null | string
 */
function getConfigValue (\PDO $conn, string $key): ?string
{
    $sql = "SELECT key_value FROM config_keys WHERE key_name = :key_name ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':key_name', $key);
        $res->execute();
        
        if ($res->rowCount()) {
            return $res->fetch()['key_value'];
        }
        return null;
    }
    catch (Exception $e) {
        return null;
    }
}

/**
 * getConfigValues
 * Retorna um array com todas as chaves e valores das Configurações estendidas
 * @param \PDO $conn
 * @return array
 */
function getConfigValues (\PDO $conn): array
{
    $return = [];
    $notReturn = [];
    
    /* Essas chaves não serão retornadas */
    $notReturn[] = 'API_TICKET_BY_MAIL_TOKEN';
    $notReturn[] = 'MAIL_GET_PASSWORD';

    $sql = "SELECT key_name, key_value FROM config_keys ";
    try {
        $res = $conn->prepare($sql);
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                if (!in_array($row['key_name'], $notReturn))
                    $return[$row['key_name']] = $row['key_value'];
            }
        }
        return $return;
    }
    catch (Exception $e) {
        return $return;
    }
}


/**
 * saveNewTags
 * Checa se há novas tags em um array informado - se existirem novas tags serão gravadas
 * @param \PDO $conn
 * @param array $tags
 * @return bool
 */
function saveNewTags (\PDO $conn, array $tags): bool
{
    if (!is_array($tags)){
        return false;
    }

    $tags = filter_var_array($tags, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    foreach ($tags as $tag) {
        $sql = "SELECT tag_name FROM input_tags WHERE tag_name = :tag ";
        try {
            $res = $conn->prepare($sql);
            $res->bindParam(':tag', $tag);
            $res->execute();
            if (!$res->rowCount()) {
                $sqlIns = "INSERT INTO input_tags (tag_name) VALUES (:tag)";
                try {
                    $resInsert = $conn->prepare($sqlIns);
                    $resInsert->bindParam(':tag', $tag);
                    $resInsert->execute();
                }
                catch (Exception $e) {
                    return false;
                }
            }
        }
        catch (Exception $e) {
            return false;
        }
    }
    return true;
}


/**
 * getTagsList
 * Retorna a listagem de tags existentes ou uma tag específica na tabela de referência
 * @param \PDO $conn
 * @param int $id
 * @return array
 */
function getTagsList(\PDO $conn, ?int $id = null): array
{
    $data = [];
    $terms = "";
    if ($id) {
        $terms = " WHERE id = :id ";
    }

    $sql = "SELECT id, tag_name FROM input_tags {$terms} ORDER BY tag_name";
    try {
        $res = $conn->prepare($sql);
        if ($id) {
            $res->bindParam(':id', $id, PDO::PARAM_INT);
        }
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $tag) {
                $data[] = $tag;
            }
            if ($id)
                return $data[0];
            return $data;
        }
        return [];

    }
    catch (Exception $e) {
        return [];
    }
}


/**
 * getTagCount
 * Retorna a quantidade de vezes que a tag informada está sendo utilizada nos chamados
 * @param \PDO $conn
 * @param string $tag
 * 
 * @return int
 */
function getTagCount(\PDO $conn, string $tag, ?string $startDate = null, ?string $endDate = null, ?string $area = null, bool $requesterArea = false, ?string $client = null): int
{

    $terms = "";
    $aliasAreas = ($requesterArea ? "ua.AREA" : "o.sistema");
    
    if ($startDate) {
        $terms .= " AND o.data_abertura >= :startDate ";
    }
    if ($endDate) {
        $terms .= " AND o.data_abertura <= :endDate ";
    }

    if ($area && !empty($area) && $area != -1) {
        // $terms .= " AND o.sistema IN ({$area})";
        $terms .= " AND {$aliasAreas} IN ({$area})";
    }

    if ($client && !empty($client)) {
        $terms .= " AND o.client IN ({$client})";
    }
    
    $sql = "SELECT count(*) total 
            FROM 
                ocorrencias o, sistemas s, usuarios ua, `status` st 
            WHERE 
                o.status = st.stat_id AND st.stat_ignored <> 1 AND 
                o.sistema = s.sis_id AND o.aberto_por = ua.user_id AND 
                MATCH(oco_tag) AGAINST ('\"$tag\"' IN BOOLEAN MODE) 
                {$terms}";
    try {
        $res = $conn->prepare($sql);
        // $res->bindParam(':tag', $tag);
        if ($startDate) 
            $res->bindParam(":startDate", $startDate);
        if ($endDate) 
            $res->bindParam(":endDate", $endDate);
        // if ($area && !empty($area) && $area != -1)
        //     $res->bindParam(":area", $area);

        $res->execute();
        if ($res->rowCount()) {
            return $res->fetch()['total'];
        }
        return 0;
    }
    catch (Exception $e) {
        echo $sql . "<hr/>" . $e->getMessage();
        // exit;
        return 0;
    }
}


/**
 * getScreenInfo
 * Retorna o array com as informações do perfil de tela de abertura
 * [conf_cod], [conf_name], [conf_user_opencall - permite autocadastro], [conf_custom_areas], 
 * [conf_ownarea - area para usuários que se autocadastram], [conf_ownarea_2], [conf_opentoarea]
 * [conf_screen_area], []... [conf_screen_msg]
 * @param \PDO $conn
 * @param int $screenId
 * @return array
 */
function getScreenInfo (\PDO $conn, int $screenId): array
{
    $sql = "SELECT 
                *
            FROM 
                configusercall
            WHERE 
                conf_cod = :screenID ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':screenID', $screenId);
        $res->execute();

        if ($res->rowCount())
            return $res->fetch();
        return [];
    }
    catch (Exception $e) {
        return [];
    }
}


/**
 * getScreenProfiles
 * Retorna a listagem de perfis de tela de abertura
 * Indices: conf_cod, conf_name, etc..
 *
 * @param \PDO $conn
 * 
 * @return array
 */
function getScreenProfiles (\PDO $conn): array
{
    $sql = "SELECT * FROM configusercall ORDER BY conf_name";
    try {
        $res = $conn->prepare($sql);
        $res->execute();
        
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return [];
    }
}


/**
 * getDefaultScreenProfile
 * Retorna o código do perfil de tela padrão ou 0 se não existir
 * @param \PDO $conn
 * 
 * @return int
 */
function getDefaultScreenProfile(\PDO $conn): int
{
    $sql = "SELECT conf_cod FROM configusercall WHERE conf_is_default = 1";
    try {
        $res = $conn->prepare($sql);
        $res->execute();
        
        if ($res->rowCount()) {
            return $res->fetch()['conf_cod'];
        }
        return 0;
    }
    catch (Exception $e) {
        return 0;
    }
}


/**
 * getFormRequiredInfo
 * Retorna um array com os valores de obrigariedade para os campos do perfil de campos disponíveis
 * Indices: nome do campo, valor (0|1)
 *
 * @param \PDO $conn
 * @param int $profileId
 * 
 * @return array
 */
function getFormRequiredInfo (\PDO $conn, int $profileId, ?string $table = null): array
{
    
    $fields = [];
    $table = ($table === null ? "screen_field_required" : $table);
    
    
    $sql = "SELECT 
                *
            FROM 
                {$table}
            WHERE 
                profile_id = :profileId ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':profileId', $profileId);
        $res->execute();

        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $fields[$row['field_name']] = $row['field_required'];
            }
            return $fields;
        }
        return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}


/**
 * pass
 * Retorna se a combinação de usuário e senha(ou hash:versão 4x) é válida
 * @param \PDO $conn
 * @param string $user
 * @param string $pass (deve vir com md5)
 * @return bool
 */
function pass(\PDO $conn, string $user, string $pass): bool
{
    $user = filter_var($user, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $pass = filter_var($pass, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    $sql = "SELECT 
                `user_id`, `password`, `hash`
            FROM 
                usuarios 
            WHERE 
                login = :user AND
                nivel < 4
            ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':user', $user);

        $res->execute();
        if ($res->rowCount()) {

            $row = $res->fetch();
            if (!empty($row['hash'])) {
                /* usuário possui hash de senha */
                return password_verify($pass, $row['hash']);
            }

            if ($pass === $row['password'] && !empty($pass)) {
                return true;
            }
            return false;

        }
        return false;
    }
    catch (Exception $e) {
        return false;
    }

    return false;
}


/**
 * Valida usuário e senha quanto a configuração de autenticação for para LDAP
 * É utilizada quando o tipo de autenticação de autenticação configurado em AUTH_TYPE for LDAP
 */
function passLdap (string $username, string $pass, array $ldapConfig): bool
{
    if (empty($username) || empty($pass)) {
        return false;
    }

    $username = filter_var($username, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $pass = filter_var($pass, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    $ldapConn = ldap_connect($ldapConfig['LDAP_HOST'], $ldapConfig['LDAP_PORT']);
    if (!$ldapConn) {
        // echo ldap_error($ldapConn);
        return false;
    }

    ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);

    $username = $username . "@" . $ldapConfig['LDAP_DOMAIN'];

    if (@ldap_bind($ldapConn, $username, $pass)) {
        // echo ldap_error($ldapConn);
        return true;
    }
    return false;
}


/**
 * getUserLdapData
 *
 * @param string $username
 * @param string $pass
 * @param array $ldapConfig
 * 
 * @return array
 */
function getUserLdapData(string $username, string $pass, array $ldapConfig): array
{
    $ldapConn = ldap_connect($ldapConfig['LDAP_HOST'], $ldapConfig['LDAP_PORT']);
    if (!$ldapConn) {
        return false;
    }
    
    ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);

    $usernameAtDomain = $username . "@" . $ldapConfig['LDAP_DOMAIN'];

    if (@ldap_bind($ldapConn, $usernameAtDomain, $pass)) {
        // echo ldap_error($ldapConn);

        /* (&(objectClass=user)(objectCategory=person)(!(userAccountControl:1.2.840.113556.1.4.803:=2))) */
        $search_filter = "(|(sAMAccountName=" . $username .")(uid=" . $username ."))";
        $results = ldap_search($ldapConn, $ldapConfig['LDAP_BASEDN'], $search_filter);
        
        if (!$results) {
            return [];
        }

        $datas = ldap_get_entries($ldapConn, $results);

        if (!($datas['count']) || !$datas) {
            return [];
        }

        $data = [];
        $data['username'] = $username;
        $data['password'] = $pass;
        
        $data['LDAP_FIELD_FULLNAME'] = (isset($datas[0][$ldapConfig['LDAP_FIELD_FULLNAME']][0]) ? $datas[0][$ldapConfig['LDAP_FIELD_FULLNAME']][0] : "");
        $data['LDAP_FIELD_EMAIL'] = (isset($datas[0][$ldapConfig['LDAP_FIELD_EMAIL']][0]) ? $datas[0][$ldapConfig['LDAP_FIELD_EMAIL']][0] : "");
        $data['LDAP_FIELD_PHONE'] = (isset($datas[0][$ldapConfig['LDAP_FIELD_PHONE']][0]) ? $datas[0][$ldapConfig['LDAP_FIELD_PHONE']][0] : "");

        return $data;
        
    }
    return [];
}


/**
 * isLocalUser
 * Retorna se existe um usuário com o nome de login informado
 *
 * @param \PDO $conn
 * @param string $user
 * 
 * @return bool
 */
function isLocalUser (\PDO $conn, string $user): bool
{
    $user = filter_var($user, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    $sql = "SELECT user_id FROM usuarios WHERE login = :user ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':user', $user);
        $res->execute();
        if ($res->rowCount()) {
            return true;
        }
        return false;
    }
    catch (Exception $e) {
        return false;
    }

    return false;
}


/**
 * getUsers
 * Retorna um array com a listagem dos usuários ou do usuário específico caso o id seja informado
 * @param \PDO $conn
 * @param int|null $id
 * @param array|null $level
 * @param bool|null $can_route - filtra pelos usuários que podem encaminhar chamados
 * @param bool|null $can_get_routed - filtra pelos usuários que podem receber chamados encaminhados
 * @return array
 */
function getUsers (\PDO $conn, ?int $id = null, ?array $level = null, ?bool $can_route = null, ?bool $can_get_routed = null, ?array $areas = null): array
{
    $in = "";
    if ($level) {
        $in = implode(',', array_map('intval', $level));
        // VERSION 2. For strings: apply PDO::quote() function to all elements
        // $in = implode(',', array_map([$conn, 'quote'], $level));
    }
    $terms = ($id ? " WHERE user_id = :id " : '');
    
    if (!$id) {
        if ($level) {
            $terms .= " WHERE nivel IN ($in) ";
        }

        if ($can_route !== null) {
            $can_route = ($can_route ? 1 : 0);
            $terms .= ($terms ? " AND " : " WHERE ") . "can_route = {$can_route} ";
        }
        if ($can_get_routed !== null) {
            $can_get_routed = ($can_get_routed ? 1 : 0);
            $terms .= ($terms ? " AND " : " WHERE ") . "can_get_routed = {$can_get_routed} ";
        }

        if ($areas) {
            $in = implode(',', array_map('intval', $areas));
            $terms .= ($terms ? " AND " : " WHERE ") . " AREA IN ({$in}) ";
        }
    }
    
    $sql = "SELECT * FROM usuarios {$terms} ORDER BY nome, login";
    try {
        $res = $conn->prepare($sql);
    
        if ($id) {
            $res->bindParam(':id', $id); 
        }
        
        $res->execute();
        /* $res->debugDumpParams() */
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            if ($id)
                return $data[0];
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
        // return [];
    }
}


/**
 * getUsersByPrimaryArea
 * Retorna um array com a listagem dos usuários da área informada
 * @param \PDO $conn
 * @param null|int $area
 * @param null|array $level
 * @return array
 */
function getUsersByPrimaryArea (\PDO $conn, ?int $area = null, ?array $level = null): array
{
    $return = [];
    $in = "";
    if ($level) {
        $in = implode(',', array_map('intval', $level));
    }
    $terms = ($area ? "AND u.AREA = :area " : '');
    $terms = (empty($terms) && $level ? "AND nivel IN ({$in})" : $terms);

    $sql = "SELECT u.user_id, u.nome FROM usuarios u, sistemas a 
            WHERE u.AREA = a.sis_id 
            {$terms} ORDER BY nome";
    try {
        $res = $conn->prepare($sql);
        if (!empty($terms)) {
            if ($area)
                $res->bindParam(':area', $area); 
        }
        $res->execute();
        /* $res->debugDumpParams() */
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return $return;
    }
    catch (Exception $e) {
        return $return;
    }
}

/**
 * getUserInfo
 * Retorna o array com as informações do usuário e da área de atendimento que ele está vinculado
 * [user_id], [login], [nome], [email], [fone], [nivel], [area_id], [user_admin], [last_logon], 
 * [area_nome], [area_status], [area_email], [area_atende], [sis_screen], [sis_wt_profile],
 * [language]
 * @param \PDO $conn: conexao PDO
 * @param int $userId: id do usuário
 * @param string $userName: login do usuário - se for informado, o filtro será por ele
 * @return array
 */
function getUserInfo (\PDO $conn, int $userId, string $userName = ''): array
{
    $terms = (empty($userName) ? " user_id = :userId " : " login = :userName ");
    $sql = "SELECT 
                u.user_id,
                u.user_client,  
                u.login, u.nome, 
                u.email, u.fone, 
                u.password, u.hash, 
                u.nivel, u.AREA as area_id, 
                u.user_admin, u.last_logon, 
                u.can_route, u.can_get_routed,
                a.sistema as area_nome, 
                a.sis_status as area_status, 
                a.sis_email as area_email, 
                a.sis_atende as area_atende, a.sis_screen, 
                a.sis_wt_profile, 
                a.sis_opening_mode as opening_mode,
                p.upref_lang as language,
                cl.id, cl.fullname, cl.nickname
            FROM 
                sistemas a, usuarios u 
                LEFT JOIN uprefs p ON u.user_id = p.upref_uid
                LEFT JOIN clients cl ON u.user_client = cl.id
            WHERE 
                u.AREA = a.sis_id 
                AND 
                {$terms} ";
    try {
        $res = $conn->prepare($sql);

        if (!empty($userName)) {
            $res->bindParam(':userName', $userName); 
        } else
            $res->bindParam(':userId', $userId); 

        $res->execute();

        if ($res->rowCount())
            return $res->fetch();
        return [];
    }
    catch (Exception $e) {
        return [];
    }
}

/**
 * getUserAreas
 * Retorna uma string com as áreas SECUNDÁRIAS associadas ao usuário
 * @param \PDO $conn: conexao PDO
 * @param int $userId: id do usuário
 * @return string
 *
 */
function getUserAreas (\PDO $conn, int $userId): string
{
    $areas = "";
    $sql = "SELECT uarea_sid FROM usuarios_areas WHERE uarea_uid = '{$userId}' ";
    
    try {
        $res = $conn->query($sql);
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                if (strlen((string)$areas) > 0)
                    $areas .= ",";
                $areas .= $row['uarea_sid'];
            }
            return $areas;
        }
        return $areas;
    }
    catch (Exception $e) {
        return $areas;
    }
}



/**
 * unique_multidim_array
 * Retorna o array multidimensional sem duplicados baseado na chave fornecida
 * @param mixed $array
 * @param mixed $key
 * 
 * @return array | null
 */
function unique_multidim_array($array, $key): ?array
{
    $temp_array = array();
    $i = 0;
    $key_array = array();

    foreach ($array as $val) {
        if (!in_array($val[$key], $key_array)) {
            $key_array[$i] = $val[$key];
            $temp_array[$i] = $val;
        }
        $i++;
    }
    return $temp_array;
} 



/**
 * getUsersByArea
 * Retorna todos os usuários de uma determinada área - sendo primária ou secundária
 * Também retorna a quantidade de chamados vinculados (sob responsabilidade) a cada usuário
 * @param \PDO $conn: conexao PDO
 * @param int|null  $area: id da area
 * @param bool|null $getTotalTickets: se true, retorna o total de chamados vinculados ao usuário
 * @param bool|null $canRoute: Filtra por usuários que podem encaminhar chamados
 * @param bool|null $canGetRouted: Filtra por usuários que podem receber encaminhamentos de chamados
 * @return array|null
 *
 */
function getUsersByArea (
    \PDO $conn, 
    ?int $area, 
    ?bool $getTotalTickets = true, 
    ?bool $canRoute = null, 
    ?bool $canGetRouted = null
    ): ?array
{

    if (!$area) {
        return [];
    }

    $terms = "";
    if ($canRoute !== null) {
        $canRoute = ($canRoute ? 1 : 0);
        $terms .= " AND u.can_route = ". $canRoute;
    }

    if ($canGetRouted !== null) {
        $canGetRouted = ($canGetRouted ? 1 : 0);
        $terms .= " AND u.can_get_routed = " . $canGetRouted;
    }
    
    $primaryUsers = [];
    $secondaryUsers = [];
    $totalTickets = [];
    
    /* Checando com a área sendo primária */
    $sql = "SELECT 
                u.user_id, u.nome, u.user_bgcolor, u.user_textcolor, '0' total 
            FROM 
                sistemas a, usuarios u
            WHERE 
                a.sis_id = u.AREA AND
                a.sis_id = :area AND
                u.nivel < 4  
                {$terms} 
            ORDER BY 
                u.nome
            ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(":area", $area, PDO::PARAM_INT);
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $primaryUsers[] = $row;
            }
        }
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
        // return [];
    }

    /* Checando com a área sendo secundária */
    $sql = "SELECT 
                u.user_id, u.nome, u.user_bgcolor, u.user_textcolor, '0' total 
            FROM 
                usuarios as u, usuarios_areas as ua 
            WHERE
                u.user_id = ua.uarea_uid AND 
                u.nivel < 4 AND
                ua.uarea_sid = :area 
                {$terms}
            ORDER BY 
                u.nome
            ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(":area", $area, PDO::PARAM_INT);
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $secondaryUsers[] = $row;
            }
        }
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }


    /* Quantidade de chamados sob responsabilidade */
    if ($getTotalTickets) {
        $sql = "SELECT 
                    u.user_id, u.nome, u.user_bgcolor, u.user_textcolor, count(*) total 
                FROM 
                    ocorrencias o, status s, usuarios u, sistemas a 
                WHERE 
                    o.status = s.stat_id AND 
                    s.stat_painel = 1 AND 
                    o.operador = u.user_id AND 
                    o.oco_scheduled = 0 AND 
                    u.nivel < 4 AND 
                    a.sis_id = u.AREA AND 
                    a.sis_id = :area 
                    {$terms}
                GROUP BY 
                    user_id, nome, u.user_bgcolor, u.user_textcolor
                ";
        try {
            $res = $conn->prepare($sql);
            $res->bindParam(":area", $area, PDO::PARAM_INT);
            $res->execute();
            if ($res->rowCount()) {
                foreach ($res->fetchAll() as $row) {
                    $totalTickets[] = $row;
                }
            }
        }
        catch (Exception $e) {
            return ['error' => $e->getMessage()];
            // return [];
        }
    }

    
    if ($getTotalTickets) {
        $output = array_merge($totalTickets, $primaryUsers, $secondaryUsers);
    } else {
        $output = array_merge($primaryUsers, $secondaryUsers);
    }
    
    $output = unique_multidim_array($output, 'user_id');
    
    $keys = array_column($output, 'nome');
    array_multisort($keys, SORT_ASC, $output);

    return $output;
}



/**
 * getOnlyOpenUsers
 * Retorna a listagem de usuários de nível somente abertura
 *
 * @param mixed $conn
 * 
 * @return array
 */
function getOnlyOpenUsers($conn): array
{
    $onlyOpenUsers = [];
    $sql = "SELECT 
                u.user_id, 
                u.nome, 
                u.user_bgcolor, 
                u.user_textcolor, '0' total 
            FROM
                usuarios u
            WHERE
                u.nivel = 3 
            ORDER BY
                u.nome
            ";
        try {
            $res = $conn->query($sql);
            if ($res->rowCount()) {
                foreach ($res->fetchAll() as $row) {
                    $onlyOpenUsers[] = $row;
                }
                return $onlyOpenUsers;
            } else 
                return [];
        }
        catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
}


/**
 * getUsersBySetOfAreas
 * Retorna todos os usuários de uma ou várias áreas informadas - sendo primárias ou secundárias
 * Também retorna a quantidade de chamados vinculados (sob responsabilidade) a cada usuário
 * @param \PDO $conn: conexao PDO
 * @param array  $area: array com id(s) da(s) area(s)
 * @param bool|null $getTotalTickets: se true, retorna o total de chamados vinculados ao usuário
 * @param bool|null $canRoute: Filtra por usuários que podem encaminhar chamados
 * @param bool|null $canGetRouted: Filtra por usuários que podem receber encaminhamentos de chamados
 * @param array|null $level: Filtra pelos níveis dos usuários
 * @return array|null
 *
 */
function getUsersBySetOfAreas (\PDO $conn, array $area = [], ?bool $getTotalTickets = true, ?bool $canRoute = null, ?bool $canGetRouted = null, ?array $level = null): ?array
{

    $terms = "";
    $terms2 = "";
    
    $csvAreas = "";
    if (!empty($area)) {
        $csvAreas = implode(',', $area);
        $terms .= " AND a.sis_id IN ({$csvAreas}) ";
        $terms2 .= " AND ua.uarea_sid IN ({$csvAreas}) ";
    }
    
    
    if ($canRoute !== null) {
        $canRoute = ($canRoute ? 1 : 0);
        $terms .= " AND u.can_route = ". $canRoute;
        $terms2 .= " AND u.can_route = ". $canRoute;
    }

    if ($canGetRouted !== null) {
        $canGetRouted = ($canGetRouted ? 1 : 0);
        $terms .= " AND u.can_get_routed = " . $canGetRouted;
        $terms2 .= " AND u.can_get_routed = " . $canGetRouted;
    }

    if ($level !== null) {
        $csvLevels = implode(',', $level);
        $terms .= " AND u.nivel IN ({$csvLevels}) ";
        $terms2 .= " AND u.nivel IN ({$csvLevels}) ";
    }
    
    $primaryUsers = [];
    $secondaryUsers = [];
    $totalTickets = [];
    
    /* Checando com a área sendo primária */
    $sql = "SELECT 
                u.user_id, u.nome, u.user_bgcolor, u.user_textcolor, '0' total 
            FROM 
                sistemas a, usuarios u
            WHERE 
                a.sis_id = u.AREA AND
                u.nivel < 4  
                {$terms} 
            ORDER BY 
                u.nome
            ";
    try {
        $res = $conn->prepare($sql);
        // $res->bindParam(":area", $area, PDO::PARAM_INT);
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $primaryUsers[] = $row;
            }
        }
    }
    catch (Exception $e) {
        return [
                'error' => $e->getMessage(), 
                'sql' => $sql
            ];
    }

    /* Checando com a área sendo secundária */
    $sql = "SELECT 
                u.user_id, u.nome, u.user_bgcolor, u.user_textcolor, '0' total 
            FROM 
                usuarios as u, usuarios_areas as ua 
            WHERE
                u.user_id = ua.uarea_uid AND 
                u.nivel < 4 
                {$terms2}
            ORDER BY 
                u.nome
            ";
    try {
        $res = $conn->prepare($sql);
        // $res->bindParam(":area", $area, PDO::PARAM_INT);
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $secondaryUsers[] = $row;
            }
        }
    }
    catch (Exception $e) {
        return [
            'error' => $e->getMessage(), 
            'sql' => $sql
        ];
    }


    /* Quantidade de chamados sob responsabilidade */
    if ($getTotalTickets) {
        $sql = "SELECT 
                    u.user_id, u.nome, u.user_bgcolor, u.user_textcolor, count(*) total 
                FROM 
                    ocorrencias o, status s, usuarios u, sistemas a 
                WHERE 
                    o.status = s.stat_id AND 
                    s.stat_painel = 1 AND 
                    o.operador = u.user_id AND 
                    o.oco_scheduled = 0 AND 
                    u.nivel < 4 AND 
                    a.sis_id = u.AREA  
                    {$terms}
                GROUP BY 
                    user_id, nome, u.user_bgcolor, u.user_textcolor
                ";
        try {
            $res = $conn->prepare($sql);
            // $res->bindParam(":area", $area, PDO::PARAM_INT);
            $res->execute();
            if ($res->rowCount()) {
                foreach ($res->fetchAll() as $row) {
                    $totalTickets[] = $row;
                }
            }
        }
        catch (Exception $e) {
            return [
                'error' => $e->getMessage(), 
                'sql' => $sql
            ];
        }
    }

    
    if ($getTotalTickets) {
        $output = array_merge($totalTickets, $primaryUsers, $secondaryUsers);
    } else {
        $output = array_merge($primaryUsers, $secondaryUsers);
    }
    
    $output = unique_multidim_array($output, 'user_id');
    
    $keys = array_column($output, 'nome');
    array_multisort($keys, SORT_ASC, $output);

    return $output;
}




/**
 * getUserAreasNames
 * Retorna um array com os nomes das áreas cujos ids são informados em string
 * @param PDO $conn
 * @param mixed $areasIds
 * @return array
 */
function getUserAreasNames(\PDO $conn, string $areasIds): array
{
    $names = [];
    $sql = "SELECT sistema FROM sistemas WHERE sis_id IN ({$areasIds}) ORDER BY sistema";
    try {
        $res = $conn->query($sql);
        foreach ($res->fetchAll() as $row) {
            $names[] = $row['sistema'];
        }
        return $names;
    }
    catch (Exception $e) {
        return [];
    }
}


/**
 * getClients
 * Retorna um array com a listagem de clientes ou do cliente específico caso o id seja informado
 * @param \PDO $conn
 * @param int|null $id
 * @param int|null $operationType (1 - apenas interno atendimento | 2 (ou qualquer outro valor) - apenas externo - usuario final)
 * @return array
 */
function getClients (\PDO $conn, ?int $id = null, ?int $operationType = null, ?string $ids = null): array
{
    $terms = "";
    $terms = ($id ? " WHERE id = :id " : '');
    
    if (!$id) {
        if ($operationType !== null) {
            $terms .= ($operationType == 1 ? " WHERE id = 1" : " WHERE id <> 1 ");
        }

        if ($ids) {
            $terms .= ($terms ? " AND " : " WHERE ") . "id IN ({$ids})";
        }
    }


    $sql = "SELECT * FROM clients {$terms} ORDER BY fullname, nickname";
    try {
        $res = $conn->prepare($sql);
        if ($id) {
            $res->bindParam(':id', $id); 
        }
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            if ($id)
                return $data[0];
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return [];
    }
}



/**
 * getClientsNamesByIds
 * Retorna um array com os nomes dos clientes cujos ids são informados em string
 * @param PDO $conn
 * @param mixed $ids
 * @return array
 */
function getClientsNamesByIds(\PDO $conn, string $ids, ?bool $nickname = false): array
{
    $names = [];
    $alias = ($nickname ? 'nickname' : 'fullname');
    $sql = "SELECT {$alias} FROM clients WHERE id IN ({$ids}) ORDER BY {$alias}";
    try {
        $res = $conn->query($sql);
        foreach ($res->fetchAll() as $row) {
            $names[] = $row[$alias];
        }
        return $names;
    }
    catch (Exception $e) {
        return [];
    }
}


/**
 * getClientsTypes
 * Retorna a listagem de tipos de clientes ou um cliente específico caso o id seja informado
 * @param \PDO $conn
 * @param int|null $id
 * 
 * @return array
 */
function getClientsTypes(\PDO $conn, ?int $id = null): array
{

    $terms = ($id ? " WHERE id = :id " : '');
    
    $sql = "SELECT * FROM client_types {$terms} ORDER BY type_name";
    try {
        $res = $conn->prepare($sql);
        if ($id) {
            $res->bindParam(':id', $id); 
        }
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            if ($id)
                return $data[0];
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}



/**
 * getClientsStatus
 * Retorna a listagem de tipos de status de clientes ou um status específico caso o id seja informado
 * @param \PDO $conn
 * @param int|null $id
 * 
 * @return array
 */
function getClientsStatus(\PDO $conn, ?int $id = null): array
{

    $terms = ($id ? " WHERE id = :id " : '');
    
    $sql = "SELECT * FROM client_status {$terms} ORDER BY status_name";
    try {
        $res = $conn->prepare($sql);
        if ($id) {
            $res->bindParam(':id', $id); 
        }
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            if ($id)
                return $data[0];
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}



function getClientByTicket(\PDO $conn, int $ticket): array
{
    $sql = "SELECT 
                cl.id, cl.fullname, cl.nickname 
            FROM
                ocorrencias o
            LEFT JOIN clients cl ON cl.id = o.client
            WHERE
                o.numero = :ticket
            ";

    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':ticket', $ticket);
        $res->execute();
        if ($res->rowCount()) {
            return $res->fetch();
        }
        return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * getTableCompat
 * Para manter a compatibilidade com versões antigas
 * Faz o teste com a nomenclatura da tabela areaxarea_abrechamado
 * Em versões antigas essa tabela era areaXarea_abrechamado
 * @param PDO $conn
 * @return string
 */
function getTableCompat(\PDO $conn): string
{
    $table = "areaxarea_abrechamado";
    $sqlTest = "SELECT * FROM {$table}";
    try {
        $conn->query($sqlTest);
        return $table;
    } catch (Exception $e) {
        $table = "areaXarea_abrechamado";
        return $table;
    }
}



/**
 * getMeasureTypes
 * Retorna a listagem dos tipos de caracteríscas que podem ser medidas no inventário ou uma característica especifíca caso o id seja informado
 * @param \PDO $conn
 * @param int|null $id
 * @param bool $onlyHavingUnit : se true retorna apenas os tipos que possuem unidade de medida
 * 
 * @return array
 */
function getMeasureTypes(\PDO $conn, ?int $id = null, bool $onlyHavingUnit = false): array
{

    $terms = ($id ? " WHERE mt.id = :id " : '');
    $groupBy = "";

    if (!$id && $onlyHavingUnit) {
        $terms .= " LEFT JOIN measure_units mu ON mu.type_id = mt.id WHERE mu.id IS NOT NULL ";
        $groupBy = " GROUP BY mt.id, mt.mt_name, mt.mt_description ";
    }
    
    $sql = "SELECT
                mt.id, mt.mt_name, mt.mt_description
            FROM 
                measure_types mt {$terms} 
                {$groupBy}
            ORDER BY mt_name";
    try {
        $res = $conn->prepare($sql);
        if ($id) {
            $res->bindParam(':id', $id); 
        }
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            if ($id)
                return $data[0];
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}


/**
 * getMeasureUnits
 * Retorna a listagem de unidades de medida ou de uma medida específica caso o id seja informado
 * Também pode ser filtrado pelo tipo de medida
 * @param \PDO $conn
 * @param int|null $id
 * @param int|null $type : id do tipo de medida
 * @param bool|null $onlyBaseUnit
 * 
 * @return array
 */
function getMeasureUnits(\PDO $conn, ?int $id = null, ?int $type = null, ?bool $onlyBaseUnit = false): array
{

    $terms = ($id ? " WHERE id = :id " : '');

    if (!$id && $type !== null) {
        $terms .= " WHERE type_id = :type ";
    }

    if (!$id && $onlyBaseUnit == true) {

        $terms .= ($terms ? " AND " : " WHERE ");
        $terms .= " equity_factor = 1 ";
    }
    
    $sql = "SELECT * FROM measure_units {$terms} ORDER BY unit_abbrev";
    try {
        $res = $conn->prepare($sql);
        if ($id) {
            $res->bindParam(':id', $id); 
        } elseif ($type !== null) {
            $res->bindParam(':type', $type); 
        }
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            if ($id || ($type !== null && $onlyBaseUnit == true))
                return $data[0];
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}




/**
 * renderMeasureUnitsByType
 * Retorna um conjuntos de tags span com as unidades de medida de um tipo informado
 *
 * @param \PDO $conn
 * @param int $unitType
 * 
 * @return string
 */
function renderMeasureUnitsByType(\PDO $conn, int $unitType): string
{
    $html = "";
    $newUnitArray = [];
    $units = getMeasureUnits($conn, null, $unitType);

    foreach ($units as $unit) {
        /* Adcionando uma coluna com o valor para ordenação */    
        if ($unit['operation'] == '/') {
            $unit['pos_value'] = (1 / $unit['equity_factor']);
        } elseif ($unit['operation'] == '*') {
            $unit['pos_value'] = (1 * $unit['equity_factor']);
        } else {
            $unit['pos_value'] = 1;
        }

        /* Novo array com o campo de valor do posicionamento */
        $newUnitArray[] = $unit; 
    }

    /* Ordena o array pela coluna específica */
    $pos = array_column($newUnitArray, 'pos_value');
    array_multisort($pos, SORT_ASC, $newUnitArray);

    $i = 0;
    foreach ($newUnitArray as $unit) {

        $signal = "";
        if ($i < (count($newUnitArray) -1)) {
            $signal = ($unit['pos_value'] < 1 ? '<i class="fas fa-less-than"></i>' : '<i class="fas fa-greater-than"></i>');
        }
        $color = ($unit['equity_factor'] == 1 ? 'warning' : 'info');
        $title = ($unit['equity_factor'] == 1 ? TRANS('REFERENCE_BASE') : "");

        $html .= '<span title="'.$title.'" class="badge badge-'.$color.' p-2 m-2 mb-4">'.$unit['unit_abbrev'].'</span>'. $signal;
        $i++;
    }

    return $html;

}


function calcUnitAbsValue (\PDO $conn, int $unit_id, float $value) : float
{
    $units = getMeasureUnits($conn, $unit_id);

    if ($units['operation'] == '/') {
        return ($value / $units['equity_factor']);
    }
    
    if ($units['operation'] == '*') {
        return ($value * $units['equity_factor']);
    } 
        
    return $value;
}


/**
 * setModelSpecsAbsValues
 * Grava o valor absoluto de cada característica do modelo - campo abs_value
 *
 * @param \PDO $conn
 * @param int $model_id
 * 
 * @return bool
 */
function setModelSpecsAbsValues (\PDO $conn, int $model_id): bool
{
    $specs = getModelSpecs($conn, $model_id);
    
    foreach ($specs as $spec) {
        $units = getMeasureUnits($conn, $spec['unit_id']);

        if ($units['operation'] == '/') {
            $abs_value = ($spec['spec_value'] / $units['equity_factor']);
        }
        elseif ($units['operation'] == '*') {
            $abs_value = ($spec['spec_value'] * $units['equity_factor']);
        } else {
            $abs_value = $spec['spec_value'];
        }

        $sql = "UPDATE model_x_specs
                SET abs_value = :abs_value
                WHERE id = :id";
        
        try {
            $res = $conn->prepare($sql);
            $res->bindParam(':abs_value', $abs_value);
            $res->bindParam(':id', $spec['spec_id']);
            $res->execute();
            
        } catch (Exception $e) {
            return false;
        }
    }

    return true;

}


/**
 * getModelsBySpecUnit
 * Retorna todos os modelos que possuem uma determinada unidade de medida
 *
 * @param \PDO $conn
 * @param int $unit_id
 * 
 * @return array
 */
function getModelsBySpecUnit (\PDO $conn, int $unit_id): array
{
    $sql = "SELECT DISTINCT model_id FROM model_x_specs WHERE measure_unit_id = :unit_id";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':unit_id', $unit_id);
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return [];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}



/**
 * getMeasureTypeByMeasureUnit
 * Retorna o tipo de medida de uma unidade de medida fornecida
 *
 * @param \PDO $conn
 * @param int $unit_id
 * 
 * @return int
 */
function getMeasureTypeByMeasureUnit (\PDO $conn, int $unit_id): int
{
    $sql = "SELECT DISTINCT(type_id) FROM measure_units WHERE id = :unit_id";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':unit_id', $unit_id);
        $res->execute();
        if ($res->rowCount()) {
            $row = $res->fetch();
            return $row['type_id'];
        }
        return 0;
    } catch (Exception $e) {
        echo $e->getMessage();
        return 0;
    }
}



/**
 * modelHasAttribute
 * Retorna se um modelo atende às especificações de um atributo fornecido em um valor para comparação
 *
 * @param \PDO $conn
 * @param int $model_id
 * @param int $measure_unit_id
 * @param string $operation
 * @param float $comparison_value
 * 
 * @return bool
 */
function modelHasAttribute (\PDO $conn, int $model_id, int $measure_unit_id, string $operation, float $comparison_value): bool
{
    
    /* Gerar o valor absoluto a partir a measure_unit_id e do comparison_value */
    $abs_value = calcUnitAbsValue($conn, $measure_unit_id, $comparison_value);
    $measure_type = getMeasureTypeByMeasureUnit($conn, $measure_unit_id);

    $sql = "SELECT m.id FROM model_x_specs m, measure_units u 
            WHERE 
                m.model_id = :model_id AND 
                u.id = m.measure_unit_id AND
                u.type_id = {$measure_type} AND
                m.abs_value {$operation} :abs_value";
    
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':model_id', $model_id);
        $res->bindParam(':abs_value', $abs_value);

        $res->execute();
        if ($res->rowCount()) {
            return true;
        }
        return false;
    } catch (Exception $e) {
        echo $e->getMessage();
        return false;
    }
}



/**
 * getModelSpecs
 * Retorna as características existentes para o tipo de modelo informado
 * @param \PDO $conn
 * @param int $modelId
 * 
 * @return array
 */
function getModelSpecs(\PDO $conn, int $modelId): array
{
    $sql = "SELECT
                spec.id as spec_id, spec.spec_value, spec.abs_value,
                mt.mt_name, mt.id as type_id,
                mu.unit_name, mu.unit_abbrev, mu.id as unit_id
            FROM 
                model_x_specs spec,
                measure_types mt,
                measure_units mu
            WHERE 
                mu.id = spec.measure_unit_id AND
                mu.type_id = mt.id AND
                spec.model_id = :modelId
            ORDER BY mt_name";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':modelId', $modelId);
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}


/**
 * getAssetsCategories
 * Retorna as categorias possíveis para os tipos de ativos ou uma categoria específica caso o id seja informado
 * @param \PDO $conn
 * @param int|null $id
 * 
 * @return array
 */
function getAssetsCategories(\PDO $conn, ?int $id = null): array
{
    $terms = ($id ? " WHERE id = :id " : '');
    $sql = "SELECT * FROM assets_categories {$terms} ORDER BY cat_name";
    try {
        $res = $conn->prepare($sql);
        if ($id) {
            $res->bindParam(':id', $id); 
        }
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            if ($id)
                return $data[0];
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}



/**
 * getAssetsTypes
 * Retorna os tipos de ativos cadastrados - ou um tipo específico caso o id seja informado
 * Tipos para filtro: (1 - não são partes de outros tipos de ativos | 2 - podem ser partes de outros tipos de ativos)
 * 
 * @param \PDO $conn
 * @param int|null $id
 * @param int|null $type (1 - não são partes de outros tipos de ativos | 2 - podem ser partes de outros tipos de ativos)
 * @param array|null $category Categorias para filtro
 * @param int|null $inProfile Filtra a partir do perfil de campos para cadastro de ativos
 * 
 * @return array
 */
// function getAssetsTypes(\PDO $conn, ?int $id = null, ?int $type = null, ?int $category = null, ?int $inProfile = null, ?bool $andHasProfile = null): array
function getAssetsTypes(\PDO $conn, ?int $id = null, ?int $type = null, ?array $category = null, ?int $inProfile = null, ?bool $andHasProfile = null): array
{
    
    /**
     * $type 1: ativos que não podem ser agregados a outros
     * $type 2: ativos que podem ser agregados a outros ativos (partes internas)
     * $inProfile null: desconsidera o filtro
     * $inProfile 0: ativos que não estão vinculados a nenhum perfil - não possuem um perfil - is null
     * $inProfile id: ativos que estão vinculados a um perfil específico
     */

    $terms = ($id ? " WHERE t.tipo_cod = :id " : '');

    if (!$id) {

        if ($category !== null && !empty($category)) {
            $terms .= ($terms ? " AND " : " WHERE ");

            $category = implode(",", $category);
            
            // $terms .= " c.id = " . $category;
            $terms .= " c.id IN (" . $category . ")";
        }

        if ($inProfile !== null) {
            $terms .= ($terms ? " AND " : " WHERE ");
            
            if ($andHasProfile === true) {
                $terms .= " (pt.profile_id = " . $inProfile . "  )";
            } elseif ($andHasProfile === false) {
                $terms .= " (pt.profile_id = " . $inProfile . " OR pt.profile_id IS NULL )";
            } else {
                $terms .= " pt.profile_id = " . $inProfile;
            }


            // if ($inProfile == 0) {
            //     $terms .= " pt.profile_id IS NULL ";
            // } else {
            //     $terms .= " pt.profile_id = " . $inProfile;
            // }
        } elseif ($andHasProfile === true) {
            $terms .= ($terms ? " AND " : " WHERE ");
            $terms .= " pt.profile_id IS NOT NULL ";
        } elseif ($andHasProfile === false) {
            $terms .= ($terms ? " AND " : " WHERE ");
            $terms .= " pt.profile_id IS NULL ";
        }
    }

    $sql = "SELECT 
                t.tipo_cod, t.tipo_nome, t.tipo_categoria, 
                c.cat_name, c.id, c.cat_description, 
                p.profile_name, p.id as profile_id
                FROM 
                    tipo_equip t 
                LEFT JOIN assets_categories c ON c.id = t.tipo_categoria
                LEFT JOIN profiles_x_assets_types pt ON pt.asset_type_id = t.tipo_cod
                LEFT JOIN assets_fields_profiles p ON p.id = pt.profile_id
                {$terms}
                ORDER BY 
                    t.tipo_nome, c.cat_name
                ";
    try {
        $res = $conn->prepare($sql);
        if ($id) {
            $res->bindParam(':id', $id); 
        }
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            if ($id)
                return $data[0];
            return $data;
        }
        return [];
        // return ["sql" => $sql];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage(), "sql" => $sql];
    }
}


/**
 * canBeChild
 * Retorna se um ativo qualquer pode ser filho de algum ativo
 * @param \PDO $conn
 * @param int $asset_id
 * 
 * @return bool
 */
function canBeChild (\PDO $conn, int $asset_id): bool
{
    $sql = "SELECT id FROM assets_types_part_of 
            WHERE
                child_id = :asset_id
            ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':asset_id', $asset_id);
        $res->execute();
        if ($res->rowCount()) {
            return true;
        }
        return false;
    } 
    catch (Exception $e) {
        return ['error' => $e->getMessage(), "sql" => $sql];
    }
}

/**
 * getAssetsTypesPossibleParents
 * Retorna a listagem de tipos de ativos que podem ser pais do tipo informado
 * @param \PDO $conn
 * @param int $id
 * 
 * @return array
 */
function getAssetsTypesPossibleParents (\PDO $conn, int $id): array
{
    $sql = "SELECT 
                t.tipo_cod, t.tipo_nome,
                p.parent_id, p.child_id 
            FROM 
                assets_types_part_of p
            LEFT JOIN tipo_equip t ON p.parent_id = t.tipo_cod
            WHERE 
                p.child_id = :id
            ORDER BY 
                t.tipo_nome
            ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':id', $id); 
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}


/**
 * getAssetsTypesByProfile
 * Retorna a listagem de tipos (asset_type_id) de tipos de ativos que estão vinculados a um perfil específico
 * @param \PDO $conn
 * @param int $profileId
 * 
 * @return array
 */
function getAssetsTypesByProfile (\PDO $conn, int $profileId): array
{
    $sql = "SELECT * FROM profiles_x_assets_types WHERE profile_id = :profileId";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':profileId', $profileId); 
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}



function getAssetsTypesPossibleChilds (\PDO $conn, int $id): array
{
    $sql = "SELECT 
                t.tipo_cod, t.tipo_nome
                -- p.parent_id, p.child_id 
            FROM 
                assets_types_part_of p
            LEFT JOIN tipo_equip t ON p.child_id = t.tipo_cod
            WHERE 
                p.parent_id = :id
            ORDER BY 
                t.tipo_nome
            ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':id', $id); 
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}



/**
 * getPossibleChildsFromManyAssetsTypes
 * Retorna a listagem de possíveis tipos de ativos filhos, normalizados, a partir de um array de tipos de ativos pais
 * @param \PDO $conn
 * @param array $assetTypes
 * 
 * @return array
 */
function getPossibleChildsFromManyAssetsTypes (\PDO $conn, array $assetTypes): array
{
    if (empty($assetTypes)){
        return [];
    }

    $data = [];
    $dataFiltered = [];

    /* Quantidade de tipos de ativos selecionados para o perfil */
    $countTypes = count($assetTypes);
    /* Definindo as variáveis dinâmicas como arrays */
    for ($i = 1; $i <= $countTypes; $i++){
        /* Será criado um array para cada tipo de ativo selecionado */
        ${'array'.$i} = [];
    }

    $i = 0;
    /* Cada array receberá a listagem de seus possíveis campos de configuração */
    foreach ($assetTypes as $type) {
        ${'array'.$i} = getAssetsTypesPossibleChilds($conn, $type);
        $i++;
    }

    /* Combinando todos os arrays */
    for ($i = 0; $i <= $countTypes; $i++){
        $data = array_merge($data, ${'array'.$i});
    }

    /* Removendo os valores repetidos */
    foreach ($data as $key => $value) {
        $dataFiltered = (in_array($value, $dataFiltered)) ? $dataFiltered : array_merge($dataFiltered, [$value]);
    }

    return $dataFiltered;
}



/**
 * getAssetTypesFromIds
 * Retorna a listagem de tipos de ativos a partir de uma string de IDs
 * @param \PDO $conn
 * @param string $ids
 * 
 * @return array
 */
function getAssetTypesFromIds(\PDO $conn, ?string $ids): array
{

    if (!$ids) {
        return [];
    }
    
    $sql = "SELECT 
                t.tipo_cod, t.tipo_nome, t.tipo_categoria, t.is_part_of, 
                c.cat_name, c.id, c.cat_description
                FROM 
                    tipo_equip t 
                LEFT JOIN assets_categories c ON c.id = t.tipo_categoria 
                WHERE t.tipo_cod IN ({$ids})
                ORDER BY 
                    t.tipo_nome, c.cat_name
                ";
    try {
        $res = $conn->prepare($sql);
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}




/**
 * getAssetsRequiredInfo
 * Retorna a lista dos campos do perfil: 0 para não obrigatório, 1 para obrigatório
 * @param \PDO $conn
 * @param int $profileId
 * 
 * @return array
 */
function getAssetsRequiredInfo (\PDO $conn, int $profileId): array
{
    
    $fields = [];
    
    $sql = "SELECT 
                *
            FROM 
                assets_fields_required
            WHERE 
                profile_id = :profileId ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':profileId', $profileId);
        $res->execute();

        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $fields[$row['field_name']] = $row['field_required'];
            }
            return $fields;
        }
        return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}


/**
 * getAssetsModels
 * Retorna a listagem de modelos de tipos de ativos com base nos parâmetros fornecidos
 * Pode ser filtrado por tipo de ativo e também pelo fabricante
 * @param \PDO $conn
 * @param int|null $modelId
 * @param int|null $assetTypeId
 * @param int|null $manufacturerId
 * 
 * @return array
 */
function getAssetsModels(\PDO $conn, ?int $modelId = null, ?int $assetTypeId = null, ?int $manufacturerId = null): array
{

    $terms = ($modelId ? " AND m.marc_cod = :modelId " : '');

    if (!$modelId) {
        $terms .= ($assetTypeId ? " AND t.tipo_cod = :assetTypeId " : '');
        $terms .= ($manufacturerId ? " AND m.marc_manufacturer = :manufacturerId " : '');
    }

    
    $sql = "SELECT 
                m.marc_cod as codigo, 
                m.marc_manufacturer as fabricante_cod,
                m.marc_nome as modelo, 
                t.tipo_nome as tipo, 
                t.tipo_cod as tipo_cod,
                f.fab_nome as fabricante
            FROM 
				tipo_equip as t , 
                marcas_comp as m LEFT JOIN fabricantes f on f.fab_cod = m.marc_manufacturer
            WHERE 
                m.marc_tipo = t.tipo_cod {$terms}
            ORDER BY m.marc_nome, t.tipo_nome";

    try {
        $res = $conn->prepare($sql);
        if ($modelId) {
            $res->bindParam(':modelId', $modelId);
        }
        if ($assetTypeId) {
            $res->bindParam(':assetTypeId', $assetTypeId);
        }
        if ($manufacturerId) {
            $res->bindParam(':manufacturerId', $manufacturerId);
        }
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            if ($modelId)
                return $data[0];
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
    
    
}



/**
 * getAreasToOpen
 * Retorna um array com as informacoes das areas possiveis de receberem chamados do usuario logado
 * sis_id , sistema
 * @param PDO $conn
 * @return array
 */
function getAreasToOpen(\PDO $conn, string $userAreas): array
{
    if (empty($userAreas))
        return [];
    $userAreas = filter_var($userAreas, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    $table = getTableCompat($conn);
    $sql = "SELECT s.sis_id, s.sistema 
            FROM sistemas s, {$table} a 
            WHERE
                s.sis_status = 1  AND
                s.sis_atende = 1  AND 
                s.sis_id = a.area AND 
                a.area_abrechamado IN (:userAreas) 
            GROUP BY 
                sis_id, sistema 
            ORDER BY sistema";

    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':userAreas', $userAreas, PDO::PARAM_STR);
        $res->execute();

        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return [];
    }
}



/**
 * setBasicProfile
 * Retorna um array com as informacoes padrão do perfil básico para cadastro de ativos
 *
 * @return array
 */
function setBasicProfile(): array
{
    return [
        'id' => '0',
        'profile_name' => 'Basic',
        'asset_type' => '1',
        'manufacturer' => '1',
        'model' => '1',
        'department' => '1',
        'asset_unit' => '1',
        'asset_tag' => '1',
        'serial_number' => '1',
        'part_number' => '0',
        'situation' => '1',
        'net_name' => '0',
        'invoice_number' => '1',
        'cost_center' => '0',
        'price' => '1',
        'buy_date' => '1',
        'supplier' => '1',
        'assistance_type' => '0',
        'warranty_type' => '1',
        'warranty_time' => '1',
        'extra_info' => '1',
        'field_specs_ids' => '',
        'field_custom_ids' => ''
    ];
}


/* Define os campos básicos obrigatórios para cadastro de ativos */
function setBasicRequired(): array
{
    return [
        'asset_type' => 1,
        'manufacturer' => 1,
        'model' => 1,
        'asset_unit' => '1',
        'department' => 1,
        'asset_tag' => '1'
    ];
}


/**
 * getAssetsProfiles
 * Retorna a listagem de perfis de ativos ou um perfil específico caso o id seja informado
 * @param \PDO $conn
 * @param int|null $id
 * @param int|null $assetTypeId : filtra para encontrar o perfil vinculado ao tipo de ativo
 * 
 * @return array
 */
function getAssetsProfiles (\PDO $conn, ?int $id = null, ?int $assetTypeId = null): array
{
    $terms = ($id ? " WHERE id = :id " : '');

    if (!$id) {

        if ($assetTypeId) {
            $terms .= "LEFT JOIN profiles_x_assets_types pa ON 
                        pa.profile_id = p.id 
                        WHERE 
                            pa.asset_type_id = :assetTypeId 
                            ";
        }
    }

    $sql = "SELECT p.* 
            FROM assets_fields_profiles p 
             
            {$terms} ORDER BY profile_name";
    $res = $conn->prepare($sql);
    
    try {
        if ($id) {
            $res->bindParam(':id', $id); 
        } elseif ($assetTypeId) {
            $res->bindParam(':assetTypeId', $assetTypeId); 
        }
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            if ($id)
                return $data[0];
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}




/**
 * getOperationalStates
 * Retorna a listagem de situações operacionais ou uma situação específica caso o id seja informado
 * @param \PDO $conn
 * @param int|null $id
 * 
 * @return array
 */
function getOperationalStates(\PDO $conn, ?int $id = null): array
{
    
    $terms = ($id ? " WHERE situac_cod = :id " : '');

    $sql = "SELECT * FROM situacao {$terms} ORDER BY situac_nome";
    try {
        $res = $conn->prepare($sql);
        if ($id) {
            $res->bindParam(':id', $id);
        }
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            if ($id)
                return $data[0];
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}


/**
 * getSuppliers
 * Retorna a listagem de fornecedores ou um fornecedor específico
 * @param \PDO $conn
 * @param int|null $id
 * 
 * @return array
 */
function getSuppliers(\PDO $conn, ?int $id = null): array
{
    $terms = ($id ? " WHERE forn_cod = :id " : '');

    $sql = "SELECT * FROM fornecedores {$terms} ORDER BY forn_nome";
    try {
        $res = $conn->prepare($sql);
        if ($id) {
            $res->bindParam(':id', $id);
        }
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            if ($id)
                return $data[0];
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}



/**
 * getAssistances
 * Retorna a listagem de assistências ou uma assistência em específico
 * @param \PDO $conn
 * @param int|null $id
 * 
 * @return array
 */
function getAssistancesTypes(\PDO $conn, ?int $id = null): array
{
    $terms = ($id ? " WHERE assist_cod = :id " : '');

    $sql = "SELECT * FROM assistencia {$terms} ORDER BY assist_desc";
    try {
        $res = $conn->prepare($sql);
        if ($id) {
            $res->bindParam(':id', $id);
        }
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            if ($id)
                return $data[0];
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}


/**
 * getWarrantiesTypes
 * Retorna a listagem de tipos de garantias ou um tipo de garantia específico
 *
 * @param \PDO $conn
 * @param int|null $id
 * 
 * @return array
 */
function getWarrantiesTypes(\PDO $conn, ?int $id = null): array
{
    $terms = ($id ? " WHERE tipo_garant_cod = :id " : '');

    $sql = "SELECT * FROM tipo_garantia {$terms} ORDER BY tipo_garant_nome";
    try {
        $res = $conn->prepare($sql);
        if ($id) {
            $res->bindParam(':id', $id);
        }
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            if ($id)
                return $data[0];
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}



/**
 * getWarrantiesTimes
 * Retorna os tempos possíveis para garantias ou um registro específico
 * @param \PDO $conn
 * @param int|null $id
 * 
 * @return array
 */
function getWarrantiesTimes(\PDO $conn, ?int $id = null): array
{
    $terms = ($id ? " WHERE tempo_cod = :id " : '');

    $sql = "SELECT * FROM tempo_garantia {$terms} ORDER BY tempo_meses";
    try {
        $res = $conn->prepare($sql);
        if ($id) {
            $res->bindParam(':id', $id);
        }
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            if ($id)
                return $data[0];
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}



/**
 * getAssetSpecs
 * Retorna um array com os IDs e outras informações dos modelos de todos os tipos de ativos que o compõe
 * @param \PDO $conn
 * @param int $assetId
 * @param bool|null $isDigital
 * @param bool|null $isProduct
 * 
 * @return array
 */
function getAssetSpecs(
    \PDO $conn, 
    int $assetId, 
    ?bool $hasTag = null, 
    ?bool $isDigital = null, 
    ?bool $isProduct = null
): array
{
    $terms = '';
    if ($hasTag !== null) {
        $terms = ($hasTag ? " a.asset_spec_tagged_id IS NOT NULL AND" : " a.asset_spec_tagged_id IS NULL AND");
    }

    if ($isDigital !== null) {

        $isDigital = ($isDigital == true || $isDigital == 1 ? 1 : 0);
        $terms.= (strlen((string)$terms) ? " AND cat.cat_is_digital = {$isDigital} AND " : " cat.cat_is_digital = {$isDigital} AND ");
    }

    if ($isProduct !== null) {

        $isProduct = ($isProduct == true || $isProduct == 1 ? 1 : 0);
        $terms.= (strlen((string)$terms) ? " AND cat.cat_is_product = {$isProduct} AND " : " cat.cat_is_product = {$isProduct} AND ");
    }
    
    
    $sql = "SELECT t.*, m.*, a.*, e.comp_inst, e.comp_inv, cat.* 
            FROM 
                ((tipo_equip t, marcas_comp m, assets_x_specs a
            LEFT JOIN 
                equipamentos e on e.comp_cod = a.asset_spec_tagged_id) 
            LEFT JOIN 
                assets_categories cat ON cat.id = t.tipo_categoria) 
            WHERE 
                {$terms}
                a.asset_id = :id AND
                a.asset_spec_id = m.marc_cod AND
                m.marc_tipo = t.tipo_cod
            ORDER BY
                t.tipo_nome, m.marc_nome    
            ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':id', $assetId);
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}



/**
 * getAssetCategoryInfo
 * Retorna array com as informações sobre a categoria do ativo informado
 *
 * @param \PDO $conn
 * @param int $assetId
 * 
 * @return array
 */
function getAssetCategoryInfo(\PDO $conn, int $assetId): array
{
    $sql = "SELECT 
                cat.*
            FROM
                equipamentos a, tipo_equip t
                LEFT JOIN assets_categories cat ON cat.id = t.tipo_categoria

            WHERE
                a.comp_tipo_equip = t.tipo_cod AND 
                a.comp_cod = :id
            ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':id', $assetId);
        $res->execute();
        if ($res->rowCount()) {
            return $res->fetch();
        }
        return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}


/**
 * getAssetParentId
 * Retorna o registro com ID, etiqueta e codigo de unidade do ativo pai de um ativo informado - caso não exista retorna vazio
 * @param \PDO $conn
 * @param int $assetId
 * 
 * @return array
 */
function getAssetParentId(\PDO $conn, int $assetId) :array
{
    $sql = "SELECT 
                a.asset_id, e.comp_inv, e.comp_inst
            FROM 
                assets_x_specs a, equipamentos e
            WHERE 
                a.asset_spec_tagged_id = :id AND a.asset_id = e.comp_cod ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':id', $assetId);
        $res->execute();
        if ($res->rowCount()) {
            return $res->fetch();
        }
        return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}



/**
 * isAssetModelFreeToLink
 * Retorna se existem ativos com o modelo informado e se estão disponíveis para serem vinculados a outro ativo pai
 *
 * @param \PDO $conn
 * @param int $modelId
 * 
 * @return bool
 */
function isAssetModelFreeToLink(\PDO $conn, int $modelId) :bool
{
    $sql = "SELECT 
                comp_cod
            FROM 
                equipamentos
            WHERE 
                comp_marca = :id ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':id', $modelId);
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $sql = "SELECT 
                            asset_id
                        FROM 
                            assets_x_specs
                        WHERE 
                            asset_spec_tagged_id = :id ";
                $res = $conn->prepare($sql);
                $res->bindParam(':id', $row['comp_cod']);
                $res->execute();
                
                if (!$res->rowCount()) {
                    /* Se pelo menos um dos ativos nao está vinculado, então retorna true */
                    return true;
                }
            }
        }
        /* Não existe ativo cadastrado com o modelo informado */
        return false;
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}



/**
 * getAssetIdFromTag
 * Retorna o ID do ativo a partir do código da unidade e da etiqueta
 *
 * @param \PDO $conn
 * @param int $unit
 * @param string $tag
 * 
 * @return int|null
 */
function getAssetIdFromTag(\PDO $conn, int $unit, string $tag): ?int
{
    $sql = "SELECT 
                comp_cod
            FROM 
                equipamentos
            WHERE 
                comp_inst = :unit AND comp_inv = :tag ";

    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':unit', $unit);
        $res->bindParam(':tag', $tag);
        $res->execute();
        if ($res->rowCount()) {
            return $res->fetch()['comp_cod'];
        }
        return null;
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}



/**
 * assetHasParent
 * Retorna se o ativo informado possui ativo pai
 * Indica que o ativo é filho de outro ativo
 *
 * @param \PDO $conn
 * @param int $asset_id
 * 
 * @return bool
 */
function assetHasParent(\PDO $conn, int $asset_id): bool
{
    $sql = "SELECT 
                asset_id
            FROM 
                assets_x_specs
            WHERE 
                asset_spec_tagged_id = :id ";
    
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':id', $asset_id);
        $res->execute();
        if ($res->rowCount()) {
            return true;
        }
        return false;
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}



/**
 * getAssetDescendants
 * Retorna um array com os ativos descendentes do ativo principal - até a quinta geração
 * @param \PDO $conn
 * @param int $asset_id
 * 
 * @return array
 */
function getAssetDescendants (\PDO $conn, int $asset_id) :array
{
    /* Colocar em um array flat todos os ativos filhos e filhos de filhos do ativo principal - até a quinta geração */

    /* Primeira etapa - obter os primeiros filhos */
    $sql = "SELECT * FROM assets_x_specs WHERE asset_id = :id AND asset_spec_tagged_id IS NOT NULL";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':id', $asset_id);
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }

            /* Segunda etapa - obter os filhos dos filhos */
            foreach ($data as $row) {
                $sql = "SELECT * FROM assets_x_specs WHERE asset_id = :id AND asset_spec_tagged_id IS NOT NULL";
                $res = $conn->prepare($sql);
                $res->bindParam(':id', $row['asset_spec_tagged_id']);
                $res->execute();
                if ($res->rowCount()) {
                    foreach ($res->fetchAll() as $row) {
                        $data[] = $row;
                    }

                    /* Terceira etapa - obter os filhos dos filhos dos filhos */
                    foreach ($data as $row) {
                        $sql = "SELECT * FROM assets_x_specs WHERE asset_id = :id AND asset_spec_tagged_id IS NOT NULL";
                        $res = $conn->prepare($sql);
                        $res->bindParam(':id', $row['asset_spec_tagged_id']);
                        $res->execute();
                        if ($res->rowCount()) {
                            foreach ($res->fetchAll() as $row) {
                                $data[] = $row;
                            }

                            /* Quarto etapa - obter os filhos dos filhos dos filhos dos filhos */
                            foreach ($data as $row) {
                                $sql = "SELECT * FROM assets_x_specs WHERE asset_id = :id AND asset_spec_tagged_id IS NOT NULL";
                                $res = $conn->prepare($sql);
                                $res->bindParam(':id', $row['asset_spec_tagged_id']);
                                $res->execute();
                                if ($res->rowCount()) {
                                    foreach ($res->fetchAll() as $row) {
                                        $data[] = $row;
                                    }

                                    /* Quinta etapa - obter os filhos dos filhos dos filhos dos filhos dos filhos */
                                    foreach ($data as $row) {
                                        $sql = "SELECT * FROM assets_x_specs WHERE asset_id = :id AND asset_spec_tagged_id IS NOT NULL";
                                        $res = $conn->prepare($sql);
                                        $res->bindParam(':id', $row['asset_spec_tagged_id']);
                                        $res->execute();
                                        if ($res->rowCount()) {
                                            foreach ($res->fetchAll() as $row) {
                                                $data[] = $row;
                                            }
                                        }
                                    }

                                }
                            }

                        }
                    }
                }
            }

            /* Retorna o array final com os filhos até a quinta geração (se existirem) */
            return array_unique($data, SORT_REGULAR);

        }
        else {
            return [];
        }
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}






/**
 * modelHasSavedSpecs
 * Retorna se o modelo informado possui especificações salvas
 *
 * @param \PDO $conn
 * @param int $modelId
 * 
 * @return bool
 */
function modelHasSavedSpecs (\PDO $conn, int $modelId): bool 
{
    $sql = "SELECT id FROM model_x_child_models WHERE model_id = :id";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':id', $modelId);
        $res->execute();
        if ($res->rowCount()) {
            return true;
        }
        return false;
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}


/**
 * getSavedSpecs
 * Retorna a listagem de modelos filhos de um modelo informado
 *
 * @param \PDO $conn
 * @param int $modelId
 * 
 * @return array
 */
function getSavedSpecs (\PDO $conn, int $modelId): array
{
    $sql = "SELECT 
                id,
                model_id,
                model_child_id
            FROM 
                model_x_child_models
            WHERE 
                model_id = :id";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':id', $modelId);
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}




/**
 * updateAssetDepartment
 * Faz o update do departamento para o ativo informado
 *
 * @param \PDO $conn
 * @param int $assetId
 * @param int $departmentId
 * 
 * @return bool
 */
function updateAssetDepartment(\PDO $conn, int $assetId, int $departmentId): bool
{
    $sql = "UPDATE equipamentos SET comp_local = :dep WHERE comp_cod = :id";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':dep', $departmentId);
        $res->bindParam(':id', $assetId);
        $res->execute();
        return true;
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}




/**
 * insertNewDepartmentInHistory
 * Insere um novo departamento na tabela de histórico de departamentos para o ativo informado
 * Primeiro faz a checagem se o departamento atual já o departamento informado
 *
 * @param \PDO $conn
 * @param int $assetId
 * @param int $departmentId
 * @param int $userId
 * 
 * @return bool
 */
function insertNewDepartmentInHistory(\PDO $conn, int $assetId, int $departmentId, int $userId) :bool
{
    
    $sql = "SELECT 
                hist_cod 
            FROM 
                historico 
            WHERE 
                asset_id = :asset_id AND 
                hist_local = :department_id AND
                hist_cod = (SELECT MAX(hist_cod) FROM historico WHERE asset_id = :asset_id)";
    
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':asset_id', $assetId);
        $res->bindParam(':department_id', $departmentId);
        $res->execute();
        if ($res->rowCount()) {
            return true;
        }
        $sql = "INSERT INTO 
                        historico
                    (
                        asset_id,
                        hist_local,
                        hist_user
                    )
                    VALUES
                    (
                        :asset_id,
                        :department_id,
                        :user_id
                    )";
            try {
                $res = $conn->prepare($sql);
                $res->bindParam(':asset_id', $assetId);
                $res->bindParam(':department_id', $departmentId);
                $res->bindParam(':user_id', $userId);
                $res->execute();
                if ($res->rowCount()) {
                    return true;
                }
                return false;
            }
            catch (Exception $e) {
                return ['error' => $e->getMessage()];
            }
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
    
}



/**
 * insertNewAssetSpecChange
 * Insere um novo registro de log de alteração de especificação para o ativo informado
 *
 * @param \PDO $conn
 * @param int $asset_id
 * @param int $spec_id
 * @param int $user_id
 * 
 * @return bool
 */
function insertNewAssetSpecChange (\PDO $conn, int $asset_id, int $spec_id, string $action, int $user_id) :bool
{
    
    $actions = [
        'add',
        'remove'
    ];

    if (!in_array($action, $actions)) {
        echo "Only add or remove are allowed";
        return false;
    }

    $sql = "INSERT INTO 
                assets_x_specs_changes
            (
                asset_id,
                spec_id,
                action,
                user_id
            )
            VALUES
            (
                :asset_id,
                :spec_id,
                :action,
                :user_id
            )";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':asset_id', $asset_id);
        $res->bindParam(':spec_id', $spec_id);
        $res->bindParam(':action', $action);
        $res->bindParam(':user_id', $user_id);
        $res->execute();
        if ($res->rowCount()) {
            return true;
        }
        return false;
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}



/**
 * getAssetSpecsChanges
 * Retorna a listagem de regitros de modificações de especificações de um ativo informado
 *
 * @param \PDO $conn
 * @param int $asset_id
 * 
 * @return array
 */
function getAssetSpecsChanges (\PDO $conn, int $asset_id) :array
{
    $sql = "SELECT 
                a.id,
                a.asset_id,
                a.spec_id,
                a.updated_at,
                a.action,
                t.tipo_nome,
                f.fab_nome,
                m.marc_nome,
                u.nome 
            FROM 
                assets_x_specs_changes a, usuarios u, tipo_equip t, marcas_comp m, fabricantes f
            WHERE 
                a.asset_id = :asset_id AND 
                a.spec_id = m.marc_cod AND
                a.user_id = u.user_id AND 
                m.marc_tipo = t.tipo_cod AND 
                m.marc_manufacturer = f.fab_cod

            ORDER BY 
                a.updated_at DESC
                ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':asset_id', $asset_id);
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}


function getAssetDepartmentsChanges (\PDO $conn, int $asset_id): array
{
    $asset_info = getEquipmentInfo($conn, null, null, $asset_id);
    $asset_tag = $asset_info['comp_inv'];
    $asset_unit = $asset_info['comp_inst'];

    $data = [];

    /* A partir da versão 5 a consulta é apenas pelo asset_id */
    $sql = "SELECT 
                l.local,
                h.hist_data,
                u.nome
            FROM 
                historico h, usuarios u, localizacao l
            WHERE 
                h.asset_id = :asset_id AND
                h.hist_user = u.user_id AND
                h.hist_local = l.loc_id
            ORDER BY 
                h.hist_data DESC
        ";

    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':asset_id', $asset_id);
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            // return $data;
        }
        // return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }


    /* Até a versão 4 a consulta utiliza a etiqueta e a unidade do ativo */
    $sql = "SELECT 
                l.local,
                h.hist_data,
                u.nome
            FROM 
            localizacao l, historico h LEFT JOIN usuarios u ON u.user_id = h.hist_user
            WHERE 
                h.hist_inv = :asset_tag AND
                h.hist_inst = :asset_unit AND
                h.hist_local = l.loc_id
            ORDER BY 
                h.hist_data DESC
        ";

    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':asset_tag', $asset_tag);
        $res->bindParam(':asset_unit', $asset_unit);
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            // return $data;
        }
        // return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }

    return $data;
}



/**
 * getSpecsIdsFromAsset
 * Retona um array com apenas os IDs da especificação do ativo informado
 * Será utilizado para comparar as mudanças com base no array gerado antes e depois de qualquer edição
 *
 * @param \PDO $conn
 * @param int $assetId
 * 
 * @return array
 */
function getSpecsIdsFromAsset(\PDO $conn, int $assetId) :array
{
    $sql = "SELECT 
                asset_spec_id
            FROM 
                assets_x_specs
            WHERE 
                asset_id = :asset_id";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':asset_id', $assetId);
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row['asset_spec_id'];
            }
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}




/**
 * getAreaAllowedUnits
 * Retorna um array com a listagem de unidades que a área informada pode visualizar no módulo de inventário
 * @param \PDO $conn
 * @param int $area_id
 * @param int|null $client_id
 * 
 * @return array
 */
function getAreaAllowedUnits (\PDO $conn, int $area_id, ?int $client_id = null): array 
{
    
    $terms = "";
    if ($client_id) {
        $terms = " AND u.inst_client = :client_id ";
    }
    
    $sql = "SELECT 
                a.unit_id, u.inst_client
            FROM 
                areas_x_units a, instituicao u
            WHERE 
                a.area_id = :area_id AND 
                u.inst_cod = a.unit_id
                {$terms}
                ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':area_id', $area_id);
        if ($client_id) {
            $res->bindParam(':client_id', $client_id);
        }
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}



/**
 * getAreaAllowedUnitsNames
 * Retona a listagem com os nomes dos clientes e nomes das unidades permitidas para serem visualizadas pela a área informada
 * @param \PDO $conn
 * @param int $area_id
 * @param int|null $client_id
 * 
 * @return array
 */
function getAreaAllowedUnitsNames (\PDO $conn, int $area_id, ?int $client_id = null): array 
{
    
    $terms = "";
    if ($client_id) {
        $terms = " AND u.inst_client = :client_id ";
    }
    
    $sql = "SELECT 
                c.nickname, u.inst_nome
            FROM 
                areas_x_units a, instituicao u, clients c
            WHERE 
                a.area_id = :area_id AND 
                u.inst_cod = a.unit_id AND 
                c.id = u.inst_client
                {$terms}
                ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':area_id', $area_id);
        if ($client_id) {
            $res->bindParam(':client_id', $client_id);
        }
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}


/**
 * getAreaAllowedClients
 * Retorna um array com a listagem de clientes que a área informada pode visualizar no módulo de inventário
 * @param \PDO $conn
 * @param int $area_id
 * 
 * @return array
 */
function getAreaAllowedClients (\PDO $conn, int $area_id): array
{
    $sql = "SELECT 
            DISTINCT(u.inst_client), c.nickname
        FROM 
            areas_x_units a, instituicao u, clients c
        WHERE 
            a.area_id = :area_id AND 
            u.inst_cod = a.unit_id AND 
            u.inst_client = c.id
                ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':area_id', $area_id);
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}





/**
 * getTicketWorkers
 * Retorna um array com os funcionários vinculados à ocorrência
 * Índices retornados: user_id|main_worker(0/1)|nome|email
 * @param \PDO $conn
 * @param int $ticket
 * @param int|null $type (1 - main worker, 2 - auxiliar - null todos)
 * 
 * @return array
 */
function getTicketWorkers(\PDO $conn, int $ticket, ?int $type = null): array
{
    $types = [
        '1' => '1',
        '2' => '0'
    ];

    $terms = '';
    if (array_key_exists($type, $types)) {
        $terms = "AND txw.main_worker = {$types[$type]} ";
    }
    
    $sql = "SELECT 
                txw.user_id, txw.main_worker, u.nome, u.email
            FROM 
                ticket_x_workers txw, usuarios u
            WHERE 
                txw.user_id = u.user_id AND
                txw.ticket = :ticket
                {$terms}";

    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':ticket', $ticket, PDO::PARAM_INT);
        $res->execute();

        if ($res->rowCount()) {
            
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            if ($type && $type == 1) {
                return $data[0];
            }
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return [];
    }
}


/**
 * getScheduledTicketsByWorker
 * Retorna os números dos chamados que o operador informado está vinculado - não importa se ele é o principal ou auxiliar
 * O padrão é retornar apenas os agendados
 * @param \PDO $conn
 * @param int $user_id
 * @param bool $scheduled : se for falso, retorna apenas os não agendados, se for nulo retorna todos
 * 
 * @return array
 */
function getScheduledTicketsByWorker(\PDO $conn, int $user_id, ?bool $scheduled = true): array
{

    $terms = "AND o.oco_scheduled = 1";

    if ($scheduled !== null && !$scheduled) {
        $terms = "AND o.oco_scheduled = 0";
    } else {
        $terms = "";
    }
    
    
    
    $sql = "SELECT 
                txw.ticket
            FROM 
                ticket_x_workers txw, ocorrencias o
            WHERE 
                txw.user_id = :user_id AND
                txw.ticket = o.numero 
                {$terms}
                ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':user_id', $user_id);
        $res->execute();

        if ($res->rowCount()) {
            
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return [];
    }
}


/**
 * getDefaultAreaToOpen
 * Retorna o id da area padrão para receber chamados da área informada por $areaId
 * @param \PDO $conn
 * @param int $areaId
 * 
 * @return int|null
 */
function getDefaultAreaToOpen(\PDO $conn, int $areaId): ?int
{
    $sql = "SELECT 
                area 
            FROM 
                areaxarea_abrechamado 
            WHERE 
                area_abrechamado = :areaId AND 
                default_receiver = 1 
            ";

    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':areaId', $areaId, PDO::PARAM_INT);
        $res->execute();
        if ($res->rowCount()) {
            $row = $res->fetch();
            return $row['area'];
        }
        return null;
    }
    catch (Exception $e) {
        //$exception .= "<hr>" . $e->getMessage();
        return null;
    }
}


/**
 * getIssuesByArea
 * Retorna um array com as informacoes dos tipos de problemas - listagem
 * keys: prob_id | problema | prob_area | prob_sla | prob_tipo_1, prob_tipo_2 | prob_tipo_3 | prob_descricao
 * @param PDO $conn
 * @param bool $all
 * @param int|null $areaId
 * @param int|null $showHidden : se estiver marcado como "0" não exibirá os tipos de problemas marcados como ocultos para a área
 * @param int|null $hasProfileForm
 * @return array
 */
function getIssuesByArea(\PDO $conn, bool $all = false, ?int $areaId = null, ?int $showHidden = 1, ?int $hasProfileForm = null): array
{
    $areaId = (isset($areaId) && filter_var($areaId, FILTER_VALIDATE_INT) ? $areaId : "");
    
    $terms = "";
    if (!empty($areaId)) {
        $terms = " (prob_area = :areaId OR prob_area IS NULL OR prob_area = '-1') AND prob_active = 1 ";
        if (!$showHidden) {
            $terms .= " AND (FIND_IN_SET('{$areaId}', prob_not_area) < 1 OR FIND_IN_SET('{$areaId}', prob_not_area) IS NULL ) ";
        }
    } else {
        if ($all) {
            $terms = " 1 = 1 ";
        } else {
            $terms = " (prob_area IS NULL OR prob_area = '-1') AND prob_active = 1 ";
        }
    }


    if ($hasProfileForm != null && $hasProfileForm == 1) {
        if (strlen((string)$terms)) {
            $terms .= " AND ";
        }
        $terms .= " prob_profile_form IS NOT NULL ";
    }

    $sql = "SELECT 
                MIN(prob_id) as prob_id, 
                MIN(prob_area) as prob_area, 
                MIN(prob_descricao) as prob_descricao, 
                problema, 
                prob_profile_form  
            FROM 
                problemas
            WHERE 
                {$terms}
            GROUP BY 
                problema, prob_profile_form
            ORDER BY
                problema";


    try {
        $res = $conn->prepare($sql);
        
        if (!empty($areaId)) {
            $res->bindParam(':areaId', $areaId, PDO::PARAM_INT);
        }
        $res->execute();

        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return [];
    }
}



/**
 * getIssuesByArea4
 * Retorna array com as informações dos tipos de problemas
 * Função específica para a nova versão de relacionamento NxN entre areas e tipos de problemas
 * keys: prob_id | problema | prob_area | prob_sla | prob_tipo_1, prob_tipo_2 | prob_tipo_3 | prob_descricao | prob_profile_form
 *
 * @param \PDO $conn
 * @param bool $all
 * @param int|null $areaId
 * @param int|null $showHidden : para exibir ou não os tipos de problemas marcados como exceção para a área informada
 * @param string|null $areasFromUser : Limita o retorno à tipos de problemas vinculados às áreas para as quais o usuário pode abrir chamados
 * @param int|null $hasProfileForm
 * 
 * @return array
 */
function getIssuesByArea4(
        \PDO $conn, 
        bool $all = false, 
        ?int $areaId = null, 
        ?int $showHidden = 1, 
        ?string $areasFromUser = null,
        ?int $hasProfileForm = null
): array
{
    $areaId = (isset($areaId) && $areaId != '-1' && filter_var($areaId, FILTER_VALIDATE_INT) ? $areaId : "");
    $areasToOpen = [];
    
    $terms = "";
    if (!empty($areaId)) {
        $terms = " (a.area_id = :areaId OR a.area_id IS NULL) AND p.prob_active = 1 ";
        if (!$showHidden) {
            $terms .= " AND (FIND_IN_SET('{$areaId}', prob_not_area) < 1 OR FIND_IN_SET('{$areaId}', prob_not_area) IS NULL ) ";
        }
    } else {
        if ($all) {
            $terms = " 1 = 1 ";
        } else {
            
            if ($areasFromUser) {
                $areasToOpen = getAreasToOpen($conn, $areasFromUser);
                if (count($areasToOpen)) {
                    $areas_ids = [];
                    foreach ($areasToOpen as $area) {
                        $areas_ids[] = $area['sis_id'];
                    }
                    $areas_ids = implode(',', $areas_ids);
                    
                    $terms = " (a.area_id IS NULL OR  a.area_id IN ({$areas_ids}) ) AND p.prob_active = 1 ";
                } else
                    $terms = " (a.area_id IS NULL) AND p.prob_active = 1 ";
            } else
                $terms = " (a.area_id IS NULL) AND p.prob_active = 1 ";
        }
    }

    if ($hasProfileForm != null && $hasProfileForm == 1) {
        if (strlen((string)$terms)) {
            $terms .= " AND ";
        }
        $terms .= " p.prob_profile_form IS NOT NULL ";
    }

    /* -- a.prob_id, a.area_id as prob_area, p.prob_descricao, p.problema */
    /* -- a.prob_id, a.area_id, p.prob_descricao, p.problema */
    $sql = "SELECT 
                
                a.prob_id, p.prob_descricao, p.problema, p.prob_profile_form
            FROM 
                problemas p, areas_x_issues a
            WHERE 
                p.prob_id = a.prob_id AND 
                {$terms}

            GROUP BY 
                
                a.prob_id, p.prob_descricao, p.problema, p.prob_profile_form
            
            ORDER BY
                problema";
    try {
        $res = $conn->prepare($sql);
        
        if (!empty($areaId)) {
            $res->bindParam(':areaId', $areaId, PDO::PARAM_INT);
        }
        $res->execute();

        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        // return array("sql" => $sql);
        return [];
    }
    catch (Exception $e) {
        echo $sql . "<hr>" . $e->getMessage();
        return [];
    }
}

/**
 * hiddenAreasByIssue
 * Retorna a listagem de areas que possuem o tipo de problema como oculto para utilização em chamados
 * @param \PDO $conn
 * @param int $issueId
 * 
 * @return array
 */
function hiddenAreasByIssue(\PDO $conn, int $issueId): array
{
    $areasArray = [];
    $data = [];
    $sql = "SELECT prob_not_area FROM problemas WHERE prob_id = :issueId AND prob_not_area IS NOT NULL ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':issueId', $issueId, PDO::PARAM_INT);
        $res->execute();
        if ($res->rowCount()) {
            $areasArray = explode(',', (string)$res->fetch()['prob_not_area']);

            foreach ($areasArray as $areaId) {
                $data[] = getAreaInfo($conn, $areaId);
            }
            return $data;
            
            // return $areasArray;
        }
        return [];
    }
    catch (Exception $e) {
        // $exception .= "<hr>" . $e->getMessage();
        return [];
    }
}


/**
 * getIssueDetailed
 * Retorna um array com as informacoes de Sla e categorias do tipo de problema informado - 
 * nomenclauras parecidas também são buscadas
 * keys: prob_id | problema | 
 * @param PDO $conn
 * @param int $id
 * @param ?int $areaId
 * @return array
 */
function getIssueDetailed(\PDO $conn, int $id, ?int $areaId = null): array
{
    $areaId = (isset($areaId) && $areaId != '-1' && filter_var($areaId, FILTER_VALIDATE_INT) ? $areaId : "");
    $termsIssueName = "";
    $terms = "";
    
    if (empty($id))
        return [] ;

    $sqlName = "SELECT problema FROM problemas WHERE prob_id = :id ";
    try {
        $resName = $conn->prepare($sqlName);
        $resName->bindParam(":id", $id, PDO::PARAM_INT);
        $resName->execute();
        if ($resName->rowCount()) {
            $issueName = $resName->fetch()['problema'];
            $termsIssueName = " AND lower(p.problema) LIKE (lower(:issueName)) ";
        } else {
            return [];
        }
    }
    catch (Exception $e) {
        echo $e->getMessage();
        return [];
    }
   
    if (!empty($areaId)) {
        $terms = " AND (ai.area_id = :areaId OR ai.area_id IS NULL) ";
    }
    
    $sql = "SELECT 
                p.prob_id, p.problema, sl.slas_desc, 
                pt1.probt1_desc, pt2.probt2_desc, pt3.probt3_desc
            FROM areas_x_issues ai, problemas as p 
            
            LEFT JOIN sla_solucao as sl on sl.slas_cod = p.prob_sla 
            LEFT JOIN prob_tipo_1 as pt1 on pt1.probt1_cod = p.prob_tipo_1 
            LEFT JOIN prob_tipo_2 as pt2 on pt2.probt2_cod = p.prob_tipo_2 
            LEFT JOIN prob_tipo_3 as pt3 on pt3.probt3_cod = p.prob_tipo_3 

            WHERE p.prob_id = ai.prob_id 
                {$termsIssueName} {$terms} 

            GROUP BY
                prob_id, problema, slas_desc, probt1_desc, probt2_desc, probt3_desc

            ORDER BY p.problema";
    try {
        $res = $conn->prepare($sql);
        
        if ((!empty($areaId))) {
            $res->bindParam(':areaId', $areaId, PDO::PARAM_INT);
        }
        $res->bindParam(':issueName', $issueName, PDO::PARAM_STR);
        $res->execute();

        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        echo $sql . "<hr>" . $e->getMessage();
        return [];
    }
}


/**
 * Retorna as informações do tipo de problema informado
 * @param PDO $conn
 * @param int $id
 * @return array
 */
function getIssueById(\PDO $conn, int $id):array
{
    $sql = "SELECT * FROM problemas WHERE prob_id =:id ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':id',$id);
        $res->execute();
        if ($res->rowCount()) {
            return $res->fetch();
        }
        return [];
    }
    catch (Exception $e) {
        return [];
    }
}


/**
 * Retorna se a área informada possui o tipo de problema com a nomenclatura do id informado
 * @param PDO $conn
 * @param int $areaId
 * @param int $probID
 * @return bool
 */
function areaHasIssueName(\PDO $conn, int $areaId, int $probId):bool
{
    $issueName = "";
    $sql = "SELECT problema FROM problemas WHERE prob_id =:probId ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':probId',$probId);
        $res->execute();
        if ($res->rowCount()) {
            $issueName = $res->fetch()['problema'];

            // $sql = "SELECT * FROM problemas WHERE problema = '{$issueName}' AND prob_area = :areaId ";
            $sql = "SELECT * FROM 
                        problemas p, areas_x_issues ai 
                    WHERE 
                        p.problema = '{$issueName}' AND ai.area_id = :areaId AND 
                        p.prob_id = ai.prob_id ";
            
            
            $res = $conn->prepare($sql);
            $res->bindParam(':areaId', $areaId);
            $res->execute();
            if ($res->rowCount()) {
                return true;
            }
            return false;
        }
        return false;
    }
    catch (Exception $e) {
        return false;
    }
}



/**
 * Retorna se o tipo de problema informado (de acordo com sua nomenclatura) existe desvinculado de áreas de atendimento
 * @param PDO $conn
 * @param int $probID
 * @return bool
 */
function issueFreeFromArea(\PDO $conn, int $probId):bool
{
    $issueName = "";
    $sql = "SELECT problema FROM problemas WHERE prob_id =:probId ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':probId',$probId);
        $res->execute();
        if ($res->rowCount()) {
            $issueName = $res->fetch()['problema'];

            $sql = "SELECT * FROM problemas p, areas_x_issues ai 
                    WHERE 
                        p.problema = '{$issueName}' AND (ai.area_id IS NULL)
                        AND p.prob_id = ai.prob_id ";
            $res = $conn->prepare($sql);
            $res->execute();
            if ($res->rowCount()) {
                return true;
            }
            return false;
        }
        return false;
    }
    catch (Exception $e) {
        return false;
    }
}



/**
 * Retorna a descrição do tipo de problema
 * @param PDO $conn
 * @param int $id
 * @return string
 */
function issueDescription(\PDO $conn, int $id):string
{
    $sql = "SELECT prob_descricao FROM problemas WHERE prob_id =:id ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':id',$id);
        $res->execute();
        if ($res->rowCount()) {
            return $res->fetch()['prob_descricao'];
        }
        return '';
    }
    catch (Exception $e) {
        return '';
    }
}



/**
 * getAreasByIssue
 * Retorna um array com as informações das áreas de atendimento
 * vinculadas ao tipo de problema informado (via id) - Nova arquitetura NxN para 
 * areas x tipos de problemas: areas_x_issues
 *
 * @param \PDO $conn
 * @param int $id : id do tipo de problema
 * 
 * @return array
 */
function getAreasByIssue (\PDO $conn, int $id, ?string $labelAll = "Todas"): array
{
    $data = [];

    /* Só retornará registro se existir com area_id = null */
    $sqlAllAreas = "SELECT 
                       area_id as sis_id, 
                       '{$labelAll}' as sistema
                    FROM areas_x_issues
                    WHERE 
                        area_id IS NULL AND 
                        prob_id = :id ";
    try {
        $res = $conn->prepare($sqlAllAreas);
        $res->bindParam(':id', $id);
        $res->execute();

        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            return $data;
            
        }
    }
    catch (Exception $e) {
        // $exception .= "<hr>" . $e->getMessage();
        return [];
    }


    $sql = "SELECT * FROM sistemas s, areas_x_issues ap 
            WHERE 
                (s.sis_id = ap.area_id OR ap.area_id IS NULL) AND 
                ap.prob_id = :id 
            ORDER BY s.sistema";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':id',$id);
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return [];
    }
}



/**
 * getAreasMinusExceptionsByIssue
 * Retorna um array com todas as áreas que podem receber chamados do tipo de solicitação informado
 * Exclui as áreas que estão marcadas como exceção (quanto o tipo de solicitação for para todas as áreas)
 *
 * @param \PDO $conn
 * @param int $issueId
 * 
 * @return array
 */
function getAreasMinusExceptionsByIssue (\PDO $conn, int $issueId): array
{
    
    /*** Areas que são exceção para o tipo de solicitação */
    $areaExceptions = hiddenAreasByIssue($conn, $issueId);

    /*** Separando apenas a coluna com os IDs */
    $exceptionIDs = (!empty($areaExceptions) ? array_column($areaExceptions, 'area_id') : []);

    /*** Todas as áreas para qual o tipo de solicitação está vinculado */
    $allTreatingAreas = getAreasByIssue($conn, $issueId);

    
    /*** Caso nenhuma área em específico estiver definida, então todas as áreas podem ser */
    if ($allTreatingAreas[0]['sis_id'] == null) {
        /*** Array com todas as áreas ativas que prestam atendimento */
        $allTreatingAreas = getAreas($conn, 0, 1, 1);
    }

    /*** Caso não existam exceções, retorna apenas o array com todas as áreas elegíveis */
    if (empty($exceptionIDs)) {
        return $allTreatingAreas;
    }
    
    /*** Caso existam exceções */
    $newAllAreas = [];
    foreach ($allTreatingAreas as $area) {
        if (!in_array($area['sis_id'], $exceptionIDs)) {
            $newAllAreas[] = $area;
        }
    }

    return $newAllAreas;

}



/**
 * getAreaInDynamicMode
 * Retorna um array com os id (area_receiver) da área de atendimento a ser definida a partir da seleção de um tipo de solicitação
 * Indices: common_areas_btw_issue_and_user | area_receiver | many_options:bool
 * Regras:
 * 1 - Se o tipo de problema não tiver área associada - Será aberto para a área definida como padrão da área primária do usuário
 * 2 - Se o usuário só puder abrir chamados para uma única área, esta será a área destino do chamado
 * 3 - Se existir apenas uma área comum entre as áreas do tipo de problema e as áreas para as quais o usuário pode abrir, esta 
 * será a área destino do chamado
 * 4 - Se o usuário só puder abrir chamado para uma única área, essa será a área do chamado
 * 5 - Se existirem mais de uma área para o tipo de problema, a área destino será a área padrão do tipo de problema (se existir)
 * 5.1 - caso não exista área padrão para o tipo de problema, será checado se a área padrão do usuário está entre as áreas 
 * associadas ao tipo de problema, caso positivo, será a área destino
 * 5.2 - Se ao final de todas as checagens ainda existir mais de uma área possível (significa que o tipo de problema está 
 * vinculado a mais de uma área e não possui definição de área padrão, da mesma forma, a área padrão do usuário não está 
 * entre as áreas possíveis): será escolhida a primeira área do array e retornado o indice "many_options" como true
 * 
 * @param \PDO $conn
 * @param int $issueType
 * @param int $userPrimaryArea
 * @param string $userAllAreas
 * 
 * @return array
 */
function getAreaInDynamicMode(\PDO $conn, int $issueType, int $userPrimaryArea, string $userAllAreas): array
{
    $data = [];

    $data['issue_id'] = $issueType;
    $issueInfo = getIssueById($conn, $issueType);
    $data['many_options'] = false;
    $data['area_receiver'] = [];
    $data['areas_by_issue'] = [];


    /* Áreas vinculadas ao tipo de problema - removendo as áreas para as quais o tipo de problema é oculto*/
    // $possibleAreas = getAreasByIssue($conn, $issueType);
    $possibleAreas = getAreasMinusExceptionsByIssue($conn, $issueType);
    foreach ($possibleAreas as $area) {
        if (!empty($area['sis_id']))
            $data['areas_by_issue'][] = $area['sis_id'];
    }


    /* Áreas para as quais o usuário logado pode abrir chamado */
    $areasToOpen = getAreasToOpen($conn, $userAllAreas);
    foreach ($areasToOpen as $area) {
        $data['user_areas_to_open'][] = $area['sis_id'];
    }


    /* Áreas comuns entre as áreas do tipo de problema e as áreas para as quais o usuário pode abrir chamado */
    $commonAreas = array_intersect($data['areas_by_issue'],$data['user_areas_to_open']);
    foreach ($commonAreas as $area) {
        $data['common_areas_btw_issue_and_user'][] = $area;
    }

    /* Se existir apenas uma área comum entre as vinculadas ao tipo de problema e as que o usuário pode abrir, 
    então essa será a área de atendimento do chamado */
    if (isset($data['common_areas_btw_issue_and_user']) && count($data['common_areas_btw_issue_and_user']) == 1) {
        $data['debug'] = "Linha " . __LINE__;
        $data['area_receiver'] = $data['common_areas_btw_issue_and_user'];
        return $data;
    }


    $data['issue_area_default'] = "";
    if (!empty($issueInfo['prob_area_default'])) {
        $data['issue_area_default'] = $issueInfo['prob_area_default'];
        
        /* Se a área padrão do tipo de problema estiver entre as áreas para as quais o usuário puder abrir, 
        então esta será a área de atendimento do chamado */
        if (in_array($data['issue_area_default'], $data['user_areas_to_open'])) {
            
            // $data['debug'] = "Linha " . __LINE__;
            $data['area_receiver'][] = $data['issue_area_default'];
            return $data;
        }
    }


    /* Se existir apenas uma área associada para o tipo de problema essa será a área destino */
    if (isset($data['areas_by_issue']) && count($data['areas_by_issue']) == 1) {
        // $data['debug'] = "Linha " . __LINE__;
        $data['area_receiver'] = $data['areas_by_issue'];
        return $data;
    }

    /* Área padrão para receber chamados a partir da área primária do usuário logado */
    /* Se a área está configurada para modo dinâmico de abertura, obrigatoriamente tem definida a área padrão para abertura */
    $data['user_default_area_to_open'] = getDefaultAreaToOpen($conn, $userPrimaryArea);


    // if (!isset($data['common_areas_btw_issue_and_user']) && count($data['user_areas_to_open']) > 1) {
    if (empty($data['issue_area_default']) && count($data['user_areas_to_open']) > 1) {
        /* Significa que o tipo de problema não possui área definida (serve para todas) */
        /* Nesse caso a área destino será a área definida como padrão para a área primária do usuário */
        $data['debug'] = "Linha " . __LINE__;
        $data['area_receiver'][] = $data['user_default_area_to_open'];
        $data['message'] = "Será aberto para a área padrão de abertura da área primária do usuário.";

        return $data;
        
    } elseif (count($data['user_areas_to_open']) == 1) {
        
        // $data['debug'] = "Linha " . __LINE__;
        // $data['area_receiver'][] = $data['user_areas_to_open'];
        $data['area_receiver'] = $data['user_areas_to_open'];
        return $data;
    }


    if (isset($data['common_areas_btw_issue_and_user']) && count($data['common_areas_btw_issue_and_user']) > 1) {

        // $data['many_options'] = true;
        
        if (!empty($data['issue_area_default']) && in_array($data['issue_area_default'], $data['user_areas_to_open'])) {
            
            // $data['debug'] = "Linha " . __LINE__;
            $data['area_receiver'][] = $data['issue_area_default'];
            return $data;
        } else {
    
            if (!empty($data['user_default_area_to_open']) && in_array($data['user_default_area_to_open'], $data['common_areas_btw_issue_and_user'])) {


                // $data['debug'] = "Linha " . __LINE__;
                $data['area_receiver'][] = $data['user_default_area_to_open'];
                $data['area_receiver'][] = array_unique($data['area_receiver']); 
    
                $data['message'] = "Será aberto para a área padrão de abertura da área primária do usuário.";
                return $data;
            } else {
                // $data['debug'] = "Linha " . __LINE__;
                $data['message'] = "Nem a área padrão do problema nem a área padrão da área primária estão entre as áreas possíveis para o tipo de problema - desenvolver a regra";
    
                $data['many_options'] = true;
                /* Temporariamente estou definindo a primeira area comum ao tipo de problema e áreas áreas do usuaŕio para receber o chamado */
                $data['area_receiver'][] = $data['common_areas_btw_issue_and_user'][0];
                return $data;
            }
        }
    }
    return $data;
}


/**
 * Retorna o departamento de uma tag (com unidade) informada
 * @param PDO $conn
 * @param int $unit
 * @param int $tag
 * @return null|int
 */
function getDepartmentByUnitAndTag(\PDO $conn, int $unit, int $tag):?int
{

    $sql = "SELECT comp_local FROM equipamentos WHERE comp_inst = :unit AND comp_inv = :tag ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':unit',$unit);
        $res->bindParam(':tag',$tag);
        $res->execute();
        if ($res->rowCount()) {
            return $res->fetch()['comp_local'];
        }
        return null;
    }
    catch (Exception $e) {
        return null;
    }
}


/**
 * Retorna se um tipo de problema possui roteiros relacionados
 * @param PDO $conn
 * @param int $id
 * @return bool
 */
function issueHasScript(\PDO $conn, int $id):bool
{
    $sql = "SELECT prscpt_id FROM prob_x_script WHERE prscpt_prob_id = :id ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':id', $id);
        $res->execute();
        if ($res->rowCount()) {
            return true;
        }
        return false;
    }
    catch (Exception $e) {
        return false;
    }
}


/**
 * Retorna se um tipo de problema possui roteiros para usuário final
 * @param PDO $conn
 * @param int $id
 * @return bool
 */
function issueHasEnduserScript(\PDO $conn, int $id):bool
{
    $sql = "SELECT script.scpt_enduser FROM problemas as p 
            LEFT JOIN prob_x_script as sc on sc.prscpt_prob_id = p.prob_id 
            LEFT JOIN scripts as script on script.scpt_id = sc.prscpt_scpt_id 
            WHERE p.prob_id = :id ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':id', $id);
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                if ($row['scpt_enduser'] == 1)
                    return true;
            }
            return false;
        }
        return false;
    }
    catch (Exception $e) {
        return false;
    }
}


/**
 * getOpenerLevel
 * Retorna o código do nível do usuário que abriu o chamado
 * @param \PDO $conn
 * @param int $ticket
 * @return int
 */
function getOpenerLevel (\PDO $conn, int $ticket): int
{
    $sql = "SELECT u.nivel FROM usuarios u, ocorrencias o WHERE o.numero = :ticket AND o.aberto_por = u.user_id ";
    $result = $conn->prepare($sql);
    $result->bindParam(':ticket', $ticket);
    $result->execute();

    return $result->fetch()['nivel'];
}


/**
 * getOpenerEmail
 * Retorna o endereço de e-mail do usuário que abriu o chamado
 * @param \PDO $conn
 * @param int $ticket
 * @return string
 */
function getOpenerEmail (\PDO $conn, int $ticket): string
{
    $sql = "SELECT u.email FROM usuarios u, ocorrencias o WHERE o.numero = :ticket AND o.aberto_por = u.user_id ";
    $result = $conn->prepare($sql);
    $result->bindParam(':ticket', $ticket);
    $result->execute();

    return $result->fetch()['email'];
}


function getOpenerInfo (\PDO $conn, int $ticket): array
{
    $data = [];
    $sql = "SELECT * FROM usuarios u, ocorrencias o WHERE o.numero = :ticket AND o.aberto_por = u.user_id ";
    $result = $conn->prepare($sql);
    $result->bindParam(':ticket', $ticket);
    $result->execute();

    $data = $result->fetch();
    unset($data['password']);
    unset($data['hash']);
    return $data;
}

/**
 * isAreasIsolated
 * Retorna se a configuração atual está marcada para isolamento de visibilidade entre áreas
 * @param PDO $conn
 * @return bool
 */
function isAreasIsolated($conn): bool
{
    $config = getConfig($conn);
    if ($config['conf_isolate_areas'] == 1)
        return true;
    return false;
}


/**
 * getTicketData
 * Busca as informacoes do ticket na tabela de ocorrencias
 * @param \PDO $conn
 * @param int $ticket
 * @return array
 */
function getTicketData (\PDO $conn, int $ticket): array
{
    $sql = "SELECT * 
            FROM ocorrencias  
            WHERE 
                numero = :ticket";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':ticket', $ticket, PDO::PARAM_INT);
        $res->execute();
        
        if ($res->rowCount()) {
            return $res->fetch();
        }
        return [];
    }
    catch (Exception $e) {
        return [];
    }
}



/**
 * isTicketTreater
 * Retorna se o usuário informado é o responsável pelo atendimento do chamado também informado
 *
 * @param \PDO $conn
 * @param int $ticket
 * @param int $user
 * 
 * @return bool
 */
function isTicketTreater (\PDO $conn, int $ticket, int $user): bool
{
    $sql = "SELECT 
                o.numero
            FROM
                ocorrencias o,
                usuarios u,
                `status` st
            WHERE
                o.numero = :ticket AND
                o.operador = u.user_id AND
                u.nivel < 3 AND
                o.status = st.stat_id AND
                st.stat_painel = 1 AND 
                u.user_id = :user
    ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':ticket', $ticket, PDO::PARAM_INT);
        $res->bindParam(':user', $user, PDO::PARAM_INT);
        $res->execute();
        if ($res->rowCount()) {
            return true;
        }
        return false;
    }
    catch (Exception $e) {
        return false;
    }
}



/**
 * isRated
 * Retorna se um dado ticket já teve o atendimento validado e consequentemente avaliado
 * @param \PDO $conn
 * @param int $ticket
 * 
 * @return bool
 */
function isRated (\PDO $conn, int $ticket): bool 
{
    $sql = "SELECT * FROM tickets_rated WHERE ticket = :ticket AND rate IS NOT NULL";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':ticket', $ticket, PDO::PARAM_INT);
        $res->execute();

        if ($res->rowCount()) {
            return true;
        }
        return false;
    }
    catch (Exception $e) {
        echo '<hr>' . $e->getMessage();
        return false;
    }
}


/**
 * hasRatingRow
 * Retorna se existe regitro em tickets_rated para o ticket informado
 * @param \PDO $conn
 * @param int $ticket
 * 
 * @return bool
 */
function hasRatingRow (\PDO $conn, int $ticket): bool
{
    $sql = "SELECT * FROM tickets_rated WHERE ticket = :ticket";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':ticket', $ticket, PDO::PARAM_INT);
        $res->execute();

        if ($res->rowCount()) {
            return true;
        }
        return false;
    }
    catch (Exception $e) {
        echo '<hr>' . $e->getMessage();
        return false;
    }
}

/**
 * getRatedInfo
 * Retorna um array com as informações de avaliação do chamado
 * @param \PDO $conn
 * @param int $ticket
 * 
 * @return array
 */
function getRatedInfo (\PDO $conn, int $ticket): array
{
    $sql = "SELECT * FROM tickets_rated WHERE ticket = :ticket";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':ticket', $ticket, PDO::PARAM_INT);
        $res->execute();

        if ($res->rowCount()) {
            return $res->fetch();
        }
        return [];
    }
    catch (Exception $e) {
        echo '<hr>' . $e->getMessage();
        return [];
    }
}


/**
 * getTicketRate
 * Retorna a avaliacao do chamado
 *
 * @param \PDO $conn
 * @param int $ticket
 * 
 * @return string|null
 */
function getTicketRate(\PDO $conn, int $ticket): ?string
{
    $sql = "SELECT * FROM tickets_rated WHERE ticket = :ticket AND rate IS NOT NULL";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':ticket', $ticket, PDO::PARAM_INT);
        $res->execute();

        if ($res->rowCount()) {
            return $res->fetch()['rate'];
        }
        return null;
    }
    catch (Exception $e) {
        echo '<hr>' . $e->getMessage();
        return null;
    }
}


function isWaitingRate(\PDO $conn, int $ticket, int $statusDone, string $baseDate): bool
{

    $sql = "SELECT 
            o.numero
        FROM 
            ocorrencias o
            LEFT JOIN tickets_rated tr ON tr.ticket = o.numero
        WHERE
            o.`numero` = {$ticket} AND 
            o.`operador` <> o.`aberto_por` AND
            o.`status` = {$statusDone} AND
            o.`data_fechamento` IS NOT NULL AND
            o.`data_fechamento` >= '{$baseDate}' AND
            tr.rate IS NULL
        ";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount()) {
            return true;
        }
        return false;
    }
    catch (Exception $e) {
        echo "<hr>" . $e->getMessage();
        return false;
    }
}


/**
 * isRejected
 * Retorna se a conclusão do atendimento de um ticket foi rejeitada
 * @param \PDO $conn
 * @param int $ticket
 * 
 * @return bool
 */
function isRejected(\PDO $conn, int $ticket): bool
{
    $sql = "SELECT * FROM 
                tickets_rated 
            WHERE 
                ticket = :ticket AND 
                rate IS NULL AND 
                rejected_count > 0";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':ticket', $ticket, PDO::PARAM_INT);
        $res->execute();

        if ($res->rowCount()) {
            return true;
        }
        return false;
    }
    catch (Exception $e) {
        echo '<hr>' . $e->getMessage();
        return false;
    }
}


/**
 * hasBeenRejected
 * Retorna se o ticket foi rejeitado em algum momento
 * @param \PDO $conn
 * @param int $ticket
 * 
 * @return bool
 */
function hasBeenRejected (\PDO $conn, int $ticket): bool
{
    $sql = "SELECT 
                tr.ticket
            FROM
                tickets_rated tr
            WHERE
                tr.ticket = :ticket AND 
                tr.rejected_count > 0
            ";

    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':ticket', $ticket, PDO::PARAM_INT);
        $res->execute();

        if ($res->rowCount()) {
            return true;
        }
        return false;
    }
    catch (Exception $e) {
        echo '<hr>' . $e->getMessage();
        return false;
    }
}


/**
 * getRejectedCount
 * Retorna a quantidade de rejeições do ticket informado
 * @param \PDO $conn
 * @param int $ticket
 * 
 * @return int
 */
function getRejectedCount(\PDO $conn, int $ticket): int
{
    $sql = "SELECT 
                rejected_count
            FROM
                tickets_rated
            WHERE
                ticket = :ticket
                ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':ticket', $ticket, PDO::PARAM_INT);
        $res->execute();

        if ($res->rowCount()) {
            return $res->fetch()['rejected_count'];
        }
        return 0;
    }
    catch (Exception $e) {
        echo '<hr>' . $e->getMessage();
        return 0;
    }
}

/**
 * ratingLevels
 * Retorna o array dos tipos de avaliação que um atendimento pode receber
 * @return array
 */
function ratingLabels () :array
{
    return [
        'great' => TRANS('ASSESSMENT_GREAT'),
        'good' => TRANS('ASSESSMENT_GOOD'),
        'regular' => TRANS('ASSESSMENT_REGULAR'),
        'bad' => TRANS('ASSESSMENT_BAD'),
        'not_rated' => TRANS('NOT_RATED_IN_TIME')
    ];
}


function ratingLabelsStates () :array
{
    return [
        'rejected' => TRANS('SERVICE_REJECTED'),
        // 'not_rated' => TRANS('SERVICE_NOT_RATED'),
        'evaluate' => TRANS('APPROVE_AND_CLOSE'),
        'pending' => TRANS('PENDING')
    ];
}

/**
 * ratingClasses
 * Retorna as classes para formatação das etiquetas dos tipos de avaliação - 
 * As classes estão definidas em switch_radio.css
 * @return array
 */
function ratingClasses () :array
{
    return [
        'great' => 'color-great',
        'good' => 'color-good',
        'regular' => 'color-regular',
        'bad' => 'color-bad',
        'rejected' => 'color-bad',
        'not_rated' => 'color-not-rated',
        'pending' => 'color-to-rate',
        'evaluate' => 'color-to-rate'
    ];
}




/**
 * renderRate
 * Renderiza a avaliação do atendimento em um badge
 *
 * @param string|null $rate
 * @param bool|null $isDone
 * @param bool|null $isRequester
 * @param string|null $id
 * 
 * @return string
 */
function renderRate (
            ?string $rate, 
            ?bool $isDone = false , 
            ?bool $isRequester = false, 
            ?bool $isRejected = false, 
            ?string $id = null): string
{

    $rate_key = ($rate ? $rate : '');
    
    if (!$rate && $isDone) {
        $rate_key = ($isRequester ? 'evaluate' : 'pending');
    }

    if ($isRejected && !$isDone) {
        $rate_key = "rejected";
    }


    // $label = TRANS('SERVICE_NOT_RATED');
    // $class = "badge-info";
    $label = '';
    $class = '';
    $typeLabels = array_merge(ratingLabels(), ratingLabelsStates());

    $typeClasses = ratingClasses();

    foreach ($typeLabels as $key => $value) {
        if ($rate_key == $key) {
            $label = $value;
            break;
        }
    }
    foreach ($typeClasses as $key => $value) {
        if ($rate_key == $key) {
            $class = $value;
            break;
        }
    }

    $tagId = ($id ? "id=" . $id : "");

    $html = '<span class="badge ' . $class . ' text-white align-middle" '. $tagId .'>'. $label .'</span>'; /* p-2 m-2 mb-2 */

    return $html;
}



/**
 * isFather
 * Testa se o ticket informado pode ser um chamado pai
 * @param \PDO $conn
 * @param int $ticket
 * @return bool
 */
function isFatherOk (\PDO $conn, ?int $ticket): bool
{
    $sql = "SELECT o.numero 
            FROM ocorrencias o, `status` s 
            WHERE 
                o.`status` = s.stat_id AND 
                s.stat_painel NOT IN (3) AND 
                o.numero = :ticket";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':ticket', $ticket, PDO::PARAM_INT);
        $res->execute();
        
        if ($res->rowCount()) {
            return true;
        }
        return false;
    }
    catch (Exception $e) {
        return false;
    }
}


/**
 * getCustomFields
 * Retorna a listagem de campos personalizados de acordo com os filtros $tableTo e $type. 
 * Possíveis $type para filtro: text, number, select, select_multi, date, time, datetime, textarea, checkbox 
 * Ou retorna um registro específico caso o $id seja fornecido
 *
 * @param \PDO $conn
 * @param int|null $id
 * @param string|null $tableTo
 * @param array|null $type
 * @param int|null $active
 * 
 * @return array
 */
function getCustomFields (\PDO $conn, ?int $id = null, ?string $tableTo = null, ?array $type = null, ?int $active = 1): array
{

    $terms = ' WHERE 1 = 1 ';
    $typeList = ["text", "number", "select", "select_multi", "date", "time", "datetime", "textarea", "checkbox"];
    
    
    if (!$id) {
        if ($type && is_array($type)) {

            $typesOk = array_intersect($type,$typeList);
            
            if (count($typesOk)) {
                $typesOk = implode("','", $typesOk);
                $terms .= " AND field_type IN ('$typesOk') ";
            } else {
                return [];
            }
            
        }

        if (!empty($tableTo)) {
            $terms .= " AND field_table_to = :tableTo ";
        }

        if (!empty($active)) {
            $terms .= " AND field_active = :active ";
        }
    } else {
        /* Se tiver $id não importa o $type nem o $tableTo*/
        $terms = "WHERE id = :id ";
    }
    

    $sql = "SELECT * FROM custom_fields {$terms} ORDER BY field_active, field_order, field_label";
    try {
        $res = $conn->prepare($sql);
        if ($id)
            $res->bindParam(':id', $id, PDO::PARAM_INT);
        else {
            if ($tableTo)
                $res->bindParam(':tableTo', $tableTo, PDO::PARAM_STR);
            // if ($type)
            //     $res->bindParam(':type', $type, PDO::PARAM_STR);
            if ($active)
                $res->bindParam(':active', $active, PDO::PARAM_STR);
        }
        
        $res->execute();
        
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            if ($id)
                return $data[0];
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        dump($sql);
        echo $e->getMessage();
        return [];
    }
}


/**
 * getCustomFieldOptionValues
 * Retorna o array com a listagem de opções de seleção para o custom Field ID $fieldId informado
 *
 * @param \PDO $conn
 * @param int $fieldId
 * 
 * @return array
 */
function getCustomFieldOptionValues(\PDO $conn, int $fieldId): array
{
    $sql = "SELECT * FROM custom_fields_option_values WHERE custom_field_id = :fieldId ORDER BY option_value ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':fieldId', $fieldId, PDO::PARAM_INT);
        $res->execute();

        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        // $exception .= "<hr>" . $e->getMessage();
        return [];
    }
}



/**
 * getCustomFieldValue
 * Retorna o valor de um option em um campo personalizado do tipo 'select'.
 * Se o campo for do tipo 'select_multi' então retorna a lista de valores
 *
 * @param \PDO $conn
 * @param string $id
 * 
 * @return string|null
 */
function getCustomFieldValue(\PDO $conn, ?string $id = null): ?string
{

    if (!$id) {
        return null;
    }

    $values = "";
    $ids = explode(',', (string)$id);

    foreach ($ids as $id) {
        $sql = "SELECT option_value FROM custom_fields_option_values WHERE id = :id";
        try {
            $res = $conn->prepare($sql);
            $res->bindParam(':id', $id, PDO::PARAM_INT);
            $res->execute();
            if ($res->rowCount()) {
                if (strlen((string)$values)) $values .= ", ";
                $values .= $res->fetch()['option_value'];
            }
            // return null;
        }
        catch (Exception $e) {
            // $exception .= "<hr>" . $e->getMessage();
            $values = "";
        }
    }
    return $values;
    
}



/**
 * hasCustomFields
 * Retorna se o ticket informado possui informações em campos extras
 *
 * @param \PDO $conn
 * @param int $key (número do ticket ou id do ativos ou ID de outras tabelas envolvidas)
 * @param string $table : o padrão é a busca no tabela tickets_x_cfields
 * 
 * @return bool
 */
function hasCustomFields(\PDO $conn, int $key, ?string $table = null) : bool
{
    if (!$table) {
        $table = "tickets_x_cfields";
        $fieldId = "ticket";
    } elseif ($table == "assets_x_cfields") {
        $fieldId = "asset_id";
    } elseif ($table == "clients_x_cfields") {
        $fieldId = "client_id";
    }

    $sql = "SELECT id FROM {$table} WHERE {$fieldId} = :id";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':id', $key, PDO::PARAM_INT);
        $res->execute();

        if ($res->rowCount()) {
            return true;
        }
        return false;
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}


/**
 * getTicketCustomFields
 * Retorna um array com todas as informações dos campos extras (campos personalizados) de um ticket informado
 * Índices: field_name, field_label, field_type, field_title, field_placeholder, field_description, 
 * field_value_idx, field_value
 * @param \PDO $conn
 * @param int $ticket
 * 
 * @return array
 */
function getTicketCustomFields(\PDO $conn, int $ticket, ?int $fieldId = null): array
{
    $ticketExtraInfo = [];
    $empty = [];
    $empty['field_id'] = "";
    $empty['field_name'] = "";
    $empty['field_type'] = "";
    $empty['field_label'] = "";
    $empty['field_title'] = "";
    $empty['field_placeholder'] = "";
    $empty['field_description'] = "";
    $empty['field_attributes'] = "";
    $empty['field_value_idx'] = "";
    $empty['field_value'] = "";
    $empty['field_is_key'] = "";
    $empty['field_order'] = "";


    $terms = "";
    if ($fieldId) {
        $terms = " AND c.id = :fieldId ";
    }

    $sql = "SELECT 
                c.id field_id, c.field_name, c.field_type, c.field_label, c.field_title, c.field_placeholder, 
                c.field_description, c.field_attributes, c.field_order, t.cfield_value as field_value_idx,
                t.cfield_value as field_value, t.cfield_is_key as field_is_key
            FROM 
                custom_fields c, tickets_x_cfields t WHERE t.cfield_id = c.id AND ticket = :ticket 
                {$terms}
            ORDER BY field_order, field_label";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':ticket', $ticket, PDO::PARAM_INT);
        if ($fieldId) {
            $res->bindParam(':fieldId', $fieldId, PDO::PARAM_INT);
        }
        $res->execute();
        if ($res->rowCount()) {
            $idx = 0;
            foreach ($res->fetchAll() as $row) {
                
                if ($row['field_is_key']) {
                    /* Buscar valor correspondente ao cfield_value */
                    $ticketExtraInfo[$idx]['field_id'] = $row['field_id'];
                    $ticketExtraInfo[$idx]['field_name'] = $row['field_name'];
                    $ticketExtraInfo[$idx]['field_type'] = $row['field_type'];
                    $ticketExtraInfo[$idx]['field_label'] = $row['field_label'];
                    $ticketExtraInfo[$idx]['field_title'] = $row['field_title'];
                    $ticketExtraInfo[$idx]['field_placeholder'] = $row['field_placeholder'];
                    $ticketExtraInfo[$idx]['field_description'] = $row['field_description'];
                    $ticketExtraInfo[$idx]['field_attributes'] = $row['field_attributes'];
                    $ticketExtraInfo[$idx]['field_value_idx'] = $row['field_value'];
                    $ticketExtraInfo[$idx]['field_value'] = getCustomFieldValue($conn, $row['field_value']);
                    $ticketExtraInfo[$idx]['field_is_key'] = $row['field_is_key'];
                    $ticketExtraInfo[$idx]['field_order'] = $row['field_order'];
                } else {
                    $ticketExtraInfo[] = $row;
                }
                $idx++;
            }
            if ($fieldId) {
                /* Único registro retornado */
                return $ticketExtraInfo[0];
            }
            return $ticketExtraInfo;
        }
        return $empty;
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}



/**
 * getAssetCustomFields
 * Retorna um array com todas as informações dos campos extras (campos personalizados) de um ativo informado
 * Índices: field_name, field_label, field_type, field_title, field_placeholder, field_description, 
 * field_value_idx, field_value
 * @param \PDO $conn
 * @param int $assetId
 * 
 * @return array
 */
function getAssetCustomFields(\PDO $conn, int $assetId, ?int $fieldId = null): array
{
    $ticketExtraInfo = [];
    $empty = [];
    $empty['field_id'] = "";
    $empty['field_name'] = "";
    $empty['field_type'] = "";
    $empty['field_label'] = "";
    $empty['field_title'] = "";
    $empty['field_placeholder'] = "";
    $empty['field_description'] = "";
    $empty['field_attributes'] = "";
    $empty['field_value_idx'] = "";
    $empty['field_value'] = "";
    $empty['field_is_key'] = "";
    $empty['field_order'] = "";


    $terms = "";
    if ($fieldId) {
        $terms = " AND c.id = :fieldId ";
    }

    $sql = "SELECT 
                c.id field_id, c.field_name, c.field_type, c.field_label, c.field_title, c.field_placeholder, 
                c.field_description, c.field_attributes, c.field_order, a.cfield_value as field_value_idx,
                a.cfield_value as field_value, a.cfield_is_key as field_is_key
            FROM 
                custom_fields c, assets_x_cfields a WHERE a.cfield_id = c.id AND a.asset_id = :asset_id 
                {$terms}
            ORDER BY field_order, field_label";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':asset_id', $assetId, PDO::PARAM_INT);
        if ($fieldId) {
            $res->bindParam(':fieldId', $fieldId, PDO::PARAM_INT);
        }
        $res->execute();
        if ($res->rowCount()) {
            $idx = 0;
            foreach ($res->fetchAll() as $row) {
                
                if ($row['field_is_key']) {
                    /* Buscar valor correspondente ao cfield_value */
                    $ticketExtraInfo[$idx]['field_id'] = $row['field_id'];
                    $ticketExtraInfo[$idx]['field_name'] = $row['field_name'];
                    $ticketExtraInfo[$idx]['field_type'] = $row['field_type'];
                    $ticketExtraInfo[$idx]['field_label'] = $row['field_label'];
                    $ticketExtraInfo[$idx]['field_title'] = $row['field_title'];
                    $ticketExtraInfo[$idx]['field_placeholder'] = $row['field_placeholder'];
                    $ticketExtraInfo[$idx]['field_description'] = $row['field_description'];
                    $ticketExtraInfo[$idx]['field_attributes'] = $row['field_attributes'];
                    $ticketExtraInfo[$idx]['field_value_idx'] = $row['field_value'];
                    $ticketExtraInfo[$idx]['field_value'] = getCustomFieldValue($conn, $row['field_value']);
                    $ticketExtraInfo[$idx]['field_is_key'] = $row['field_is_key'];
                    $ticketExtraInfo[$idx]['field_order'] = $row['field_order'];
                } else {
                    $ticketExtraInfo[] = $row;
                }
                $idx++;
            }
            if ($fieldId) {
                /* Único registro retornado */
                return $ticketExtraInfo[0];
            }
            return $ticketExtraInfo;
        }
        return $empty;
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * getClientCustomFields
 * Retorna um array com todas as informações dos campos extras (campos personalizados) de um ativo informado
 * Índices: field_name, field_label, field_type, field_title, field_placeholder, field_description, 
 * field_value_idx, field_value
 * @param \PDO $conn
 * @param int $clientId
 * 
 * @return array
 */
function getClientCustomFields(\PDO $conn, int $clientId, ?int $fieldId = null): array
{
    $ticketExtraInfo = [];
    $empty = [];
    $empty['field_id'] = "";
    $empty['field_name'] = "";
    $empty['field_type'] = "";
    $empty['field_label'] = "";
    $empty['field_title'] = "";
    $empty['field_placeholder'] = "";
    $empty['field_description'] = "";
    $empty['field_attributes'] = "";
    $empty['field_value_idx'] = "";
    $empty['field_value'] = "";
    $empty['field_is_key'] = "";
    $empty['field_order'] = "";


    $terms = "";
    if ($fieldId) {
        $terms = " AND c.id = :fieldId ";
    }

    $sql = "SELECT 
                c.id field_id, c.field_name, c.field_type, c.field_label, c.field_title, c.field_placeholder, 
                c.field_description, c.field_attributes, c.field_order, a.cfield_value as field_value_idx,
                a.cfield_value as field_value, a.cfield_is_key as field_is_key
            FROM 
                custom_fields c, clients_x_cfields a WHERE a.cfield_id = c.id AND a.client_id = :client_id 
                {$terms}
            ORDER BY field_order, field_label";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':client_id', $clientId, PDO::PARAM_INT);
        if ($fieldId) {
            $res->bindParam(':fieldId', $fieldId, PDO::PARAM_INT);
        }
        $res->execute();
        if ($res->rowCount()) {
            $idx = 0;
            foreach ($res->fetchAll() as $row) {
                
                if ($row['field_is_key']) {
                    /* Buscar valor correspondente ao cfield_value */
                    $ticketExtraInfo[$idx]['field_id'] = $row['field_id'];
                    $ticketExtraInfo[$idx]['field_name'] = $row['field_name'];
                    $ticketExtraInfo[$idx]['field_type'] = $row['field_type'];
                    $ticketExtraInfo[$idx]['field_label'] = $row['field_label'];
                    $ticketExtraInfo[$idx]['field_title'] = $row['field_title'];
                    $ticketExtraInfo[$idx]['field_placeholder'] = $row['field_placeholder'];
                    $ticketExtraInfo[$idx]['field_description'] = $row['field_description'];
                    $ticketExtraInfo[$idx]['field_attributes'] = $row['field_attributes'];
                    $ticketExtraInfo[$idx]['field_value_idx'] = $row['field_value'];
                    $ticketExtraInfo[$idx]['field_value'] = getCustomFieldValue($conn, $row['field_value']);
                    $ticketExtraInfo[$idx]['field_is_key'] = $row['field_is_key'];
                    $ticketExtraInfo[$idx]['field_order'] = $row['field_order'];
                } else {
                    $ticketExtraInfo[] = $row;
                }
                $idx++;
            }
            if ($fieldId) {
                /* Único registro retornado */
                return $ticketExtraInfo[0];
            }
            return $ticketExtraInfo;
        }
        return $empty;
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}



/**
 * getChannels
 * Retorna um array com a listagem dos canais de entrada ou do canal específico caso o id seja informado
 * O $type filtra se os canais exibidos estão marcados como only_set_by_system:0|1 (de utilização por meios automatizados)
 * @param \PDO $conn
 * @param null|int $id
 * @param null|string $type : restrict|open| null:todos => Tipos de canais
 * @return array
 */
function getChannels (\PDO $conn, ?int $id = null, ?string $type = null): array
{
    $return = [];

    $terms = '';
    $typeList = ["restrict", "open"];
    
    if (!$id && !empty($type)) {
        if (in_array($type, $typeList)) {
            $terms = "WHERE only_set_by_system = :type ";
        } else {
            $return[] = "Invalid type for channel";
            return $return;
        }
        $filter = ($type == "restrict" ? 1 : 0);
    }

    $terms = ($id ? "WHERE id = :id " : $terms); /* Se tiver $id não importa o $type */

    $sql = "SELECT * FROM channels {$terms} ORDER BY name";
    try {
        $res = $conn->prepare($sql);
        if ($id)
            $res->bindParam(':id', $id, PDO::PARAM_INT);
        elseif ($type) 
            $res->bindParam(':type', $filter, PDO::PARAM_INT);

        $res->execute();
        
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            if ($id)
                return $data[0];
            return $data;
        }
        return $return;
    }
    catch (Exception $e) {
        return $return;
    }
}

/**
 * getDefaultChannel
 * Retorna o canal padrão
 * @param PDO $conn
 * @return array
 */
function getDefaultChannel (\PDO $conn): array
{
    $return = [];
    
    $sql = "SELECT * FROM channels WHERE is_default = 1 ";
    try {
        $res = $conn->prepare($sql);
        $res->execute();
        if ($res->rowCount()) {
            $row = $res->fetch();
            return $row;
        }
        return $return;
    }
    catch (Exception $e) {
        return $return;
    }
}


/**
 * isSystemChannel
 * Retorna se o canal informado pelo $id é de utilização interna do sistema ou não
 * @param \PDO $conn
 * @param int $id
 * @return bool
 */
function isSystemChannel (\PDO $conn, int $id): bool
{
    $sql = "SELECT id FROM channels WHERE id = :id AND only_set_by_system = 1 ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':id', $id, PDO::PARAM_INT);
        $res->execute();
        
        if ($res->rowCount()) {
            return true;
        }
        return false;
    }
    catch (Exception $e) {
        return false;
    }
}


/**
 * getWorktime
 * Retorna um array com as informações de horarios da jornada de trabalho
 * @param \PDO $conn
 * @param int $profileId
 * @return array
 */
function getWorktime ($conn, $profileId): array
{
    $empty = [];
    
    if (empty($profileId)) {
        return $empty;
    }
        
    $sql = "SELECT * FROM worktime_profiles WHERE id = '{$profileId}'";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount())
            return $res->fetch();
        return $empty;
    }
    catch (Exception $e) {
        return $empty;
    }
}

/**
 * getStatementsInfo
 * Retorna um array com os textos do termo de responsabilidade informado
 * @param \PDO $conn
 * @param string $slug
 * @return array
 */
function getStatementsInfo (\PDO $conn, string $slug): array
{
    $empty = [];
    $empty['header'] = "";
    $empty['title'] = "";
    $empty['p1_bfr_list'] = "";
    $empty['p2_bfr_list'] = "";
    $empty['p3_bfr_list'] = "";
    $empty['p1_aft_list'] = "";
    $empty['p2_aft_list'] = "";
    $empty['p3_aft_list'] = "";
    
    if (empty($slug)) {
        return $empty;
    }
        
    $sql = "SELECT * FROM asset_statements WHERE slug = '{$slug}'";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount())
            return $res->fetch();
        return $empty;
    }
    catch (Exception $e) {
        return $empty;
    }
}


/**
 * getUnits
 * Retorna um array com a listagem com as unidades/instituicoes ou de uma unidade específica caso o id seja informado
 * @param \PDO $conn
 * @param int|null $status : 0 - inactive | 1 - active
 * @param int|null $id
 * @param int|null $client 
 * @return array
 * keys: inst_cod | inst_nome | inst_status | id (client) | fullname (client) | nickname (client)
 */
function getUnits (\PDO $conn, ?int $status = 1, ?int $id = null, ?int $client = null, ?string $allowedUnits = null ): array
{
    $return = [];

    $terms = "";
    
    if (!$id) {

        if ($status != null) {
            $terms = "WHERE un.inst_status = :status ";
        }

        if ($client != null) {
            $terms .= (!empty($terms) ? "AND " : "WHERE ");
            $terms .= " (un.inst_client = :client OR un.inst_client IS NULL)";
        }

        if ($allowedUnits != null) {
            $terms .= (!empty($terms) ? "AND " : "WHERE ");
            $terms .= " un.inst_cod IN ({$allowedUnits})";
        }
    }
    
    $terms = ($id ? "WHERE un.inst_cod = :id " : $terms); /* Se tiver $id não importa os demais critérios */

    $sql = "SELECT 
                un.*, cl.id, cl.fullname, cl.nickname 
            FROM 
                instituicao un
            LEFT JOIN
                clients cl ON cl.id = un.inst_client
                {$terms} 
                ORDER BY inst_nome";
    try {
        $res = $conn->prepare($sql);
        if ($id)
            $res->bindParam(':id', $id, PDO::PARAM_INT);
        else {
            if ($status != null)
                $res->bindParam(':status', $status, PDO::PARAM_INT);
            if ($client)
                $res->bindParam(':client', $client, PDO::PARAM_INT);
        }

        $res->execute();
        
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            if ($id)
                return $data[0];
            return $data;
        }
        return $return;
    }
    catch (Exception $e) {
        $return['error'] = $e->getMessage();
        return $return;
    }
}



/**
 * getDepartments
 * Retorna um array com a listagem com os departamentos (com prédio) ou de uma departamento específico caso o id seja informado
 * @param \PDO $conn
 * @param int|null $id
 * @param int|null $status : 0 - inactive | 1 - active
 * @param int|null $unit
 * @param int|null $client
 * @return array
 * keys: l.*, reit_nome, prioridade, dominio, pred_desc, tempo_resposta, unidade
 */
function getDepartments (\PDO $conn, ?int $status = 1, ?int $id = null, ?int $unit = null, ?int $client = null): array
{
    $return = [];

    $terms = '';
    
    if (!$id) {
        if ($status !== null)
            $terms .= " WHERE l.loc_status = :status ";
        
        if ($unit !== null) {
            $terms .= (!empty($terms) ? " AND " : " WHERE ");
            $terms .= " (l.loc_unit = :unit OR l.loc_unit IS NULL)";
        }

        if ($client !== null) {
            $terms .= (!empty($terms) ? " AND " : " WHERE ");
            $terms .= " (cl.id = :client OR cl.id IS NULL)";
        }
    }

    $terms = ($id ? "WHERE l.loc_id = :id " : $terms); /* Se tiver $id não importa o $status */

    $sql = "SELECT 
                l.* , r.reit_nome, pr.prior_nivel AS prioridade, d.dom_desc AS dominio, 
                pred.pred_desc, 
                sla.slas_desc as tempo_resposta, 
                un.inst_nome as unidade, 
                cl.id as client_id, cl.fullname, cl.nickname
            FROM 
                localizacao AS l
                LEFT  JOIN reitorias AS r ON r.reit_cod = l.loc_reitoria
                LEFT  JOIN prioridades AS pr ON pr.prior_cod = l.loc_prior
                LEFT  JOIN dominios AS d ON d.dom_cod = l.loc_dominio
                LEFT JOIN predios as pred on pred.pred_cod = l.loc_predio 
                LEFT JOIN sla_solucao as sla on sla.slas_cod = pr.prior_sla
                LEFT JOIN instituicao as un on un.inst_cod = l.loc_unit
                LEFT JOIN clients as cl on cl.id = un.inst_client
                {$terms}
                ORDER BY local";

    try {
        $res = $conn->prepare($sql);
        if ($id) {
            $res->bindParam(':id', $id, PDO::PARAM_INT);
        }
        else {
            if ($unit !== null)
                $res->bindParam(':unit', $unit, PDO::PARAM_INT);
            if ($status !== null)
                $res->bindParam(':status', $status, PDO::PARAM_INT);
            if ($client !== null)
                $res->bindParam(':client', $client, PDO::PARAM_INT);
        }

        $res->execute();
        
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            if ($id)
                return $data[0];
            return $data;
        }
        return $return;
    }
    catch (Exception $e) {
        return $return;
    }
}


/**
 * getBuildings
 * Retorna a listagem de prédios com unidades e clientes
 *
 * @param \PDO $conn
 * @param int|null $id
 * @param int|null $unit
 * @param int|null $client
 * 
 * @return array
 */
function getBuildings (\PDO $conn, ?int $id = null, ?int $unit = null, ?int $client = null): array
{
    $terms = "";
    if ($id) {
        $terms = " WHERE p.pred_cod = :id ";
    } elseif ($unit) {
        $terms = " WHERE u.inst_cod = :unit OR u.inst_cod IS NULL ";
    } elseif ($client) {
        $terms = " WHERE c.id = :client OR c.id IS NULL ";
    }

    $sql = "SELECT
                p.pred_cod,
                p.pred_desc,
                u.inst_cod,
                u.inst_nome,
                c.id, 
                c.nickname
            FROM
                predios p
                LEFT JOIN instituicao u ON u.inst_cod = p.pred_unit 
                LEFT JOIN clients c ON c.id = u.inst_client 
                {$terms} 
            ORDER BY    
                p.pred_desc, c.nickname, u.inst_nome
            ";

    $res = $conn->prepare($sql);
    if ($id) {
        $res->bindParam(':id', $id, PDO::PARAM_INT);
    } elseif ($unit){
        $res->bindParam(':unit', $unit, PDO::PARAM_INT);
    } elseif ($client){
        $res->bindParam(':client', $client, PDO::PARAM_INT);
    }

    $res->execute();
        
    if ($res->rowCount()) {
        foreach ($res->fetchAll() as $row) {
            $data[] = $row;
        }
        if ($id)
            return $data[0];
        return $data;
    }
    return [];
}


/**
 * getRectories
 * Retorna a listagem de reitorias
 * @param \PDO $conn
 * @param int|null $id
 * @param int|null $unit
 * @param int|null $client
 * 
 * @return array
 */
function getRectories (\PDO $conn, ?int $id = null, ?int $unit = null, ?int $client = null): array
{
    $terms = "";
    if ($id) {
        $terms = " WHERE r.reit_cod = :id ";
    } elseif ($unit) {
        $terms = " WHERE u.inst_cod = :unit OR u.inst_cod IS NULL ";
    } elseif ($client) {
        $terms = " WHERE c.id = :client OR c.id IS NULL ";
    }

    $sql = "SELECT
                r.reit_cod,
                r.reit_nome,
                u.inst_cod,
                u.inst_nome,
                c.id, 
                c.nickname
            FROM
                reitorias r
                LEFT JOIN instituicao u ON u.inst_cod = r.reit_unit 
                LEFT JOIN clients c ON c.id = u.inst_client 
                {$terms} 
            ORDER BY    
                r.reit_nome, c.nickname, u.inst_nome
            ";

    $res = $conn->prepare($sql);
    if ($id) {
        $res->bindParam(':id', $id, PDO::PARAM_INT);
    } elseif ($unit){
        $res->bindParam(':unit', $unit, PDO::PARAM_INT);
    } elseif ($client){
        $res->bindParam(':client', $client, PDO::PARAM_INT);
    }

    $res->execute();
        
    if ($res->rowCount()) {
        foreach ($res->fetchAll() as $row) {
            $data[] = $row;
        }
        if ($id)
            return $data[0];
        return $data;
    }
    return [];
}


/**
 * getDomains
 * Retorna a listagem de domínios
 *
 * @param \PDO $conn
 * @param int|null $id
 * @param int|null $unit
 * @param int|null $client
 * 
 * @return array
 */
function getDomains (\PDO $conn, ?int $id = null, ?int $unit = null, ?int $client = null): array
{
    $terms = "";
    if ($id) {
        $terms = " WHERE d.dom_cod = :id ";
    } elseif ($unit) {
        $terms = " WHERE u.inst_cod = :unit OR u.inst_cod IS NULL ";
    } elseif ($client) {
        $terms = " WHERE c.id = :client OR c.id IS NULL ";
    }

    $sql = "SELECT
                d.dom_cod,
                d.dom_desc,
                u.inst_cod,
                u.inst_nome,
                c.id, 
                c.nickname
            FROM
                dominios d
                LEFT JOIN instituicao u ON u.inst_cod = d.dom_unit 
                LEFT JOIN clients c ON c.id = u.inst_client 
                {$terms} 
            ORDER BY    
                d.dom_desc, c.nickname, u.inst_nome
            ";

    $res = $conn->prepare($sql);
    if ($id) {
        $res->bindParam(':id', $id, PDO::PARAM_INT);
    } elseif ($unit){
        $res->bindParam(':unit', $unit, PDO::PARAM_INT);
    } elseif ($client){
        $res->bindParam(':client', $client, PDO::PARAM_INT);
    }

    $res->execute();
        
    if ($res->rowCount()) {
        foreach ($res->fetchAll() as $row) {
            $data[] = $row;
        }
        if ($id)
            return $data[0];
        return $data;
    }
    return [];
}



/**
 * getPriorities
 * Retorna um array com a listagem de prioridades de atendimento ou uma prioridade específica caso o id seja informado
 * @param \PDO $conn
 * @param null|int $id
 * @return array
 * keys: pr_cod | pr_nivel | pr_default | pr_desc | pr_color 
 */
function getPriorities (\PDO $conn, ?int $id = null ): array
{
    $return = [];

    $terms = '';
    
    $terms = ($id ? "WHERE pr_cod = :id " : $terms); /* Se tiver $id não importa o $status */

    $sql = "SELECT * FROM prior_atend {$terms} ORDER BY pr_desc";
    try {
        $res = $conn->prepare($sql);
        if ($id)
            $res->bindParam(':id', $id, PDO::PARAM_INT);

        $res->execute();
        
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            if ($id)
                return $data[0];
            return $data;
        }
        return $return;
    }
    catch (Exception $e) {
        return $return;
    }
}


/**
 * getDefaultPriority
 * Retorna um array com a prioridade padrão de atendimento
 * @param \PDO $conn
 * @return array
 * keys: pr_cod | pr_nivel | pr_default | pr_desc | pr_color 
 */
function getDefaultPriority (\PDO $conn): array
{
    $default = 1;
    $sql = "SELECT * FROM prior_atend WHERE pr_default = :default ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':default', $default, PDO::PARAM_INT);

        $res->execute();
        
        if ($res->rowCount()) {
            // $data[] = $res->fetch();
            return $res->fetch();
        }
        return [];
    }
    catch (Exception $e) {
        return [];
    }
}


/**
 * updateLastLogon
 * Atualiza a informação sobre a data do último logon do usuário
 * @param \PDO $conn
 * @param int $userId
 * @return void
 */
function updateLastLogon (\PDO $conn, int $userId): void
{
    $sql = "UPDATE usuarios SET last_logon = '" . date("Y-m-d H:i:s") . "', forget = NULL WHERE user_id = '{$userId}' ";
    try {
        $conn->exec($sql);
    }
    catch (Exception $e) {
        return ;
    }
}

/**
 * getMailConfig
 * Retorna o array com as informações de configuração de e-mail
 * @param \PDO $conn
 * @return array
 */
function getMailConfig (\PDO $conn): array
{
    $sql = "SELECT * FROM mailconfig";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount())
            return $res->fetch();
        return [];
    }
    catch (Exception $e) {
        return [];
    }
}
/**
 * getEventMailConfig
 * Retorna o array com as informações dos templates de mensagens de e-mail para cada evento
 * @param \PDO $conn
 * @param string $event
 * @return array
 */
function getEventMailConfig (\PDO $conn, string $event): array
{
    $sql = "SELECT * FROM msgconfig WHERE msg_event like (:event)";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':event', $event, PDO::PARAM_STR);
        $res->execute();

        if ($res->rowCount())
            return $res->fetch();
        return [];
    }
    catch (Exception $e) {
        return [];
    }
}

/**
 * getStatusInfo
 * Retorna o array com as informações do status filtrado
 * [stat_id], [status], [stat_cat], [stat_painel], [stat_time_freeze], [stat_ignored]
 * @param \PDO $conn
 * @param int $statusId
 * @return array
 */
function getStatusInfo ($conn, ?int $statusId): array
{
    if (!$statusId)
        return array("status" => "", "stat_cat" => "", "stat_painel" => "", "stat_time_freeze" => "");
    
    $sql = "SELECT * FROM `status` WHERE stat_id = '" . $statusId . "'";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount())
            return $res->fetch();
        return [];
    }
    catch (Exception $e) {
        return [];
    }
}



/**
 * getOperatorTickets
 * Retorna o total de chamados vinculados a um determinado operador
 * @param \PDO $conn
 * @param int $userId
 * @return int
 */
function getOperatorTickets (\PDO $conn, int $userId): int
{
    $sql = "SELECT 
                count(*) AS total 
            FROM 
                ocorrencias o, `status` s 
            WHERE 
                o.operador = {$userId} AND 
                o.status = s.stat_id AND 
                s.stat_painel = 1  AND 
                o.oco_scheduled = 0
            ";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount())
            return $res->fetch()['total'];
        return 0;
    }
    catch (Exception $e) {
        return 0;
    }
}


/**
 * getAreaInfo
 * Retorna o array com as informações da área de atendimento:
 * [area_id], [area_name], [status], [email], [atende], [screen], [wt_profile], [sis_months_done ]
 * @param \PDO $conn
 * @param int $areaId
 * @return array
 */
function getAreaInfo (\PDO $conn, int $areaId): array
{
    $sql = "SELECT 
                sis_id as area_id, 
                sistema as area_name, 
                sis_status as status, 
                sis_email as email, 
                sis_atende as atende, 
                sis_screen as screen, 
                sis_wt_profile as wt_profile, 
                sis_months_done 
            FROM 
                sistemas 
            WHERE 
                sis_id = '" . $areaId . "'";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount())
            return $res->fetch();
        return [];
    }
    catch (Exception $e) {
        return [];
    }
}


/**
 * getAreaAdminsOld
 * Retorna os admins da área informada (apenas área primária) por $areaId ou vazio
 * Indices retornados: user_id | nome | email
 * @param \PDO $conn
 * @param int $areaId
 * 
 * @return array
 */
function getAreaAdminsOld (\PDO $conn, int $areaId):array
{
    $data = [];
    $sql = "SELECT user_id, nome, email FROM usuarios WHERE AREA = :areaId AND user_admin = 1 ORDER BY nome";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':areaId', $areaId);
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return [];
    }
}

/**
 * getAreaAdmins
 * Retorna os admins da área informada (primária ou secundárias) por $areaId ou vazio
 *
 * @param \PDO $conn
 * @param int $areaId
 * 
 * @return array
 */
function getAreaAdmins (\PDO $conn, int $areaId):array
{
    $dataPrimary = [];
    $dataSecundary = [];

    /**
     * Checagem na tabela sobre as áreas secundárias
     */
    $sql = "SELECT 
                u.user_id, 
                u.nome, 
                u.email
            FROM
                usuarios u, users_x_area_admin uadmin
            WHERE
                u.user_id = uadmin.user_id AND
                u.user_admin = 1 AND
                uadmin.area_id = :areaId
            ORDER BY
                u.nome
            ";

    try {
        $res = $conn->prepare($sql);
        $res->bindParam(":areaId", $areaId, PDO::PARAM_INT);
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $dataSecundary[] = $row;
            }
        }
    }
    catch (Exception $e) {
        return [
                'error' => $e->getMessage(),
                'sql'   => $sql
            ];
    }

    /**
     * Checagem sobre as áreas primárias
     */
    $sql = "SELECT user_id, nome, email FROM usuarios WHERE AREA = :areaId AND user_admin = 1 ORDER BY nome";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':areaId', $areaId);
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $dataPrimary[] = $row;
            }
        }
    }
    catch (Exception $e) {
        return [
            'error' => $e->getMessage(),
            'sql'   => $sql
        ];
    }


    $output = array_merge($dataPrimary, $dataSecundary);
    $output = unique_multidim_array($output, 'user_id');
    
    $keys = array_column($output, 'nome');
    array_multisort($keys, SORT_ASC, $output);

    return $output;

}


/**
 * getManagedAreasByUser
 * Retorna array com a listagem das áreas que o usuário informado é gerente (área primária e secundárias)
 * @param \PDO $conn
 * @param int $userId
 * 
 * @return array
 */
function getManagedAreasByUser (\PDO $conn, int $userId):array
{
    $dataPrimary = [];
    $dataSecundary = [];

    /**
     * Checagem na tabela sobre as áreas não primárias
     */
    $sql = "SELECT 
                s.sis_id, 
                s.sistema, 
                s.sis_email
            FROM
                -- sistemas s, usuarios u, usuarios_areas uareas, users_x_area_admin uadmin
                sistemas s, usuarios u, users_x_area_admin uadmin
            WHERE
                -- s.sis_id = uareas.uarea_sid AND 
                -- uareas.uarea_sid = uadmin.area_id AND 
                -- u.user_id = uareas.uarea_uid AND 
                s.sis_id = uadmin.area_id AND 
                uadmin.user_id = u.user_id AND 
                u.user_admin = 1 AND 
                u.user_id = :userId
            ORDER BY
                s.sistema
            ";

    try {
        $res = $conn->prepare($sql);
        $res->bindParam(":userId", $userId, PDO::PARAM_INT);
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $dataSecundary[] = $row;
            }
        }
    }
    catch (Exception $e) {
        return [
                'error' => $e->getMessage(),
                'sql'   => $sql
            ];
    }

    /**
     * Checagem sobre as áreas primárias
     */
    $sql = "SELECT 
                s.sis_id, 
                s.sistema, 
                s.sis_email 
            FROM 
                sistemas s, 
                usuarios u
            WHERE 
                u.AREA = s.sis_id AND
                u.user_id = :userId AND 
                u.user_admin = 1 
            ORDER BY s.sistema";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':userId', $userId);
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $dataPrimary[] = $row;
            }
        }
    }
    catch (Exception $e) {
        return [
            'error' => $e->getMessage(),
            'sql'   => $sql
        ];
    }

    $output = array_merge($dataPrimary, $dataSecundary);
    $output = unique_multidim_array($output, 'sis_id');
    
    $keys = array_column($output, 'sistema');
    array_multisort($keys, SORT_ASC, $output);

    return $output;

}


/**
 * getAreas
 * Retorna o array de registros das áreas cadastradas:
 * [sis_id], [sistema], [status], [sis_email], [sis_atende], [sis_screen], [sis_wt_profile]
 * @param \PDO $conn
 * @param int $all |1: todos os registros| 0: checará os outros parametros de filtro
 * @param int|null $status |0: inativas| 1: ativas | null: qualquer
 * @param int|null $atende |0: somente abertura| 1: atende chamados | null: qualquer
 * @param array|null $ids: caso sejam informados IDS, a consulta retornará apenas o registros correspondentes
 * @return array
 */
function getAreas (\PDO $conn, int $all = 1, ?int $status = 1, ?int $atende = 1, ?array $ids = null): array
{
    $terms = "";
    
    if ($ids !== null && !empty($ids)) {
        $stringIds = implode(',', $ids);
        $terms = " AND sis_id IN ({$stringIds})";
    } elseif ($all == 0) {
        // $terms .= ($status == 1 ? " AND sis_status = 1 " : " AND sis_status = 0 ");
        $terms .= (isset($status) && $status == 1 ? " AND sis_status = 1 " : (isset($status) && $status == 0 ? " AND sis_status = 0 " : ""));
        // $terms .= ($atende == 1 ? " AND sis_atende = 1 " : " AND sis_atende = 0 ");
        $terms .= (isset($atende) && $atende == 1 ? " AND sis_atende = 1 " : (isset($atende) && $atende == 0 ? " AND sis_atende = 0 " : ""));
    }
    
    $data = [];
    $sql = "SELECT 
                *
            FROM 
                sistemas 
            WHERE 
                1 = 1 
                {$terms}
            ORDER BY sistema";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return ["error" => $e->getMessage()];
    }
}

/**
 * getModuleAccess
 * Retorna se a área tem permissão de acesso ao módulo do sistema:
 * [perm_area], [perm_modulo]
 * @param \PDO $conn: conexão PDO
 * @param int $module - 1: ocorrências - 2: inventário
 * @param mixed $areaId - id da área de atendimento - podem ser várias áreas (secundárias) 
 * @return bool
 */
function getModuleAccess (\PDO $conn, int $module, $areaId): bool
{
    $sql = "SELECT 
                perm_area, perm_modulo
            FROM 
                permissoes 
            WHERE 
                perm_modulo = '" . $module . "' 
            AND
                perm_area IN ('" . $areaId . "') ";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount())
            return true;
        return false;
    }
    catch (Exception $e) {
        return false;
    }
}


/**
 * getStatus
 * Retorna o array de registros dos status cadastradas:
 * [stat_id], [status], [stat_cat], [stat_painel], [stat_time_freeze], [stat_ignored]
 * @param \PDO $conn
 * @param int $all 1: todos os registros | 0: checará os outros parametros de filtro
 * @param string $painel 1: vinculado ao operador, 2: principal  3: oculto
 * @param string $timeFreeze 0: status sem parada 1: status de parada
 * @param array | null $except : array com ids de status para não serem listados
 * @return array
 */
function getStatus (\PDO $conn, int $all = 1, string $painel = '1,2,3', string $timeFreeze = '0,1', ?array $except = null): array
{
    $terms = "";
    $excluding = "";
    if ($all == 0) {
        $terms .= " AND stat_painel in ({$painel}) ";
        $terms .= " AND stat_time_freeze in ({$timeFreeze}) ";

        if ($except && !empty($except)) {
            $treatedExcept = array_map('intval', $except);
            foreach ($treatedExcept as $exclude) {
                if (strlen((string)$excluding)) $excluding .= ",";
                $excluding .= $exclude;
            }
            $terms .= " AND stat_id NOT IN ({$excluding}) ";
        }
    }
    
    $data = [];
    $sql = "SELECT 
                *
            FROM 
                status 
            WHERE 
                1 = 1 
                {$terms}
            ORDER BY status";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return [];
    }
}

/**
 * getStatusById
 * Retorna o array com o registro pesquisado
 * @param \PDO $conn
 * @param int $id
 * @return array
 */
function getStatusById(\PDO $conn, int $id): array
{
    $empty = [];

    $sql = "SELECT * FROM `status` WHERE stat_id = {$id} ";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount())
            return $res->fetch();
        return $empty;
    }
    catch (Exception $e) {
        // return $e->getMessage();
        return $empty;
    }
}



/**
 * getTicketEntries
 * Retorna os assentamentos do chamado informado
 * Fields: 
 * @param \PDO $conn
 * @param int $ticket
 * @param bool|null $private
 * 
 * @return array|null
 */
function getTicketEntries(\PDO $conn, int $ticket, ?bool $private = false): ?array
{

    $terms = "";
    if (!$private) {
        $terms = " AND a.asset_privated = 0 ";
    }

    $data = [];
    /* $sql = "SELECT 
                a.*, u.*
            FROM 
                assentamentos a, usuarios u 
            WHERE 
                a.responsavel = u.user_id AND
                a.ocorrencia = :ticket 
                {$terms}
            ORDER BY numero"; */

    $sql = "SELECT 
                a.*, u.* 
            FROM 
                assentamentos a LEFT JOIN usuarios u ON u.user_id = a.responsavel 
            WHERE 
                a.ocorrencia = :ticket 
                {$terms}
            ORDER BY numero";
            
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':ticket', $ticket, PDO::PARAM_INT);
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return [];
    }
}



/**
 * getLastEntry
 * Retorna o array com as informações do último assentamento do chamado:
 * [numero], [ocorrencia], [assentamento], [data], [responsavel], [asset_privated], [tipo_assentamento]
 * @param \PDO $conn
 * @param int $ticket
 * @param bool $onlyPublic Define se também será considerado assentamento privado
 * @return array
 */
function getLastEntry (\PDO $conn, int $ticket, bool $onlyPublic = true): array
{
    $empty = [];
    $empty['numero'] = "";
    $empty['ocorrencia'] = "";
    $empty['assentamento'] = "";
    $empty['data'] = "";
    $empty['responsavel'] = "";
    $empty['asset_privated'] = "";
    $empty['tipo_assentamento'] = "";

    $terms = ($onlyPublic ? " AND asset_privated = 0 " : "");
    
    $sql = "SELECT 
                * 
            FROM 
                assentamentos 
            WHERE 
                ocorrencia = '{$ticket}' 
                AND
                numero = (SELECT MAX(numero) FROM assentamentos WHERE ocorrencia = '{$ticket}' {$terms} )
            ";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $row = $res->fetch();
        $row['assentamento'] = str_replace(["'", "\""], "", $row['assentamento']);
        return $row;
        // return $res->fetch();
    }
    return $empty;
}


/**
 * getLastScheduledDate
 * Retorna a última data de agendamento do chamado
 *
 * @param \PDO $conn
 * @param int $ticket
 * 
 * @return string|null
 */
function getLastScheduledDate (\PDO $conn, int $ticket): ?string
{
    
    $sql = "SELECT oco_scheduled_to 
            FROM ocorrencias 
            WHERE 
                numero = :ticket AND 
                oco_scheduled_to IS NOT NULL";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':ticket', $ticket, PDO::PARAM_INT);
        $res->execute();
        if ($res->rowCount()) {
            return $res->fetch()['oco_scheduled_to'];
        }

        /* ocorrencias_log.log_data_agendamento */
        $sql = "SELECT
                log_data_agendamento
            FROM
                ocorrencias_log
            WHERE 
                log_numero = :ticket AND
                log_id = (
                            SELECT MAX(log_id) 
                            FROM ocorrencias_log 
                            WHERE 
                                log_numero = :ticket AND 
                                log_data_agendamento IS NOT NULL
                        )
        ";
        try {
            $res = $conn->prepare($sql);
            $res->bindParam(':ticket', $ticket, PDO::PARAM_INT);
            $res->execute();
            if ($res->rowCount()) {
                return $res->fetch()["log_data_agendamento"];
            }
            return null;
        } catch (Exception $e) {
            // echo $e->getMessage();
            return null;
        }

    } catch (Exception $e) {
        // echo $e->getMessage();
        return null;
    }
}



/**
 * getTicketFiles
 * Retorna um array com as informações dos arquivos anexos ao chamado informado
 * @param \PDO $conn
 * @param int $ticket
 * 
 * @return array|null
 */
function getTicketFiles(\PDO $conn, int $ticket): ?array
{
    $sql = "SELECT * FROM imagens WHERE img_oco = :ticket";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(":ticket", $ticket, PDO::PARAM_INT);
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return [];
    }
}


/**
 * getTicketRelatives
 * Retorna um array com os números dos chamados relacionados (pai e filhos)
 * @param \PDO $conn
 * @param int $ticket
 * 
 * @return array|null
 */
function getTicketRelatives(\PDO $conn, int $ticket): ?array
{
    $sql = "SELECT * FROM ocodeps WHERE dep_pai = :ticket OR dep_filho = :ticket";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(":ticket", $ticket, PDO::PARAM_INT);
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return [];
    }
}


/**
 * Retrieves the first father of a given ticket number and populates the $store array with the relationships.
 *
 * @param PDO $conn the database connection object
 * @param int $ticketNumber the ticket number to search for
 * @param array &$store the array to store the relationships
 * @throws Exception if an error occurs while executing the SQL query
 * @return int the ticket number of the first father
 */
function getFirstFather(\PDO $conn, int $ticketNumber, array &$store): ?int
{
    $data = [];
    $parent = [];
    $sons = [];
    
    // primeiro checo se possui qualquer relacionamento
    $sql = "SELECT * FROM ocodeps WHERE dep_pai = :ticket OR dep_filho = :ticket ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(":ticket", $ticketNumber, PDO::PARAM_INT);
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
                $parent[] = $row['dep_pai'];
                $sons[] = $row['dep_filho'];
            }
        
        
            /* Como só pode existir um pai - reduzo o array em que ele aparece */
            $parent = array_unique($parent);
            if (in_array($ticketNumber, $parent)) {
                unset($parent[array_search($ticketNumber, $parent)]);
            }

            $relation['parent'] = $parent;
            
            $sons = array_unique($sons);
            if (in_array($ticketNumber, $sons)) {
                unset($sons[array_search($ticketNumber, $sons)]);
            }
            $relation['sons'] = $sons;

            $store[] = $ticketNumber;

            /* Recursividade para subir na hierarquia */
            foreach ($relation['parent'] as $parent) {
                getFirstFather($conn, $parent, $store);
            }

            return min($store);
        }
        return null;
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Retrieves the ticket's down relations from the database.
 *
 * @param PDO $conn The database connection.
 * @param int $ticketNumber The ticket number to retrieve the relations for.
 * @param array &$store The reference to the array to store the relations in.
 * @throws Exception When an error occurs during the retrieval process.
 * @return array The array of relations for the given ticket number.
 */
function getTicketDownRelations(\PDO $conn, ?int $ticketNumber, array &$store): array
{
    $data = [];
    $parent = [];
    $sons = [];

    if (!$ticketNumber) {
        return [];
    }
    
    // primeiro checo se possui qualquer relacionamento
    $sql = "SELECT * FROM ocodeps WHERE dep_pai = :ticket OR dep_filho = :ticket ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(":ticket", $ticketNumber, PDO::PARAM_INT);
        $res->execute();
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
                $parent[] = $row['dep_pai'];
                $sons[] = $row['dep_filho'];
            }
            $parent = array_unique($parent);
        
            if (in_array($ticketNumber, $parent)) {
                unset($parent[array_search($ticketNumber, $parent)]);
            }

            /* Cada chamado só terá um único pai */
            $relation['parent'] = (!empty($parent[0]) ? $parent[0] : null);
            
            $sons = array_unique($sons);
            if (in_array($ticketNumber, $sons)) {
                unset($sons[array_search($ticketNumber, $sons)]);
            }
            $relation['sons'] = $sons;

            $store[$ticketNumber] = $relation;

            /* Recursividade para percorrer os filhos */
            foreach ($relation['sons'] as $son) {
                getTicketDownRelations($conn, $son, $store);
            }

            return $store;
        }
        return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}


/**
 * Generates an HTML tree structure using a given array.
 * Receberá o array resultante da função getTicketDownRelations()
 *
 * @param array $relations The array representing the family tree.
 * @param int $parentId The ID of the parent node.
 * @return string The HTML tree structure.
 */
function generateFamilyTreeGeneric(array $relations, ?int $parentId = null) {
    $html = '<ul>';
    foreach ($relations as $key => $ticket) {
        if ($ticket['parent'] == $parentId) {
            $html .= '<li><div>' . $key;
            if (isset($ticket['sons'])) {
                $html .= generateFamilyTreeGeneric($relations, $key);
            }
            $html .= '</div></li>';
        }
    }
    $html .= '</ul>';
    return $html;
}


function generateFamilyTree(array $relations, ?int $parentId = null, string $nodeIdPrefix = 'tree_') {
    
    $html = '';
    $firstRound = ($parentId === null);
    if ($firstRound) {
        $html = '<ul class="ocomon-tree"><li>';
    } else {
        $html .= '<ul>';
    }
    
    foreach ($relations as $key => $ticket) {
        if ($ticket['parent'] == $parentId) {
            
            if ($firstRound) {
                $html .= '<div class="sticky tree-nodes" data-ticket="' . $key . '" id="' . $nodeIdPrefix . $key . '">' . $key . '<div id="badge_' . $key . '" class="badge-light"></div></div>';
            } else {
                $html .= '<li><div class="tree-nodes" data-ticket="' . $key . '" id="' . $nodeIdPrefix . $key . '">' . $key . '<div id="badge_' . $key . '" class="badge-light"></div></div>';
            }
            
            if (isset($ticket['sons'])) {
                $html .= generateFamilyTree($relations, $key);
            }
            if (!$firstRound) {
                $html .= '</li>';
            }
        }
    }

    if ($firstRound) {
        $html .= '</li></ul>';
    } else {
        $html .= '</ul>';
    }

    return $html;
}



/**
 * hasDependency
 * Retorna se um dado chamado possui dependências em subchamados
 * @param \PDO $conn
 * @param int $ticket
 * 
 * @return bool
 */
function hasDependency(\PDO $conn, int $ticket): bool
{
    $sql = "SELECT * FROM ocodeps WHERE dep_pai = :ticket ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(":ticket", $ticket, PDO::PARAM_INT);
        $res->execute();
        if ($res->rowCount()){
            foreach ($res->fetchAll() as $row) {
                $sql = "SELECT o.numero FROM ocorrencias o, `status` s 
                        WHERE
                            o.numero = :ticket AND 
                            o.`status` = s.stat_id AND 
                            s.stat_painel NOT IN (3)
                ";
                try {
                    $result = $conn->prepare($sql);
                    $result->bindParam(':ticket', $row['dep_filho'], PDO::PARAM_INT);
                    $result->execute();
                    if ($result->rowCount()) {
                        return true;
                    }
                }
                catch (Exception $e) {
                    return true;
                }
            }
        }
        return false;
    }
    catch (Exception $e) {
        return true;
    }
    return false;
}




/**
 * getSolutionInfo
 * Retorna o array com as informações de descrição técnica e solução para o chamado ou vazio caso nao tenha registro:
 * [numero], [problema], [solucao], [data], [responsavel]
 * @param \PDO $conn
 * @param int $ticket
 * @return array
 */
function getSolutionInfo (\PDO $conn, int $ticket): array
{
    $sql = "SELECT 
                * 
            FROM 
                solucoes 
            WHERE 
                numero = :ticket 
            ";
    
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':ticket', $ticket, PDO::PARAM_INT);
        $res->execute();
        if ($res->rowCount()) {
            return $res->fetch();
        }
        return [];
    }
    catch (Exception $e) {
        return [];
    }

}


/**
 * getGlobalUri
 * Retorna a url de acesso global da ocorrencia
 * @param \PDO $conn
 * @param int $ticket
 * @return string
 */
function getGlobalUri (\PDO $conn, int $ticket): string
{
    $config = getConfig($conn);

    $sql = "SELECT * FROM global_tickets WHERE gt_ticket = '" . $ticket . "' ";
    $res = $conn->query($sql);
    if ( $res->rowCount() ) {
        $row = $res->fetch();
        return $config['conf_ocomon_site'] . "/ocomon/geral/ticket_show.php?numero=" . $ticket . "&id=" . urlencode($row['gt_id']);
    }

    $rand = random64();
    $rand = str_replace(" ", "+", $rand);
    $sql = "INSERT INTO global_tickets (gt_ticket, gt_id) VALUES ({$ticket}, '" . $rand . "')";
    $conn->exec($sql);
    
    return $config['conf_ocomon_site'] . "/ocomon/geral/ticket_show.php?numero=" . $ticket . "&id=" . urlencode($rand);
}


/**
 * getGlobalTicketId
 * Retorna o id global da ocorrência para acesso por qualquer usuário
 * @param \PDO $conn
 * @param int $ticket
 * 
 * @return string|null
 */
function getGlobalTicketId (\PDO $conn, int $ticket): ?string
{
    $sql = "SELECT gt_id FROM global_tickets WHERE gt_ticket = :ticket ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':ticket', $ticket);
        $res->execute();
        if ($res->rowCount()) {
            // return $res->fetch()['gt_id'];
            return str_replace(" ", "+", $res->fetch()['gt_id']);
        }
        return null;
    }
    catch (Exception $e) {
        // $exception .= "<hr>" . $e->getMessage();
        return null;
    }
}


/**
 * getGlobalTicketRatingId
 * Retorna o id random para avaliação do atendimento - Caso o ID não exista, é criado e então retornado
 * @param \PDO $conn
 * @param int $ticket
 * 
 * @return string|null
 */
function getGlobalTicketRatingId (\PDO $conn, int $ticket): ?string
{
    $sql = "SELECT gt_rating_id FROM global_tickets WHERE gt_ticket = :ticket ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':ticket', $ticket);
        $res->execute();
        if ($res->rowCount()) {

            $row = $res->fetch();

            if (!empty($row['gt_rating_id'])) {
                return $row['gt_rating_id'];
            }

            $rand = random64();
            $rand = str_replace(" ", "+", $rand);
            $sql = "UPDATE global_tickets SET gt_rating_id = '{$rand}' WHERE gt_ticket = :ticket";
            try {
                $res = $conn->prepare($sql);
                $res->bindParam(':ticket', $ticket);
                $res->execute();

                return $rand;
            }
            catch (Exception $e) {
                // $exception .= "<hr>" . $e->getMessage();
                // echo $e->getMessage();
                return null;
            }
        }
        return null;
    }
    catch (Exception $e) {
        // $exception .= "<hr>" . $e->getMessage();
        return null;
    }
}

/**
 * getEnvVarsValues
 * Retorna um array com os valores das variáveis de ambiente para serem utilizadas nos templates de envio de e-mail
 * @param \PDO $conn
 * @param int $ticket
 * @return array
 */
function getEnvVarsValues (\PDO $conn, int $ticket, ?array $row = null): array
{
    
    if (!$row) {
        include ("../../includes/queries/queries.php");
        $sql = $QRY["ocorrencias_full_ini"] . " WHERE o.numero = {$ticket} ";
        $res = $conn->query($sql);
        $row = $res->fetch();
    }
    
    $config = getConfig($conn);
    $lastEntry = getLastEntry($conn, $ticket);
    $solution = getSolutionInfo($conn, $ticket);
    $workers = getTicketWorkers($conn, $ticket);

    /* Variáveis de ambiente para os e-mails */
    $vars = array();

    $vars = array();
    $vars['%numero%'] = $row['numero'];
    $vars['%usuario%'] = $row['contato'];
    $vars['%contato%'] = $row['contato'];
    $vars['%contato_email%'] = $row['contato_email'];
    $vars['%descricao%'] = nl2br($row['descricao']);
    $vars['%departamento%'] = $row['setor'];
    $vars['%telefone%'] = $row['telefone'];
    $vars['%site%'] = "<a href='" . $config['conf_ocomon_site'] . "'>" . $config['conf_ocomon_site'] . "</a>";
    $vars['%area%'] = $row['area'];
    $vars['%area_email%'] = $row['area_email'];
    $vars['%operador%'] = $row['nome'];
    $vars['%editor%'] = $row['nome'];
    $vars['%aberto_por%'] = $row['aberto_por'];
    $vars['%problema%'] = $row['problema'];
    $vars['%versao%'] = VERSAO;
    $vars['%url%'] = getGlobalUri($conn, $ticket);
    $vars['%url%'] = str_replace(" ", "+", $vars['%url%']);
    $vars['%linkglobal%'] = $vars['%url%'];

    $vars['%unidade%'] = $row['unidade'];
    $vars['%etiqueta%'] = $row['etiqueta'];
    $vars['%patrimonio%'] = $row['unidade']."&nbsp;".$row['etiqueta'];
    $vars['%data_abertura%'] = dateScreen($row['oco_real_open_date']);
    $vars['%status%'] = $row['chamado_status'];
    $vars['%data_agendamento%'] = (!empty($row['oco_scheduled_to']) ? dateScreen($row['oco_scheduled_to']) : "");
    $vars['%data_fechamento%'] = (!empty($row['data_fechamento']) ? dateScreen($row['data_fechamento']) : "");

    $vars['%dia_agendamento%'] = (!empty($vars['%data_agendamento%']) ? explode(" ", $vars['%data_agendamento%'])[0] : '');
    $vars['%hora_agendamento%'] = (!empty($vars['%data_agendamento%']) ? explode(" ", $vars['%data_agendamento%'])[1] : '');

    $vars['%descricao_tecnica%'] = $solution['problema'] ?? "";
    $vars['%solucao%'] = $solution['solucao'] ?? "";
    $vars['%assentamento%'] = nl2br($lastEntry['assentamento']);

    $vars['%funcionario_responsavel%'] = "";
    $vars['%funcionario%'] = [];
    $vars['%funcionario_email%'] = [];
    $vars['%funcionarios%'] = "";
    $func = "";
    if (!empty($workers)) {
        // $i = 0;
        foreach ($workers as $worker) {
            if (strlen((string)$func) > 0) {
                $func .= ", ";
            }

            if ($worker['main_worker'] == 1) {
                $vars['%funcionario_responsavel%'] = $worker['nome'];
            }

            $func .= $worker['nome'];
            $vars['%funcionario%'][] = $worker['nome'];
            $vars['%funcionario_email%'][] = $worker['email'];
            // $i++;
        }
        $vars['%funcionarios%'] .= $func;
    }
    return $vars;
}

/**
 * getEnvVars
 * Retorna o registro gravado com as variáveis de ambiente disponíveis
 * @param \PDO $conn
 * @return bool | array
 */
function getEnvVars (\PDO $conn)
{
    $sql = "SELECT vars FROM environment_vars";
    try {
        $res = $conn->query($sql);
        return $res->fetch()['vars'];
    }
    catch (Exception $e) {
        return false;
    }
}


/** 
 * insert_ticket_stage
 * Realiza a inserção das informações de período de tempo para o chamado
 * @param \PDO $conn
 * @param int $ticket: número do chamado
 * @param string $stage_type: start|stop
 * @param int $tk_status: status do chamado - só será gravado quando o $stage_type for 'start'
 * @param string $specificDate: data específica para gravar - para os casos de chamados saindo 
 *  da fila de agendamento por meio de processos automatizados
 * @return bool
 * 
*/
function insert_ticket_stage (\PDO $conn, int $ticket, string $stageType, int $tkStatus, string $specificDate = ''): bool
{

    $date = (!empty($specificDate) ? $specificDate : date("Y-m-d H:i:s"));
    
    $sqlTkt = "SELECT * FROM `tickets_stages` 
                WHERE ticket = {$ticket} AND id = (SELECT max(id) FROM tickets_stages WHERE ticket = {$ticket}) ";
    $resultTkt = $conn->query($sqlTkt);
    $recordsTkt = $resultTkt->rowCount();

    /* Nenhum registro do chamado na tabela. Nesse caso posso apenas inserir um novo */
    if (!$recordsTkt && $stageType == 'start') {
        
        $sql = "INSERT INTO tickets_stages (id, ticket, date_start, status_id) 
        values (null, {$ticket}, '" . $date . "', {$tkStatus}) ";
    
    } elseif (!$recordsTkt && $stageType == 'stop') {
        
        /* Para chamados existentes anteriormente à implementação da tickets_stages */
        $sqlDateTicket = "SELECT data_abertura, oco_real_open_date FROM ocorrencias WHERE numero = {$ticket} ";
        $resDateTicket = $conn->query($sqlDateTicket);

        $rowDateTicket = $resDateTicket->fetch();

        $openDate = $rowDateTicket['data_abertura'];
        $realOpenDate = $rowDateTicket['oco_real_open_date'];

        $recordDate = (!empty($realOpenDate) ? $realOpenDate : $openDate);

        /* Chamado já existia - nesse caso adiciono um período de start e stop com data de abertura registrada para o chamado*/
        /* o Status zero será para identificar que o período foi inserido nessa condição especial */
        $sql = "INSERT INTO tickets_stages (id, ticket, date_start, date_stop, status_id) 
        values (null, {$ticket}, '" . $recordDate . "', '" . $date . "', 0) ";
        try {
            $conn->exec($sql);
        }
        catch (Exception $e) {
            return false;
        }
        
        //Não posso iniciar um estágio de tempo sem ter primeiro um registro de 'start'
        // return false;
        return true;
    }

    /* Já há registro para esse chamado na tabela de estágios de tempo */
    if ($recordsTkt) {
        $row = $resultTkt->fetch();

        /* há uma data de parada no último registro */
        if (!empty($row['date_stop'])) {
            /* Então preciso inserir novo registro de start */
            if ($stageType == 'start') {
                $sql = "INSERT INTO tickets_stages (id, ticket, date_start, status_id) 
                        values (null, {$ticket}, '" . $date . "', {$tkStatus}) ";
            } elseif ($stageType == 'stop') {
                return false;
            }
        } else {
            /* Preciso atualizar o registro com a parada (stop) */
            if ($stageType == 'stop') {
                $sql = "UPDATE tickets_stages SET date_stop = '" . $date . "' WHERE id = " . $row['id'] . " ";
            } elseif ($stageType == 'start') {
                return false;
            }
        }
    }
    try {
        $conn->exec($sql);
    }
    catch (Exception $e) {
        return false;
    }

    return true;
}


/**
 * firstLog
 * Insere um registro em ocorrencias_log com o estado atual do chamado caso esse registro não exista
 * @param \PDO $conn
 * @param int $numero: número do chamado
 * @param mixed $tipo_edicao: código do tipo de edição - (0: abertura, 1: edição, ...)
 * @param mixed $auto_record
 * @return bool
 */
function firstLog(\PDO $conn, int $numero, $tipo_edicao='NULL', $auto_record = ''): bool
{
    
    /* $tipo_edicao='NULL' */
    include ("../../includes/queries/queries.php");
    
    //Checando se já existe um registro para o chamado
    $sql_log_base = "SELECT * FROM ocorrencias_log WHERE log_numero = '".$numero."' ";
    $qry = $conn->query($sql_log_base);
    $existe_log = $qry->rowCount();

    if (!$existe_log){//AINDA NAO EXISTE REGISTRO - NESSE CASO ADICIONO UM REGISTRO COMPLETO COM O ESTADO ATUAL DO CHAMADO
    
        $qryfull = $QRY["ocorrencias_full_ini"]." WHERE o.numero = " . $numero;
        $qFull = $conn->query($qryfull);
        $rowfull = $qFull->fetch(PDO::FETCH_OBJ);
        
        $base_descricao = $rowfull->descricao;
        $base_departamento = $rowfull->setor_cod;
        $base_area = $rowfull->area_cod;
        $base_cliente = $rowfull->client_id;
        $base_prioridade = $rowfull->oco_prior;
        $base_problema = $rowfull->prob_cod;
        $base_unidade = $rowfull->unidade_cod;
        $base_etiqueta = $rowfull->etiqueta;
        $base_contato = $rowfull->contato;
        $base_contato_email = $rowfull->contato_email;
        $base_telefone = $rowfull->telefone;
        $base_operador = $rowfull->operador_cod;
        $base_data_agendamento = $rowfull->oco_scheduled_to;
        $base_status = $rowfull->status_cod;
        
        $val = array();
        $val['log_numero'] = $rowfull->numero;
        
        if ($auto_record == ''){
            $val['log_quem'] = $_SESSION['s_uid'];
        } else
            $val['log_quem'] = $base_operador;            
        
        // $val['log_data'] = date("Y-m-d H:i:s");            
        $val['log_data'] = $rowfull->oco_real_open_date;            
        $val['log_prioridade'] = ($rowfull->oco_prior == "" || $rowfull->oco_prior == "-1" )?'NULL':"'$base_prioridade'";  
        $val['log_descricao'] = $rowfull->descricao == ""?'NULL':"'$base_descricao'";  
        $val['log_area'] = ($rowfull->area_cod == "" || $rowfull->area_cod =="-1")?'NULL':"'$base_area'";  
        $val['log_cliente'] = ($rowfull->client_id == "" || $rowfull->client_id =="-1")?'NULL':"'$base_cliente'";  
        $val['log_problema'] = ($rowfull->prob_cod == "" || $rowfull->prob_cod =="-1")?'NULL':"'$base_problema'";  
        $val['log_unidade'] = ($rowfull->unidade_cod == "" || $rowfull->unidade_cod =="-1" || $rowfull->unidade_cod =="0")?'NULL':"'$base_unidade'";  
        $val['log_etiqueta'] = ($rowfull->etiqueta == "" || $rowfull->etiqueta =="-1" || $rowfull->etiqueta =="0")?'NULL':"'$base_etiqueta'";  
        $val['log_contato'] = ($rowfull->contato == "")?'NULL':"'$base_contato'";  
        $val['log_contato_email'] = ($rowfull->contato_email == "")?'NULL':"'$base_contato_email'";  
        $val['log_telefone'] = ($rowfull->telefone == "")?'NULL':"'$base_telefone'";  
        $val['log_departamento'] = ($rowfull->setor_cod == "" || $rowfull->setor_cod =="-1")?'NULL':"'$base_departamento'";  
        $val['log_responsavel'] = ($rowfull->operador_cod == "" || $rowfull->operador_cod =="-1")?'NULL':"'$base_operador'";  
        $val['log_data_agendamento'] = ($rowfull->oco_scheduled_to == "")?'NULL':"'$base_data_agendamento'";  
        $val['log_status'] = ($rowfull->status_cod == "" || $rowfull->status_cod =="-1")?'NULL':"'$base_status'";  
        $val['log_tipo_edicao'] = $tipo_edicao;
        
    
        //GRAVA O REGISTRO DE LOG DO ESTADO ANTERIOR A EDICAO
        $sql_base = "INSERT INTO `ocorrencias_log` ".
            "\n\t(`log_numero`, `log_quem`, `log_data`, `log_descricao`, `log_prioridade`, ".
            "\n\t`log_area`, `log_problema`, `log_unidade`, `log_etiqueta`, ".
            "\n\t`log_contato`, `log_contato_email`, `log_telefone`, `log_departamento`, `log_responsavel`, `log_data_agendamento`, ".
            "\n\t`log_status`, ".
            "\n\t`log_cliente`, ".
            "\n\t`log_tipo_edicao`) ".
            "\nVALUES ".
            "\n\t('".$val['log_numero']."', '".$val['log_quem']."', '".$val['log_data']."', ".$val['log_descricao'].", ".$val['log_prioridade'].", ".
            "\n\t".$val['log_area'].", ".$val['log_problema'].", ".$val['log_unidade'].", ".$val['log_etiqueta'].", ".
            "\n\t".$val['log_contato'].", ".$val['log_contato_email'].", ".$val['log_telefone'].", ".$val['log_departamento'].", ".$val['log_responsavel'].", ".$val['log_data_agendamento'].", ".
            "\n\t".$val['log_status'].", ".
            "\n\t".$val['log_cliente'].", ".
            "\n\t".$val['log_tipo_edicao']." ".
            "\n\t )";
        
        try {
            $conn->exec($sql_base);
            return true;
        }
        catch (Exception $e) {
            return false;
        }
    }
    return false;
}

/**
 * recordLog
 * Grava o registro de modificações do chamado na tabela ocorrencias_log
 * @param \PDO $conn: conexão
 * @param int $ticket: número do chamado
 * @param array $beforePost: array de informações do chamado antes de sofrer modificações
 * @param array $afterPost: array das informações postadas para modificar o chamado
 * @param int $operationType: código do tipo de operação - retornado pelo functions::getOperationType()
 * @return bool: true se conseguir realizar a inserção e false em caso de falha
 */
function recordLog(\PDO $conn, int $ticket, array $beforePost, array $afterPost, int $operationType, ?int $author = null): bool
{
    $logCliente = (array_key_exists("cliente", $afterPost) ? $afterPost['cliente'] : "dontCheck");
    $logPrioridade = (array_key_exists("prioridade", $afterPost) ? $afterPost['prioridade'] : "dontCheck");
    $logArea = (array_key_exists("area", $afterPost) ? $afterPost['area'] : "dontCheck");
    $logProblema = (array_key_exists("problema", $afterPost) ? $afterPost['problema'] : "dontCheck");
    $logUnidade = (array_key_exists("unidade", $afterPost) ? $afterPost['unidade'] : "dontCheck");
    $logEtiqueta = (array_key_exists("etiqueta", $afterPost) ? $afterPost['etiqueta'] : "dontCheck");
    $logContato = (array_key_exists("contato", $afterPost) ? $afterPost['contato'] : "dontCheck");
    $logContatoEmail = (array_key_exists("contato_email", $afterPost) ? $afterPost['contato_email'] : "dontCheck");
    $logTelefone = (array_key_exists("telefone", $afterPost) ? $afterPost['telefone'] : "dontCheck");
    $logDepartamento = (array_key_exists("departamento", $afterPost) ? $afterPost['departamento'] : "dontCheck");
    $logOperador = (array_key_exists("operador", $afterPost) ? $afterPost['operador'] : "dontCheck");
    // $logLastEditor = (array_key_exists("last_editor", $afterPost) ? $afterPost['last_editor'] : "dontCheck");


    $logStatus = (array_key_exists("status", $afterPost) ? $afterPost['status'] : "dontCheck");
    $logAgendadoPara = (array_key_exists("agendadoPara", $afterPost) ? $afterPost['agendadoPara'] : "dontCheck");

    $val = array();
    $val['log_numero'] = $ticket;
    $val['log_quem'] = $_SESSION['s_uid'] ?? $author;            
    $val['log_data'] = date("Y-m-d H:i:s");            

    if ($logPrioridade == "dontCheck") $val['log_prioridade'] = 'NULL'; else
        $val['log_prioridade'] = (($beforePost['oco_prior'] == $logPrioridade) || ((empty($beforePost['oco_prior']) || $beforePost['oco_prior']=="-1" || $beforePost['oco_prior']==NULL)  && ($logPrioridade == "" || $logPrioridade == "-1" || $logPrioridade == NULL)))?'NULL': "'$logPrioridade'"; 
    
    if ($logCliente == "dontCheck") $val['log_cliente'] = 'NULL'; else
        $val['log_cliente'] = ($beforePost['client_id'] == $logCliente)?'NULL':"'$logCliente'";    
    
    if ($logArea == "dontCheck") $val['log_area'] = 'NULL'; else
        $val['log_area'] = ($beforePost['area_cod'] == $logArea)?'NULL':"'$logArea'";
    
    if ($logProblema == "dontCheck") $val['log_problema'] = 'NULL'; else
        $val['log_problema'] = ($beforePost['prob_cod'] == $logProblema)?'NULL':"'$logProblema'";
    
    if ($logUnidade == "dontCheck") $val['log_unidade'] = 'NULL'; else
        $val['log_unidade'] = (($beforePost['unidade_cod'] == $logUnidade) || ((empty($beforePost['unidade_cod']) || $beforePost['unidade_cod']=="-1" || $beforePost['unidade_cod']==NULL)  && ($logUnidade == "" || $logUnidade == "-1" || $logUnidade == NULL)))?'NULL':"'$logUnidade'";  

    if ($logEtiqueta == "dontCheck") $val['log_etiqueta'] = 'NULL'; else
        $val['log_etiqueta'] = ($beforePost['etiqueta'] == $logEtiqueta)?'NULL':"'".noHtml($logEtiqueta)."'";

    if ($logContato == "dontCheck") $val['log_contato'] = 'NULL'; else
        $val['log_contato'] = ($beforePost['contato'] == $logContato)?'NULL':"'".noHtml($logContato)."'";
    
    if ($logContatoEmail == "dontCheck") $val['log_contato_email'] = 'NULL'; else
        $val['log_contato_email'] = ($beforePost['contato_email'] == $logContatoEmail)?'NULL':"'".noHtml($logContatoEmail)."'";

    if ($logTelefone == "dontCheck") $val['log_telefone'] = 'NULL'; else
        $val['log_telefone'] = ($beforePost['telefone'] == $logTelefone)?'NULL':"'$logTelefone'";

    if ($logDepartamento == "dontCheck") $val['log_departamento'] = 'NULL'; else    
        $val['log_departamento'] = (($beforePost['setor_cod'] == $logDepartamento) || ((empty($beforePost['setor_cod']) || $beforePost['setor_cod']=="-1" || $beforePost['setor_cod']==NULL)  && ($logDepartamento == "" || $logDepartamento == "-1" || $logDepartamento == NULL)))?'NULL':"'$logDepartamento'"; 

    if ($logOperador == "dontCheck") $val['log_responsavel'] = 'NULL'; else
        $val['log_responsavel'] = ($beforePost['operador_cod'] == $logOperador)?'NULL':"'$logOperador'";

    if ($logStatus == "dontCheck") $val['log_status'] = 'NULL'; else
        $val['log_status'] = ($beforePost['status_cod'] == $logStatus)?'NULL':"'$logStatus'";

    if ($logAgendadoPara == "dontCheck") $val['log_data_agendamento'] = 'NULL'; else
        $val['log_data_agendamento'] = ($beforePost['oco_scheduled_to'] == $logAgendadoPara || $logAgendadoPara == "")?'NULL':"'$logAgendadoPara'";

    $val['log_tipo_edicao'] = $operationType; //Edição     


    //GRAVA O REGISTRO DE LOG DA ALTERACAO REALIZADA
    $sqlLog = "INSERT INTO `ocorrencias_log` 
    (`log_numero`, `log_quem`, `log_data`, `log_prioridade`, 
    `log_area`, `log_problema`, `log_unidade`, `log_etiqueta`, `log_departamento`, 
    `log_contato`, `log_contato_email`, `log_telefone`, `log_responsavel`, 
    `log_data_agendamento`, `log_status`, `log_cliente`,
    `log_tipo_edicao`) 
    VALUES 
    ('".$val['log_numero']."', '".$val['log_quem']."', '".$val['log_data']."', ".$val['log_prioridade'].", 
    ".$val['log_area'].", ".$val['log_problema'].", ".$val['log_unidade'].", ".$val['log_etiqueta'].", 
    ".$val['log_departamento'].",
    ".$val['log_contato'].", ".$val['log_contato_email'].", ".$val['log_telefone'].", ".$val['log_responsavel'].", ". $val['log_data_agendamento'].", 
    ".$val['log_status'].", ".$val['log_cliente'].", ".$val['log_tipo_edicao'].")";

    try {
        $conn->exec($sqlLog);
        return true;
    }
    catch (Exception $e) {
        echo $e->getMessage() . "<br/>" . $sqlLog . "<br/>";
        return false;
    }
}


/*************************
 * ****** INVENTÁRIO *****
 ************************/


/**
 * Retorna o array com as informações da tabela de equipamentos
 * Podem ser passados os dados de etiqueta (unidade e etiqueta) ou o código da tabela de equipamentos
 * Retorna o array vazio se não localizar o registro
 * @param PDO $conn variável de conexão
 * @param int|null $unit código da unidade
 * @param varchar|null $tag etiqueta do equipamento
 * @param int|null $cod código do equipamento na tabela de equipamentos
 */
function getEquipmentInfo (\PDO $conn, ?int $unit, ?string $tag, ?int $cod = null): array
{

    $terms = "";
    if (!empty($cod)) {
        $terms .= " AND comp_cod = '{$cod}' ";
    } elseif (empty($unit) || empty($tag)) {
        return [];
    }
    
    if (empty($cod)) {
        $terms .= " AND comp_inv = '{$tag}' AND comp_inst = '{$unit}' ";
    }

    $sql = "SELECT * FROM equipamentos WHERE 1 = 1 {$terms} ";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount())
            return $res->fetch();
        return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}


/**
 * getManufacturers
 * Retorna um array com a listagem de fabricantes ou um fabricante específico caso o id seja informado
 * @param PDO $conn
 * @param int|null $id
 * @param int|null $type: 1: hw | 2: sw | 0: any(default)
 * @return array
 */
function getManufacturers (\PDO $conn, ?int $id, ?int $type = 0): array
{
    $data = [];

    $terms = ($id !== null ? " WHERE fab_cod = :id " : "");

    if (!$id) {
        $terms = ($type !== null && $type != 0 ? " WHERE fab_tipo IN ({$type},3) OR fab_tipo IS NULL " : '');
    }
    
    $sql = "SELECT * FROM fabricantes {$terms} ORDER BY fab_nome";
    try {
        $res = $conn->prepare($sql);
        
        if ($id) {
            $res->bindParam(':id', $id);
        }
        $res->execute();

        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            if ($id)
                return $data[0];
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}


/**
 * getPeripheralInfo
 * Retorna um array com as informações do componente interno (não avulso)
 * @param \PDO $conn
 * @param mixed $peripheralCod
 * @return array
 */
function getPeripheralInfo (\PDO $conn, $peripheralCod): array
{
    $empty = [];
    $empty['mdit_cod'] = "";
    $empty['mdit_manufacturer'] = "";
    $empty['mdit_fabricante'] = "";
    $empty['mdit_desc'] = "";
    $empty['mdit_desc_capacidade'] = "";
    $empty['mdit_sufixo'] = "";
    
    if (empty($peripheralCod)) {
        return $empty;
    }
        
    $sql = "SELECT * FROM modelos_itens WHERE mdit_cod = '{$peripheralCod}'";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount())
            return $res->fetch();
        return $empty;
    }
    catch (Exception $e) {
        return $empty;
    }
}

/**
 * getCostCenters
 * Retorna o array com as informações da tabela de Centros de Custos
 * Retorna o array vazio se não localizar o registro
 * Campos de retorno (se não vazio): ccusto_id, ccusto_name, ccusto_cod
 * @param \PDO $conn
 * @param int $ccId
 * @return array
 */
function getCostCenters (\PDO $conn, ?int $ccId = null, ?int $client = null): array
{
    $terms = "";
    
    if ($ccId) {
        $terms = "WHERE cc.`". CCUSTO_ID . "` = '{$ccId}' ";
    } elseif ($client) {
        $terms = "WHERE cc.client = '{$client}' OR cc.client IS NULL";
    }
    
    $sql = "SELECT 
                cc." . CCUSTO_ID . " AS ccusto_id, 
                cc." . CCUSTO_DESC . " AS ccusto_name, 
                cc." . CCUSTO_COD . " AS ccusto_cod,
                cl.nickname, cl.id
            FROM 
                `" . DB_CCUSTO . "`.`" . TB_CCUSTO . "` cc
                LEFT JOIN clients cl ON cl.id = cc.client 
                {$terms}
           ";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                $data[] = $row;
            }
            if ($ccId)
                return $data[0];
            return $data;
        }   
    return [];
    }
    catch (Exception $e) {
        return ['error' => $e->getMessage(),
                'sql' => $sql];
    }
}