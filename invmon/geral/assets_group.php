<?php
session_start();
require_once (__DIR__ . "/" . "../../includes/include_basics_only.php");
require_once (__DIR__ . "/" . "../../includes/classes/ConnectPDO.php");
use includes\classes\ConnectPDO;

if ($_SESSION['s_logado'] != 1 || ($_SESSION['s_nivel'] != 1 && $_SESSION['s_nivel'] != 2)) {
    exit;
}

$conn = ConnectPDO::getInstance();
$exception = "";
//Todas as áreas que o usuário percente
$uareas = $_SESSION['s_uareas'];

$post = (isset($_POST) ? $_POST : '');

// var_dump($post);


$options = [
    'client' => [
        'label' => TRANS('CLIENT'),
        'table' => 'clients',
        'field_id' => 'id',
        'field_name' => 'nickname',
        'table_reference' => 'instituicao',
        'table_reference_alias' => 'un',
        'field_reference' => 'inst_client',
        'sql_alias' => 'cl.id',
        'alias' => 'cl'
    ],
    'category' => [
        'label' => TRANS('ASSET_CATEGORY'),
        'table' => 'assets_categories',
        'field_id' => 'id',
        'field_name' => 'cat_name',
        'table_reference' => 'tipo_equip',
        'table_reference_alias' => 't',
        'field_reference' => 'tipo_categoria',
        'sql_alias' => 'cat.id',
        'alias' => 'cat'
    ],
    'asset_type' => [
        'label' => TRANS('ASSET_TYPE'),
        'table' => 'tipo_equip',
        'field_id' => 'tipo_cod',
        'field_name' => 'tipo_nome',
        'table_reference' => 'equipamentos',
        'table_reference_alias' => 'e',
        'field_reference' => 'comp_tipo_equip',
        'sql_alias' => 't.tipo_cod',
        'alias' => 't'
    ],
    'unit' => [
        'label' => TRANS('COL_UNIT'),
        'table' => 'instituicao',
        'field_id' => 'inst_cod',
        'field_name' => 'inst_nome',
        'table_reference' => 'equipamentos',
        'table_reference_alias' => 'e',
        'field_reference' => 'comp_inst',
        'sql_alias' => 'un.inst_cod',
        'alias' => 'un'
    ],
    'department' => [
        'label' => TRANS('DEPARTMENT'),
        'table' => 'localizacao',
        'field_id' => 'loc_id',
        'field_name' => 'local',
        'table_reference' => 'equipamentos',
        'table_reference_alias' => 'e',
        'field_reference' => 'comp_local',
        'sql_alias' => 'l.loc_id',
        'alias' => 'l'
    ],
    'model' => [
        'label' => TRANS('COL_MODEL'),
        'table' => 'marcas_comp',
        'field_id' => 'marc_cod',
        'field_name' => 'marc_nome',
        'table_reference' => 'equipamentos',
        'table_reference_alias' => 'e',
        'field_reference' => 'comp_marca',
        'sql_alias' => 'm.marc_cod',
        'alias' => 'm'
    ],
    'state' => [
        'label' => TRANS('STATE'),
        'table' => 'situacao',
        'field_id' => 'situac_cod',
        'field_name' => 'situac_nome',
        'table_reference' => 'equipamentos',
        'table_reference_alias' => 'e',
        'field_reference' => 'comp_situac',
        'sql_alias' => 's.situac_cod',
        'alias' => 's'
    ],
    'manufacturer' => [
        'label' => TRANS('COL_MANUFACTURER'),
        'table' => 'fabricantes',
        'field_id' => 'fab_cod',
        'field_name' => 'fab_nome',
        'table_reference' => 'equipamentos',
        'table_reference_alias' => 'e',
        'field_reference' => 'comp_fab',
        'sql_alias' => 'f.fab_cod',
        'alias' => 'f'
    ]
];


/* Controle para apenas unidades visíveis pela área primária do usuário */
$terms = "";
if (!empty($_SESSION['s_allowed_units'])) {
    $terms = " AND un.inst_cod IN ({$_SESSION['s_allowed_units']}) ";
}

/** Traz o total de ativos, consolidado, com base nas configurações de acesso do usuário logado */
$qryTotal = "SELECT 
            COUNT(*) AS total  
        FROM 
            equipamentos e, 
            instituicao un
        WHERE 
            e.comp_inst = un.inst_cod
            {$terms}
        ";
$execTotal = $conn->query($qryTotal);
$regTotal = $execTotal->fetch()['total'];

if (isset($_SESSION['flash']) && !empty($_SESSION['flash'])) {
    echo $_SESSION['flash'];
    $_SESSION['flash'] = '';
}

?>
    <div id="assets_group"> <!-- class="just-padding" -->
        <p><?= TRANS('THEREARE'); ?>&nbsp;<span class="font-weight-bold text-danger"><?= $regTotal; ?></span>&nbsp;<?= TRANS('ASSETS_IN_SYSTEM'); ?>:</p>

        <div class="list-group list-group-root well">
<?php

if (isset($post['group_1']) && !empty($post['group_1'])) {
    /* Primeiro filtro de agrupamento */

    $sql_level_1 = "SELECT 
            COUNT(*) total, 
            {$options[$post['group_1']]['alias']}.{$options[$post['group_1']]['field_id']},
            COALESCE ({$options[$post['group_1']]['alias']}.{$options[$post['group_1']]['field_name']}, 'N/A') AS 
                \"{$options[$post['group_1']]['label']}\"
        FROM 
            (((equipamentos e,
            tipo_equip t,
            marcas_comp m,
            fabricantes f,
            localizacao l,
            instituicao un
            LEFT JOIN clients cl ON cl.id = un.inst_client)
            LEFT JOIN assets_categories cat ON cat.id = t.tipo_categoria)
            LEFT JOIN situacao s ON s.situac_cod = e.comp_situac)

        WHERE
            e.comp_inst = un.inst_cod AND 
            e.comp_tipo_equip = t.tipo_cod AND 
            e.comp_marca = m.marc_cod AND 
            e.comp_fab = f.fab_cod AND 
            e.comp_local = l.loc_id 
            {$terms}

        GROUP BY
            {$options[$post['group_1']]['alias']}.{$options[$post['group_1']]['field_id']},
            {$options[$post['group_1']]['alias']}.{$options[$post['group_1']]['field_name']}
        ORDER BY
            total DESC
    ";

    try {
        $res_level_1 = $conn->query($sql_level_1);
    }
    catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage();
        dump($sql_level_1);
        echo message('danger', 'Ooops!', '<hr>' . $sql_level_1 . $exception, '', '', 1);
        return;
    }

    foreach ($res_level_1->fetchAll() as $row_level_1) {
    ?>
        <!-- Links no primeiro nivel -->
        <a href="#<?= $post['group_1']; ?>-<?= $row_level_1[$options[$post['group_1']]['field_id']] ?>" class="list-group-item" data-toggle="collapse">
            
            <div class="card-header bg-light" >
                <span class="glyphicon icon-expand"></span>&nbsp;
                <span class="badge badge-light p-2" data-toggle="popover" data-content="<?= $options[$post['group_1']]['label']; ?>" data-placement="top" data-trigger="hover">
                    <?= $row_level_1[$options[$post['group_1']]['label']]; ?>
                </span>
                <span class="badge badge-primary p-2 "><?= $row_level_1['total']; ?></span>
            </div>
        </a>
        <?php

        if (isset($post['group_2']) && !empty($post['group_2'])) {
            /* Tratamento para os casos de comparação onde o campo não possui informações - nulo */
            $group_1_id_or_null = (empty($row_level_1[$options[$post['group_1']]['field_id']]) ? " IS NULL " : " = " . $row_level_1[$options[$post['group_1']]['field_id']]);

            $sql_level_2 = "SELECT 
                COUNT(*) total, 
                {$options[$post['group_1']]['alias']}.{$options[$post['group_1']]['field_id']},
                COALESCE ({$options[$post['group_1']]['alias']}.{$options[$post['group_1']]['field_name']}, 'N/A') AS 
                \"{$options[$post['group_1']]['label']}\",

                {$options[$post['group_2']]['alias']}.{$options[$post['group_2']]['field_id']},
                COALESCE ({$options[$post['group_2']]['alias']}.{$options[$post['group_2']]['field_name']}, 'N/A') AS 
                    \"{$options[$post['group_2']]['label']}\"
                
                FROM 
                    (((equipamentos e,
                    tipo_equip t,
                    marcas_comp m,
                    fabricantes f,
                    localizacao l,
                    instituicao un
                    LEFT JOIN clients cl ON cl.id = un.inst_client)
                    LEFT JOIN assets_categories cat ON cat.id = t.tipo_categoria)
                    LEFT JOIN situacao s ON s.situac_cod = e.comp_situac)

                WHERE

                    {$options[$post['group_1']]['sql_alias']} {$group_1_id_or_null} AND 

                    e.comp_inst = un.inst_cod AND 
                    e.comp_tipo_equip = t.tipo_cod AND 
                    e.comp_marca = m.marc_cod AND 
                    e.comp_fab = f.fab_cod AND 
                    e.comp_local = l.loc_id 
                    {$terms}


                GROUP BY
                    {$options[$post['group_1']]['alias']}.{$options[$post['group_1']]['field_id']},
                    {$options[$post['group_1']]['alias']}.{$options[$post['group_1']]['field_name']},

                    {$options[$post['group_2']]['alias']}.{$options[$post['group_2']]['field_id']},
                    {$options[$post['group_2']]['alias']}.{$options[$post['group_2']]['field_name']}
                ORDER BY
                    total DESC
            ";
            
            try {
                $res_level_2 = $conn->query($sql_level_2);

            }
            catch (Exception $e) {
                $exception .= "<hr>" . $e->getMessage();
                dump($sql_level_2);
                echo message('danger', 'Ooops!', '<hr>' . $sql_level_2 . $exception, '', '', 1);
                return;
            }
        
            
            ?>
                <!-- Div que envolve os links do segundo nível: baseado nas informações do group_1 -->
                <div class="list-group collapse" id="<?= $post['group_1']; ?>-<?= $row_level_1[$options[$post['group_1']]['field_id']] ?>">
            <?php

            foreach ($res_level_2 as $row_level_2) {

                ?>
                    <!-- Links no segundo nível -->
                    <a href="#<?= $post['group_1']; ?>-<?= $row_level_1[$options[$post['group_1']]['field_id']] ?>--<?= $post['group_2']; ?>-<?= $row_level_2[$options[$post['group_2']]['field_id']]; ?>" class="list-group-item" data-toggle="collapse">

                        <div class="card-header bg-light" >
                            <span class="glyphicon icon-expand"></span>&nbsp;
                            <span class="badge badge-light p-2" data-toggle="popover" data-content="<?= $options[$post['group_1']]['label']; ?>" data-placement="top" data-trigger="hover">
                                <?= $row_level_1[$options[$post['group_1']]['label']]; ?>
                            </span>
                            <!-- <span class="badge badge-secondary p-2 "><?= $row_level_1['total']; ?></span> -->
                            &nbsp;<i class="fas fa-angle-right"></i>&nbsp;
                            <span class="badge badge-light p-2" data-toggle="popover" data-content="<?= $options[$post['group_2']]['label']; ?>" data-placement="top" data-trigger="hover">
                                <?= $row_level_2[$options[$post['group_2']]['label']]; ?>
                            </span>
                            <span class="badge badge-primary p-2 "><?= $row_level_2['total']; ?></span>
                        </div>
                    </a>
                <?php

                if (isset($post['group_3']) && !empty($post['group_3'])) {
                    $group_2_id_or_null = (empty($row_level_2[$options[$post['group_2']]['field_id']]) ? " IS NULL" : " = " . $row_level_2[$options[$post['group_2']]['field_id']]);

                    $sql_level_3 = "SELECT 
                        COUNT(*) total, 
                        {$options[$post['group_1']]['alias']}.{$options[$post['group_1']]['field_id']},
                        COALESCE ({$options[$post['group_1']]['alias']}.{$options[$post['group_1']]['field_name']}, 'N/A') AS 
                        \"{$options[$post['group_1']]['label']}\",

                        {$options[$post['group_2']]['alias']}.{$options[$post['group_2']]['field_id']},
                        COALESCE ({$options[$post['group_2']]['alias']}.{$options[$post['group_2']]['field_name']}, 'N/A') AS 
                            \"{$options[$post['group_2']]['label']}\",

                        {$options[$post['group_3']]['alias']}.{$options[$post['group_3']]['field_id']},
                        COALESCE ({$options[$post['group_3']]['alias']}.{$options[$post['group_3']]['field_name']}, 'N/A') AS 
                            \"{$options[$post['group_3']]['label']}\"
                        
                        FROM 
                            (((equipamentos e,
                            tipo_equip t,
                            marcas_comp m,
                            fabricantes f,
                            localizacao l,
                            instituicao un
                            LEFT JOIN clients cl ON cl.id = un.inst_client)
                            LEFT JOIN assets_categories cat ON cat.id = t.tipo_categoria)
                            LEFT JOIN situacao s ON s.situac_cod = e.comp_situac)

                        WHERE
                            {$options[$post['group_1']]['sql_alias']} {$group_1_id_or_null} AND 
                            {$options[$post['group_2']]['sql_alias']} {$group_2_id_or_null} AND 

                            e.comp_inst = un.inst_cod AND 
                            e.comp_tipo_equip = t.tipo_cod AND 
                            e.comp_marca = m.marc_cod AND 
                            e.comp_fab = f.fab_cod AND 
                            e.comp_local = l.loc_id 
                            {$terms}

                        GROUP BY
                            {$options[$post['group_1']]['alias']}.{$options[$post['group_1']]['field_id']},
                            {$options[$post['group_1']]['alias']}.{$options[$post['group_1']]['field_name']},

                            {$options[$post['group_2']]['alias']}.{$options[$post['group_2']]['field_id']},
                            {$options[$post['group_2']]['alias']}.{$options[$post['group_2']]['field_name']}, 

                            {$options[$post['group_3']]['alias']}.{$options[$post['group_3']]['field_id']},
                            {$options[$post['group_3']]['alias']}.{$options[$post['group_3']]['field_name']}
                        ORDER BY
                            total DESC
                    ";
                    
                    try {
                        $res_level_3 = $conn->query($sql_level_3);


                        ?>
                        <!-- Div que envolve os links do terceiro nível: baseado nas informações do group_2 -->
                        <div class="list-group collapse" id="<?= $post['group_1']; ?>-<?= $row_level_1[$options[$post['group_1']]['field_id']]; ?>--<?= $post['group_2']; ?>-<?= $row_level_2[$options[$post['group_2']]['field_id']]; ?>">
                        <?php

                        foreach ($res_level_3 as $row_level_3) {
                        ?>
                            <!-- Links no terceiro nível -->
                            <a href="#<?= $post['group_1']; ?>-<?= $row_level_1[$options[$post['group_1']]['field_id']]; ?>--<?= $post['group_2']; ?>-<?= $row_level_2[$options[$post['group_2']]['field_id']] ?>--<?= $post['group_3']; ?>-<?= $row_level_3[$options[$post['group_3']]['field_id']]; ?>" class="list-group-item" data-toggle="collapse">

                                <div class="card-header bg-light" >
                                    <span class="glyphicon icon-expand"></span>&nbsp;
                                    <span class="badge badge-light p-2" data-toggle="popover" data-content="<?= $options[$post['group_1']]['label']; ?>" data-placement="top" data-trigger="hover">
                                        <?= $row_level_1[$options[$post['group_1']]['label']]; ?>
                                    </span>
                                    &nbsp;<i class="fas fa-angle-right"></i>&nbsp;
                                    <span class="badge badge-light p-2" data-toggle="popover" data-content="<?= $options[$post['group_2']]['label']; ?>" data-placement="top" data-trigger="hover">
                                        <?= $row_level_2[$options[$post['group_2']]['label']]; ?>
                                    </span>
                                    &nbsp;<i class="fas fa-angle-right"></i>&nbsp;
                                    <span class="badge badge-light p-2" data-toggle="popover" data-content="<?= $options[$post['group_3']]['label']; ?>" data-placement="top" data-trigger="hover">
                                        <?= $row_level_3[$options[$post['group_3']]['label']]; ?>
                                    </span>
                                    <span class="badge badge-primary p-2 "><?= $row_level_3['total']; ?></span>
                                </div>
                            </a>
                        <?php

                            if (isset($post['group_4']) && !empty($post['group_4'])) {

                                $group_3_id_or_null = (empty($row_level_3[$options[$post['group_3']]['field_id']]) ? " IS NULL" : " = " . $row_level_3[$options[$post['group_3']]['field_id']]);

                                $sql_level_4 = "SELECT 
                                    COUNT(*) total, 
                                    {$options[$post['group_1']]['alias']}.{$options[$post['group_1']]['field_id']},
                                    COALESCE ({$options[$post['group_1']]['alias']}.{$options[$post['group_1']]['field_name']}, 'N/A') AS 
                                    \"{$options[$post['group_1']]['label']}\",
            
                                    {$options[$post['group_2']]['alias']}.{$options[$post['group_2']]['field_id']},
                                    COALESCE ({$options[$post['group_2']]['alias']}.{$options[$post['group_2']]['field_name']}, 'N/A') AS 
                                        \"{$options[$post['group_2']]['label']}\",
            
                                    {$options[$post['group_3']]['alias']}.{$options[$post['group_3']]['field_id']},
                                    COALESCE ({$options[$post['group_3']]['alias']}.{$options[$post['group_3']]['field_name']}, 'N/A') AS 
                                        \"{$options[$post['group_3']]['label']}\",

                                    {$options[$post['group_4']]['alias']}.{$options[$post['group_4']]['field_id']},
                                    COALESCE ({$options[$post['group_4']]['alias']}.{$options[$post['group_4']]['field_name']}, 'N/A') AS 
                                        \"{$options[$post['group_4']]['label']}\"
                                    
                                    FROM 
                                        (((equipamentos e,
                                        tipo_equip t,
                                        marcas_comp m,
                                        fabricantes f,
                                        localizacao l,
                                        instituicao un
                                        LEFT JOIN clients cl ON cl.id = un.inst_client)
                                        LEFT JOIN assets_categories cat ON cat.id = t.tipo_categoria)
                                        LEFT JOIN situacao s ON s.situac_cod = e.comp_situac)

                                    WHERE

                                        {$options[$post['group_1']]['sql_alias']} {$group_1_id_or_null} AND 
                                        {$options[$post['group_2']]['sql_alias']} {$group_2_id_or_null} AND 
                                        {$options[$post['group_3']]['sql_alias']} {$group_3_id_or_null} AND 

                                        e.comp_inst = un.inst_cod AND 
                                        e.comp_tipo_equip = t.tipo_cod AND 
                                        e.comp_marca = m.marc_cod AND 
                                        e.comp_fab = f.fab_cod AND 
                                        e.comp_local = l.loc_id 
                                        {$terms}

                                    GROUP BY
                                        {$options[$post['group_1']]['alias']}.{$options[$post['group_1']]['field_id']},
                                        {$options[$post['group_1']]['alias']}.{$options[$post['group_1']]['field_name']},
            
                                        {$options[$post['group_2']]['alias']}.{$options[$post['group_2']]['field_id']},
                                        {$options[$post['group_2']]['alias']}.{$options[$post['group_2']]['field_name']}, 
            
                                        {$options[$post['group_3']]['alias']}.{$options[$post['group_3']]['field_id']},
                                        {$options[$post['group_3']]['alias']}.{$options[$post['group_3']]['field_name']},

                                        {$options[$post['group_4']]['alias']}.{$options[$post['group_4']]['field_id']},
                                        {$options[$post['group_4']]['alias']}.{$options[$post['group_4']]['field_name']}
                                    ORDER BY
                                        total DESC
                                ";
                                
                                try {
                                    $res_level_4 = $conn->query($sql_level_4);
                                    ?>
                                    <!-- Div que envolve os links do quarto nível: baseado nas informações do group_3 -->
                                    <div class="list-group collapse" id="<?= $post['group_1']; ?>-<?= $row_level_1[$options[$post['group_1']]['field_id']] ?>--<?= $post['group_2']; ?>-<?= $row_level_2[$options[$post['group_2']]['field_id']] ?>--<?= $post['group_3']; ?>-<?= $row_level_3[$options[$post['group_3']]['field_id']]; ?>">
                                    <?php
            
                                    foreach ($res_level_4 as $row_level_4) {
                                    ?>
                                        <!-- Links no quarto nível -->
                                        <a href="#<?= $post['group_1']; ?>-<?= $row_level_1[$options[$post['group_1']]['field_id']]; ?>--<?= $post['group_2']; ?>-<?= $row_level_2[$options[$post['group_2']]['field_id']]; ?>--<?= $post['group_3']; ?>-<?= $row_level_3[$options[$post['group_3']]['field_id']]; ?>--<?= $post['group_4']; ?>-<?= $row_level_4[$options[$post['group_4']]['field_id']]; ?>" class="list-group-item" data-toggle="collapse">

                                            <div class="card-header bg-light" >
                                                <span class="glyphicon icon-expand"></span>&nbsp;
                                                <span class="badge badge-light p-2" data-toggle="popover" data-content="<?= $options[$post['group_1']]['label']; ?>" data-placement="top" data-trigger="hover">
                                                    <?= $row_level_1[$options[$post['group_1']]['label']]; ?>
                                                </span>
                                                &nbsp;<i class="fas fa-angle-right"></i>&nbsp;
                                                <span class="badge badge-light p-2" data-toggle="popover" data-content="<?= $options[$post['group_2']]['label']; ?>" data-placement="top" data-trigger="hover">
                                                    <?= $row_level_2[$options[$post['group_2']]['label']]; ?>
                                                </span>
                                                &nbsp;<i class="fas fa-angle-right"></i>&nbsp;
                                                <span class="badge badge-light p-2" data-toggle="popover" data-content="<?= $options[$post['group_3']]['label']; ?>" data-placement="top" data-trigger="hover">
                                                    <?= $row_level_3[$options[$post['group_3']]['label']]; ?>
                                                </span>
                                                &nbsp;<i class="fas fa-angle-right"></i>&nbsp;
                                                <span class="badge badge-light p-2" data-toggle="popover" data-content="<?= $options[$post['group_4']]['label']; ?>" data-placement="top" data-trigger="hover">
                                                    <?= $row_level_4[$options[$post['group_4']]['label']]; ?>
                                                </span>

                                                <span class="badge badge-primary p-2 "><?= $row_level_4['total']; ?></span>
                                            </div>

                                        </a>
                                    <?php
            
                                        if (isset($post['group_5']) && !empty($post['group_5'])) {
                                            
                                            $group_4_id_or_null = (empty($row_level_4[$options[$post['group_4']]['field_id']]) ? " IS NULL" : " = " . $row_level_4[$options[$post['group_4']]['field_id']]);

                                            $sql_level_5 = "SELECT 
                                                COUNT(*) total, 
                                                {$options[$post['group_1']]['alias']}.{$options[$post['group_1']]['field_id']},
                                                COALESCE ({$options[$post['group_1']]['alias']}.{$options[$post['group_1']]['field_name']}, 'N/A') AS 
                                                \"{$options[$post['group_1']]['label']}\",
                        
                                                {$options[$post['group_2']]['alias']}.{$options[$post['group_2']]['field_id']},
                                                COALESCE ({$options[$post['group_2']]['alias']}.{$options[$post['group_2']]['field_name']}, 'N/A') AS 
                                                    \"{$options[$post['group_2']]['label']}\",
                        
                                                {$options[$post['group_3']]['alias']}.{$options[$post['group_3']]['field_id']},
                                                COALESCE ({$options[$post['group_3']]['alias']}.{$options[$post['group_3']]['field_name']}, 'N/A') AS 
                                                    \"{$options[$post['group_3']]['label']}\",
            
                                                {$options[$post['group_4']]['alias']}.{$options[$post['group_4']]['field_id']},
                                                COALESCE ({$options[$post['group_4']]['alias']}.{$options[$post['group_4']]['field_name']}, 'N/A') AS 
                                                    \"{$options[$post['group_4']]['label']}\",

                                                {$options[$post['group_5']]['alias']}.{$options[$post['group_5']]['field_id']},
                                                COALESCE ({$options[$post['group_5']]['alias']}.{$options[$post['group_5']]['field_name']}, 'N/A') AS 
                                                    \"{$options[$post['group_5']]['label']}\"
                                                
                                                FROM 
                                                    (((equipamentos e,
                                                    tipo_equip t,
                                                    marcas_comp m,
                                                    fabricantes f,
                                                    localizacao l,
                                                    instituicao un
                                                    LEFT JOIN clients cl ON cl.id = un.inst_client)
                                                    LEFT JOIN assets_categories cat ON cat.id = t.tipo_categoria)
                                                    LEFT JOIN situacao s ON s.situac_cod = e.comp_situac)

                                                WHERE
                                                    {$options[$post['group_1']]['sql_alias']} {$group_1_id_or_null} AND 
                                                    {$options[$post['group_2']]['sql_alias']} {$group_2_id_or_null} AND 
                                                    {$options[$post['group_3']]['sql_alias']} {$group_3_id_or_null} AND 
                                                    {$options[$post['group_4']]['sql_alias']} {$group_4_id_or_null} AND 

                                                    e.comp_inst = un.inst_cod AND 
                                                    e.comp_tipo_equip = t.tipo_cod AND 
                                                    e.comp_marca = m.marc_cod AND 
                                                    e.comp_fab = f.fab_cod AND 
                                                    e.comp_local = l.loc_id 
                                                    {$terms}
                        
                                                GROUP BY
                                                    {$options[$post['group_1']]['alias']}.{$options[$post['group_1']]['field_id']},
                                                    {$options[$post['group_1']]['alias']}.{$options[$post['group_1']]['field_name']},
                        
                                                    {$options[$post['group_2']]['alias']}.{$options[$post['group_2']]['field_id']},
                                                    {$options[$post['group_2']]['alias']}.{$options[$post['group_2']]['field_name']}, 
                        
                                                    {$options[$post['group_3']]['alias']}.{$options[$post['group_3']]['field_id']},
                                                    {$options[$post['group_3']]['alias']}.{$options[$post['group_3']]['field_name']},
            
                                                    {$options[$post['group_4']]['alias']}.{$options[$post['group_4']]['field_id']},
                                                    {$options[$post['group_4']]['alias']}.{$options[$post['group_4']]['field_name']},

                                                    {$options[$post['group_5']]['alias']}.{$options[$post['group_5']]['field_id']},
                                                    {$options[$post['group_5']]['alias']}.{$options[$post['group_5']]['field_name']}
                                                ORDER BY
                                                    total DESC
                                            ";
                                            
                                            try {
                                                $res_level_5 = $conn->query($sql_level_5);
                                                ?>
                                                <!-- Div que envolve os links do quinto nível: baseado nas informações do group_4 -->
                                                <div class="list-group collapse" id="<?= $post['group_1']; ?>-<?= $row_level_1[$options[$post['group_1']]['field_id']] ?>--<?= $post['group_2']; ?>-<?= $row_level_2[$options[$post['group_2']]['field_id']] ?>--<?= $post['group_3']; ?>-<?= $row_level_3[$options[$post['group_3']]['field_id']] ?>--<?= $post['group_4']; ?>-<?= $row_level_4[$options[$post['group_4']]['field_id']] ?>">
                                                <?php
                        
                                                foreach ($res_level_5 as $row_level_5) {
                                                ?>
                                                    <!-- Links no quino nível -->
                                                    <a href="#<?= $post['group_1']; ?>-<?= $row_level_1[$options[$post['group_1']]['field_id']] ?>--<?= $post['group_2']; ?>-<?= $row_level_2[$options[$post['group_2']]['field_id']] ?>--<?= $post['group_3']; ?>-<?= $row_level_3[$options[$post['group_3']]['field_id']] ?>--<?= $post['group_4']; ?>-<?= $row_level_4[$options[$post['group_4']]['field_id']] ?>--<?= $post['group_5']; ?>-<?= $row_level_5[$options[$post['group_5']]['field_id']] ?>" class="list-group-item" data-toggle="collapse">
                                                        <!-- <span class="glyphicon icon-expand"></span>
                                                        <button type="button" class="btn btn-sm text-white bg-oc-wine">
                                                            <span class="badge badge-light p-2"><?= $options[$post['group_5']]['label']; ?></span>
                                                            &nbsp;<?= $row_level_5[$options[$post['group_5']]['label']]; ?>&nbsp;
                                                            <span class="badge badge-light p-2"><?= $row_level_5['total']; ?></span>
                                                        </button> -->

                                                        <div class="card-header bg-light" >
                                                            <span class="glyphicon icon-expand"></span>&nbsp;
                                                            <span class="badge badge-light p-2" data-toggle="popover" data-content="<?= $options[$post['group_1']]['label']; ?>" data-placement="top" data-trigger="hover">
                                                                <?= $row_level_1[$options[$post['group_1']]['label']]; ?>
                                                            </span>
                                                            &nbsp;<i class="fas fa-angle-right"></i>&nbsp;
                                                            <span class="badge badge-light p-2" data-toggle="popover" data-content="<?= $options[$post['group_2']]['label']; ?>" data-placement="top" data-trigger="hover">
                                                                <?= $row_level_2[$options[$post['group_2']]['label']]; ?>
                                                            </span>
                                                            &nbsp;<i class="fas fa-angle-right"></i>&nbsp;
                                                            <span class="badge badge-light p-2" data-toggle="popover" data-content="<?= $options[$post['group_3']]['label']; ?>" data-placement="top" data-trigger="hover">
                                                                <?= $row_level_3[$options[$post['group_3']]['label']]; ?>
                                                            </span>
                                                            &nbsp;<i class="fas fa-angle-right"></i>&nbsp;
                                                            <span class="badge badge-light p-2" data-toggle="popover" data-content="<?= $options[$post['group_4']]['label']; ?>" data-placement="top" data-trigger="hover">
                                                                <?= $row_level_4[$options[$post['group_4']]['label']]; ?>
                                                            </span>
                                                            &nbsp;<i class="fas fa-angle-right"></i>&nbsp;
                                                            <span class="badge badge-light p-2" data-toggle="popover" data-content="<?= $options[$post['group_5']]['label']; ?>" data-placement="top" data-trigger="hover">
                                                                <?= $row_level_5[$options[$post['group_5']]['label']]; ?>
                                                            </span>


                                                            <span class="badge badge-primary p-2 "><?= $row_level_5['total']; ?></span>
                                                        </div>
                                                        
                                                    </a>


                                                     <!-- Listagem dos chamados no quinto nível -->
                                                    <div class="list-group-item collapse" id="<?= $post['group_1']; ?>-<?= $row_level_1[$options[$post['group_1']]['field_id']] ?>--<?= $post['group_2']; ?>-<?= $row_level_2[$options[$post['group_2']]['field_id']] ?>--<?= $post['group_3']; ?>-<?= $row_level_3[$options[$post['group_3']]['field_id']] ?>--<?= $post['group_4']; ?>-<?= $row_level_4[$options[$post['group_4']]['field_id']] ?>--<?= $post['group_5']; ?>-<?= $row_level_5[$options[$post['group_5']]['field_id']] ?>">
                                                        
                                                    </div>
                                                    
                                                <?php
                                                }
                        
                                                ?>
                                                </div><!-- Envolve os links do quinto nível -->
                                                <?php
                        
                        
                        
                                            }
                                            catch (Exception $e) {
                                                $exception .= "<hr>" . $e->getMessage();
                                                dump($sql_level_5);
                                                echo message('danger', 'Ooops!', '<hr>' . $sql_level_5 . $exception, '', '', 1);
                                                return;
                                            }
            
                                        } else {
                                            /**
                                             * Não tem o quinto filtro
                                             * Exibe a listagem com base apenas no quarto filtro
                                             */
                                            ?>
                                            <div class="list-group-item collapse" id="<?= $post['group_1']; ?>-<?= $row_level_1[$options[$post['group_1']]['field_id']]; ?>--<?= $post['group_2']; ?>-<?= $row_level_2[$options[$post['group_2']]['field_id']]; ?>--<?= $post['group_3']; ?>-<?= $row_level_3[$options[$post['group_3']]['field_id']]; ?>--<?= $post['group_4']; ?>-<?= $row_level_4[$options[$post['group_4']]['field_id']]; ?>">
                                                
                                            </div>
                                        <?php
                                        }
                                    }
            
                                    ?>
                                    </div><!-- Envolve os links do quarto nível -->
                                    <?php
                                }
                                catch (Exception $e) {
                                    $exception .= "<hr>" . $e->getMessage();
                                    dump($sql_level_4);
                                    echo message('danger', 'Ooops!', '<hr>' . $sql_level_4 . $exception, '', '', 1);
                                    return;
                                }
                            } else {
                                /**
                                 * Não tem o quarto filtro
                                 * Exibe a listagem com base apenas no terceiro filtro
                                 */
                                ?>
                                <div class="list-group-item collapse" id="<?= $post['group_1']; ?>-<?= $row_level_1[$options[$post['group_1']]['field_id']]; ?>--<?= $post['group_2']; ?>-<?= $row_level_2[$options[$post['group_2']]['field_id']]; ?>--<?= $post['group_3']; ?>-<?= $row_level_3[$options[$post['group_3']]['field_id']]; ?>">
                                    
                                </div>
                            <?php
                            }
                        }

                        ?>
                        </div><!-- Envolve os links do terceiro nível -->
                        <?php
                    }
                    catch (Exception $e) {
                        $exception .= "<hr>" . $e->getMessage();
                        dump($sql_level_3);
                        echo message('danger', 'Ooops!', '<hr>' . $sql_level_3 . $exception, '', '', 1);
                        return;
                    }

                } else {
                    /**
                     * Não tem o terceiro filtro
                     * Exibe a listagem com base apenas no segundo filtro
                     */
                    ?>
                        <div class="list-group-item collapse" id="<?= $post['group_1']; ?>-<?= $row_level_1[$options[$post['group_1']]['field_id']]; ?>--<?= $post['group_2']; ?>-<?= $row_level_2[$options[$post['group_2']]['field_id']]; ?>">
                            
                        </div>
                    <?php
                }

            }
        
            ?>
                </div> <!-- Envolve os links do segundo nível -->
            <?php
        } else {
            /**
             * Não tem o segundo filtro
             * Exibe a listagem com base apenas no primeiro filtro
             */
            ?>
                <div class="list-group-item collapse" id="<?= $post['group_1']; ?>-<?= $row_level_1[$options[$post['group_1']]['field_id']] ?>">
                    
                </div>
            <?php
        }
    }
    
} else {
    /**
     * Nenhum filtro de agrupamento
     * Exibirá todos os chamados em aberto para as áreas do usuário logado
     */
    ?>
        <div class="list-group-item " id="show_all-1">
            <?= message('info', 'Ooops!', TRANS('SELECT_AT_LEAST_ONE_FIELD_TO_GROUP'), '', '', 1); ?>
        </div>
    <?php
}



?>
        </div> <!-- list-group-root -->
    </div> <!-- just-padding -->
<?php




