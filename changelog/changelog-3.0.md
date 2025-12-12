# Changelog OcoMon a partir da versão 3.0rc1 (*lançado em 15/12/2020*) à 3.0 (*lançado em 01/02/2021*)

## Modificações gerais

+ Front-end reescrito
    - Novo menu de navegação
    - Bootstrap 4.5
    - Font-awesome
    - Datatables
    - Responsividade
    
+ Utilização de gráficos ilustrativos com a bibilioteca ChartJS

+ Utilização do PDO para conexão com o banco

+ Nova classe para cálculo de tempo válido
    - A principal diferença para o modelo da nova classe e a classe antiga é que a classe antiga somava todo o período integralmente e descontava os tempos inválidos só no final do período, ou seja, só era possível saber o tempo filtrado atualizado do chamado após o mesmo estar encerrado no sistema. A nova classe trabalha com períodos de tempo, somando cada período em tempo real de acordo com o status da ocorrência e com a jornada de trabalho, mantendo o tempo total atualizado independentemente de quando acontece a parada.
    
+ Paradas de relógio
    - Em função do status do chamado
    - Em função dos horários não cobertos pela jornada de trabalho associada ao chamado
    
+ Melhor controle sobre os SLAs
    
+ Melhorias no sistema de login

+ Maior controle sobre acesso aos módulos (**3.0**)

+ Muitas melhorias internas (onde quase ninguém nota)
    - CSRF
    - Melhor tratamento dos inputs
    - Melhor processamento das informações
    - Limpeza e otimização dos códigos
    - Otimização do arquivo de idiomas (remoção das chaves obsoletas) (**3.0**)
    - Refatoração
    
+ Melhor experiência de uso
    - Melhor sistema de mensagens de retorno para o usuário
    - Melhor navegabilidade
    - Melhorias em consistências em diversos formulários
    
+ **Importante**
    - O campo de etiqueta, agora permite a utilização de caracteres alfanuméricos.
    - Mudanças de diversas nomenclaturas para serem mais claras sobre seu significado e função
    - Reorganização da disposição dos itens do menu
    
    
    
## Módulo de Ocorrências

+ Sistema avançado de filtros
    - Múltiplas seleções
    - Diversas combinações possíveis
    - Visibilidade de colunas configurável
    - Reorganização das colunas
    - Exportação em diversos formatos
        - PDF
        - Excel
        - CSV
        - Opção de impressão

+ Novo sistema de mural de avisos
    - Agora os avisos aparecem como notificações nas telas de filas
    - Controle de data de validade para os avisos
    - Controle para exibir apenas uma vez para cada usuário destino

+ Melhorias no sistema de upload de arquivos

+ Abertura de chamados
    - auto completar para o nome de contato com base nos chamados já existentes para a área do operador
    - Novo campo: email de contato
    - Agora é possível enviar e-mails de forma automática para os usuários mesmo quando o chamado tiver sido aberto por um operador técnico
    - Nova opção de impressão da ocorrência

+ Dashboard completo para o módulo de ocorrências
    - Sistema de cards informativos
    - Gráficos estatísticos

+ Registro de modificações dos chamados

+ Opção específica na tela de detalhes das Ocorrências para agendamento do chamado

+ Nova opção para informações sobre o SLA na tela de detalhes das Ocorrências

+ Melhoria no layout de impressão de ocorrências

+ Melhorias na função de roteiros de atendimento (antigo scripts de atendimento)

+ Função de permitir inserção de comentários ao chamado pelo usuário de nível somente-abertura (usuário final) após o chamado já estar aberto.

+ Agora é possível ao usuário final adicionar arquivos em chamados já abertos no sistema (**3.0rc2**)

+ Melhorias significativas nos relatórios do módulo de ocorrências

+ Na tela de detalhes das ocorrências, a listagem de subchamados agora exibe também o chamado pai (quando existir) (**3.0**)

+ Agora, ao abrir um subchamado, é adicionado um assentamento também no chamado pai (**3.0**)

+ Agora, ao remover um vínculo com chamados relacionados (pai e/ou filho) todos os chamados envolvidos também recebem assentamentos automáticos (**3.0**)
    
    
## Módulo de Administração

+ Perfis de Jornadas de Trabalho

+ Nova opção de configuração para tolerância para SLAs
    - indica quanto tempo (em percentual) após o vencimento do SLA será considerado indicador intermediário antes de ser considerado estourado.

+ Melhoria no sistema de seleção de campos nos perfis de tela de abertura

+ Na listagem de perfis de tela de abertura, agora são informadas as áreas em cada perfil está sendo aplicado

+ Na listagem das áreas de atendimento, agora também é informado o perfil de jornada associado e também os módulos de acesso

+ Agora todas as configurações relacionadas às áreas de atendimento estão integradas em apenas uma área de configuração

+ Agora cada Status pode ser configurado quanto a gerar parada de relógio ou não

+ Registro da data e horário do último login do usuário

+ Placeholder para sugestão de formatação da máscara de data nas configurações básicas (**3.0rc2**)

+ Removidos diversos itens de inventário - Agora estão diretamente na área de inventário (**3.0rc3**)

+ Adicionada a opção de configurar os textos para formulário de trânsito e termo de compromisso para equipamentos (**3.0**)

+ Na listagem de departamentos, na coluna do nível de resposta, agora é exibido o tempo de resposta associado (**3.0**)
    
    
## Módulo de Inventário

+ Módulo reescrito para se alinhar ao novo padrão visual do sistema (**3.0rc3**)

+ Re-organização do menu de inventário (itens que estavam no menu de administração agora estão diretamente no menu de inventário) (**3.0rc3**).

+ Agora, ao vincular um componente avulso a um equipamento, ele assume o departamento (localização) do equipamento (**3.0rc2**)

+ Adicionados ao menu de inventário (**3.0rc2**):

    - Modelos de Equipamentos
    - Modelos de componentes
    - componentes Avulsos
    
+ Máscara de formatação de moeda para o cadastro e edição de componentes avulsos (**3.0rc2**).

+ A área de estatísticas e relatórios recebeu melhorias. Algumas opções foram removidas. (**3.0rc3**).

+ Na tela de detalhes do equipamento, agora há opções para geração de formulário de trânsito e termo de compromisso (**3.0**)

+ Ao alterar a localização de um equipamento, a localização também é automaticamente alterada para todos os seus componentes avulsos (**3.0**)

+ Na listagem de equipamentos, agora há destaque para equipamentos que tenham situação operacional configurada para ser destacada (**3.0**)

+ No retorno do filtro avançado para equipamentos, foi adicionado a possibilidade de exibir as colunas com os componentes de configuração do equipamento (**3.0**)


## Bugs corrigidos

+ Resolvido o bug que fazia com a tela de login aparecesse no painel interno do sistema após o tempo de sessão estourar (**3.0rc3**)

+ Resolvida a consulta por etiqueta quando o valor informado é alfanumérico (inventário) - (**3.0rc3**)

+ Resolvido o envio de e-mails sobre vencimentos das garantias de equipamentos (**3.0**)

+ Ao alterar os dados de identificação de um equipamento (etiqueta e/ou unidade) agora o mesmo não perde a referência com seus componentes avulsos (**3.0**)

+ Corrigida a exclusão de equipamentos (permitida apenas para administradores)

+ Corrigido o bug da versão (**3.0rc3**) que impedia cadastro, edição e exclusão de tipos de equipamentos e tipos de componentes (**3.0**)

+ Diversos ajustes menores e melhorias internas


---

# Itens removidos de versões anteriores

+ Replicação de chamados
+ Personalização da aparência e criação de temas via interface do sistema

+ Tempo de documentação
    - Função muito onerosa e de pouca utilidade

+ As dependências de status não são mais utilizadas para cálculo de tempo válido

+ Não é mais possível alterar a descrição dos chamados

+ Não é mais possível alterar as datas dos chamados

+ Usuários administradores de áreas de atendimento agora só terão acesso extra para gerenciamento de usuários (da própria área)

+ Suporte para autenticação via LDAP
    - O suporte para esse tipo de autenticação já existiu em versões antigas mas com o tempo de inatividade do projeto e a falta de ambiente adequado para testes resolvi removê-lo por completo. A intenção é voltar a isso em versões futuras.
    
    
## **Importante saber** (em caso de atualização):
    
- Os tempos de SLAs atingidos pelos chamados pré-existentes (*até a versão 2.0*) à atualização poderão sofrer mudanças pois as regras para cálculo foram modificadas;

- A nova versão do OcoMon não considera mais, para fins de cálculo de SLA, os níveis de dependência configurados para cada status. Agora o que irá influenciar para desconto de tempos será a configuração dos status quanto à parada de relógio (**deve ser configurado**) em conjunto com a cobertura de tempo das Jornadas de Trabalho.

- Chamados pré-existentes à atualização do sistema não terão contabilizados o tempo em que estiveram em cada status. Para estes, o cálculo do SLA utilizará como filtro apenas as Jornadas de trabalho associadas.

- As configurações de carga horária que existiam no arquivo config.inc.php não serão mais utilizadas. A partir de agora as mesmas devem ser realizadas por meio da criação de  Perfis de Jornadas de Trabalho no menu de administração em [*Admin :: Configurações Gerais :: Perfis de Jornada*] e associá-los às áreas de atendimento em [*Admin :: Configurações Gerais :: Áreas de Atendimento*]

- As novas funcionalidades de parada de relógio só serão aplicadas para ações realizadas após a atualização da versão.

- A máscara de formatação de data e hora sofreu mudança no formato, portanto, é necessário ajustar em [*Admin :: Configurações Gerais :: Configurações básicas :: Formato de data*]

- Nesse primeiro momento apenas o idioma Português está disponível.

- Algumas variáveis de ambiente foram renomeadas para se adequarem ao seu real sentido. Portanto, será necessário revisar os modelos de mensagens configurados em [*Admin :: Configurações Gerais :: E-mail - Mensagens padrão*] para atualizar as variáveis utilizadas.

---


### Fique por dentro

+ Site oficial: [https://ocomonphp.sourceforge.io/](https://ocomonphp.sourceforge.io/)

+ Instruções para instalação ou atualização: [https://ocomonphp.sourceforge.io/instalacao/](https://ocomonphp.sourceforge.io/instalacao/)

+ Requisitos: [https://ocomonphp.sourceforge.io/versoes-requisitos/](https://ocomonphp.sourceforge.io/versoes-requisitos/)

+ Twitter: [https://twitter.com/OcomonOficial](https://twitter.com/OcomonOficial)

+ Canal no Youtube: [https://www.youtube.com/channel/UCFikgr9Xk2bE__snw1_RYtQ](https://www.youtube.com/channel/UCFikgr9Xk2bE__snw1_RYtQ)


### Entre em contato:
+ E-mail: [ocomon.oficial@gmail.com](ocomon.oficial@gmail.com)
