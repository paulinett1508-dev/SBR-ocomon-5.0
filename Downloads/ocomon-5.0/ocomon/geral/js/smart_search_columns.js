
/* Constantes referentes às colunas resultantes do filtro avançado - valem para o painel de controle e outras buscas similares*/
const reportAllColumns = [
    'client',
    'area_solicitante',
    'area',
    'problema',
    'aberto_por',
    'canal',
    'contato',
    'contato_email',
    'telefone',
    'departamento',
    'descricao',
    'tech_description',
    'solution',
    'funcionarios',
    'data_abertura',
    'agendado',
    'agendado_para',
    'data_atendimento',
    'data_fechamento',
    'unidade',
    'etiqueta',
    'status',
    'tempo_absoluto',
    'tempo',
    'duracao_abs',
    'duracao_filtrado',
    'prioridade',
    'rate',
    'rejeicao',
    'sla',
    'sla_resposta',
    'sla_solucao',
    'input_tags',
];

const reportDefaultHiddenColumns = [
    'aberto_por',
    'canal',
    'contato_email',
    'telefone',
    'agendado',
    'agendado_para',
    'data_atendimento',
    'data_fechamento',
    'unidade',
    'etiqueta',
    'prioridade',
    'tempo_absoluto',
    'tempo',
    'duracao_abs',
    'duracao_filtrado',
    'rate',
    'rejeicao',
    'sla_resposta',
    'sla_solucao',
    'input_tags',
    'funcionarios',
    'tech_description',
    'solution'
];


const reportNotSearchable = [
    'telefone',
    'descricao',
    'data_abertura',
    'agendado',
    'agendado_para',
    'data_atendimento',
    'data_fechamento',
    'tempo_absoluto',
    'tempo',
    'duracao_abs',
    'duracao_filtrado',
    'rate',
    'rejeicao',
    'sla_resposta',
    'sla_solucao',
    'input_tags',
    'custom_field'
];

const reportNotOrderable = ['rate','sla','tempo_absoluto','duracao_abs','input_tags'];

// const reportDefaultColumnsOrder = [];
