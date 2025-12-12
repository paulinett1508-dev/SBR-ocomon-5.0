# OcoMon 4.0x (Outubro de 2021) - Versão anterior: 3.3


## Requisitos da versão

+ MySQL a partir da versão 5.6 (Ou MariaDB a partir da versão 10.2)
+ PHP a partir da versão 7.4
    - Módulos do PHP: PDO, pdo_mysql, curl, gd, openssl, ldap, mbstring
    
## Principais macro novidades

+ Autenticação via LDAP;
+ API para integração;
+ Abertura de chamados via e-mail;
+ Fila de e-mails;
+ Campos customizados;
+ Rótulos (tags) para as ocorrências;
+ Filtro para o Painel de Controle;
+ Gerência de áreas de atendimento;
+ Formulário aberto para abertura de chamados por usuários não cadastrados;
+ Flexibilidade para configuração de obrigatoriedade de qualquer dos campos das ocorrências em cada uma das etapas de vida (abertura, edição e encerramento);
+ Diversas melhorias cosméticas, refinamentos múltiplos, refatoramentos e otimizações diversas;


### Autenticação em base LDAP

+ Agora é possível configurar diretamente pela interface do sistema para que o sistema realize a autenticação em uma base LDAP/AD;

+ No primeiro login do usuário, caso ele ainda não exista na base local, seu registro será criado;

### API para integração

+ Desenvolvida uma API para permitir integração com outros sistemas. Nesse momento apenas métodos para abertura e leitura de chamados;

### Abertura de chamados por e-mail

+ Agora é possível configurar o sistema para que ele verifique uma conta específica de email em busca de novas mensagens que se tornarão, de forma automatizada, chamados no sistema;

### Filas de emails

+ Agora é possível configurar para que o sistema não realize o processamento de envio de email no mesmo instante da ação do usuário. Nesse caso, o email é salvo no banco de dados e será enviado de acordo com a periodicidade definida em configuração por meio do mecanismo de agendamento de tarefas do servidor;

### Campos customizados/personalizados

+ Agora é possível criar campos personalizados para serem utilizados nas ocorrências.

+ É possível criar campos dos tipos: texto, seleção simples, seleção múltipla, número, área de texto, data, data e hora, hora e caixa de marcação.

+ Campos do tipo texto permitem a utilização de máscara de formatação (se for expressão regular será utilizada também para validação);

+ Os campos são vinculados aos chamados por meio dos perfis de tela de abertura;

+ Os campos personalizados estão integrados ao filtro avançado e podem ser utilizados como critério de filtro (exceto campos do tipo caixa de texto e caixa de marcação) e aparecem como colunas (inicialmente ocultas) no retorno da consulta;

+ No filtro avançado é possível pesquisar por intervalo para os campos do tipo número, data e data e hora;

### Sistema de rótulos/tags para os chamados

+ Agora é possível adicionar rótulos/tags aos chamados em qualquer etapa de seu ciclo de vida (abertura, ediçao ou encerramento);

+ As tags estão integradas ao filtro avançado (podem ser utilizadas como critério de filtro e aparecem como coluna no retorno da consulta);

+ As consultas no filtro avançado podem utilizar operador "E" ou "OU" em combinação com o operador "NÃO". Ex: pesquisar chamados que obrigatoriamente tenham as tags "treinamento" E "desenvolvimento" mas NÃO tenham a tag "suporte";

+ No painel de controle foi adicionado a nuvem de tags do mês corrente;

+ Foi criado um relatório específico para gerar nuvem de tags por período;

### Gerencia de áreas

+ Agora é possível permitir que usuários de nível somente-abertura possam ver a fila de chamados da sua área (de outros usuários da mesma área);

+ Adicionado uma nova opção no menu Home: "Minha Área" com as listagems de chamados de outros usuários agrupados pela área que o operador logado for gerente. Essa opção também está disponível para usuários com nível de operação e administração mesmo que não sejam gerentes da área;

+ Usuários de nível somente abertura e operadores, quando gerentes, podem acessar a listagem de usuários sob sua gerência, bem como realizar alterações limitadas em seus perfis;

+ Usuários podem ser definidos como gerentes de área por meio de edição em seu perfil ou diretamente nas configurações das áreas de atendimento;

### Formulário para abertura de chamados sem autenticação

+ Agora é possível disponibilizar um formulário de abertura de chamados para usuários não cadastrados;

+ O formulário utiliza o perfil de tela de abertura para definição dos campos a serem exibidos (com limitações específicas em função do contexto) e sistema de captcha para evitar bots e spams;


---
## IMPORTANTE - Mudanças estruturais

### Relação entre áreas de atendimento e tipos de problemas

+ Mudança estrutural no relacionamento entre as tabelas de áreas de atendimento e tipos de problemas: antes era 1xN, agora: NxN

+ Na prática, antes, para que um tipo de problema existisse especificamente apenas para algumas áreas de atendimento (mas não para todas), o sistema replicava o cadastro, gerando um ID diferente mas mantendo a mesma nomenclatura. Agora, apenas um ID é mantido, e a associação com mais áreas não geram duplicidades.

+ Para possibilitar essa mudança sem a perda de informações de instalações com versões anteriores, foi criado um mecanismo assistente que orienta sobre o procedimento necessário e então permite a atualização. O procedimento só é necessário para atualizações;

### Mudança na criptografia de armazenamento de senhas

+ Antes a senha era diretamente armazenada com o algoritmo MD5. Agora é armazenado apenas o Hash com o algoritmo Bcryp.

+ A atualização é transparente para os usuários e não tem efeitos colaterais;

---

## Módulo de Ocorrências

+ Comportamento modificado: antes, se um operador qualquer adicionasse uma informação a um chamado, automaticamente esse operador ficava como sendo o "Responsável" pelo chamado, mesmo que o chamado já estivesse na fila de outro operador. Agora, uma vez que o chamado já esteja na fila direta de algum operador, ele só mudará de fila se for expressamente selecionado um novo técnico responsável para ele;

+ Chamados encerrados agora não permitem que o usuário solicitante adicione novas informações;

+ Reabertura de chamados: Agora apenas solicitante ou responsável direto pelo chamado podem reabri-lo (dentro do prazo configurado);

+ Reabertura de chamados: Agora é necessário informar uma justificativa para a "reabertura" do chamado, assim como optar por enviar e-mail para área e/ou usuário;

+ Painel de Controle: Criado filtro de áreas. Além de poder selecionar as áreas de atendimento, também é possível definir se a consulta considerará as áreas selecionadas como sendo a área solicitante do chamado ou a área responsável pelo atendimento (origem ou destino dos chamados); As áreas passíveis de seleção estão limitadas às áreas para às quais o operador logado faz parte, de acordo com a configuração de isolamento de visibilidade;

+ Painel de Controle: Mais cards agora disponibilizam listagem de ocorrências (ao clicar na informação do card);

+ Tela de detalhes das ocorrências: agora, ao clicar em "Atender", a janela que abre já solicita o preenchimento do assentamento além de dar a opção de envio de email para a área e para o solicitante;

+ Tela de detalhes das ocorrências: agora, ao clicar em "Agendar", a janela que abre já solicita o preenchimento do assentamento além de dar a opção de envio de email;

+ Tela de detalhes das ocorrências: Nova informação exibida: Área Solicitante;

+ Adicionada a coluna "Área Solicitante" (visível por padrão) no retorno das consultas do filtro avançado.

+ Nas listagens de filas: agora é exibido um popup com a descrição completa do chamado, sem a necessidade de clique.

+ Nas listagens de filas: indicadores visuais sobre a interação com o chamado com popovers informando o assentamento mais recente, autor e data da ação;

+ Na fila geral: se o tipo de problema estiver vazio, aparecerá o badge "pendente;

+ Chamados com até 10 minutos de vida, aparecerão com um badge de "Novo" na fila de atendimento;

+ No menu "Home :: Meus Chamados" - Adicionada guia/seção: "Pendentes para mim", referente aos chamados diretamente pendentes para o operador logado;

+ No filtro avançado, não é mais permitido consultas sem nenhum critério de filtro (É preciso definir ao menos um critério em qualquer dos campos oficiais). Essa ação visa evitar consultas que requerem muito processamento em função de bases de dados muito grandes;

+ No Encaminhamento de chamados, antes só era possível selecionar os usuários que tinham a área destino do chamado como sendo a área primária em sua conta - agora também é possível selecionar usuários que possuem a área destino do chamado como secundária em sua conta;

+ Agora é possível agendar chamados para o dia corrente (em horário futuro);

+ Na área de detalhes das ocorrências, agora a prioridade vem formatada com o respectivo destaque (configurado na área de prioridades de atendimento);

+ Novo campo: Canal da solicitação;

+ Mural de avisos: agora é possível definir um aviso como recorrente para re-exibição diária;

+ Mural de avisos: agora, administradores podem definir avisos para serem exibidos à áreas solicitantes (de nível somente abertura);

+ Mural de avisos: nas notificações de avisos do mural, adicionada a data da postagem.

---
## Módulo de Inventário

+ No retorno do filtro avançado foi adicionada a coluna "Valor";

+ Agora, no cadastro de fabricantes, as opções de definição Hardware e Software vêm preenchidas;

---
    
## Módulo de Administração

+ Área para habilitar e configurar a abertura de chamados por e-mail (requer configuração no agendador de tarefas do sistema: cron | agendador de tarefas);

+ API: Área destinada a desenvolvedores: opção para registro e gerenciamento de aplicações a serem integradas ao sistema;

+ Mecanismo para possibilitar a utilização de filas de e-mails ao invés de realizar o processamento de envio no mesmo momento das respectivas ações no sistema (requer configuração no agendador de tarefas do sistema: cron | agendador de tarefas);

+ Agora é possível testar se as configurações para envio de e-mail estão funcionando diretamente na tela de definição das configurações;

+ Agora é possível definir que usuários operadores não tratem os chamados abertos por eles mesmos; Nesse caso, usuários de nível operador terão acesso de nível somente-abertura aos chamados abertos por eles mesmos;

+ Definição de prazo para reabertura de chamados;

+ Nas configurações básicas, foi removida a configuração de definição dos rótulos referentes às categorias para tipos de problemas. Agora essa opção pode ser acessada diretamente por meio das configurações dos tipos de problemas.

+ Configuração criada para definir se campos customizados que não foram utilizados na tela de abertura poderão aparecer nas telas de edição e encerramento: Ou seja, com essa configuração, é possível que as telas de edição e encerramento só exibam os campos extras que já estão com informações para o chamado;

+ Área para cadastro e configuração de campos personalizados;

+ Áreas de atendimento: adicionada a coluna para redirecionamento aos respectivos tipos de problemas;

+ Áreas de atendimento: adiciona a coluna informando os respectivos gerentes;

+ Áreas de atendimento: é possível definir gerentes diretamente no processo de edição das informações da área;

+ Área para cadastro de rótulos/tags;

+ Área para habilitar e configurar formulário para abertura de chamados sem autenticação;

+ Tipos de Status: nomenclatura ajustada para os paineis: fila aberta (antes é painel principal) e fila direta (antes era painel superior).

+ Tipos de Status: status com painel oculto, obrigatoriamente ficarão marcados com parada de relógio;

+ Tipos de Problemas: agora é possível vincular múltiplas áreas de atendimento a um mesmo tipo de problema sem replicação de IDs;

+ Tipos de Problemas: agora é possível adicionar exceções para que tipos de problemas que não estejam vinculados a nenhum área (aparecendo para todas) possam ficar ocultos para áreas específicas;

+ Tipos de problemas: na listagem, agora é possível saber as áreas vinculadas à cada tipo de problema;

+ Tipos de problemas: agora é possível gerir suas categorias sem recarregar a tela, mantendo as informações já digitadas;

+ Tipos de problemas: agora é possível desabilitar tipos de problemas no sistema;

+ Perfis de tela de abertura: agora é possível definir a obrigatoriedade de preenchimento de cada campo;

+ Perfis de tela de abertura: agora já na listagem é possível saber que campos estão habilitados (inclusive campos personalizados);

+ Perfis de tela de abertura: agora é possível ocultar o campo de descrição, desde que pelo menos um campo esteja habilitado para o perfil;

+ Nova opção para definição de obrigatoriamente de preenchimento dos campos das ocorrências nas telas de edição e encerramento. Para os campos na tela de abertura, as definições são realizadas diretamente em cada perfil de tela.

+ Nova área para criação e configuração de canais de solicitação;

+ Melhorias nas opções de configuração dos rótulos para prioridades de atendimento. Agora é possível definir a cor da fonte, facilitando o ajuste do melhor contraste entre a cor da fonte e a cor de fundo para os badges.

+ Agora a seleção de idioma apresenta para seleção o nome do idioma (amigavelmente) e não o arquivo correspondente;

___

## Diversos

+ Tela de login redesenhada: com opções para recuperação de senha e memorização do usuário além de controle de quantidade de tentativas mal sucedidas de login;
    + A saber: as opções para alteração de senha ou recuperação de senha só estarão disponíveis caso o tipo de autenticação esteja configurado para base local;

+ Nome de usuário: agora o sistema aceita que seja no formato de endereço de e-mail;

+ Acesso direto às informações do perfil de usuário, agrupando as opções de alteração de senha e definição de idioma;

+ Usuários administradores podem navegar com nível de operador (opção acessível via tela do perfil do usuário);

+ Agora, com a URL global dos chamados, não é mais necessário estar autenticado para acessar as informações do chamado (apenas leitura);

+ Agora, nos relatórios e formulários de consulta, sempre que existir a definição de período entre duas datas, os campos estarão sincronizados: Ex: a segunda data só poderá ser selecionada a partir do valor da primeira data;

+ Ajustes diversos: mudanças de nomenclaturas e reposicionamento de colunas de informações em diversas listagens do sistema.

___

## Bugs corrigidos e ajustes

+ No painel de controle, caso não existissem chamados em aberto no sistema, os cards ficavam zerados;

+ Em qualquer dos formulários que permitem o upload de arquivos, caso fosse deixando algum campo extra de upload sem seleção de arquivo (clicando no sinal de "+") o sistema retornava que o tipo de arquivo não era permitido;

+ Em alguns casos, arquivos superiores a 900kb eram corrompidos pelo sistema de upload.

+ Agora é possível configurar o sistema para upload de arquivos em até 10Mb.

+ Adicionadas novas informações de ajuda na interface de administração do sistema;

+ Na tela de login, quando o usuário clicava em cadastro para abertura de chamados e cancelava, as mensagens de erro caso houvesse tentativa mal sucedida de login, não eram exibidas.

+ Nas ocorrências: No retorno do filtro avançado, após clicar para ver os detalhes de qualquer dos resultados o filtro deixava de funcionar para novas buscas;

+ Nas ocorrências: Quando a descrição da ocorrência possuia formatação ocasionava a quebra de layout nas listagens onde a exibição da descrição era truncada;

+ Nas ocorrências: Para usuários de nível somente-abertura não estava sendo possível ter acesso aos roteiros de atendimento relacionados aos seus chamados;

+ No inventário: ao alterar a etiqueta, chamados relacionados e arquivos diretamente relacionados perdiam o vínculo;

+ No inventário: o sistema permitia a inclusão de etiquetas com espaços para equipamentos e componentes avulsos;

+ No inventário: Não era possível a seleção de softwares padrão quando não existia ainda nenhum software padrão definido;

+ No inventário: No retorno do filtro avançado de equipamentos, após clicar para ver os detalhes de qualquer dos resultados o filtro deixava de funcionar para novas buscas;

+ No inventário: Na edição das informações de inventário, o campo do centro de custos não estava trazendo selecionado o centro associado;

+ No inventário: vários relatórios retornavam com legendas null nos gráficos (na versão 3.3);

+ Ajustes da formatação e quebras de linha para as mensagens enviadas por e-mail. Em alguns casos havia um espaçamento excessivo entre as linhas das mensagens. Em outros casos, todas as linhas vinham emendadas.

+ Na tela de detalhes das ocorrências, ao abrir os detalhes dos e-mails enviados, a data de envio não era exibida corretamente.

+ Na administração de Prioridades de atendimento não era possível a definição de prioridade padrão;

+ Corrigido o bug que impedia a abertura de chamados por e-mail quando se definia tags automáticas para esses casos.

___

## Questões conhecidas

+ Campos customizados não gravam histórico de modificações;

+ Na API de integração, não é possível enviar arquivos como anexos na criação e também não é possível obter os anexos existentes via o método de leitura;

+ A abertura de chamados por e-mail não suporta a inclusão de anexos;

+ O módulo de inventário será foco de desenvolvimento para as atualizações após o lançamento da versão 4.0;


---



