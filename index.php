<?php
session_start();
require_once 'includes/db.php';

$erro = '';

if (isset($_POST["btnIniciarSessao"])) {
  $login = trim($_POST["login"] ?? '');
  $password = trim($_POST["password"] ?? '');

  $stmt = $pdo->prepare("
        SELECT * 
        FROM utilizadores 
        WHERE login = :login 
        AND password = :password
        LIMIT 1
    ");

  $stmt->execute([
    ':login' => $login,
    ':password' => $password
  ]);

  $linha = $stmt->fetch();

  if (!$linha) {
    $erro = "Utilizador e/ou palavra-passe inválidos.";
  } else {
    $_SESSION['currentNome'] = $linha["nome"];
    $_SESSION['currentID'] = $linha["id_utilizador"];
    $_SESSION['currentLogin'] = $linha["login"];
    $_SESSION['currentFoto'] = $linha["foto"];
    $_SESSION['erro'] = 1;

    $stmtUpdate = $pdo->prepare("
            UPDATE utilizadores 
            SET data = :data 
            WHERE id_utilizador = :id
        ");

    $stmtUpdate->execute([
      ':data' => date("Y-m-d H:i:s"),
      ':id' => $linha["id_utilizador"]
    ]);

    header('Location: backoffice.php');
    exit;
  }
}
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Iniciar sessão | PsiqSys</title>

  <link rel="stylesheet" href="vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="vendors/base/vendor.bundle.base.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="shortcut icon" href="images/favicon2.png" type="image/x-icon">
</head>

<body class="login-page">

  <main class="login-wrapper">
    <section class="login-card">

      <div class="login-brand">
        <div class="login-logo">
          <i class="ti ti-pulse"></i>
        </div>
        <div>
          <h1>PsiqSys</h1>
          <p>Sistema de Gestão Clínica</p>
        </div>
      </div>

      <div class="login-header">
        <h2>Bem-vindo</h2>
        <p>Inicie sessão para aceder ao backoffice.</p>
      </div>

      <?php if (!empty($erro)): ?>
        <div class="alert alert-danger">
          <i class="ti ti-alert-circle"></i>
          <span><?= htmlspecialchars($erro) ?></span>
        </div>
      <?php endif; ?>

      <form method="post" action="index.php" class="login-form">

        <div class="form-group">
          <label class="form-label" for="login">Utilizador</label>
          <div class="input-icon">
            <i class="ti ti-user"></i>
            <input
              type="text"
              name="login"
              id="login"
              class="form-control"
              placeholder="Introduza o utilizador"
              required
              autofocus>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Palavra-passe</label>
          <div class="input-icon">
            <i class="ti ti-lock"></i>
            <input
              type="password"
              name="password"
              id="password"
              class="form-control"
              placeholder="Introduza a palavra-passe"
              required>
          </div>
        </div>

        <button
          type="submit"
          name="btnIniciarSessao"
          class="btn btn-primary login-btn">
          <i class="ti ti-login"></i>
          Iniciar sessão
        </button>

      </form>

      <div class="login-footer">
        <p>Não tem uma conta?</p>
        <span>Contacte o administrador do sistema.</span>
      </div>

    </section>
  </main>

</body>

</html>