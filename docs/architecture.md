# AgendaFlow — Arquitetura das Etapas 1 a 6

## Escopo desta entrega

As seis primeiras etapas entregam fundação, cadastros, agenda interna, jornada pública, comunicação automática e inteligência operacional: autenticação, isolamento multiempresa, disponibilidade, reservas, filas, lembretes, respostas por token, métricas e relatórios.

## Arquitetura proposta

O sistema segue um monólito modular Laravel. O backend mantém autenticação, autorização, validação e contexto do tenant; o React renderiza páginas via Inertia, sem uma API REST paralela.

```text
Navegador
  -> rotas web + CSRF
  -> middleware de autenticação
  -> ResolveCurrentCompany (contexto do tenant)
  -> Form Requests / Policies
  -> Controllers finos
  -> Actions e Services de domínio
  -> Eloquent + scopes por company_id
  -> MySQL

Scheduler -> Commands -> Jobs -> Queue -> Notifications / Providers
```

Decisões centrais:

- Banco único com `company_id` para dados empresariais.
- A empresa ativa é resolvida no backend a partir da associação autenticada, nunca de um ID arbitrário enviado pelo frontend.
- A interface recebe apenas dados já autorizados pelo Laravel.
- Operações críticas ficam em Actions/Services; controllers coordenam a requisição.
- Jobs transportam o identificador do log, restauram o tenant e entregam mensagens fora do ciclo HTTP.

## Estrutura de pastas

```text
app/
├── Actions/                 # casos de uso e transações
├── Concerns/                # comportamentos reutilizáveis de models
├── Http/
│   ├── Controllers/         # coordenação HTTP/Inertia
│   ├── Middleware/          # tenant, Inertia e autenticação
│   └── Requests/            # validação de escrita
├── Models/                  # entidades Eloquent
├── Policies/                # autorização por usuário e empresa
├── Providers/               # bindings e inicialização
├── Services/                # disponibilidade, eventos e métricas derivadas
└── Support/Tenancy/         # contexto do tenant atual
database/
├── factories/
├── migrations/
└── seeders/
resources/js/
├── components/              # componentes reutilizáveis
├── hooks/
├── layouts/                 # shell autenticado e autenticação
├── pages/                   # endpoints Inertia
└── types/
routes/
├── web.php
├── settings.php
└── console.php
tests/
├── Feature/                 # fluxos HTTP e isolamento
└── Unit/
```

## Entidades principais

Entidades implementadas:

- `users`: identidade global, credenciais e empresa ativa.
- `companies`: tenant, slug público, status e progresso inicial.
- `company_user`: associação N:N, função (`owner`, `admin`, `professional`) e status.
- `professionals`: equipe, contato, foto, status e vínculo opcional com usuário.
- `services`: catálogo, duração, intervalo técnico, preço, categoria e status.
- `professional_service`: relação N:N protegida também por `company_id`.
- `customers`: dados de CRM, contato, nascimento, anotações e campos preparados para histórico.
- `business_hours`: jornada semanal padrão da empresa.
- `professional_hours`: substituição semanal específica por profissional.
- `appointments`: compromisso, snapshot de duração/preço, status e ocupação com buffer.
- `blocked_periods`: indisponibilidade de um profissional ou da empresa inteira.
- `appointment_events`: trilha append-only de criação, reagendamento e status.
- `reminder_settings`: confirmação, antecedências e canais configurados por empresa.
- `notification_logs`: outbox auditável com destinatário, mensagem, tentativa, erro e chave idempotente.

Entidades das próximas etapas:

- SaaS: `plans`, `subscriptions`.

## Estratégia multi-tenant

1. O login autentica uma identidade global.
2. `ResolveCurrentCompany` confirma que `users.current_company_id` pertence ao usuário e está ativa.
3. `CurrentCompany` mantém o tenant resolvido apenas durante a requisição.
4. Models empresariais usam `BelongsToCompany`, que preenche `company_id` e aplica um escopo global quando há tenant corrente.
5. Policies continuam obrigatórias para operações por recurso, como segunda camada de proteção.
6. Parâmetros públicos usam UUID; cada controller busca o recurso somente depois que o tenant foi resolvido, evitando revelar UUIDs de outra empresa.
7. Jobs de comunicação leem o `company_id` do log sem escopo e reinstalam o contexto antes de consultar dados empresariais.
8. A jornada pública resolve uma empresa ativa pelo slug e instala o mesmo contexto de tenant apenas durante aquela requisição.

Esse desenho combina proteção por middleware, escopo Eloquent, foreign keys, vínculo de membership e policies. A interface não é tratada como barreira de segurança.

## Identidade visual por tenant

`companies.primary_color` e `companies.logo_path` armazenam a marca de cada empresa. Logos ficam no disco público em um diretório derivado do UUID do tenant; substituições e remoções limpam o arquivo anterior. Somente `owner` e `admin` podem alterar esses campos.

O backend compartilha apenas a URL pública da logo, sem expor seu caminho interno. O React transforma a cor em tokens semânticos aplicados ao painel, aos portais e às telas públicas. Texto sobre a cor principal e a variação para tema escuro são calculados automaticamente para preservar contraste.

## Relatórios e dashboard

`ReportPeriod` interpreta filtros no fuso do tenant e converte seus limites para UTC. `ReportAnalyticsService` percorre os agendamentos em lotes com `lazyById`, mantendo uso de memória estável, e produz métricas, série temporal, distribuição por status e rankings sem colocar regra financeira no React.

O faturamento previsto soma snapshots de preço exceto cancelamentos e faltas. Clientes novos possuem primeiro atendimento dentro do período; recorrentes já tinham atendimento anterior e voltaram no intervalo. A taxa de confirmação considera registros confirmados ou concluídos sobre o volume total selecionado.

Relatórios consolidados exigem papel `owner` ou `admin`. O dashboard aceita o escopo opcional de profissional e aplica uma consulta vazia quando esse papel ainda não tem um profissional vinculado, impedindo fallback acidental para os dados da empresa.

## Comunicação, fila e idempotência

Toda intenção de envio cria primeiro um `notification_log` com chave idempotente única. A chave combina agendamento, tipo, canal, horário atual da reserva e antecedência; repetir o scheduler não duplica uma comunicação. Apenas depois do commit o job é liberado para a fila `notifications`.

O scheduler roda a cada minuto, percorre empresas e agendamentos com `lazyById` e enfileira confirmações ou lembretes dentro da janela de execução. O job resolve novamente o tenant, incrementa tentativas, marca envio, falha ou ausência de destinatário e registra o evento de confirmação enviado.

WhatsApp depende de `MessagingProviderInterface`; o provider local apenas registra a mensagem e pode ser substituído por Cloud API, Evolution ou Twilio. E-mail usa Laravel Notifications dentro do job. Nenhum controller chama provedores externos.

O token público tem duas representações: hash SHA-256 para busca e valor criptografado para compor links futuros. URLs não expõem IDs numéricos. GET exibe a intenção e POST protegido por CSRF confirma ou cancela; o cancelamento libera o slot e avisa responsáveis da empresa.

## Disponibilidade e concorrência

`AvailabilityService` converte a data solicitada para o fuso da empresa e combina jornada padrão, substituição profissional, intervalo, duração do serviço, buffer, agendamentos ocupantes, bloqueios e limites de antecedência. As datas são persistidas em UTC e apresentadas no fuso do tenant.

Na criação e no reagendamento, o backend consulta a disponibilidade novamente dentro de uma transação. A linha do profissional é bloqueada com `FOR UPDATE`, serializando tentativas concorrentes para o mesmo recurso. `ends_at` representa o término do serviço; `blocks_until` inclui o intervalo técnico e é usado na detecção de sobreposição.

No fluxo público com “qualquer profissional”, todos os profissionais ativos que oferecem o serviço são bloqueados em ordem estável. O primeiro ainda disponível recebe a reserva; se nenhum estiver livre, a tentativa volta ao passo de horários sem criar cliente ou agendamento parcial.

## Fluxo de autenticação

### Cadastro

1. O visitante envia responsável, empresa, e-mail, telefone e senha.
2. Laravel Fortify valida a requisição e limita tentativas.
3. Uma transação cria `users`, `companies` e `company_user` com papel `owner`.
4. A empresa criada se torna `current_company_id` do usuário.
5. O usuário entra autenticado e é direcionado ao onboarding.

### Login

1. Fortify valida credenciais, aplica rate limiting e regenera a sessão.
2. O middleware resolve a empresa ativa a partir das associações autorizadas.
3. Usuários sem empresa válida são direcionados a um estado seguro de seleção/recuperação.
4. Rotas empresariais exigem `auth`, `verified` e `company`.

### Logout

A sessão é invalidada e o token CSRF é regenerado.

## Dependências

- PHP 8.3+ e Laravel 13.
- Laravel Sail e MySQL 8.4 para desenvolvimento consistente.
- Laravel Fortify para autenticação, verificação de e-mail, recuperação e 2FA.
- Inertia 3 + React 19 + TypeScript para a aplicação híbrida.
- Tailwind CSS 4 e componentes shadcn/Radix para UI acessível.
- Vite para build e desenvolvimento.
- PHPUnit, Laravel Pint e Larastan para qualidade.
- Banco SQLite em memória nos testes rápidos; MySQL no ambiente Sail.

## Critérios de conclusão das Etapas 1 a 6

- Cadastro cria usuário e empresa de forma atômica.
- Login, logout, redefinição de senha e verificação de e-mail funcionam.
- Rotas protegidas resolvem o tenant no backend.
- Usuários não podem selecionar uma empresa sem vínculo.
- Dashboard e onboarding são responsivos e acessíveis.
- Proprietários e administradores gerenciam profissionais, serviços, clientes e horários.
- Profissionais têm leitura do catálogo e da equipe, mas não alteram os cadastros; clientes ficam restritos até existir escopo por agenda.
- Horários exigem sete dias distintos e validam jornada e intervalo.
- Agenda diária e semanal respeita o papel do usuário e o fuso da empresa.
- Criação e reagendamento manual usam somente slots recalculados pelo backend.
- Bloqueios individuais e gerais removem os intervalos correspondentes.
- Mudanças de status seguem transições válidas e registram eventos.
- Empresa ativa expõe catálogo e disponibilidade em uma URL pública por slug.
- Visitante conclui a reserva sem conta e recebe uma página de sucesso com evento de calendário.
- Criações, reagendamentos e mudanças relevantes geram comunicações assíncronas pelos canais configurados.
- Scheduler envia confirmação e lembretes sem duplicar logs em execuções repetidas.
- Cliente confirma ou cancela por token exclusivo sem autenticação e sem IDs incrementais na URL.
- Histórico exibe canal, status, tentativa, data e erro por tenant.
- Dashboard apresenta métricas reais do dia e próximos atendimentos, respeitando o papel do usuário.
- Relatórios filtram hoje, 7 dias, 30 dias, mês atual ou intervalo personalizado no fuso da empresa.
- Indicadores cobrem volume, receita prevista, concluídos, cancelamentos, faltas, confirmação e recorrência.
- Série temporal e rankings possuem valores textuais e alternativa tabular acessível.
- Cor e logo da empresa são isoladas por tenant e aparecem no painel e no agendamento público.
- Listagens têm busca, paginação, estados vazios e cartões móveis sem rolagem horizontal.
- Build, testes e análise estática terminam sem erros.
