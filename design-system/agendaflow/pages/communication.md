# Comunicação

## Objetivo

Permitir que proprietários e administradores entendam rapidamente o que será enviado, quando será enviado e o resultado de cada tentativa.

## Estrutura

- Cabeçalho com contexto de automação e promessa de idempotência.
- Cartões separados para confirmação e lembretes.
- Canais em opções grandes, rotuladas e com ícones.
- Ação de salvar isolada no final da configuração.
- Histórico abaixo, com status textual e erro próximo da tentativa.
- Cartão de conexão do WhatsApp antes das regras de envio, com estado textual, ação principal e QR Code temporário.

## Regras de interação

- Toggles desativam apenas os campos dependentes e preservam os valores escolhidos.
- Pelo menos uma antecedência e um canal permanecem obrigatórios.
- Ações têm altura mínima de 44 px e foco visível.
- Cores nunca são a única forma de comunicar status.
- A tabela pode rolar dentro do card em telas estreitas sem causar rolagem na página inteira.
- Estados vazios explicam que ainda não houve comunicação, sem sugerir falha.
- A geração e a leitura do QR Code exibem loading e verificação automática; conectado, desconectado e erro nunca dependem apenas de cor.
- O QR Code reserva uma área quadrada estável, recebe descrição acessível e não é persistido após a conexão.

## Páginas públicas de resposta

- Conteúdo centralizado, uma única decisão principal e detalhes do atendimento antes da ação.
- Cancelamento usa alerta explícito e botão destrutivo; confirmação usa ação primária.
- Resultado confirmado/cancelado é idempotente e orienta o próximo passo.
- GET apenas apresenta a decisão; POST realiza a mudança com proteção CSRF.
