<?php
$titulo = 'Motoristas';
require BASE_PATH . '/views/layouts/header.php';
?>
<main class="conteudo">
  <div class="page-head">
    <div>
      <h1>Motoristas</h1>
      <p class="sub">Gerencie os motoristas e os veículos cadastrados no sistema.</p>
    </div>
  </div>

  <?php if ($msg): ?><div class="ok"><?= e($msg) ?></div><?php endif; ?>

  <section class="card">
    <div class="card-topo">
      <h2>Motoristas cadastrados</h2>
      <span class="badge"><?= count($motoristas) ?> motorista(s)</span>
    </div>

    <?php if (!$motoristas): ?>
      <p class="vazio">Nenhum motorista cadastrado. Cadastre um para começar a emitir notas.</p>
    <?php else: ?>
    <div class="tabela-wrap">
      <table>
        <thead><tr><th>Motorista</th><th>CPF</th><th>CNH</th><th>Telefone</th><th>Veículo</th><th>Placa</th><th>Login</th><th>Ações</th></tr></thead>
        <tbody>
        <?php foreach ($motoristas as $m): ?>
          <tr>
            <td><strong><?= e($m['nome']) ?></strong></td>
            <td><?= e($m['cpf']) ?: '—' ?></td>
            <td><?= e($m['cnh']) ?: '—' ?></td>
            <td><?= e($m['telefone']) ?: '—' ?></td>
            <td><?= e($m['modelo_veiculo']) ?: '—' ?></td>
            <td><?= !empty($m['placa_veiculo']) ? '<span class="badge placa-badge">' . e($m['placa_veiculo']) . '</span>' : '—' ?></td>
            <td><?= !empty($m['login_email']) ? e($m['login_email']) : '<span class="badge aviso">Sem login</span>' ?></td>
            <td class="acoes">
              <a href="<?= e(url('motorista-form', ['id' => $m['id']])) ?>">Editar</a>
                <?php if (!empty($m['usuario_id'])): ?>
                <a href="<?= e(url('usuario-senha', ['id' => $m['usuario_id'], 'retorno' => 'motoristas'])) ?>">Trocar senha</a>
              <?php endif; ?>
              <form method="post" action="<?= e(url('motorista-excluir')) ?>" onsubmit="return confirm('Excluir este motorista? As notas antigas continuam com o nome dele.')">
                <?= csrf_campo() ?>
                <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
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
