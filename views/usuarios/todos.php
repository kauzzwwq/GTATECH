<?php
$titulo = 'Usuários';
require BASE_PATH . '/views/layouts/header.php';
use App\Models\Usuario;
?>
<main class="conteudo">
  <div class="page-head">
    <div>
      <h1>Usuários</h1>
      <p class="sub">Quem entra no sistema. Cada perfil enxerga e faz coisas diferentes.</p>
    </div>
    <div class="acoes-topo"><a class="btn" href="<?= e(url('usuario-form')) ?>">+ Novo usuário</a></div>
  </div>

  <?php if ($msg): ?><div class="ok"><?= e($msg) ?></div><?php endif; ?>
  <?php if ($erro): ?><div class="alerta"><?= e($erro) ?></div><?php endif; ?>

  <section class="card legenda-perfis">
    <strong>Perfis:</strong>
    <span><span class="badge admin">Administrador</span> vê e faz tudo</span>
    <span><span class="badge trab">Produtor</span> cria as notas</span>
    <span><span class="badge trab">Motorista</span> vê, baixa o PDF e segue a rota</span>
    <span><span class="badge trab">Recebedor</span> vê, baixa o PDF e dá baixa no recebimento</span>
  </section>

  <section class="card">
    <div class="tabela-wrap">
      <table>
        <thead><tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Cadastro vinculado</th><th>Cadastro</th><th>Ações</th></tr></thead>
        <tbody>
        <?php foreach ($usuarios as $u): ?>
          <tr>
            <td><strong><?= e($u['nome']) ?></strong><?= (int)$u['id'] === $meuId ? ' <span class="badge">você</span>' : '' ?></td>
            <td><?= e($u['email']) ?></td>
            <td><span class="badge <?= $u['perfil'] === 'admin' ? 'admin' : 'trab' ?>"><?= e(Usuario::PERFIS[$u['perfil']] ?? $u['perfil']) ?></span></td>
            <td>
              <?php if (in_array($u['perfil'], ['produtor', 'motorista', 'destinatario'], true)): ?>
                <?= $u['vinculo'] ? e($u['vinculo']) : '<span class="badge aviso">Sem cadastro</span>' ?>
              <?php else: ?>—<?php endif; ?>
            </td>
            <td><?= e(date('d/m/Y', strtotime($u['criado_em']))) ?></td>
            <td class="acoes">
              <a href="<?= e(url('usuario-form', ['id' => $u['id']])) ?>">Editar</a>
              <a href="<?= e(url('usuario-senha', ['id' => $u['id'], 'retorno' => 'usuarios'])) ?>">Trocar senha</a>
              <?php if ((int)$u['id'] !== $meuId): ?>
                <form method="post" action="<?= e(url('usuario-excluir')) ?>" onsubmit="return confirm('Excluir este usuário?')">
                  <?= csrf_campo() ?>
                  <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                  <button class="link perigo" type="submit">Excluir</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>
<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
