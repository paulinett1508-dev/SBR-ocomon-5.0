# OcoMon 3.3 (Abril de 2021) - Versão anterior: 3.2


## Módulo de Ocorrências

+ Ao tentar agendar um chamado já aberto, se a data informada não for uma data futura ou simplesmente não informada, agora há uma mensagem de retorno. Antes o sistema apenas não realizava o agendamento mas não informava sobre a situação.

+ Agora as opções para envio de e-mails (abertura, edição, encerramento, agendamento e a opção de envio via a tela de detalhes das ocorrências) não serão exibidas caso o sistema esteja configurado para não enviar e-mails (opção nova na área de administração).

+ Na tela de abertura de chamados, a consulta sobre a configuração do equipamento (se estiver habilitada para o perfil e a área tiver permissão para o módulo de inventário), também trará resultado caso a etiqueta fornecida seja de um componente avulso;

+ Na tela de detalhes da ocorrência, agora a data de agendamento é exibida juntamente com o respectivo horário (antes era exibida apenas a data).


## Módulo de Inventário

+ Agora na tela inicial [Início] também é apresentado o resumo (com listagem e gráfico) dos componentes avulsos cadastrados;

+ No cadastro de componentes avulsos foram criados dois novos campos (para alinhar com o cadastro de equipamentos): tipo de assistencia e tipo de garantia;

+ No cadastro de componentes avulsos, agora é possível cadastrar um novo modelo para o componente sem precisar sair da tela de cadastro do componente;

+ Na listagem de componentes avulsos foi adicionada a coluna para exibir a etiqueta do componente;

+ Na consulta rápida, agora o sistema retorna diretamente a tela de detalhes do registro pesquisado. A etiqueta fornecida pode ser tanto de um equipamento quanto de um componente avulso;

+ **IMPORTANTE:** Até a versão 3.2, no cadastro de **modelos de componentes**, a informação de fabricante era textual (campo de texto) e não buscava da mesma base de fabricantes dos equipamentos. Essa característica foi alterada visando futuras melhorias no sistema de inventário e portanto, a partir dessa atualização, os usuários deverão atualizar manualmente os fabricantes da sua base de **modelos de componentes avulsos**. As informações antigas sobre os fabricantes aparecerão incorporadas à nomenclatura do modelo do componente até serem removidas manualmente.

    + Como proceder para atualizar os fabricantes da base de modelos de componentes avulsos:
        
        1. Edite o modelo do componente [Inventário::Hardware::Modelos de componentes] e selecione o fabricante na listagem de fabricantes (caso o fabricante não exista será necessário primeiro cadastrá-lo);
        
        2. Remova o nome do fabricante que estará sendo exibido no campo destinado ao modelo.
        
        - **OBS:** Também é possível realizar a atualização de forma automatizada. Sinta-se livre para implementar sua automação ou consulte-nos sobre esse serviço.


+ Nova opção de filtro avançado para busca de componentes avulsos. 

    - Destaque: buscar componentes vinculados ou não vinculados a equipamentos;

    
    
## Módulo de Administração

+ Opção para desabilitar o envio de emails pelo sistema;


## Diversos

+ A partir dessa versão, **não é mais necessário** remover a diretiva "ONLY_FULL_GROUP_BY" do sql_mode;

+ Ajustes no sistema de envio de e-mails para os casos de não utilização de smtp;

+ Adicionado o arquivo de Idioma para Espanhol (contribuição do Olivam Moraes [olivam.cmoraes@gmail.com](olivam.cmoraes@gmail.com))

+ Atualização da bilioteca jQuery (3.6)


## Bugs corrigidos e ajustes

+ Inventário: No cadastro de equipamento, caso fosse também cadastrado o modelo do equipamento pelo mesmo formulário, retornava erro de formulário já enviado;

+ Inventário: O sistema não conseguia gravar o log de alteração de componentes para situações onde o componente era vazio antes da alteração.

+ Inventário: Havia um bug no relatório de equipamentos por situação que não exibia alguns equipamentos se estes não tivessem HDD.

+ Inventário: No relatório de vencimento das garantias de equipamentos, a listagem estava com a data correta mas o gráfico apresentava as datas com um mês de diferença.

+ Inventário: Na consulta de garantia para componente, retornava erro caso o mesmo não tivesse as informações de data de compra e período de garantia.


---


### Fique por dentro

+ Site oficial: [https://ocomonphp.sourceforge.io/](https://ocomonphp.sourceforge.io/)

+ Instruções para instalação ou atualização: [https://ocomonphp.sourceforge.io/instalacao/](https://ocomonphp.sourceforge.io/instalacao/)

+ Requisitos: [https://ocomonphp.sourceforge.io/versoes-requisitos/](https://ocomonphp.sourceforge.io/versoes-requisitos/)

+ Twitter: [https://twitter.com/OcomonOficial](https://twitter.com/OcomonOficial)

+ Canal no Youtube: [https://www.youtube.com/channel/UCFikgr9Xk2bE__snw1_RYtQ](https://www.youtube.com/channel/UCFikgr9Xk2bE__snw1_RYtQ)


### Entre em contato:
+ E-mail: [ocomon.oficial@gmail.com](ocomon.oficial@gmail.com)
