
/* Constantes referentes às colunas resultantes do filtro avançado - valem para o painel de controle e outras buscas similares*/
const reportAllColumns = [
    'etiqueta',
    'cliente',
    'unidade',
    'asset_category',
    'type_of',
    'manufacturer',
    'model',
    'serial_number',
    'part_number',
    'department',
    'state',
    'supplier',
    'cost_center',
    'value',
    'invoice_number',
    'assistance',
    'waranty_type',
    'waranty_expire',
    'purchase_date',
    'direct_attributes',
    'aggregated_attributes',
    'soft_aggregated_attributes',
    'deprecated_attributes',
];

const reportDefaultHiddenColumns = [
    'serial_number', 
    'part_number',
    'state',
    'supplier',
    'cost_center',
    'value',
    'invoice_number',
    'assistance',
    'waranty_type',
    'waranty_expire',
    'purchase_date',
    'custom_field',
    'soft_aggregated_attributes',
    'deprecated_attributes'
];


const reportNotSearchable = [];

const reportNotOrderable = ['direct_attributes','aggregated_attributes','soft_aggregated_attributes','deprecated_attributes'];

// const reportDefaultColumnsOrder = [];
