<?php
$titulo = $cfg['titulo'];
require BASE_PATH . '/views/layouts/header.php';
?>
<main class="conteudo">
  <div class="page-head">
    <div>
      <h1><?= e($cfg['titulo']) ?></h1>
      <p class="sub">Cadastre uma vez e escolha na hora de criar a nota. Ligue ao login para a pessoa entrar no sistema.</p>
    </div>
  </div>

  <?php if ($msg): ?><div class="ok"><?= e($msg) ?></div><?php endif; ?>

  <section class="card">
    <?php if (!$itens): ?>
      <p class="vazio">Nenhum registro ainda.</p>
    <?php else: ?>
    <div class="tabela-wrap">
      <table>
        <thead><tr>
          <?php foreach ($cfg['colunas'] as $col): ?><th><?= e($cfg['campos'][$col]) ?></th><?php endforeach; ?>
          <th>Login</th><th>Ações</th>
        </tr></thead>
        <tbody>
        <?php foreach ($itens as $item): ?>
          <tr>
            <?php foreach ($cfg['colunas'] as $col): ?><td><?= e($item[$col]) ?></td><?php endforeach; ?>
            <td><?= !empty($item['login_email']) ? e($item['login_email']) : '<span class="badge aviso">Sem login</span>' ?></td>
            <td class="acoes">
              <a href="<?= e(url('produtor-form', ['id' => $item['id']])) ?>">Editar</a>
                <?php if (!empty($item['usuario_id'])): ?>
                <a href="<?= e(url('usuario-senha', ['id' => $item['usuario_id'], 'retorno' => $tipo])) ?>">Trocar senha</a>
              <?php endif; ?>
              <form method="post" action="<?= e(url('produtor-excluir')) ?>" onsubmit="return confirm('Excluir este registro? As notas já criadas não mudam.')">
                <?= csrf_campo() ?>
                <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
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
<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
