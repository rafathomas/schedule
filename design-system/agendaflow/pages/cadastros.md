# Cadastros — Etapa 2

## Direção

- Usar o sistema visual do `MASTER.md`: azul de confiança, superfícies neutras, cantos contidos e sombras mínimas.
- Cabeçalho com contexto, título, descrição objetiva e ação primária; busca logo abaixo para manter o fluxo previsível.
- Exibir recursos em cartões responsivos. Em telas pequenas, cada cartão preserva ações e leitura sem rolagem horizontal.
- Modais de cadastro têm largura limitada, rolagem interna e rodapé de ações sempre ao fim do fluxo.

## Interação e acessibilidade

- Alvos interativos com pelo menos 44 px; rótulos visíveis; campos obrigatórios marcados com `*`.
- Erros aparecem próximos ao campo e o processamento desabilita a ação de envio.
- Toda listagem diferencia estado vazio inicial de busca sem resultados.
- Exclusões exigem confirmação e descrevem a consequência.
- Horários usam `input[type=time]`, organização semanal e um estado claro de aberto/fechado.
- Movimento somente em transições curtas de cor, foco e abertura de diálogo; respeitar a preferência global por movimento reduzido.
