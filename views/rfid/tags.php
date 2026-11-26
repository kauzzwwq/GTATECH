<?php
$titulo = 'TAGs RFID';
require __DIR__ . '/../layouts/header.php';
?>
<style>
  /* Estilos só da tela de TAGs. Usam as cores e classes do próprio sistema (style.css). */
  .rfid-form { display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end; }
  .rfid-form label { flex: 1 1 220px; margin: 0; }
  .rfid-form .btn { flex: none; }
  .rfid-vinculo { display: flex; gap: 8px; align-items: center; margin: 0; }
  .rfid-vinculo select { margin: 0; min-width: 170px; }
  .badge.rf-ativa { background: var(--verde-bg); color: var(--verde); }
  td.uid { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-weight: 700; letter-spacing: .04em; white-space: nowrap; }
  .rfid-ultima-uid { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-weight: 700; letter-spacing: .06em; font-size: 1.6rem; margin: 0 0 4px; }
  .rfid-ultima-info { margin: 0 0 16px; color: var(--texto-suave, #666); }
  .acoes { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
  .acoes form { margin: 0; }
  @media (max-width: 700px) {
    .rfid-form .btn { width: 100%; }
  }
</style>

<main class="conteudo">
  <div class="page-head">
    <div>
      <h1>TAGs RFID</h1>
      <p class="sub">Cadastre os cartões e ligue cada um a um motorista.</p>
    </div>
    <div class="acoes-topo"><a class="btn sec" href="<?= e(url('rfid')) ?>">← Voltar ao painel</a></div>
  </div>

  <?php if ($mensagem): ?>
    <div class="<?= $mensagem['ok'] ? 'ok' : 'alerta' ?>"><?= e($mensagem['msg']) ?></div>
  <?php endif; ?>

  <?php if (!empty($ultima)): ?>
  <section class="card">
    <div class="card-topo">
      <h2>Última TAG lida</h2>
      <span class="badge"><?= e($ultima['data_hora']) ?></span>
    </div>

    <p class="rfid-ultima-uid"><?= e(strtoupper($ultima['tag_uid'])) ?></p>
    <p class="rfid-ultima-info">
      Resultado: <?= e($ultima['resultado']) ?>
      <?php if (!empty($ultima['motorista']) && $ultima['motorista'] !== 'Não cadastrado'): ?>
        — Motorista: <?= e($ultima['motorista']) ?>
      <?php endif; ?>
    </p>

    <form method="post" class="rfid-form">
      <?= csrf_campo() ?>
      <input type="hidden" name="acao" value="vincular_ultima">
      <input type="hidden" name="uid" value="<?= e($ultima['tag_uid']) ?>">
      <label>Encaminhar para motorista
        <select name="motorista_id" required>
          <option value="">— Selecione —</option>
          <?php foreach ($motoristas as $m): ?>
            <option value="<?= (int)$m['id'] ?>"><?= e($m['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Descrição (se a TAG ainda não existir)
        <input name="descricao" placeholder="Ex.: Cartão do João" maxlength="150">
      </label>
      <button class="btn" type="submit">Encaminhar TAG</button>
    </form>

    <p class="dica">Se a TAG ainda não estiver cadastrada, ela é criada automaticamente ao encaminhar.</p>
  </section>
  <?php endif; ?>

  <section class="card">
    <h2>Cadastrar nova TAG</h2>
    <form method="post" class="rfid-form">
      <?= csrf_campo() ?>
      <input type="hidden" name="acao" value="criar">
      <label>UID da TAG (hexadecimal)
        <input name="uid" placeholder="a1b2c3d4" required pattern="[0-9a-fA-F]{4,64}"
               autocomplete="off" autocapitalize="none" spellcheck="false"
               value="<?= !empty($ultima) ? e(strtoupper($ultima['tag_uid'])) : '' ?>">
      </label>
      <label>Descrição
        <input name="descricao" placeholder="Ex.: Cartão do João" maxlength="150">
      </label>
      <label>Motorista (opcional)
        <select name="motorista_id">
          <option value="0">— Nenhum —</option>
          <?php foreach ($motoristas as $m): ?>
            <option value="<?= (int)$m['id'] ?>"><?= e($m['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <button class="btn" type="submit">Cadastrar TAG</button>
    </form>
    <p class="dica">Dica: o UID já vem preenchido com a última TAG lida. Se a TAG não aparecer, passe o cartão no leitor e recarregue a página.</p>
  </section>

  <section class="card">
    <div class="card-topo">
      <h2>TAGs cadastradas</h2>
      <span class="badge"><?= count($cartoes) ?> TAG(s)</span>
    </div>

    <?php if (empty($cartoes)): ?>
      <p class="vazio">Nenhuma TAG cadastrada.</p>
    <?php else: ?>
    <div class="tabela-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>UID</th>
            <th>Descrição</th>
            <th>Motorista vinculado</th>
            <th>Status</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($cartoes as $c): ?>
            <tr>
              <td><?= (int)$c['id'] ?></td>
              <td class="uid"><?= e(strtoupper($c['uid'])) ?></td>
              <td><?= !empty($c['descricao']) ? e($c['descricao']) : '—' ?></td>
              <td>
                <form method="post" class="rfid-vinculo">
                  <?= csrf_campo() ?>
                  <input type="hidden" name="acao" value="vincular">
                  <input type="hidden" name="cartao_id" value="<?= (int)$c['id'] ?>">
                  <select name="motorista_id" aria-label="Motorista da TAG <?= e(strtoupper($c['uid'])) ?>">
                    <option value="0">— Nenhum —</option>
                    <?php foreach ($motoristas as $m): ?>
                      <option value="<?= (int)$m['id'] ?>" <?= (int)($c['motorista_id'] ?? 0) === (int)$m['id'] ? 'selected' : '' ?>>
                        <?= e($m['nome']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn sec sm" type="submit">Salvar</button>
                </form>
              </td>
              <td><span class="badge <?= $c['ativo'] ? 'rf-ativa' : '' ?>"><?= $c['ativo'] ? 'Ativa' : 'Inativa' ?></span></td>
              <td class="acoes">
                <form method="post">
                  <?= csrf_campo() ?>
                  <input type="hidden" name="acao" value="alternar">
                  <input type="hidden" name="cartao_id" value="<?= (int)$c['id'] ?>">
                  <button class="link <?= $c['ativo'] ? 'perigo' : '' ?>" type="submit"><?= $c['ativo'] ? 'Desativar' : 'Ativar' ?></button>
                </form>

                <form method="post"
                      onsubmit="return confirm('Excluir a TAG <?= e(strtoupper($c['uid'])) ?>? Essa ação não pode ser desfeita.');">
                  <?= csrf_campo() ?>
                  <input type="hidden" name="acao" value="excluir">
                  <input type="hidden" name="cartao_id" value="<?= (int)$c['id'] ?>">
                  <button class="link perigo" type="submit">Excluir</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </section>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>